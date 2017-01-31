<?php

error_reporting(E_ALL);

if (!ini_get('display_errors')) {
	ini_set('display_errors', '1');
}

define('LOCALE', 'en_GB.UTF-8');
setlocale(LC_ALL, LOCALE);
putenv('LC_ALL=' . LOCALE);

$config = parse_ini_file('config.ini', $process_sections = TRUE, $scanner_mode = INI_SCANNER_TYPED);

require_once '_global.inc.php';

extension_loaded('intl') or http_exit('intl extension must be installed');

$builder = new FileSystemObjectBuilder($config);
$cart = $builder->GetCart();
$chain = $builder->GetChain();
$end = array_pop($chain);

if (isset($_GET['config']))
{
	if ($builder->userisadmin)
	{
		//header('Content-Type: application/json; charset=utf-8', TRUE);
		//echo json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		header('Content-Type: text/plain; charset=utf-8', TRUE);
		echo print_r($config);
	}
	else
		http_response_code(403);
}
else if (isset($_GET['json']))
{
	if ($builder->userisadmin)
	{
		header('Content-Type: application/json; charset=utf-8', TRUE);
		echo json_encode($end, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	else
		http_response_code(403);
}
else if (isset($_GET['xml']))
{
	if ($builder->userisadmin)
	{
		error_reporting(E_ALL ^ E_STRICT ^ E_DEPRECATED);
		header('Content-Type: application/xml; charset=utf-8', TRUE);
		require_once 'XML/Serializer.php';
		$serializer = new XML_Serializer([
			'indent'          => "\t",
			'linebreak'       => "\r\n",
			'typeHints'       => FALSE,
			'addDecl'         => TRUE,
			'encoding'        => 'UTF-8'
		]);
		$serializer->serialize($end) or die("XML Serialization failed");
		echo $serializer->getSerializedData();
	}
	else
		http_response_code(403);
}
else if (isset($_GET['phpinfo']))
{
	if ($builder->userisadmin)
	{
		header('Content-Type: text/html; charset=utf-8', TRUE);
		phpinfo();
	}
	else
		http_response_code(403);
}
else if (isset($_GET['mediainfo']))
{
	if (in_array($end->mediatype, ['video','music','image']))
	{
		$format = isset($_GET['format']) ? $_GET['format'] : 'text';

		switch ($format)
		{
			case 'text':
				header('Content-Type: text/plain; charset=utf-8', TRUE);
				$output = $end->GetMediaInfo(MediaInfoFormat::Text);
				break;

			case 'xml':
				header('Content-Type: application/xml; charset=utf-8', TRUE);
				$output = $end->GetMediaInfo(MediaInfoFormat::Xml);
				break;

			case 'html':
				header('Content-Type: text/html; charset=utf-8', TRUE);
				$output = $end->GetMediaInfo(MediaInfoFormat::Html);
				break;

			default:
				http_exit('MediaInfo: output format "' . $format . '" is not implemented');
				break;
		}

		if ($output !== FALSE)
			echo $output;
		else
			http_exit('MediaInfo: unknown error');
	}
	else
	{
		http_exit('MediaInfo: file mediatype "' . $end->mediatype . '" is not implemented');
	}
}
else if (isset($_GET['url']))
{
	if (!$end->exists)
		http_die("Le fichier n'existe plus", 404);

	header('Content-Type: text/plain; charset=utf-8', TRUE);
	header('Content-Disposition: inline', TRUE);
	echo $end->GetSecureLink();
}
else if (isset($_GET['urls']))
{
	if (!$end->exists)
		http_die("Le dossier n'existe plus", 404);

	header('Content-Type: text/plain; charset=utf-8', TRUE);
	header('Content-Disposition: inline; filename=urls.txt', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $end->GetRecursiveSecureLinks($filterMediaType, $scheme);
}
else if (isset($_GET['m3u']))
{
	if (!$end->exists)
		http_die("Le fichier ou le dossier n'existe plus", 404);

	header('Content-Type: application/x-mpegurl; charset=utf-8', TRUE);
	header('Content-Disposition: attachment; filename=playlist.m3u8', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $end->GetM3U($filterMediaType, $scheme);
}
else if (isset($_GET['asx']))
{
	if (!$end->exists)
		http_die("Le fichier ou le dossier n'existe plus", 404);

	header('Content-Type: application/asx; charset=utf-8', TRUE);
	header('Content-Disposition: attachment; filename=playlist.asx', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $end->GetASX($filterMediaType, $scheme);
}
else if (isset($_GET['carturls']))
{
	header('Content-Type: text/plain; charset=utf-8', TRUE);
	header('Content-Disposition: inline; filename=urls.txt', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $cart->GetRecursiveSecureLinks($filterMediaType, $scheme);
}
else if (isset($_GET['cartm3u']))
{
	header('Content-Type: application/x-mpegurl; charset=utf-8', TRUE);
	header('Content-Disposition: attachment; filename=playlist.m3u8', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $cart->GetM3U($filterMediaType, $scheme);
}
else if (isset($_GET['cartasx']))
{
	header('Content-Type: application/asx; charset=utf-8', TRUE);
	header('Content-Disposition: attachment; filename=playlist.asx', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $cart->GetASX($filterMediaType, $scheme);
}
else if (isset($_GET['addtocart']))
{
	if (!$end->exists)
		http_die("Le fichier ou le dossier n'existe plus", 404);

	header('Content-Type: application/json; charset=utf-8', TRUE);

	$fromHeaderButton = isset($_GET['header']);
	$success = $cart->Add($end);
	$html = $fromHeaderButton ? $cart->GetHeaderActionButton($end) : $cart->GetObjectActionButton($end);

	$output = array(
		'uri' => $end->uri,
		'success' => $success,
		'html' => $html
	);

	echo json_encode($output);
}
else if (isset($_GET['removefromcart']))
{
	if (!$end->exists)
		http_die("Le fichier ou le dossier n'existe plus", 404);

	header('Content-Type: application/json; charset=utf-8', TRUE);

	$fromHeaderButton = isset($_GET['header']);
	$success = $cart->Remove($end);
	$html = $fromHeaderButton ? $cart->GetHeaderActionButton($end) : $cart->GetObjectActionButton($end);

	$output = array(
		'uri' => $end->uri,
		'success' => $success,
		'html' => $html
	);

	echo json_encode($output);
}
else if (isset($_GET['emptycart']))
{
	header('Content-Type: application/json; charset=utf-8', TRUE);
	$retvalue = $cart->EmptyCart();
	echo json_encode($retvalue);
}
else if (isset($_GET['fullcarthtml']))
{
	header('Content-Type: text/html; charset=utf-8', TRUE);
	echo $cart->GetFullHtml($end);
}
else if (isset($_GET['lightcarthtml']))
{
	header('Content-Type: text/html; charset=utf-8', TRUE);
	echo $cart->GetHeaderActionButton($end);
}
else
{
	header('X-UA-Compatible: IE=edge,chrome=1', TRUE);
	header('Content-Type: text/html; charset=utf-8', TRUE);
	
	include '_header.inc.php';

	if (isset($_GET['cart']))
	{
		$cart->GetObjects();
		$allFiles = $cart->GetAllFiles();
		include '_cart.inc.php';
	}
	else
	{
		$end->GetSiblings();

		include '_object.inc.php';
		
		if ($end->exists)
		{
			if ($end->isdir)
			{
				$end->GetChildren();
				include '_dir.inc.php';
			}
			else if ($end->isfile)
			{
				include '_file.inc.php';
			}
		}
	}

	include '_footer.inc.php';
}

?>