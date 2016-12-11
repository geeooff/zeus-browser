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
			<div id="header-toolbar" class="btn-toolbar clearfix" role="toolbar">
				<div class="btn-group pull-xs-left" role="group">
					<a href="javascript:window.history.back()" class="btn btn-secondary"><i class="fa fa-chevron-left"></i> <span class="hidden-xs-down">Retour</span></a>
				</div>
				<?php if (count($cart->objects) > 0): ?>
				<div class="btn-group pull-xs-right" role="group">
					<a href="?carturls" type="text/plain" class="btn btn-secondary"><i class="fa fa-list"></i> URLs</a>
					<a class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<span class="sr-only">Types de fichiers</span>
					</a>
					<div class="dropdown-menu">
						<a class="dropdown-item" href="?carturls" type="text/plain">Tout fichier</a>
						<div class="dropdown-divider"></div><?php
					foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
					{
						echo '
						<a class="dropdown-item" href="?carturls&mediatype=', urlencode($mediatype), '" type="text/plain"><i class="fa fa-fw fa-', $builder->mediaicons[$mediatype], '"></i> ', html_encode($mediatypelabel), '</a>';
					}
					?>
					</div>
				</div>
				<div class="btn-group pull-xs-right" role="group">
					<a href="?cartm3u" type="application/x-mpegurl" class="btn btn-secondary"><i class="fa fa-play"></i> Playlist</a>
					<a class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<span class="sr-only">Types de fichiers</span>
					</a>
					<div class="dropdown-menu">
						<div class="dropdown-header">Format M3U</div>
						<a class="dropdown-item" href="?cartm3u" type="application/x-mpegurl">Tout fichier</a>
						<div class="dropdown-divider"></div>
						<?php
					foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
					{
						echo '
						<a class="dropdown-item" href="?cartm3u&mediatype=', urlencode($mediatype), '" type="application/x-mpegurl"><i class="fa fa-fw fa-', $builder->mediaicons[$mediatype], '"></i> ', html_encode($mediatypelabel), '</a>';
					}
					?>
						<div class="dropdown-divider"></div>
						<div class="dropdown-header">Format ASX</div>
						<a class="dropdown-item" href="?cartasx" type="application/asx">Tout fichier</a>
						<div class="dropdown-divider"></div>
						<?php
					foreach($builder->mediatypelabels as $mediatype => $mediatypelabel)
					{
						echo '
						<a class="dropdown-item" href="?cartasx&mediatype=', urlencode($mediatype), '" type="application/asx"><i class="fa fa-fw fa-', $builder->mediaicons[$mediatype], '"></i> ', html_encode($mediatypelabel), '</a>';
					}
					?>
					</div>
				</div>
				<div class="btn-group pull-xs-right" role="group">
					<a href="?emptycart" type="application/json" class="btn btn-danger emptycart"><i class="fa fa-remove"></i> <span class="hidden-xs-down">Vider</span></a>
				</div>
				<?php endif; ?>
			</div>
<?php

echo '<div class="cart cart-full">';
$cart->GetFullHtml();
echo '</div>';

?>