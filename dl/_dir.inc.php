<?php

isset($end) or die(':P');

?>
			<?php if (count($end->children) > 0): ?>
			<table id="dir" class="table table-striped">
				<thead>
					<tr>
						<th class="col-icon"></th>
						<th class="col-name">Nom</th>
						<th class="col-size">Taille</th>
						<th class="col-date">Date</th>
						<th class="col-actions hidden-sm-down">&nbsp;</th>
					</tr>
				</thead>
			<?php foreach ($end->children as $index => $child): ?>
				<tr>
					<td class="col-icon" data-sort="<?= $child->mediatype ?>"><span class="fa fa-fw fa-<?= $child->mediaicon ?>"></span></td>
					<td class="col-name" data-search="<?= html_encode($child->name) ?>"><a href="<?= $child->uri ?>" type="text/html"><?= html_encode($child->name) ?></a></td>
					<td class="col-size" data-sort="<?= $child->filesize ?>"><?php if ($child->isfile): ?><?= html_encode(format_filesize($child->filesize)) ?><?php endif; ?></td>
					<td class="col-date" data-sort="<?= $child->mtime ?>">
						<span class="hidden-md-up">
							<?= html_encode(format_datetime_short($child->mtime)) ?>
						</span>
						<span class="hidden-sm-down hidden-lg-up">
							<?= html_encode(format_datetime_simple($child->mtime)) ?>
						</span>
						<span class="hidden-md-down">
							<?= html_encode(format_datetime_full($child->mtime)) ?>
						</span>
					</td>
					<td class="col-actions hidden-sm-down"><div class="cart cart-light"><?= $cart->GetObjectActionButton($child) ?></div></td>
				</tr>
			<?php endforeach; ?>
			</table>
			<?php else: ?>
			<div class="alert alert-warning" role="alert">
				Le dossier est vide.
			</div>
			<?php endif; ?>