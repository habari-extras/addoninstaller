<?php namespace Habari;
// This template could propably be replaced by the admin's plugin template if we modify both installer and admin template slightly. ?>
<div class="item plugin">
	<div class="head">
		<div class="title">
			<a href="<?php echo $addon->download_url; ?>" class="plugin"><?php echo $addon->name; ?> <span class="version"><?php echo $addon->version; ?></span></a>
			<?php if(isset($addon->author)): ?>
				<span class="dim"><?php _e('by'); ?></span>
				<?php
				$authors = array();
				foreach ( $addon->author as $author ) {
					$authors[] = isset( $author['url'] ) ? '<a href="' . $author['url'] . '">' . $author . '</a>' : $author;
				}
				// @locale The string used between the last two items in the list of authors of an addon on the "Install addons page" (one, two, three *and* four).
				echo Format::and_list( $authors, '<span class="dim">, </span>', '<span class="dim">' . _t( ' and ' ) . '</span>');
				endif;
			?>
		</div>
		
		<?php if ( isset($addon->help) ): ?>
		<a class="help" href="<?php echo $addon->help['url']; ?>">?</a>
		<?php endif; ?>
		
		<?php
		/** @var FormControlDropbutton $dbtn */
		if(count($addon->actions) > 0):
			$dbtn = FormControlDropbutton::create('actions');
			foreach($addon->actions as $key => $data) {
				$dbtn->append(FormControlSubmit::create($key)->set_url($data)->set_caption(_t("Install")));
			}
			echo $dbtn->pre_out();
			echo $dbtn->get($theme);
		endif;
		?>
		<p class="description"><?php echo $addon->description; ?></p>
	</div>
		
	<?php if(!$addon->habari_compatible): ?>
	<div class="requirements">
		<ul>
			<li class="error"><?php _e("This addon needs at least Habari version %s", array($addon->habari_version));?></li>
		</ul>
	</div>
	<?php endif; ?>
</div>