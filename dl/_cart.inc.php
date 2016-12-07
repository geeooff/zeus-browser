<?php

isset($builder) && isset($cart) or die(':P');

?>
		<div class="jumbotron">
			<div class="container">
				<h1>Panier</h1>
				<p>
					<?php
						$totalFilesSize = total_size($allFiles);
						echo 'Eléments&nbsp;: <span class="value">', count($cart->objects), '</span><br />', "\r\n";
						echo 'Fichiers&nbsp;: <span class="value">', count($allFiles), '</span><br />', "\r\n";
						echo 'Taille&nbsp;: <span class="value" data-toggle="tooltip" data-placement="right" title="', html_encode(format_filesize($totalFilesSize, true)), '">', html_encode(format_filesize($totalFilesSize)), '</span>', "\r\n";
					?>
				</p>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<div id="header-toolbar" class="btn-toolbar clearfix" role="toolbar">
						<div class="btn-group pull-left" role="group">
							<a href="javascript:window.history.back()" class="btn btn-default"><span class="glyphicon glyphicon-menu-left"></span> <span class="hidden-xs">Retour</span></a>
						</div>
						<?php if (count($cart->objects) > 0): ?>
						<div class="btn-group pull-right" role="group">
							<a href="?carturls" type="text/plain" class="btn btn-primary"><span class="glyphicon glyphicon-list"></span> URLs</a>
							<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
								<span class="caret"></span>
								<span class="sr-only">Types de fichiers</span>
							</a>
							<ul class="dropdown-menu" role="menu">
								<li><a href="?carturls" type="text/plain">Tout fichier</a></li>
								<li class="divider"></li><?php
							foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
							{
								echo '
								<li><a href="?carturls&mediatype=', urlencode($mediatype), '" type="text/plain"><span class="glyphicon glyphicon-', $builder->mediaicons[$mediatype], '"></span> ', html_encode($mediatypelabel), '</a></li>';
							}
							?>
							</ul>
						</div>
						<div class="btn-group pull-right" role="group">
							<a href="?cartm3u" type="application/x-mpegurl" class="btn btn-primary"><span class="glyphicon glyphicon-play"></span> Playlist</a>
							<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
								<span class="caret"></span>
								<span class="sr-only">Types de fichiers</span>
							</a>
							<ul class="dropdown-menu" role="menu">
								<li class="dropdown-header">Format M3U</li>
								<li><a href="?cartm3u" type="application/x-mpegurl">Tout fichier</a></li>
								<li class="divider"></li>
								<?php
							foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
							{
								echo '
								<li><a href="?cartm3u&mediatype=', urlencode($mediatype), '" type="application/x-mpegurl"><span class="glyphicon glyphicon-', $builder->mediaicons[$mediatype], '"></span> ', html_encode($mediatypelabel), '</a></li>';
							}
							?>
								<li class="divider"></li>
								<li class="dropdown-header">Format ASX</li>
								<li><a href="?cartasx" type="application/asx">Tout fichier</a></li>
								<li class="divider"></li>
								<?php
							foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
							{
								echo '
								<li><a href="?cartasx&mediatype=', urlencode($mediatype), '" type="application/asx"><span class="glyphicon glyphicon-', $builder->mediaicons[$mediatype], '"></span> ', html_encode($mediatypelabel), '</a></li>';
							}
							?>
							</ul>
						</div>
						<div class="btn-group pull-right" role="group">
							<a href="?emptycart" type="application/json" class="btn btn-danger emptycart"><span class="glyphicon glyphicon-remove"></span> <span class="hidden-xs">Vider</span></a>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
<?php

echo '<div class="cart cart-full">';
$cart->GetFullHtml();
echo '</div>';

?>