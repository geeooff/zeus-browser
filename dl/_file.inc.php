<?php

isset($end) or die(':P');

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
$securelink = $end->GetSecureLink();
$httpSecureLink = $end->GetSecureLink($scheme = 'http'); //$https ? substr_replace($securelink, 'http', 0, 5) : $securelink;
$httpsSecureLink = $end->GetSecureLink($scheme = 'https'); //$https ? $securelink : substr_replace($securelink, 'https', 0, 4);

?>
			<?php if (in_array($end->mediatype, ['video','music','image'])): ?>
				<?php if ($end->mediatype === 'image'): ?>
				<div class="card">
					<div class="card-header">Aperçu image</div>
					<a href="<?= $end->path ?>"><img src="<?= $end->path ?>" class="img-fluid card-img-bottom"></a>
				</div>
				<?php elseif ($end->mediatype === 'music'): ?>
				<div class="card">
					<div class="card-header">
						Aperçu audio
						<select class="ssl">
							<option value="<?=$httpsSecureLink?>"<?=($https ? ' selected' : '')?>>https</option>
							<option value="<?=$httpSecureLink?>"<?=($https ? '' : ' selected')?>>http</option>
						</select>
					</div>
					<audio id="player" controls>
						<?php if ($end->playermimetype != NULL): ?><source src="<?=$securelink?>" type="<?=$end->playermimetype?>"><?php endif; ?>
						<source src="<?=$securelink?>" type="<?=$end->mimetype?>">
					</audio>
				</div>
				<?php elseif ($end->mediatype === 'video'): ?>
				<div class="card">
					<div class="card-header">
						Aperçu vidéo
						<select class="ratio">
							<option value="auto" selected>auto</option>
							<option value="16by9">16:9</option>
							<option value="4by3">4:3</option>
						</select>
						<select class="ssl">
							<option value="<?=$httpsSecureLink?>"<?=($https ? ' selected' : '')?>>https</option>
							<option value="<?=$httpSecureLink?>"<?=($https ? '' : ' selected')?>>http</option>
						</select>
					</div>
					<div class="embed-responsive embed-responsive-auto">
						<video id="player" class="embed-responsive-item" controls>
							<?php if ($end->playermimetype != NULL): ?><source src="<?=$securelink?>" type="<?=$end->playermimetype?>"><?php endif; ?>
							<source src="<?=$securelink?>" type="<?=$end->mimetype?>">
						</video>
					</div>
				</div>
				<?php endif; ?>
				<div class="card">
					<div class="card-header">Informations média</div>
					<div class="card-block mediainfo">Chargement...</div>
				</div>
			<?php elseif (in_array($end->mediatype, ['text','subtitles'])): ?>
			<div class="card">
				<div class="card-header">Contenu du fichier</div>
				<pre class="card-block"><?= html_encode($end->GetAllText()) ?></pre>
			</div>
			<?php elseif ($end->extension == "pdf"): ?>
			<div class="card">
				<div class="card-header">
					Aperçu du PDF
					<select class="ratio">
						<option value="4by3" selected>4:3</option>
						<option value="16by9">16:9</option>
					</select>
				</div>
				<div class="embed-responsive embed-responsive-4by3">
					<object data="<?= $end->path ?>" class="embed-responsive-item" type="application/pdf">
						<embed src="<?= $end->path ?>" />
					</object>
				</div>
			</div>
			<?php endif; ?>