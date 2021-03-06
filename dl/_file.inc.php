<?php

isset($end) or die(':P');

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
$securelink = $end->GetSecureLink();
//$httpSecureLink = $https ? $end->GetSecureLink($scheme = 'http') : $securelink;
//$httpsSecureLink = $https ? $securelink : $end->GetSecureLink($scheme = 'https');

?>
			<?php if (in_array($end->mediatype, ['video','music','image'])): ?>
				<?php if ($end->mediatype === 'image'): ?>
				<div class="card">
					<h2 class="card-header display-4">
						Aperçu image
					</h2>
					<a href="<?= $end->path ?>"><img src="<?= $end->path ?>" class="img-fluid card-img-bottom"></a>
				</div>
				<?php elseif ($end->mediatype === 'music'): ?>
				<div class="card">
					<h2 class="card-header display-4">
						Aperçu audio
						<?php /*<select class="ssl">
							<option value="<?=$httpsSecureLink?>"<?=($https ? ' selected' : '')?>>https</option>
							<option value="<?=$httpSecureLink?>"<?=($https ? '' : ' selected')?>>http</option>
						</select>*/ ?>
					</h2>
					<audio id="player" controls>
						<?php if ($end->playermimetype != NULL): ?><source src="<?=$securelink?>" type="<?=$end->playermimetype?>"><?php endif; ?>
						<source src="<?=$securelink?>" type="<?=$end->mimetype?>">
					</audio>
				</div>
				<?php elseif ($end->mediatype === 'video'): ?>
				<div class="card">
					<h2 class="card-header display-4">
						Aperçu vidéo
						<select class="ratio">
							<option value="auto" selected>auto</option>
							<option value="21by9">21:9</option>
							<option value="16by9">16:9</option>
							<option value="4by3">4:3</option>
							<option value="1by1">1:1</option>
						</select>
						<?php /*<select class="ssl">
							<option value="<?=$httpsSecureLink?>"<?=($https ? ' selected' : '')?>>https</option>
							<option value="<?=$httpSecureLink?>"<?=($https ? '' : ' selected')?>>http</option>
						</select>*/ ?>
					</h2>
					<div class="embed-responsive embed-responsive-auto">
						<video id="player" class="embed-responsive-item" controls>
							<?php if ($end->playermimetype != NULL): ?><source src="<?=$securelink?>" type="<?=$end->playermimetype?>"><?php endif; ?>
							<source src="<?=$securelink?>" type="<?=$end->mimetype?>">
						</video>
					</div>
				</div>
				<?php endif; ?>
				<div class="mediainfo">Chargement...</div>
			<?php elseif (in_array($end->mediatype, ['text','subtitles'])): ?>
			<div class="card">
				<h2 class="card-header display-4">
					Contenu du fichier
				</h2>
				<pre class="card-block"><?= html_encode($end->GetAllText()) ?></pre>
			</div>
			<?php elseif ($end->extension == "pdf"): ?>
			<div class="card">
				<h2 class="card-header display-4">
					Aperçu du PDF
					<select class="ratio">
						<option value="4by3" selected>4:3</option>
						<option value="16by9">16:9</option>
					</select>
				</h2>
				<div class="embed-responsive embed-responsive-4by3">
					<object data="<?= $end->path ?>" class="embed-responsive-item" type="application/pdf">
						<embed src="<?= $end->path ?>" />
					</object>
				</div>
			</div>
			<?php endif; ?>