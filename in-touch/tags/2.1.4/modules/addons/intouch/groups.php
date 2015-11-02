<?php defined('DUNAMIS') OR exit('No direct script access allowed');



class IntouchGroupsDunModule extends IntouchAdminDunModule
{
	
	/**
	 * Initialise the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 * @see			IntouchAdminDunModule::initialise()
	 */
	public function initialise()
	{
		$this->action = 'groups';
		parent :: initialise();
	}
	
	/**
	 * Method to execute tasks
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function execute()
	{
		$db		=	dunloader( 'database', true );
		$input	=	dunloader( 'input', true );
		$form	=	dunloader( 'form', true );
		
		if (! dunloader( 'license', 'intouch' )->isValid() ) {
			$this->setAlert( 'alert.license.invalid', 'block' );
			return;
		}
		
		switch ( $this->task ):
		case 'delete' :
			
			if ( ( $gid = $input->getVar( 'gid', false ) ) === false ) return;
			
			$gid	= $db->Quote( $gid );
				
			$db->setQuery( "DELETE FROM `mod_intouch_groups` WHERE `id` = " . $gid );
			$db->query();
				
			$this->setAlert( 'alert.group.deleted' );
			
			break;
			
		case 'save' :
			
			if ( ( $gid = $input->getVar( 'gid', false ) ) === false ) return;
			
			$fields		= $form->loadForm( 'group', 'intouch' );
			$paramnames	=
			$params		= array();
			
			foreach ( $fields as $name => $junk ) {
				$check = array( 'name', 'group', 'active', 'gid', 'params', 'paramsoptn1', 'paramsoptn2', 'paramsoptn3', 'paramsoptn4', 'paramsoptn1c', 'paramsoptn2c', 'paramsoptn3c', 'paramsoptn4c' );
				if ( in_array( $name, $check ) ) continue;
				$paramnames[]	= $name;
			}
				
			foreach ( $paramnames as $name ) {
				$params[$name]	= $input->getVar( $name, null, 'post', 'html' );
				
				if ( in_array( $name, array( 'emailheader', 'emailfooter', 'emailsig', 'emaillegal', 'invoicelegalfooter', 'quotelegalfooter' ) ) ) {
					$params[$name]	= htmlentities( $params[$name] );
				}
			}
			
			$params['invoiceadd']	= str_replace(array("\r\n", "\r", "\n"), '||', $params['invoiceadd'] );
			$params['quoteadd']		= str_replace(array("\r\n", "\r", "\n"), '||', $params['quoteadd'] );
			
			$name			= $db->Quote( $input->getVar( 'name', null, 'post', 'string' ) );
			$group			= $db->Quote( implode( '|', $input->getVar( 'group', 0, 'post', 'array'  ) ) );
			$active			= $db->Quote( $input->getVar( 'active', 0, 'post', 'int' ) );
			$paramstring	= $db->Quote( json_encode( $params ) );
			
			if ( ( $gid = $input->getVar( 'gid', '0' ) ) == '0' ) {
				$query	= "INSERT INTO `mod_intouch_groups` ( `name`, `group`, `active`, `params` ) VALUES ( {$name}, {$group}, {$active}, {$paramstring} )";
			}
			else {
				$query	= "UPDATE `mod_intouch_groups` SET `name` = {$name}, `group` = {$group}, `active` = {$active}, `params` = {$paramstring} WHERE `id` = {$gid}";
			}
			
			$db->setQuery( $query );
			$db->query();
			
			$this->setAlert( array( 'alert.group.saved', $input->getVar( 'name' ) ) );
			
			break;
		endswitch;
		
	}
	
	
	/**
	 * Method to render back the view
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing formatted output
	 * @since		2.0.0
	 */
	public function render()
	{
		$data	= $this->buildBody();
		
		return parent :: render( $data );
	}
	
	
	/**
	 * Builds the body of the action
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing html formatted output
	 * @since		2.0.0
	 */
	public function buildBody()
	{
		$db			=	dunloader( 'database', true );
		$form		=	dunloader( 'form', true );
		$input		=	dunloader( 'input', true );
		
		switch( $this->task )
		{
			// Add New
			// Edit
			case 'addnew' :
			case 'edit' :
				
				$group	= array();
				
				if ( $this->task == 'edit' ) {
					$gid	= $input->getVar( 'gid' );
					$db->setQuery( "SELECT `id` as 'gid',  `name` , `group` , `active` , `params` FROM `mod_intouch_groups` WHERE `id` = " . $db->Quote( $gid ) );
					$group	= $db->loadAssoc();
					$params	= json_decode( $group['params'], true );
					foreach( $params as $k => $v ) {
						// Check for HTML entities
						if ( in_array( $k, array( 'emailheader', 'emailfooter', 'emailsig', 'emaillegal', 'invoicelegalfooter', 'quotelegalfooter' ) ) ) {
							$group[$k]	= html_entity_decode( html_entity_decode( $v ) );
							continue;
						}
						
						if ( in_array( $k, array( 'invoiceadd', 'quoteadd' ) ) ) {
							$v = str_replace( '||', "\r\n", $v );
						}
						$group[$k] = $v;
					}
					
					unset( $group['params'] );
				}
				
				// Grab and set the exclusion array
				$db->setQuery( "SELECT `group` FROM `mod_intouch_groups` " . ( $this->task == 'edit' ? "WHERE `id` <> " . $db->Quote( $gid ) : '' ) );
				$excludes	= $db->loadResultArray();
				
				// Template overrides (2.1+)
				$form->setItem( 'template', $this->_getTemplates(), 'intouch.group', 'option' );
				
				// Permit disabling wysiwyg
				$config	= dunloader( 'config', 'intouch' );
				if (! $config->get( 'usewysiwyg' ) ) {
					foreach( array( 'emailheader', 'emailfooter', 'emailsig', 'emaillegal', 'invoicelegalfooter', 'quotelegalfooter') as $f ) $form->setItem( $f, false, 'intouch.group', 'enable' );
				}
				else {
					$form->setItem( 'invoicelegalfooter', false, 'intouch.group', 'enable' );
					$form->setItem( 'quotelegalfooter', false, 'intouch.group', 'enable' );
					$this->_handleHiddenWysiwyg();
					
					$doc	=	dunloader( 'document', true );
					$doc->addScriptDeclaration( <<< JS

JS
							);
				}
				
				$fields = $form->setValues( $group, 'intouch.group' );
				
				$uri	=	DunUri :: getInstance( 'SERVER', true );
				$uri->delVars();
				$uri->setVar( 'module', 'intouch' );
				$uri->setVar( 'action', 'groups' );
				
				$data	=	'<form action="addonmodules.php?module=intouch&action=groups&task=save" class="form-horizontal" method="post">'
						.		$this->renderForm( $fields )
						.		'<div class="form-actions">'
						.			$form->getButton( 'submit', array( 'class' => 'btn btn-primary span2', 'value' => t( 'intouch.form.submit' ), 'name' => 'submit' ) )
						.			$form->getButton( 'reset', array( 'class' => 'btn span2', 'value' => t( 'intouch.form.reset' ), 'style' => 'margin-left: 15px; ' ) )
						.			'<a href="' . $uri->toString() . '" class="btn pull-right span2">' . t( 'intouch.form.close' ) . '</a>'
						.		'</div>'
						.	'</form>';
				
				break;
			// Default task
			default:
			case 'default':
				
				$db->setQuery( "SELECT `id`, `groupname` FROM `tblclientgroups`");
				$groups	= $db->loadAssocList( 'id' );
				$groups[0]['groupname']	= 'No Group';
				
				$db->setQuery( "SELECT i.id, i.name, i.active, i.group FROM `mod_intouch_groups` i ORDER BY `name`" );
				$results = $db->loadObjectList();
				
				$data	.=	'<div class="pull-right">'
						.	'	<form action="addonmodules.php?module=intouch&action=groups" class="spanform form-inline" method="post">'
						.	'			<button type="submit" class="btn btn-success span3 pull-right">' . t( 'intouch.form.button.addnew' ) . '</button>'
						.	'			<input name="submit" value="1" type="hidden" /><input type="hidden" name="task" value="addnew" />'
						.	'	</form>'
						.	'</div>'
						.	'<div style="clear: both; "> </div>'
						.	'<table class="table table-bordered table-striped table-hover">'
						.	'	<thead>'
						.	'		<tr>'
						.	'			<th>ID</th>'
						.	'			<th>Name</th>'
						.	'			<th>Group</th>'
						.	'			<th>Active</th>'
						.	'			<th>Actions</th>'
						.	'		</tr>'
						.	'	</thead>'
						.	'	<tbody>';
					
				$modal	= null;

				foreach ( $results as $row ) {
					
					// Build our group name
					if ( strpos( $row->group, '|' ) !== false ) {
						$groupname	=	array();
						$grps		=	explode( '|', $row->group );
						foreach ( $grps as $grp ) {
							$groupname[] = $groups[$grp]['groupname'];
						}
						$groupname	=	implode( ', ', $groupname );
					}
					else {
						$groupname	= $groups[$row->group]['groupname'];
					}
					
					// Set Modal
					$this->setModal(	'deleteGroup' . $row->id,
										t( 'intouch.admin.group.modal.delete.title', $row->name ),
										t( 'intouch.admin.group.modal.delete.header' ),
										t( 'intouch.admin.group.modal.delete.body' ),
										'addonmodules.php?module=intouch&action=groups&task=delete&gid=' . $row->id,
										t( 'intouch.form.delete' )
							 );
					
					$status	=	( $row->active ? 'success' : 'error' );
					$label	=	( $row->active ? 'success' : 'important' );
					
					$axns	=	'<a href="addonmodules.php?module=intouch&action=groups&task=edit&gid=' . $row->id . '" class="btn btn-primary btn-mini span1">' . t( 'intouch.form.edit' ) . '</a>'
							.	'<a class="btn btn-danger btn-mini span1" href="#deleteGroup' . $row->id . '" data-toggle="modal">' . t( 'intouch.form.delete' ) . '</a>';
				
					$data	.=	'		<tr class="' . $status . '">'
							.	'			<td>' . $row->id . '</td>'
							.	'			<td>' . $row->name . '</td>'
							.	'			<td>' . $groupname . '</td>'
							.	'			<td>'
							.	'				<span class="label label-' . $label . '">' . t( 'intouch.admin.list.status.' . $status ) . '</span>'
							.	'			</td>'
							.	'			<td class="span4">' . $axns . '</td>'
							.	'		</tr>';
				}
					
				$data	.=	'	</tbody>'
						.	'</table>';
				
				$data	.=	'<div id="test" style="display: none;">Hi Im a test</div>';
				$data	.= '<script>$("#testme").click( function() { $("#myModal").modal(\'show\'); });</script>';
				
				$js = <<< JS
$( '#myModal' ).modal({show: false,backdrop: true });
JS;
				
				$doc = dunloader( 'document', true );
				
				//$doc->addScriptDeclaration( $js );
				
			break;
			// End Task Switch;
		}
		
		return $data;
	}
	
	
	/**
	 * Method to gather the templates from the templates folder for selection
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		array of objects
	 * @since		2.1.0		
	 */
	private function _getTemplates()
	{
		$dh		=	opendir( DUN_ENV_PATH . 'templates' );
		$tmpl	=	
		$data	=	array();
		
		while ( ( $file = readdir( $dh ) ) !== false ) {
			if ( in_array( $file, array( '.', '..', 'index.html', 'orderforms', 'index.php' ) ) ) continue;
			$tmpl[]	= $file;
		}
		
		sort( $tmpl );
		
		$data[]	= (object) array( 'id' => '0', 'name' => t( 'intouch.admin.form.group.option.template' ) );
		
		foreach ( $tmpl as $t ) {
			$data[]	= (object) array( 'id' => $t, 'name' => ucfirst( $t ) );
		}
		
		return $data;
	}
	
	
	/**
	 * Method to handle hidden WYSIWYG editors on other tabs
	 * @access		private
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	private function _handleHiddenWysiwyg()
	{
		$doc	= dunloader( 'document', true );
		
		if ( version_compare( DUN_ENV_VERSION, '5.2', 'ge' ) ) {
			$js	= <<< JS
jQuery('#params').next().next().bind( 'click', function() {
	tinyMCE.init({
		mode : "exact",
		elements : "invoicelegalfooter",
		theme : "advanced",
		entity_encoding: "raw",
		convert_urls : false,
		relative_urls : false,
		plugins : "style,table,advlink,inlinepopups,media,searchreplace,contextmenu,paste,directionality,visualchars,xhtmlxtras",
		theme_advanced_buttons1 : "cut,copy,paste,pastetext,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,search,replace",
		theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,media,|,ltr,rtl,cleanup,code,help",
		theme_advanced_buttons3 : "", // tablecontrols
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true
	});
});

jQuery('#params').next().next().next().bind( 'click', function() {
	tinyMCE.init({
		mode : "exact",
		elements : "quotelegalfooter",
		theme : "advanced",
		entity_encoding: "raw",
		convert_urls : false,
		relative_urls : false,
		plugins : "style,table,advlink,inlinepopups,media,searchreplace,contextmenu,paste,directionality,visualchars,xhtmlxtras",
		theme_advanced_buttons1 : "cut,copy,paste,pastetext,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,search,replace",
		theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,media,|,ltr,rtl,cleanup,code,help",
		theme_advanced_buttons3 : "", // tablecontrols
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true
	});
});
JS;
		}
		else if ( version_compare( DUN_ENV_VERSION, '5.1', 'ge' ) ) {
			$js	= <<< JS
jQuery('#params').next().next().bind( 'click', function() {
	nicEd.panelInstance('invoicelegalfooter');
});
jQuery('#params').next().next().next().bind( 'click', function() {
	nicEd.panelInstance('quotelegalfooter');
});
JS;
		}
		else {
			$js	= <<< JS
jQuery('#params').next().next().bind( 'click', function() {
	tinyMCE.init({
		mode : "exact",
		elements : "invoicelegalfooter",
		theme : "advanced",
		entity_encoding: "raw",
		convert_urls : false,
		relative_urls : false,
		plugins : "style,table,advlink,inlinepopups,media,searchreplace,contextmenu,paste,directionality,visualchars,xhtmlxtras",
		theme_advanced_buttons1 : "cut,copy,paste,pastetext,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,search,replace",
		theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,media,|,ltr,rtl,cleanup,code,help",
		theme_advanced_buttons3 : "", // tablecontrols
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true
	});
});

jQuery('#params').next().next().next().bind( 'click', function() {
	tinyMCE.init({
		mode : "exact",
		elements : "quotelegalfooter",
		theme : "advanced",
		entity_encoding: "raw",
		convert_urls : false,
		relative_urls : false,
		plugins : "style,table,advlink,inlinepopups,media,searchreplace,contextmenu,paste,directionality,visualchars,xhtmlxtras",
		theme_advanced_buttons1 : "cut,copy,paste,pastetext,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,search,replace",
		theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,media,|,ltr,rtl,cleanup,code,help",
		theme_advanced_buttons3 : "", // tablecontrols
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true
	});
});
JS;
		}
		
		$doc->addScriptDeclaration( $js );
	}
}