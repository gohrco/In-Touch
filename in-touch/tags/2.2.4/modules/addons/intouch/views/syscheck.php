<?php

foreach ( $data as $key => $items ) :
?>
<table class="table-bordered table-striped">
	<thead>
		<tr>
			<th colspan="3">
				<?= t( 'intouch.syscheck.tblhdr.' . $key ) ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php
		
		if ( $key == 'files' ) {
			$current	=	true;
			foreach ( $items as $item ) if (! $item->getValueraw() ) $current = false;
			
			if (! $current ) :
				
				?>
				<tr>
					<td colspan="2" style="text-align: right; ">
						<button id="btnfixall" class="btn btn-danger pull-right">
							<?=t( 'intouch.syscheck.general.fixall' )?>
						</button>
					</td>
					<td></td>
				</tr>
				<?php
				
			endif;
		}
		
		foreach ( $items as $row ) :
			$icon	=	'<i id="icon' . $row->getId() . '" class="icon icon-' . ( in_array( $row->getValueraw(), array( 1, 2, null ) ) ? 'ok' : 'remove' ) . ' pull-right"></i>';
		?>
		<tr>
			<td class="span2">
				<?php
				
				if ( $row->isFile() ) {
					$title	=	$icon . $row->getTitle();
				}
				else {
					$title	=	sprintf( $row->getTitle(), $icon );
				}
				
				?>
				<?=$title?>
			</td>
			<td class="span2">
				<?php
				
				if ( $row->isFile() ) {
					
					$id		=	preg_replace( '#[\\\\/\.]+#', '', $row->getId() );
					$jsid	=	preg_replace( '#[\\\\]+#', '_', $row->getId() );
					
					$value	=	'<span id="badge' . $id . '" class="badge badge-' . ( $row->getValueraw() ? 'inverse' : 'important' ) . '">'
							.	t( 'intouch.syscheck.general.yesno.' . ( $row->getValueraw() ? 'yes' : 'no' ) )
							.	'</span>'
							.	(! $row->getValueraw() && ( $row->getFilecode() == 4 || $row->getFilecode() == 2 ) ? '<button id="btn' . $id . '" class="fixfile btn btn-danger btn-mini pull-right" data-filename="' . $row->getTitle() . '" data-refid="' . $id . '">' . t( 'intouch.syscheck.general.fixit' ) . '</button>' : null );
				}
				else {
					$value	=	$row->getValue();
					switch ( $value->type ) :
					case 'text' :
						$value	=	'<strong>' . $value->text . '</strong>';
						break;
					case 'badge' :
						$value	=	'<span class="badge badge-' . ( $row->getValueraw() === true ? 'inverse' : 'important' ) . '">' . $value->text . '</span>';
						break;
					case 'sslbadge' :
						$value	=	'<span class="badge badge-' . ( in_array( $row->getValueraw(), array( true, null ) ) ? 'inverse' : 'important' ) . '">' . $value->text . '</span>';
						break;
					default :
						$value	=	$value->text;
					endswitch;
				}
				
				?>
				<?=$value?>
			</td>
			<td class="span8">
				<?php
				
				if ( $row->isFile() ) {
					
					$help	=	$row->getHelp();
					
					if (! $row->getValueraw() ) {
						$id		=	'help' . preg_replace( '#[\\\\/\.]*#', '', $row->getId() );
						$help	=	'<span id="' . $id . '" class="text-error"><strong>' . t( 'intouch.syscheck.general.attention' ) . '</strong>'
								.	$help->text . '</span>';
					}
					else {
						$help	=	null;
					}
				}
				else {
					$help	=	$row->getHelp();
				
					switch ( $help->type ) :
					case 'label-success' :
						$help	=	'<span class="label label-success">' . $help->text . '</span>';
						break;
					case 'alert-info' :
						$help	=	'<div class="alert alert-info">' . $help->text . '</div>';
						break;
					case 'alert-warning' :
						$help	=	'<div class="alert alert-danger"><strong>' . t( 'intouch.syscheck.general.supported.no' ) . '</strong>' . $help->text . '</div>';
						break;
					case 'attention' :
						$help	=	'<div class="alert alert-warning"><strong>' . t( 'intouch.syscheck.general.attention' ) . '</strong>' . $help->text . '</div>';
						break;
					default :
						$help	=	$help->type;
					endswitch;
				}
				
				?>
				<?=$help?>
			</td>
		</tr>
		<?php
		endforeach;
		?>
	</tbody>
</table><br /><br />
<?php
endforeach;
