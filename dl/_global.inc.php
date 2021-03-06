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
	public $exists;
	public $children = NULL;
	private $siblings = FALSE;
	public $prev;
	public $next;
	private $securelink_md5;
	private $securelink_expires;
	public $isdir;
	public $isfile;
	public $filesize;
	public $mtime;
	public $extension;
	public $mediatype;
	public $mediaicon;
	public $allFiles;
	public $mimetype;
	public $playermimetype;

	public function __construct(FileSystemObjectBuilder $builder, $name, $parent, $uri, $path, $realpath, $securepath)
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
				$scan = scandir($this->realpath); // asc/desc order ?
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

	public function GetSiblings()
	{
		if (!$this->siblings)
		{
			if ($this->exists && $this->parent !== NULL)
			{
				$scan = scandir($this->parent->realpath); // asc/desc order ?
				$scanfiltered = array_diff($scan, $this->builder->skip);
				$keys = array_keys($scanfiltered);

				if (($key = array_search($this->name, $scanfiltered)) !== FALSE
					&& ($index = array_search($key, $keys)) !== FALSE)
				{
					$maxIndex = count($keys) - 1;

					if ($index > 0)
					{
						$previousKey = $keys[$index - 1];
						$previous = $scanfiltered[$previousKey];
						$this->prev = $this->builder->CreateChild($this->parent, $previous);
					}
					if ($index < $maxIndex)
					{
						$nextKey = $keys[$index + 1];
						$next = $scanfiltered[$nextKey];
						$this->next = $this->builder->CreateChild($this->parent, $next);
					}
				}
			}
			else
			{
				$this->prev = NULL;
				$this->next = NULL;
			}

			$this->siblings = TRUE;
		}
		return $this->siblings;
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
				exec($this->builder->mediainfo . ' ' . escapeshellarg($this->realpath), $output, $exitcode);
				break;

			case MediaInfoFormat::Xml:
			case MediaInfoFormat::Html:
				exec($this->builder->mediainfo . ' --Output=XML ' . escapeshellarg($this->realpath), $output, $exitcode);
				break;

			//case MediaInfoFormat::Html:
			//	exec($this->builder->mediainfo . ' --Output=XML ' . escapeshellarg($this->realpath) . ' | /usr/bin/xsltproc --nonet --nowrite --nomkdir mediainfo.xsl -', $output, $exitcode);
			//	break;
		}

		if ($exitcode === 0)
		{
			$output = implode(PHP_EOL, $output);

			if ($format == MediaInfoFormat::Html)
			{
				$xml = new DOMDocument();				
				$xml->loadXML($output, LIBXML_NONET);

				$xsl = new DOMDocument();
				$xsl->load('mediainfo.xsl', LIBXML_NONET);

				$proc = new XSLTProcessor();
				$proc->setSecurityPrefs(XSL_SECPREF_DEFAULT);
				$proc->importStyleSheet($xsl);

				$output = $proc->transformToXML($xml);
			}

			return $output;
		}

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
			$format = $this->builder->config['SECURELINK_MD5'];
			$uri = rawurldecode($this->securepath);
			$expires = strtotime($this->builder->config['SECURELINK_EXPIRES']);
			$remote_addr = $_SERVER['REMOTE_ADDR'];
			$value = str_replace(
				[ '$uri', '$secure_link_expires', '$remote_addr' ],
				[ $uri, $expires, $remote_addr ],
				$format
			);
			$md5 = str_replace(
				['+', '/', '='],
				['-', '_', NULL],
				base64_encode(md5($value, TRUE))
			);
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
	public $user;
	public $userisadmin;
	public $mediatypes;
	public $mediaicons;
	public $mediatypelabels;
	public $mimetypes;
	public $playermimetypes;
	public $skip;
	public $mediainfo;

	private $baseurl;
	private $root;
	private $path;
	private $securepath;
	private $cartObject;
	private $rootObject;

	public function __construct($config)
	{
		$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
		$userisadmin = ($user === NULL || in_array($user, $config['ADMINS']));
		$root = $userisadmin ? (isset($config['ROOT_ADMIN']) ? $config['ROOT_ADMIN'] : NULL) : (isset($config['ROOT_GUEST']) ? $config['ROOT_GUEST'] : NULL);
		$path = $userisadmin ? $config['PATH_ADMIN'] : $config['PATH_GUEST'];
		$securepath = $userisadmin ? $config['SECURE_PATH_ADMIN'] : $config['SECURE_PATH_GUEST'];
		
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

		$this->config = $config;
		$this->user = $user;
		$this->userisadmin = $userisadmin;
		$this->mediatypes = $config['mediatypes'];
		$this->mediaicons = $config['mediaicons'];
		$this->mediatypelabels = $config['mediatypelabels'];
		$this->mimetypes = $config['mimetypes'];
		$this->playermimetypes = $config['playermimetypes'];
		$this->skip = $config['SKIP'];
		$this->mediainfo = isset($config['MEDIAINFO_BIN']) ? $config['MEDIAINFO_BIN'] : '/usr/bin/mediainfo';
		
		$this->baseurl = $baseurl;
		$this->root = $root;
		$this->path = $path;

		$this->rootObject = new FileSystemObject(
			$this,
			$config['ROOT_NAME'],
			NULL,
			$config['APP_PATH'],
			$path,
			$root,
			$securepath
		);

		$cartSessionName = $userisadmin ? $config['session']['name-admin'] : $config['session']['name-guest'];

		$this->cartObject = new FileSystemObjectCart(
			$this,
			$cartSessionName
		);
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

	public function __construct(FileSystemObjectBuilder $builder, $sessionName)
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

	public function Add(FileSystemObject $object)
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

	public function Remove(FileSystemObject $object)
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

	public function Contains(FileSystemObject $object)
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

	public function CountObjects()
	{
		if ($this->uris !== NULL)
		{
			return count($this->uris);
		}
		return 0;
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

	public function GetFullHtml(FileSystemObject $object)
	{
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
								<span class="pull-xs-left">
									<i class="fa fa-', $object->mediaicon ,'"></i>
									&nbsp;<a href="', $object->uri, '" type="text/html" class="btn-link">', html_encode($object->name), '</a>
								</span>
								<a href="', $object->uri, '?removefromcart" type="application/json" class="btn btn-sm btn-danger removefromcart pull-xs-right"><i class="fa fa-remove"></i></a>								
								<span class="tag tag-pill pull-xs-right">', html_encode(format_filesize($totalSize)), '</span>
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
	}

	public function GetHeaderActionButton(FileSystemObject $object)
	{
		$count = $this->CountObjects();
		$isEmpty = !($count > 0);
		$isInCart = (!$isEmpty && $object->exists) ? $this->Contains($object) : FALSE;

		$html = '';

		if ($isInCart)
			$html .= '<a href="?removefromcart&header" type="application/json" class="btn btn-outline-danger removefromcart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Retirer</span></a>' . "\r\n";
		else
			$html .= '<a href="?addtocart&header" type="application/json" class="btn btn-outline-success addtocart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Ajouter</span></a>' . "\r\n";

		if (!$isEmpty)
		{
			$html .= '<a href="?cart" type="text/html" class="btn ' . ($isEmpty ? 'btn-outline-secondary disabled': 'btn-outline-primary') . '">' . "\r\n";
			$html .= "\t" . '<span class="hidden-xs-down">Panier</span>' . "\r\n";
			$html .= "\t" . '<span class="tag tag-info tag-pill">' . $count . '</span>' . "\r\n";
			$html .= '</a>' . "\r\n";
		}

		return $html;
	}

	public function GetObjectActionButton(FileSystemObject $object)
	{
		$isInCart = $this->Contains($object);

		if ($isInCart)
			return '<a href="' . $object->uri . '?removefromcart" type="application/json" class="btn btn-outline-danger removefromcart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Retirer</span></a>' . "\r\n";
		else
			return '<a href="' . $object->uri . '?addtocart" type="application/json" class="btn btn-outline-success addtocart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Ajouter</span></a>' . "\r\n";
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

function http_exit(string $message, int $http_reponse_code = 500)
{
	header('Content-Type: text/plain', TRUE, $http_reponse_code);
	exit($message);
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