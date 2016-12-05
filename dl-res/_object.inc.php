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
								echo '<li class="active">', html_encode($object->name), '</li>', "\r\n"; 
							else if ($object->exists)
								echo '<li><a href="', $object->uri, '" type="text/html">', html_encode($object->name), '</a></li>', "\r\n"; 
							else
								echo '<li>', html_encode($object->name), '</li>', "\r\n"; 
						}
						echo '</ol>', "\r\n"; 
					}

					echo '<h2>', html_encode($end->name);
					if (!$end->exists)
					{
						echo ' <small>n\'existe pas, bitch !</small>';
					}
					echo '</h2>', "\r\n"; 		
				
					if ($end->exists)
					{
						if ($end->IsBeingWritten())
						{
							echo '<div class="alert alert-warning" role="alert">', "\r\n"; 
							echo '<strong>Attention&nbsp;!</strong> Ce ', $end->isdir ? 'dossier' : 'fichier', ' semble toujours en cours d\'écriture !', "\r\n";
							echo '</div>', "\r\n"; 
						}
						
						echo '<p>Date&nbsp;: <span class="value date" data-toggle="tooltip" data-placement="right" title="', html_encode(format_datetime_full($end->mtime)), '">', html_encode(format_datetime_simple($end->mtime)),'</span>', "\r\n"; 

						if ($end->isfile)
						{
							echo '<br>Taille&nbsp;: <span class="value filesize" data-toggle="tooltip" data-placement="right" title="', html_encode(format_filesize($end->filesize, true)),'">', html_encode(format_filesize($end->filesize)), '</span>', "\r\n"; 
						}

						echo '</p>', "\r\n"; 
					}
				?>
				<span class="btn-group">
					<button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						Debug <span class="caret"></span>
					</button>
					<ul class="dropdown-menu" role="menu">
						<li><a href="?phpinfo" type="text/html">phpinfo()</a></li>
						<li><a href="?config" type="text/plain">config.ini</a></li>
						<li><a href="?json" type="application/json">JSON</a></li>
						<li><a href="?xml" type="application/xml">XML</a></li>
						<?php if ($end->exists && in_array($end->mediatype, ['video','music','image'])): ?>
						<li><a href="?mediainfo" type="text/plain">Mediainfo (par défaut)</a></li>
						<li><a href="?mediainfo&amp;format=text" type="text/plain">Mediainfo (Texte)</a></li>
						<li><a href="?mediainfo&amp;format=xml" type="application/xml">Mediainfo (XML)</a></li>
						<li><a href="?mediainfo&amp;format=html" type="text/html">Mediainfo (HTML)</a></li>
						<?php endif; ?>
					</ul>
				</span>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<div id="header-toolbar" class="btn-toolbar clearfix" role="toolbar">
						<?php
							$parent = $end->exists ? $end->parent : $end->GetFirstExistantAncestor();
							if ($parent != null)
							{
								echo '<div class="btn-group pull-left" role="group">', "\r\n";
								echo '<a href="', $parent->uri, '" type="text/html" class="btn btn-default"><span class="glyphicon glyphicon-menu-up"></span> <span class="hidden-xs">', html_encode($parent->name), '</span></a>', "\r\n";
								echo '</div>', "\r\n";
							}
							if ($end->exists)
							{
								echo '<div class="btn-group pull-right" role="group">', "\r\n";

								if ($end->isdir)
								{
									echo '<a href="?urls" type="text/plain" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-list"></span> <span class="hidden-xs">URLs</span></a>', "\r\n";
								}
								elseif ($end->isfile)
								{
									echo '<a href="', $end->path, '" download class="btn btn-primary" role="button"><span class="glyphicon glyphicon-save"></span> <span class="hidden-xs">Télécharger</span></a>', "\r\n";
									echo '<a href="?url" type="text/plain" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-list"></span> <span class="hidden-xs">URL</span></a>', "\r\n";
								}

								if ($isInCart)
									echo '<a href="?removefromcart" type="application/json" class="btn btn-danger removefromcart" role="button"><span class="glyphicon glyphicon-shopping-cart"></span> <span class="hidden-xs">Retirer</span></a>', "\r\n";
								else
									echo '<a href="?addtocart" type="application/json" class="btn btn-primary addtocart" role="button"><span class="glyphicon glyphicon-shopping-cart"></span> <span class="hidden-xs">Ajouter</span></a>', "\r\n";

								echo '</div>', "\r\n";
							}

							echo '<div class="cart cart-light">', $cart->GetLightHtml(), '</div>';
						?>
					</div>
					<div id="header-toolbar-hidden">
						<?php
							if (!$isInCart)
								echo '<a href="?removefromcart" type="application/json" class="btn btn-danger removefromcart" role="button"><span class="glyphicon glyphicon-shopping-cart"></span> <span class="hidden-xs">Retirer</span></a>', "\r\n";
							else
								echo '<a href="?addtocart" type="application/json" class="btn btn-primary addtocart" role="button"><span class="glyphicon glyphicon-shopping-cart"></span> <span class="hidden-xs">Ajouter</span></a>', "\r\n";
						?>
					</div>
				</div>
			</div>