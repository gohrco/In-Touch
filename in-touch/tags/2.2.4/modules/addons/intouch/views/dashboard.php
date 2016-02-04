<div class="row">
	<div class="well span8">
		<?=t( 'intouch.admin.default.body' )?>
	</div>
	<div class="span4">
		<?php
		
		foreach ( $data->widgets as $widget ) :
		?>
			<div class="well well-small alert<?=$widget->status?>">
				<h3><?=$widget->header?></h3>
				<?=$widget->body?>
			</div>
		<?php 
		
		endforeach;
		?>
	</div>
</div>