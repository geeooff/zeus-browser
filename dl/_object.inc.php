<?php isset($end) or die(':P'); ?>
		<div class="jumbotron">
			<div class="container">
				<?php
					if (count($chain) > 0)
					{
						echo '<ol class="breadcrumb">', "\r\n"; 
						foreach ($chain as $object)
						{
							if ($object == $end)
								echo '<li class="breadcrumb-item active">', html_encode($object->name), '</li>', "\r\n"; 
							else if ($object->exists)
								echo '<li class="breadcrumb-item"><a href="', $object->uri, '" type="text/html">', html_encode($object->name), '</a></li>', "\r\n"; 
							else
								echo '<li class="breadcrumb-item">', html_encode($object->name), '</li>', "\r\n"; 
						}
						echo '</ol>', "\r\n"; 
					}

					echo '<h1>', html_encode($end->name);
					if (!$end->exists)
					{
						echo ' <small>n\'existe pas, bitch !</small>';
					}
					echo '</h1>', "\r\n"; 		
				
					if ($end->exists)
					{
						if ($end->IsBeingWritten())
						{
							echo '<div class="alert alert-warning" role="alert">', "\r\n"; 
							echo '<strong>Attention&nbsp;!</strong> Ce ', $end->isdir ? 'dossier' : 'fichier', ' semble toujours en cours d\'écriture !', "\r\n";
							echo '</div>', "\r\n"; 
						}
						
						echo '<p class="lead">Date&nbsp;: <span class="value date" data-toggle="tooltip" data-placement="right" title="', html_encode(format_datetime_full($end->mtime)), '">', html_encode(format_datetime_simple($end->mtime)),'</span>', "\r\n"; 

						if ($end->isfile)
						{
							echo '<br>Taille&nbsp;: <span class="value filesize" data-toggle="tooltip" data-placement="right" title="', html_encode(format_filesize($end->filesize, true)),'">', html_encode(format_filesize($end->filesize)), '</span>', "\r\n"; 
						}

						echo '</p>', "\r\n"; 
					}
				?>
				<div class="btn-group btn-group-sm">
					<button type="button" class="btn btn-outline-danger dropdown-toggle" data-toggle="dropdown">
						<i class="fa fa-bug"></i> Debug
					</button>
					<div class="dropdown-menu">
						<a class="dropdown-item" href="?phpinfo" type="text/html">phpinfo()</a>
						<a class="dropdown-item" href="?config" type="text/plain">config.ini</a>
						<a class="dropdown-item" href="?json" type="application/json">JSON</a>
						<a class="dropdown-item" href="?xml" type="application/xml">XML</a>
						<?php if ($end->exists && in_array($end->mediatype, ['video','music','image'])): ?>
						<a class="dropdown-item" href="?mediainfo" type="text/plain">Mediainfo (par défaut)</a>
						<a class="dropdown-item" href="?mediainfo&format=text" type="text/plain">Mediainfo (Texte)</a>
						<a class="dropdown-item" href="?mediainfo&format=xml" type="application/xml">Mediainfo (XML)</a>
						<a class="dropdown-item" href="?mediainfo&format=html" type="text/html">Mediainfo (HTML)</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div id="header-toolbar">
				<?php
					$parent = $end->exists ? $end->parent : $end->GetFirstExistantAncestor();
					if ($parent != null)
					{
						echo '<div class="btn-group">', "\r\n";
						echo "\t", '<a href="', $parent->uri, '" type="text/html" class="btn btn-outline-primary"><i class="fa fa-chevron-up"></i> <span class="hidden-xs-down">', html_encode($parent->name), '</span></a>', "\r\n";
						echo '</div>', "\r\n";
					}
					if ($end->exists)
					{
						if ($end->isdir)
						{
							echo '<div class="btn-toolbar">', "\r\n";
							echo '<div class="btn-group">', "\r\n";
							echo "\t", '<a href="?urls" type="text/plain" class="btn btn-secondary"><i class="fa fa-list"></i> <span class="hidden-xs-down">URLs</span></a>', "\r\n";
							echo "\t", '<a class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">', "\r\n";
							echo "\t\t", '<span class="sr-only">Types de fichiers</span>', "\r\n";
							echo "\t", '</a>', "\r\n";
							echo "\t", '<div class="dropdown-menu">', "\r\n";
							echo "\t\t", '<a class="dropdown-item" href="?urls" type="text/plain">Tout fichier</a>', "\r\n";
							echo "\t\t", '<div class="dropdown-divider"></div>', "\r\n";
					
							foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
							{
								echo "\t\t", '<a class="dropdown-item" href="?urls&mediatype=', urlencode($mediatype), '" type="text/plain"><i class="fa fa-fw fa-', $builder->mediaicons[$mediatype], '"></i> ', html_encode($mediatypelabel), '</a>', "\r\n";
							}

							echo "\t", '</div>', "\r\n";
							echo '</div>', "\r\n";

							echo '<div class="btn-group">', "\r\n";
							echo "\t", '<a href="?m3u" type="application/x-mpegurl" class="btn btn-secondary"><i class="fa fa-play"></i> <span class="hidden-xs-down">Playlist</span></a>', "\r\n";
							echo "\t", '<a class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">', "\r\n";
							echo "\t\t", '<span class="sr-only">Types de fichiers</span>', "\r\n";
							echo "\t", '</a>', "\r\n";
							echo "\t", '<div class="dropdown-menu">', "\r\n";
							echo "\t\t", '<div class="dropdown-header">Format M3U</div>', "\r\n";
							echo "\t\t", '<a class="dropdown-item" href="?m3u" type="application/x-mpegurl">Tout fichier</a>', "\r\n";
							echo "\t\t", '<div class="dropdown-divider"></div>', "\r\n";

							foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
							{
								echo "\t\t", '<a class="dropdown-item" href="?m3u&mediatype=', urlencode($mediatype), '" type="application/x-mpegurl"><i class="fa fa-fw fa-', $builder->mediaicons[$mediatype], '"></i> ', html_encode($mediatypelabel), '</a>', "\r\n";
							}

							echo "\t\t", '<div class="dropdown-divider"></div>', "\r\n";
							echo "\t\t", '<div class="dropdown-header">Format ASX</div>', "\r\n";
							echo "\t\t", '<a class="dropdown-item" href="?asx" type="application/asx">Tout fichier</a>', "\r\n";
							echo "\t\t", '<div class="dropdown-divider"></div>', "\r\n";

							foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
							{
								echo "\t\t", '<a class="dropdown-item" href="?asx&mediatype=', urlencode($mediatype), '" type="application/asx"><i class="fa fa-fw fa-', $builder->mediaicons[$mediatype], '"></i> ', html_encode($mediatypelabel), '</a>', "\r\n";
							}

							echo "\t", '</div>', "\r\n";
							echo '</div>', "\r\n";
							echo '</div>', "\r\n";
						}
						elseif ($end->isfile)
						{
							echo '<div class="btn-group">', "\r\n";
							echo "\t", '<a href="', $end->path, '" download class="btn btn-secondary"><i class="fa fa-save"></i> <span class="hidden-xs-down">Télécharger</span></a>', "\r\n";
							echo "\t", '<a href="?url" type="text/plain" class="btn btn-secondary"><i class="fa fa-list"></i> <span class="hidden-xs-down">URL</span></a>', "\r\n";
							echo "\t", '<a href="?m3u" type="application/x-mpegurl" class="btn btn-secondary"><i class="fa fa-play"></i> <span class="hidden-xs-down">Playlist</span></a>', "\r\n";
							echo "\t", '<a class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">', "\r\n";
							echo "\t\t", '<span class="sr-only">Types de fichiers</span>', "\r\n";
							echo "\t", '</a>', "\r\n";
							echo "\t", '<div class="dropdown-menu dropdown-menu-right">', "\r\n";
							echo "\t\t", '<a class="dropdown-item" href="?m3u" type="application/x-mpegurl">Format M3U</a>', "\r\n";
							echo "\t\t", '<a class="dropdown-item" href="?asx" type="application/asx">Format ASX</a>', "\r\n";
							echo "\t", '</div>', "\r\n";
							echo '</div>', "\r\n";
						}
					}

					echo '<div class="cart cart-light btn-group">', $cart->GetLightHtml($end), '</div>', "\r\n";
				?>
			</div>