<?php

isset($end) or die(':P');
include_once('../dl-res/_object.inc.php');

$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
$securelink = $end->GetSecureLink();
$httpSecureLink = $end->GetSecureLink($scheme = 'http'); //$https ? substr_replace($securelink, 'http', 0, 5) : $securelink;
$httpsSecureLink = $end->GetSecureLink($scheme = 'https'); //$https ? $securelink : substr_replace($securelink, 'https', 0, 4);

?>
			<?php if (in_array($end->mediatype, ['video','music','image'])): ?>
				<?php if ($end->mediatype === 'image'): ?>
				<div class="row">
					<div class="col-xs-12">
						<h2>Aperçu image</h2>
						<a href="<?= $end->path ?>" class="thumbnail"><img src="<?= $end->path ?>"></a>
					</div>
				</div>
				<?php elseif ($end->mediatype === 'music'): ?>
				<div class="row">
					<div class="col-xs-12">
						<h2>
							Aperçu audio
							<select class="ssl">
								<option value="<?=$httpsSecureLink?>"<?=($https ? ' selected' : '')?>>https</option>
								<option value="<?=$httpSecureLink?>"<?=($https ? '' : ' selected')?>>http</option>
							</select>
						</h2>
						<audio id="player" controls>
							<?php if ($end->playermimetype != NULL): ?><source src="<?=$securelink?>" type="<?=$end->playermimetype?>"><?php endif; ?>
							<source src="<?=$securelink?>" type="<?=$end->mimetype?>">
						</audio>
					</div>
				</div>
				<?php elseif ($end->mediatype === 'video'): ?>
				<div class="row">
					<div class="col-xs-12">
						<h2>
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
						</h2>
						<div class="embed-responsive embed-responsive-auto">
							<video id="player" class="embed-responsive-item" controls>
								<?php if ($end->playermimetype != NULL): ?><source src="<?=$securelink?>" type="<?=$end->playermimetype?>"><?php endif; ?>
								<source src="<?=$securelink?>" type="<?=$end->mimetype?>">
							</video>
						</div>
					</div>
				</div>
				<?php endif; ?>
				<div class="row">
					<div class="col-xs-12">
						<h2>Informations média</h2>
						<div class="mediainfo">Chargement...</div>
					</div>
				</div>				
			<?php elseif (in_array($end->mediatype, ['text','subtitles'])): ?>
			<div class="row">
				<div class="col-xs-12">
					<h2>Contenu du fichier</h2>
					<pre><?= html_encode($end->GetAllText()) ?></pre>
				</div>				
			</div>
			<?php elseif ($end->extension == "pdf"): ?>
			<div class="row">
				<div class="col-xs-12">
					<h2>
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
			</div>
			<?php endif; ?>
<?php

include('../dl-res/_footer.inc.php');

?>