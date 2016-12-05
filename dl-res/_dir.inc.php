<?php

isset($end) or die(':P');

?>
			<div class="row">
				<div class="col-xs-12">
					<?php if (count($end->children) > 0): ?>
					<table id="dir" class="table table-striped">
						<thead>
							<tr>
								<th class="col-icon"></th>
								<th class="col-name">Nom</th>
								<th class="col-size">Taille</th>
								<th class="col-date">Date</th>
								<th class="col-actions"></th>
							</tr>
						</thead>
					<?php foreach ($end->children as $index => $child): ?>
						<tr>
							<td class="col-icon" data-sort="<?= $child->mediatype ?>"><span class="glyphicon glyphicon-<?= $child->mediaicon ?>"></span></td>
							<td class="col-name" data-search="<?= html_encode($child->name) ?>"><a href="<?= $child->uri ?>" type="text/html"><?= html_encode($child->name) ?></a></td>
							<td class="col-size" data-sort="<?= $child->filesize ?>"><?php if ($child->isfile): ?><?= html_encode(format_filesize($child->filesize)) ?><?php endif; ?></td>
							<td class="col-date" data-sort="<?= $child->mtime ?>">
								<span class="hidden-md hidden-lg">
									<?= html_encode(format_datetime_short($child->mtime)) ?>
								</span>
								<span class="hidden-xs hidden-sm hidden-lg">
									<?= html_encode(format_datetime_simple($child->mtime)) ?>
								</span>
								<span class="hidden-xs hidden-sm hidden-md">
									<?= html_encode(format_datetime_full($child->mtime)) ?>
								</span>
							</td>
							<td class="col-actions hidden-xs hidden-sm">&nbsp;</td>
						</tr>
					<?php endforeach; ?>
					</table>
					<?php else: ?>
					<div class="alert alert-warning" role="alert">
						Le dossier est vide.
					</div>
					<?php endif; ?>
				</div>
			</div>