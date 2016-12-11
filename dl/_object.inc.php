<?php

isset($end) or die(':P');
$isInCart = $end->exists ? $cart->Contains($end) : FALSE;

?>
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

					echo '<h1 class="display-4">', html_encode($end->name);
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
				<div class="dropdown">
					<button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown">
						<i class="fa fa-bug"></i> Debug
					</button>
					<div class="dropdown-menu">
						<a class="dropdown-item" href="?phpinfo" type="text/html">phpinfo()</a>
						<a class="dropdown-item" href="?config" type="text/plain">config.ini</a>
						<a class="dropdown-item" href="?json" type="application/json">JSON</a>
						<a class="dropdown-item" href="?xml" type="application/xml">XML</a>
						<?php if ($end->exists && in_array($end->mediatype, ['video','music','image'])): ?>
						<a class="dropdown-item" href="?mediainfo" type="text/plain">Mediainfo (par défaut)</a>
						<a class="dropdown-item" href="?mediainfo&amp;format=text" type="text/plain">Mediainfo (Texte)</a>
						<a class="dropdown-item" href="?mediainfo&amp;format=xml" type="application/xml">Mediainfo (XML)</a>
						<a class="dropdown-item" href="?mediainfo&amp;format=html" type="text/html">Mediainfo (HTML)</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div id="header-toolbar" class="btn-toolbar clearfix" role="toolbar">
				<?php
					$parent = $end->exists ? $end->parent : $end->GetFirstExistantAncestor();
					if ($parent != null)
					{
						echo '<div class="btn-group pull-xs-left" role="group">', "\r\n";
						echo '<a href="', $parent->uri, '" type="text/html" class="btn btn-secondary"><i class="fa fa-chevron-up"></i> <span class="hidden-xs-down">', html_encode($parent->name), '</span></a>', "\r\n";
						echo '</div>', "\r\n";
					}
					if ($end->exists)
					{
						echo '<div class="btn-group pull-xs-right" role="group">', "\r\n";

						if ($end->isdir)
						{
							echo '<a href="?urls" type="text/plain" class="btn btn-secondary" role="button"><i class="fa fa-list"></i> <span class="hidden-xs-down">URLs</span></a>', "\r\n";
						}
						elseif ($end->isfile)
						{
							echo '<a href="', $end->path, '" download class="btn btn-secondary" role="button"><i class="fa fa-save"></i> <span class="hidden-xs-down">Télécharger</span></a>', "\r\n";
							echo '<a href="?url" type="text/plain" class="btn btn-secondary" role="button"><i class="fa fa-list"></i> <span class="hidden-xs-down">URL</span></a>', "\r\n";
						}

						if ($isInCart)
							echo '<a href="?removefromcart" type="application/json" class="btn btn-danger removefromcart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Retirer</span></a>', "\r\n";
						else
							echo '<a href="?addtocart" type="application/json" class="btn btn-secondary addtocart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Ajouter</span></a>', "\r\n";

						echo '</div>', "\r\n";
					}

					echo '<div class="cart cart-light btn-group pull-xs-right" role="group">', $cart->GetLightHtml(), '</div>', "\r\n";
				?>
			</div>
			<div id="header-toolbar-hidden">
				<?php
					if (!$isInCart)
						echo '<a href="?removefromcart" type="application/json" class="btn btn-danger removefromcart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Retirer</span></a>', "\r\n";
					else
						echo '<a href="?addtocart" type="application/json" class="btn btn-secondary addtocart" role="button"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs-down">Ajouter</span></a>', "\r\n";
				?>
			</div>