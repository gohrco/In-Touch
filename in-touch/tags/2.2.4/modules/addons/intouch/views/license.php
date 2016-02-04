<form action="addonmodules.php?module=intouch&action=license&task=save" class="form-horizontal" method="post">
	<?php foreach ( $data->fields as $field ) : ?>
	<div class="control-group">
		<?=$field->label( array( 'class' => 'control-label' ) )?>
		<div class="controls">
			<?=$field->field( $foptn )?>
			<?=$field->description( array( 'type' => 'span', 'class' => 'help-block help-inline' ) )?>
		</div>
	</div>
	<?php endforeach; ?>
	<div class="form-actions">
		<input type="submit" class="btn btn-primary span2" value="<?=t( 'intouch.form.submit' )?>" name="submit" />
		<a href="addonmodules.php?module=intouch&action=default" class="btn btn-inverse pull-right span2"><?=t( 'intouch.form.close' )?></a>
	</div>
</form>