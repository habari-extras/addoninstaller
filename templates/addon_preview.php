<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<a id="for_installation"></a>
<?php foreach($addon_types as $type => $type_display): ?>
	<?php if(isset($addons[$type]) && count($addons[$type])): ?>
		<div class="container plugins" id="available_<?= $type ?>_list">

			<h2><?php _e('%s available for installation', array($type_display)); ?></h2>

			<?php
			foreach ( $addons[$type] as $addon ) {
				$theme->addon = $addon;
				$theme->display('addon');
			}
			?>

		</div>
	<?php endif; ?>
<?php endforeach; ?>
