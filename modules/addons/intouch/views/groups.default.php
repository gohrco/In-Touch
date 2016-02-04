<div class="pull-right">
	<form action="addonmodules.php?module=intouch&action=groups" class="spanform form-inline" method="post">
		<button type="submit" class="btn btn-success span3 pull-right"><?= t( 'intouch.form.button.addnew' )?></button>
		<input name="submit" value="1" type="hidden" /><input type="hidden" name="task" value="addnew" />
	</form>
</div>
<div style="clear: both; "> </div>

<table class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Group</th>
			<th>Active</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
		
		<?php foreach ( $data->rows as $row ) : ?>
		
		<tr class="<?=( $row->active ? 'success' : 'error' ) ?>">
			<td><?=$row->id ?></td>
			<td><?=$row->name ?></td>
			<td><?=$row->groupname ?></td>
			<td>
				<span class="label label-<?=( $row->active ? 'success' : 'important' ) ?>">
					<?=t( 'intouch.admin.list.status.' . ( $row->active ? 'success' : 'error' ) ) ?>
				</span>
			</td>
			<td class="span4">
				<a href="addonmodules.php?module=intouch&action=groups&task=edit&gid=<?=$row->id ?>" class="btn btn-primary btn-mini span1"><?=t( 'intouch.form.edit' ) ?></a>
				<a class="btn btn-danger btn-mini span1" href="#deleteGroup<?=$row->id ?>" data-toggle="modal"><?=t( 'intouch.form.delete' ) ?></a>
			</td>
		</tr>
				
		<?php endforeach; ?>
		
	</tbody>
</table>