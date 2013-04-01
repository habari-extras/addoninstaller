<?php namespace Habari;
// This template could propably be replaced by the admin's plugin template if we modify both installer and admin template slightly. ?>
<div class="item plugin clear">
	<div class="head">
		<a href="<?php echo $addon->download_url; ?>" class="addon"><?php echo $addon->name; ?> <span class="version"><?php echo $addon->version; ?></span></a>
	</div>
	
	<span class="dim"><?php _e('by'); ?></span>

	<?php if(isset($addon->author)) {
		$authors = array();
		foreach ( $addon->author as $author ) {
			$authors[] = isset( $author['url'] ) ? '<a href="' . $author['url'] . '">' . $author . '</a>' : $author;
		}
		// @locale The string used between the last two items in the list of authors of an addon on the "Install addons page" (one, two, three *and* four).
		echo Format::and_list( $authors, '<span class="dim">, </span>', '<span class="dim">' . _t( ' and ' ) . '</span>');
	} ?>
	
	<?php if ( isset($addon->help) ): ?>
		<a class="help" href="<?php echo $addon->help_url; ?>">?</a>
	<?php endif; ?>

	<ul class="dropbutton">
		<?php foreach ( $addon->actions as $addon_action => $action_url ) : ?>
			<li><a href="<?php echo Utils::htmlspecialchars( $action_url ); ?>"><?php echo $addon_action; ?></a></li>
		<?php endforeach; ?>
	</ul>
		
	<?php if(!$addon->habari_compatible): ?>
	<div class="requirements">
		<ul>
			<li class="error"><?php _e("This addon needs at least Habari version %s", array($addon->habari_version));?></li>
		</ul>
	</div>
	<?php endif; ?>
</div>