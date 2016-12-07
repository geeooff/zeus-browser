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

extension_loaded('intl') or http_die('intl extension must be installed');

$builder = new FileSystemObjectBuilder($config);
$cart = $builder->GetCart();
$chain = $builder->GetChain();
$end = array_pop($chain); // end($chain);

if (isset($_GET['config']))
{
	//header('Content-Type: application/json; charset=utf-8', TRUE);
	//echo json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	header('Content-Type: text/plain; charset=utf-8', TRUE);
	echo print_r($config);
}
else if (isset($_GET['json']))
{
	header('Content-Type: application/json; charset=utf-8', TRUE);
	echo json_encode($end, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
else if (isset($_GET['xml']))
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
else if (isset($_GET['phpinfo']))
{
	header('Content-Type: text/html; charset=utf-8', TRUE);
	phpinfo();
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
				echo $end->GetMediaInfo(MediaInfoFormat::Text) or http_die("MediaInfo failure");
				break;

			case 'xml':
				header('Content-Type: application/xml; charset=utf-8', TRUE);
				echo $end->GetMediaInfo(MediaInfoFormat::Xml) or http_die("MediaInfo failure");
				break;

			case 'html':
				header('Content-Type: text/html; charset=utf-8', TRUE);
				echo $end->GetMediaInfo(MediaInfoFormat::Html) or http_die("MediaInfo failure");
				break;

			default:
				http_response_code(404);
				break;
		}
	}
	else
	{
		http_response_code(404);
	}
}
else if (isset($_GET['url']))
{
	header('Content-Type: text/plain; charset=utf-8', TRUE);
	header('Content-Disposition: inline', TRUE);
	echo $end->GetSecureLink();
}
else if (isset($_GET['urls']))
{
	header('Content-Type: text/plain; charset=utf-8', TRUE);
	header('Content-Disposition: inline; filename=urls.txt', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $end->GetRecursiveSecureLinks($filterMediaType, $scheme);
}
else if (isset($_GET['m3u']))
{
	header('Content-Type: application/x-mpegurl; charset=utf-8', TRUE);
	header('Content-Disposition: attachment; filename=playlist.m3u8', TRUE);

	$filterMediaType = isset($_GET['mediatype']) ? $_GET['mediatype'] : NULL;
	$scheme = isset($_GET['scheme']) ? $_GET['scheme'] : NULL;

	echo $end->GetM3U($filterMediaType, $scheme);
}
else if (isset($_GET['asx']))
{
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
	header('Content-Type: application/json; charset=utf-8', TRUE);
	$retvalue = $cart->Add($end);
	echo json_encode($retvalue);
}
else if (isset($_GET['emptycart']))
{
	header('Content-Type: application/json; charset=utf-8', TRUE);
	$retvalue = $cart->EmptyCart();
	echo json_encode($retvalue);
}
else if (isset($_GET['removefromcart']))
{
	header('Content-Type: application/json; charset=utf-8', TRUE);
	$retvalue = $cart->Remove($end);
	echo json_encode($retvalue);
}
else if (isset($_GET['fullcarthtml']))
{
	header('Content-Type: text/html; charset=utf-8', TRUE);
	echo $cart->GetFullHtml();
}
else if (isset($_GET['lightcarthtml']))
{
	header('Content-Type: text/html; charset=utf-8', TRUE);
	echo $cart->GetLightHtml();
}
else
{
	header('X-UA-Compatible: IE=edge,chrome=1', TRUE);
	header('Content-Type: text/html; charset=utf-8', TRUE);
	
	include '_header.inc.php';
	include '_object.inc.php';

	if (isset($_GET['cart']))
	{
		$cart->GetObjects();
		$allFiles = $cart->GetAllFiles();
		include '_cart.inc.php';
	}
	else if ($end->exists)
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

	include '_footer.inc.php';
}

?>