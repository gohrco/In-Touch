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
				
				if ( $name == 'emailcss' ) {
					$params[$name] = htmlentities( $params[$name] );
				}
				
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
	public function render( $data = null )
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
						
						if ( $k == 'emailcss' ) {
							$v = htmlspecialchars_decode( html_entity_decode( $v ), ENT_QUOTES );
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
				}
				
				$this->_handleHiddenWysiwyg();
				
				$views	=	dunloader( 'views', 'intouch' );
				$views->setData( array( 'fields' => $form->setValues( $group, 'intouch.group' ) ) );
				
				return $views->render( 'groups.edit' );
				
				break;
			// Default task
			default:
			case 'default':
				
				// Select all client groups
				$db->setQuery( "SELECT `id`, `groupname` FROM `tblclientgroups`");
				$groups	= $db->loadAssocList( 'id' );
				$groups[0]['groupname']	= 'No Group';
				
				// Select all InTouch groups
				$db->setQuery( "SELECT i.id, i.name, i.active, i.group FROM `mod_intouch_groups` i ORDER BY `name`" );
				$results = $db->loadObjectList();
				
				foreach ( $results as &$row ) {
					
					// Build our group name
					if ( strpos( $row->group, '|' ) !== false ) {
						$groupname	=	array();
						$grps		=	explode( '|', $row->group );
						foreach ( $grps as $grp ) {
							$groupname[] = $groups[$grp]['groupname'];
						}
						$groups[$row->group]['groupname']	=	implode( ', ', $groupname );
					}
					else {
						$groupname	= $groups[$row->group]['groupname'];
					}
					
					$row->groupname	=	$groupname;
					
					// Set Modal
					$this->setModal(	'deleteGroup' . $row->id,
										t( 'intouch.admin.group.modal.delete.title', $row->name ),
										t( 'intouch.admin.group.modal.delete.header' ),
										t( 'intouch.admin.group.modal.delete.body' ),
										'addonmodules.php?module=intouch&action=groups&task=delete&gid=' . $row->id,
										t( 'intouch.form.delete' )
							 );
				}
					
				
				$views	=	dunloader( 'views', 'intouch' );
				$views->setData( array( 'rows' => $results ) );
				
				return $views->render( 'groups.default' );
				
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
jQuery("document").ready( function() {
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
