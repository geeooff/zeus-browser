<?php

abstract class MediaInfoFormat
{
	const Text = 0;
	const Xml = 1;
	const Html = 2;
}

class FileSystemObject
{
	private $builder;
	public $name;
	public $parent;
	public $uri;
	public $path;
	public $realpath;
	public $securepath;
	private $securelink_md5;
	private $securelink_expires;
	public $exists;
	public $isdir;
	public $isfile;
	public $filesize;
	public $mtime;
	public $children;
	public $extension;
	public $mediatype;
	public $mediaicon;
	public $allFiles;
	public $mimetype;
	public $playermimetype;

	public function __construct($builder, $name, $parent, $uri, $path, $realpath, $securepath)
	{
		$exists = file_exists($realpath);
		$isdir = $exists ? is_dir($realpath) : FALSE;
		$isfile = $exists ? is_file($realpath) : FALSE;
		$filesize = $isfile ? filesize($realpath) : 0;
		$mtime = $exists ? filemtime($realpath) : 0;
		$extension = $isfile ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : NULL;
		$mediatype = $isdir ? 'dir' : $builder->GetMediaType($extension);
		$mediaicon = $builder->mediaicons[$mediatype];
		$mimetype = ($isfile && isset($builder->mimetypes[$extension])) ? $builder->mimetypes[$extension] : NULL;
		$playermimetype = ($isfile && $mimetype != NULL && isset($builder->playermimetypes[$mimetype])) ? $builder->playermimetypes[$mimetype] : NULL;

		$pathsuffix = $isdir ? '/' : '';

		$this->builder = $builder;
		$this->name = $name;
		$this->parent = $parent;
		$this->uri = $uri . $pathsuffix;
		$this->path = $path . $pathsuffix;
		$this->realpath = $realpath . $pathsuffix;
		$this->securepath = $securepath . $pathsuffix;
		$this->exists = $exists;
		$this->isdir = $isdir;
		$this->isfile = $isfile;
		$this->filesize = $filesize;
		$this->mtime = $mtime;
		$this->children = NULL;
		$this->extension = $extension;
		$this->mediatype = $mediatype;
		$this->mediaicon = $mediaicon;
		$this->mimetype = $mimetype;
		$this->playermimetype = $playermimetype;
	}

	public function GetChildren()
	{
		if ($this->children === NULL)
		{
			if ($this->isdir && $this->exists)
			{
				$this->children = [];
				$scan = scandir($this->realpath);
				$scanfiltered = array_diff($scan, $this->builder->skip);
				foreach ($scanfiltered as $name)
				{
					$child = $this->builder->CreateChild($this, $name);
					$this->children[] = $child;
				}
				return $this->children;
			}
		}
		return $this->children;
	}

	public function GetAllFiles($filterMediaType = NULL)
	{
		if (!isset($this->allFiles[$filterMediaType]))
		{
			$this->allFiles[$filterMediaType] = [];
			if ($this->exists)
			{
				if ($this->isdir)
				{
					$subfiles = [];
					foreach ($this->GetChildren() as $child)
					{
						$subfiles = $child->GetAllFiles($filterMediaType);
						$this->allFiles[$filterMediaType] = array_merge($this->allFiles[$filterMediaType], $subfiles);
					}
				}
				else
				{
					if ($filterMediaType === NULL || $this->mediatype == $filterMediaType)
					{
						$this->allFiles[$filterMediaType][] = $this;
					}
				}
			}
		}
		return $this->allFiles[$filterMediaType];
	}

	public function GetM3U($filterMediaType = NULL, $scheme = NULL)
	{
		$files = $this->GetAllFiles($filterMediaType);
		$entries = array();
		foreach ($files as $index => $file)
		{
			$entries[$index] = '#EXTINF:-1;' . $file->name . "\r\n" . $file->GetSecureLink($scheme);
		}
		return "#EXTM3U" . "\r\n\r\n" . implode("\r\n\r\n", $entries);
	}

	public function GetASX($filterMediaType = NULL, $scheme = NULL)
	{
		$files = $this->GetAllFiles($filterMediaType);

		$xw = new XMLWriter();
		$xw->openMemory();
		$xw->setIndent(TRUE);
		$xw->setIndentString("\t");
		$xw->startDocument('1.0', 'UTF-8');
		$xw->startElement('asx');
		$xw->writeAttribute('version', '3.0');
		$xw->writeElement('title', $this->name);

		foreach ($files as $index => $file)
		{
			$xw->startElement('entry');
			$xw->writeElement('title', $file->name);
			$xw->startElement('ref');
			$xw->writeAttribute('href', $file->GetSecureLink($scheme));
			$xw->endElement();
			$xw->endElement();
		}

		$xw->endElement();

		echo $xw->outputMemory(TRUE);
	}

	public function GetFirstExistantAncestor()
	{
		$parent = $this->parent;
		while ($parent != NULL)
		{
			if ($parent->exists)
				break;
			else
				$parent = $parent->parent;
		}
		return $parent;
	}

	public function GetMediaInfo($format = MediaInfoFormat::Text)
	{
		$output = [];
		$exitcode;

		switch ($format)
		{
			case MediaInfoFormat::Text:
				exec('/usr/bin/mediainfo ' . escapeshellarg($this->realpath), $output, $exitcode);
				break;

			case MediaInfoFormat::Xml:
				exec('/usr/bin/mediainfo --Output=XML ' . escapeshellarg($this->realpath), $output);
				break;

			case MediaInfoFormat::Html:
				exec('/usr/bin/mediainfo --Output=XML ' . escapeshellarg($this->realpath) . ' | /usr/bin/xsltproc --nonet --nowrite --nomkdir mediainfo.xsl -', $output, $exitcode);
				break;
		}

		if ($exitcode === 0)
			return implode(PHP_EOL, $output);
		else
			return FALSE;
	}

	public function GetAllText(&$encoding = NULL)
	{
		$text = file_get_contents($this->realpath);
		
		if ($text !== FALSE)
		{
			$encoding = mb_detect_encoding($text, ['UTF-8', 'Windows-1252', 'ISO-8859-15', 'ISO-8859-1', 'CP850', 'ASCII'], TRUE);
			
			if ($encoding !== FALSE && $encoding !== 'UTF-8')
			{
				$text = mb_convert_encoding($text, 'UTF-8', $encoding);
			}
		}
		
		return $text; 
	}

	public function GetSecureLink($scheme = NULL)
	{
		if ($this->securelink_md5 === NULL || $this->securelink_expires === NULL)
		{
			$decoded_secure_path = rawurldecode($this->securepath);
			$expires = strtotime('now +2 days');
			//$value = $decoded_secure_path . $_SERVER['REMOTE_ADDR'] . $expires . ' bc81ed5fa27a92b0e84ab23723d4145e';
			$value = $decoded_secure_path . $expires . ' bc81ed5fa27a92b0e84ab23723d4145e';
			$md5 = str_replace(['+', '/', '='], ['-', '_', NULL], base64_encode(md5($value, TRUE)));
			$this->securelink_md5 = $md5;
			$this->securelink_expires = $expires;
		}		

		return unparse_url([
			'scheme' => $scheme === NULL ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') : $scheme,
			'host' => $_SERVER['HTTP_HOST'],
			'path' => $this->securepath,
			'query' => 'md5=' . $this->securelink_md5 . '&expires=' . $this->securelink_expires
		]);
	}

	public function GetRecursiveSecureLinks($filterMediaType = NULL, $scheme = NULL)
	{
		$files = $this->GetAllFiles($filterMediaType);
		$urls = array();
		foreach ($files as $index => $file)
		{
			$urls[$index] = $file->GetSecureLink($scheme);
		}
		return implode("\r\n", $urls);
	}
	
// 	public function IsBeingWritten(&$fuserOutput, &$fuserExitCode)
// 	{
// 		$output = [];
// 		$exitcode;
// 
// 		exec('fuser -n file -v ' . escapeshellarg($this->realpath), $output, $fuserExitCode);
// 		
// 		if (array_count_values($output) > 0)
// 		{
// 			$fuserOutput = implode(PHP_EOL, $output);
// 			return TRUE;
// 		}
// 
// 		return FALSE;
// 	}

	public function IsBeingWritten()
	{
		global $now;
		$seconds = $now->getTimestamp() - $this->mtime;
		return ($seconds < 60);
	}
}

class FileSystemObjectBuilder
{
	public $config;
	public $mediatypes;
	public $mediaicons;
	public $mediatypelabels;
	public $mimetypes;
	public $playermimetypes;
	public $skip;

	private $baseurl;
	private $root;
	private $path;
	private $securepath;
	private $cartObject;
	private $rootObject;

	public function __construct($config)
	{
		$this->config = $config;
		$this->mediatypes = $config['mediatypes'];
		$this->mediaicons = $config['mediaicons'];
		$this->mediatypelabels = $config['mediatypelabels'];
		$this->mimetypes = $config['mimetypes'];
		$this->playermimetypes = $config['playermimetypes'];
		$this->skip = $config['SKIP'];

		$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
		$userIsAdmin = ($user === NULL || in_array($user, $config['ADMINS']));
		$root = $userIsAdmin ? $config['ROOT_ADMIN'] : $config['ROOT_GUEST'];
		$path = $userIsAdmin ? $config['PATH_ADMIN'] : $config['PATH_GUEST'];
		$securepath = $userIsAdmin ? $config['SECURE_PATH_ADMIN'] : $config['SECURE_PATH_GUEST'];
		$cartSessionName = $userIsAdmin ? $config['session']['name-admin'] : $config['session']['name-guest'];

		// resolves root path, if not specified in config file
		if ($root == NULL || trim($root) == '')
		{
			$root = $_SERVER['DOCUMENT_ROOT'] . $path;
		}

		$baseurl = [
			'scheme' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http'),
			'user' => $user,
			'pass' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL,
			'host' => $_SERVER['HTTP_HOST'],
			'path' => $path
		];

		$cartObject = new FileSystemObjectCart($this, $cartSessionName);

		$rootObject = new FileSystemObject(
			$this,
			$config['ROOT_NAME'],
			NULL,
			$config['APP_PATH'],
			$path,
			$root,
			$securepath
		);

		$this->baseurl = $baseurl;
		$this->root = $root;
		$this->path = $path;
		$this->cartObject = $cartObject;
		$this->rootObject = $rootObject;
	}

	public function GetCart()
	{
		return $this->cartObject;
	}

	public function GetMediaType($extension)
	{
		foreach ($this->mediatypes as $mediatype => $extensions)
		{
			if (in_array($extension, $extensions))
			{
				return $mediatype;
			}
		}
		return 'file';
	}

	public function CreateChild($parent, $name)
	{
		$uri = isset($parent) ? $parent->uri . rawurlencode($name) : rawurlencode($name);
		$path = isset($parent) ? $parent->path . rawurlencode($name) : rawurlencode($name);
		$securepath = isset($parent) ? $parent->securepath . rawurlencode($name) : rawurlencode($name);
		$realpath = isset($parent) ? $parent->realpath . $name : $name;
		return new FileSystemObject($this, $name, $parent, $uri, $path, $realpath, $securepath);
	}

	public function GetChain()
	{
		return $this->Create($_SERVER['REQUEST_URI'], TRUE);
	}

	public function Create($uri, $decode = FALSE, $withChildren = FALSE)
	{
		if ($decode)
		{
			$uri = urldecode($uri);
			$arr = explode('?', $uri);
			$uri = reset($arr);
		}
		//$uriparts = array_filter(explode('/', $uri), 'strlen');
		$pathparts = array_filter(explode('/', substr($uri, strlen($this->config['APP_PATH']))), 'strlen');

		$chain = [];
		$chain[] = $this->rootObject;

		$parent = $this->rootObject;
		foreach ($pathparts as $name)
		{
			$object = $this->CreateChild($parent, $name);
			if ($withChildren)
			{
				$object->GetChildren();
			}
			$chain[] = $object;
			$parent = $object; 
		}

		return $chain;
	}
}

class FileSystemObjectCart
{
	private $builder;
	private $sessionName;
	private $uris;
	public $objects;
	public $allFiles;

	public function __construct($builder, $sessionName)
	{
		session_set_cookie_params(0, $builder->config['APP_PATH'], '', $builder->config['session']['secure'], FALSE);
		session_start();

		$this->builder = $builder;
		$this->sessionName = $sessionName;
		$this->uris = isset($_SESSION[$sessionName]) ? $_SESSION[$sessionName] : NULL;
	}

	private function Save()
	{
		if ($this->uris !== NULL && count($this->uris) > 0)
		{
			$_SESSION[$this->sessionName] = $this->uris;
		}
		else
		{
			unset($_SESSION[$this->sessionName]);
		}
	}

	public function EmptyCart()
	{
		unset($_SESSION[$this->sessionName]);
		return TRUE;
	}

	public function Add($object)
	{
		if ($object !== NULL)
		{
			return $this->AddUri($object->uri);
		}
		return 0;
	}

	public function AddUri($uri)
	{
		if ($this->uris === NULL)
		{
			$this->uris = [$uri];
			$this->Save();
			return 1;
		}
		else
		{
			if (!$this->ContainsUri($uri))
			{
				$this->uris[] = $uri;
				$this->Save();
				return 1;
			}
			else
			{
				return 2;
			}
		}
		return 0;
	}

	public function Remove($object)
	{
		if ($object !== NULL)
		{
			return $this->RemoveUri($object->uri);
		}
		return 0;
	}

	public function RemoveUri($uri)
	{
		if ($this->uris !== NULL)
		{
			if ($this->ContainsUri($uri)) {
				if (($key = array_search($uri, $this->uris)) !== FALSE)
				{
				    unset($this->uris[$key]);
				    $this->Save();
				    return 1;
				}
				else
				{
					return -1;
				}
			}
		}
		return 0;
	}

	public function Contains($object)
	{
		if ($object !== NULL)
		{
			return $this->ContainsUri($object->uri);
		}
		return FALSE;
	}

	public function ContainsUri($uri)
	{
		if ($this->uris !== NULL)
		{
			if (array_search($uri, $this->uris) === FALSE)
			{
				foreach ($this->uris as $uri2)
				{
					if (strpos($uri, $uri2) !== FALSE) {
						return TRUE;
					}
				}
				return FALSE;
			}
			return TRUE;
		}
		return FALSE;
	}

	public function GetObjects()
	{
		if (!isset($this->objects))
		{
			$this->objects = [];
			if ($this->uris !== NULL)
			{
				foreach ($this->uris as $index => $uri)
				{
					$chain = $this->builder->Create($uri, TRUE, TRUE);
					$this->objects[] = end($chain);
				}
			}
		}
		return $this->objects;
	}

	public function GetRecursiveSecureLinks($filterMediaType = NULL, $scheme = NULL)
	{
		$files = $this->GetAllFiles($filterMediaType);
		$urls = array();
		foreach ($files as $index => $file)
		{
			$urls[$index] = $file->GetSecureLink($scheme);
		}
		return implode("\r\n", $urls);
	}

	public function GetM3U($filterMediaType = NULL, $scheme = NULL)
	{
		$files = $this->GetAllFiles($filterMediaType);
		$entries = array();
		foreach ($files as $index => $file)
		{
			$entries[$index] = '#EXTINF:-1;' . $file->name . "\r\n" . $file->GetSecureLink($scheme);
		}
		return "#EXTM3U" . "\r\n\r\n" . implode("\r\n\r\n", $entries);
	}

	public function GetASX($filterMediaType = NULL, $scheme = NULL)
	{
		$files = $this->GetAllFiles($filterMediaType);

		static $xw;

		if (!isset($xw))
		{
			$xw = new XMLWriter();
			$xw->setIndent(TRUE);
			$xw->setIndentString("\t");
		}

		$xw->openMemory();
		$xw->startDocument('1.0', 'UTF-8');
		$xw->startElement('asx');
		$xw->writeAttribute('version', '3.0');

		foreach ($files as $index => $file)
		{
			$xw->startElement('entry');
			$xw->writeElement('title', $file->name);
			$xw->startElement('ref');
			$xw->writeAttribute('href', $file->GetSecureLink($scheme));
			$xw->endElement();
			$xw->endElement();
		}

		$xw->endElement();

		echo $xw->outputMemory(TRUE);
	}

	public function GetAllFiles($filterMediaType = NULL)
	{
		if (!isset($this->allFiles[$filterMediaType]))
		{
			$objects = $this->GetObjects();
			$this->allFiles[$filterMediaType] = [];
			foreach ($objects as $object)
			{
				$subfiles = $object->GetAllFiles($filterMediaType);
				$this->allFiles[$filterMediaType] = array_merge($this->allFiles[$filterMediaType], $subfiles);
			}
		}
		return $this->allFiles[$filterMediaType];
	}

	public function GetFullHtml()
	{
		echo '
			<div class="row">
				<div class="col-xs-12">';

		$objects = $this->GetObjects();

		if (count($objects) > 0)
		{
			echo '
					<div class="list-group">';

			foreach ($objects as $object)
			{
				$totalSize = $object->filesize;
				
				if ($object->isdir)
				{
					$objectFiles = $object->GetAllFiles();
					$totalSize = total_size($objectFiles);
				}
				
				echo '
						<div class="list-group-item', (!$object->exists ? ' list-group-item-danger' : ''), '">
							<h4 class="list-group-item-heading clearfix">
								<span class="pull-left">
									<span class="glyphicon glyphicon-', $object->mediaicon ,'"></span>
									&nbsp;<a href="', $object->uri, '" type="text/html" class="btn-link">', html_encode($object->name), '</a>
								</span>
								<a href="', $object->uri, '?removefromcart" type="application/json" class="btn btn-xs btn-danger removefromcart pull-right"><span class="glyphicon glyphicon-remove"></span></a>								
								<span class="badge pull-right">', html_encode(format_filesize($totalSize)), '</span>
							</h4>';

				if ($object->isdir)
				{
					echo '
							<p class="list-group-item-text">';

					foreach ($objectFiles as $objectFile)
					{
						$objectFileUri = urldecode(str_replace($object->uri, '', $objectFile->uri));
						$objectFileUri = str_replace('/', ' / ', $objectFileUri);
						//$objectFilePath = str_replace($objectFile->name, '<em>' . $objectFile->name . '</em>', $objectFileUri);

						echo '
								<a href="', $objectFile->uri,'" type="text/html">', html_encode($objectFileUri), '</a><br />';
					}

					echo '
							</p>';
				}

				echo '
						</div>';
			}

			echo '
					</div>';
		}
		else
		{
			echo '
					<div class="alert alert-warning" role="alert">
						Le panier est vide.
					</div>';
		}

		echo '
				</div>
			</div>';
	}

	public function GetLightHtml()
	{
		$objects = $this->GetObjects();

		echo '
				<div class="btn-group pull-right" role="group">
					<a href="?cart" type="text/html" class="btn btn-primary">Panier <span class="badge">', count($objects), '</span></a>
				</div>';
	}
}

function unparse_url($parsed_url, $overrides = NULL)
{ 
	$components = ($overrides !== NULL) ? array_merge($parsed_url, $overrides) : $parsed_url;
	$scheme   = isset($components['scheme']) ? $components['scheme'] . '://' : ''; 
	$host     = isset($components['host']) ? $components['host'] : ''; 
	$port     = isset($components['port']) ? ':' . $components['port'] : ''; 
	$user     = isset($components['user']) ? $components['user'] : ''; 
	$pass     = isset($components['pass']) ? ':' . $components['pass']  : ''; 
	$pass     = ($user || $pass) ? "$pass@" : ''; 
	$path     = isset($components['path']) ? $components['path'] : ''; 
	$query    = isset($components['query']) ? '?' . $components['query'] : ''; 
	$fragment = isset($components['fragment']) ? '#' . $components['fragment'] : ''; 
	return "$scheme$user$pass$host$port$path$query$fragment"; 
}

function html_encode($str)
{
	return htmlspecialchars($str, ENT_HTML5, 'UTF-8');
}

function format_filesize($filesize, $binaryDivisor = FALSE)
{
	static $fmtFilesize;

	if (!isset($fmtFilesize))
	{
		$fmtFilesize = new NumberFormatter('fr_FR', NumberFormatter::DECIMAL);
		$fmtFilesize->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 1);
	}

	$units = $binaryDivisor ? ['octets', 'Kio', 'Mio', 'Gio', 'Tio'] : ['octets', 'Ko', 'Mo', 'Go', 'To'];
	$index = 0;
	$size = $filesize;
	$divisor = $binaryDivisor ? 1024.0 : 1000.0;
	for (; $size >= $divisor && $index < count($units); $index++)
	{
		$size /= $divisor;
	}
	return $fmtFilesize->format($size) . ' ' . $units[$index];
}

function format_datetime_full($timestamp)
{
	static $fmtDate, $fmtTime;

	if (!isset($fmtDate))
		$fmtDate = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Paris', IntlDateFormatter::GREGORIAN);
	if (!isset($fmtTime))
		$fmtTime = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM, 'Europe/Paris', IntlDateFormatter::GREGORIAN);

	return $fmtDate->format($timestamp) . ' à ' . $fmtTime->format($timestamp);
}

function format_datetime_short($timestamp)
{
	static $fmtDate, $fmtTime;

	if (!isset($fmtDate))
		$fmtDate = new IntlDateFormatter('fr_FR', IntlDateFormatter::SHORT, IntlDateFormatter::NONE, 'Europe/Paris', IntlDateFormatter::GREGORIAN);
	if (!isset($fmtTime))
		$fmtTime = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::SHORT, 'Europe/Paris', IntlDateFormatter::GREGORIAN);
	
	return $fmtDate->format($timestamp) . ' à ' . $fmtTime->format($timestamp);
}

$now = new DateTime(NULL);
$today = new DateTime('today');
$yesterday = new DateTime('yesterday');

function format_datetime_simple($timestamp)
{
	global $now, $today, $yesterday;
	static $fmtDate, $fmtDateDefaultPattern, $fmtTime;

	if (!isset($fmtDate))
	{
		$fmtDate = new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, 'Europe/Paris', IntlDateFormatter::GREGORIAN);
		$fmtDateDefaultPattern = $fmtDate->getPattern();
	}
	else
		$fmtDate->setPattern($fmtDateDefaultPattern);

	if (!isset($fmtTime))
		$fmtTime = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::SHORT, 'Europe/Paris', IntlDateFormatter::GREGORIAN);

	$dt = new DateTime('@' . $timestamp);
	$diff = $now->diff($dt);
	$timeSuffix = ' à ' . $fmtTime->format($dt);
	$value = 'BURPS !';

	if ($diff->y >= 1)
	{
		$value = $fmtDate->format($dt) . ' à ' . $fmtTime->format($dt);
	}
	elseif ($diff->m > 1)
	{
		$value = 'il y a ' . $diff->m . ' mois, ' . $fmtDate->format($dt) . ' à ' . $fmtTime->format($dt);
	}
	elseif ($diff->m == 1)
	{
		$value = 'il y a un mois, ' . $fmtDate->format($dt) . ' à ' . $fmtTime->format($dt);
	}
	elseif ($diff->d > 7)
	{
		$value = 'il y a ' . $diff->d . ' jours, ' . $fmtDate->format($dt) . ' à ' . $fmtTime->format($dt);
	}
	elseif ($diff->d >= 2)
	{
		$fmtDate->setPattern('EEEE');
		$value = $fmtDate->format($dt) . ' dernier à ' . $fmtTime->format($dt);
	}
	else
	{
		$hours = ($diff->d * 24) + $diff->h; 
		
		if ($hours > 1)
		{
			$value = 'il y a ' . $diff->h . ' heures';
			if ($diff->i > 0)
			{
 				$value .= ' et ' . $diff->i . ' minutes';
			}
		}
		elseif ($diff->i > 1)
		{
			$value = 'il y a ' . $diff->i . ' minutes';
		}
		elseif ($diff->i == 1)
		{
			$value = 'il y a une minute';
		}
		elseif ($diff->s > 1)
		{
			$value = 'il y a ' . $diff->s . ' secondes';
		}
		else
		{
			$value = 'à l\'instant';
		}

		/*
		
		if ($dt > $today)
		{
			$value = 'aujourd\'hui à ' . $fmtTime->format($dt);
		}
		elseif ($dt > $yesterday)
		{
			$value = 'hier à ' . $fmtTime->format($dt);
		}
		*/
	}

	return $value;
}

function total_size($files)
{
	$size = 0;
	foreach ($files as $file)
	{
		$size = $size + $file->filesize;
	}
	return $size;
}

function http_die(string $message, int $http_reponse_code = 500)
{
	header('Content-Type: text/plain', TRUE, $http_reponse_code);
	return die($message);
}

// Source: http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
/*function getRelativePath($from, $to)
{
    // some compatibility fixes for Windows paths
    $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
    $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
    $from = str_replace('\\', '/', $from);
    $to   = str_replace('\\', '/', $to);

    $from     = explode('/', $from);
    $to       = explode('/', $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
        // find first non-matching dir
        if($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './' . $relPath[0];
            }
        }
    }
    return implode('/', $relPath);
}*/

?>