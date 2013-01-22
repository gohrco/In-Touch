<?php defined('DUNAMIS') OR exit('No direct script access allowed');

if (! defined( 'DUN_MOD_INTOUCH' ) ) define( 'DUN_MOD_INTOUCH', "@fileVers@" );

class IntouchAdminDunModule extends WhmcsDunModule
{
	/**
	 * Stores what the action we are using
	 * @access		protected
	 * @var			string
	 * @since		2.0.0
	 */
	protected $action	= 'default';
	
	/**
	 * Stores the alerts to display back
	 * @access		protected
	 * @var			array
	 * @since		2.0.0
	 */
	protected $alerts	= array( 'error' => array(), 'success' => array(), 'info' => array(), 'block' => array() );
	
	/**
	 * Stores any modals to render
	 * @access		protected
	 * @var			array
	 * @since		2.0.0
	 */
	protected $modals	= array();
	
	/**
	 * Stores what the task is for this page
	 * @access		protected
	 * @var			string
	 * @since		2.0.0
	 */
	protected $task	= 'default';
	
	/**
	 * Stores the type of module this is
	 * @access		protected
	 * @var			string
	 * @since		2.0.0
	 */
	protected $type	= 'addon';
	
	
	/**
	 * Retrieves the configuration array for the product in the addon modules menu
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		array
	 * @since		2.0.0
	 */
	public function getAdminConfig()
	{
		$data = array(
				"name"			=> t( 'intouch.addon.title' ),
				"version"		=> "@fileVers@",
				"author"		=> t( 'intouch.addon.author' ),
				"description"	=> t( 'intouch.addon.description' ),
				"language"		=> "english",
				"fields"		=> array()
		);
		
		return $data;
	}
	
	
	/**
	 * Initializes the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function initialise()
	{
		static $instance = false;
		
		if (! $instance ) {
			dunloader( 'language', true )->loadLanguage( 'intouch' );
			dunloader( 'hooks', true )->attachHooks( 'intouch' );
			
			load_bootstrap( 'intouch' );
			$instance	= true;
		}
		
		$this->task = dunloader( 'input', true )->getVar( 'task', 'default' );
	}
	
	
	/**
	 * Method to render the response back to the user
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		- $data: contains already compiled data to use
	 * 
	 * @return		string containing html formatted data
	 * @since		2.0.0
	 */
	public function render( $data = null )
	{
		$title	= $this->buildTitle();
		$navbar	= $this->buildNavigation();
		$alerts	= $this->buildAlerts();
		$modals	= $this->buildModals();
		
		$baseurl = get_baseurl( 'intouch' );
		$doc = dunloader( 'document', true );
		
		$doc->addStyleDeclaration( '#contentarea > div > h1, #content > h1 { display: none; }' );	// Wipes out WHMCS' h1
		$doc->addStyleDeclaration( '.contentarea > h1 { display: none; }' );	// Wipes out WHMCS' h1 in 5.0.3
		
		return 		'<div style="float:left;width:100%;">'
				.	'<div id="intouch">'
				.	'	' . $title
				.	'	' . $navbar
				.	'	' . $alerts
				.	'	' . $data
				.	'	' . $modals
				.	'</div>'
				.	'</div>';
	}
	
	
	/**
	 * Renders the output for the admin area of the site (WHMCS > Addons > Module name)
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing formatted output
	 * @since		2.0.0
	 */
	public function renderAdminOutput()
	{
		$action	= dunloader( 'input', true )->getVar( 'action', 'default' );
		
		$controller = dunmodule( 'intouch.' . $action );
		$controller->execute();
		
		return $controller->render();
	}
	
	
	/**
	 * Builds the alerts for display
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing html formatted output
	 * @since		2.0.0
	 */
	public function buildAlerts()
	{
		$data	= null;
		$check	= array( 'success', 'error', 'block', 'info' );
		
		foreach ( $check as $type ) {
			if ( empty( $this->alerts[$type] ) ) continue;
			$data	.=	'<div class="alert alert-' . $type . '"><h4>' . t( 'intouch.alert.' . $type ) . '</h4>'
					.	implode( "<br/>", $this->alerts[$type] )
					.	'</div>';
		}
		
		return $data;
	}
	
	
	public function buildModals()
	{
		if ( empty( $this->modals ) ) return null;
		
		$data	= null;
		foreach ( $this->modals as $modal ) {
			$id	= $modal['id'];
			$btns	=	implode("\n", $modal['buttons'] );
			$data	.=	'<div aria-hidden="true" aria-labelledby="' . $id . 'Label" role="dialog" tabindex="-1" class="modal" id="' . $id . '" style="display: none; ">'
					.	'	<div class="modal-header">'
					.	'		<button aria-hidden="true" data-dismiss="modal" class="close" type="button">x</button>'
					.	'		<h3 id=' . $id . 'Label">' . $modal['hdr'] .'</h3>'
					.	'	</div>'
					.	'	<div class="modal-body">'
					.	'		<h4>' . $modal['title'] . '</h4>'
					.	'		' . $modal['body']
					.	'	</div>'
					.	'	<div class="modal-footer">'
					.	'		' . $btns
					.	'	</div>'
					.	'</div>';
		}
		
		return $data;
	}
	
	
	/**
	 * Builds the navigation menu
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing html formatted output
	 * @since		2.0.0
	 */
	public function buildNavigation()
	{
		$uri	=	DunUri :: getInstance( 'SERVER', true );
		$uri->delVars();
		$uri->setVar( 'module', 'intouch' );
		
		$data		=	'<ul class="nav nav-pills">';
		$actions	=	array( 'default', 'groups', 'configure', 'license' );
		
		foreach( $actions as $item ) {
			if ( $item == $this->action && in_array( $this->task, array( 'default', 'save' ) ) ) {
				$data .= '<li class="active"><a href="#">' . t( 'intouch.admin.navbar.' . $item ) . '</a></li>';
			}
			else {
				$uri->setVar( 'action', $item );
				$data .= '<li><a href="' . $uri->toString() . '">' . t( 'intouch.admin.navbar.' . $item ) . '</a></li>';
			}
		}
		
		$data	.= '</ul>';
		return $data;
	}
	
	
	/**
	 * Builds the title of the page
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing html formatted output
	 * @since		2.0.0
	 */
	public function buildTitle()
	{
		$data	= '<h1>' . t( 'intouch.admin.title', t( 'intouch.admin.title.' . $this->action . '.' . $this->task ) ) . '</h1>';
		return $data;
	}
	
	
	/**
	 * Renders the sidebar for the admin area
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string
	 * @since		2.0.0
	 */
	public function renderAdminSidebar()
	{
		return;
	}
	
	
	/**
	 * Common method for rendering fields into a form
	 * @access		protected
	 * @version		@fileVers@
	 * @param		array		- $fields: contains an array of Field objects
	 *
	 * @return		string
	 * @since		2.0.0
	 */
	protected function renderForm( $fields = array(), $options = array() )
	{
		$data	= null;
		$foptn	= ( array_key_exists( 'fields', $options ) ? $options['fields'] : array() );
			
		foreach ( $fields as $field ) {
	
			if ( in_array( $field->get( 'type' ), array( 'wrapo', 'wrapc' ) ) ) {
				$data .= $field->field();
				continue;
			}
	
			$data	.= <<< HTML
<div class="control-group">
	{$field->label( array( 'class' => 'control-label' ) )}
	<div class="controls">
		{$field->field( $foptn )}
		{$field->description( array( 'type' => 'span', 'class' => 'help-block help-inline' ) )}
	</div>
</div>
HTML;
		}
	
		return $data;
	}
	
	
	protected function setAlert( $msg = array(), $type = 'success', $trans = true )
	{
		// If we are passing an array we are assuming:
		//		first item is string
		//		rest of items are variables to insert
		if ( is_array( $msg ) ) {
			$message = array_shift( $msg );
			$message = 'intouch.'.$message;
			array_unshift( $msg, $message );
			$this->alerts[$type][] = call_user_func_array('t', $msg );
			return;
		}
		
		if ( $trans ) {
			$msg = t( 'intouch.' . $msg );
		}
		
		$this->alerts[$type][] = $msg;
	}
	
	
	protected function setModal( $id, $title, $header, $body, $href, $btnlbl, $type = 'danger' )
	{
		$btns	= array(	'<button data-dismiss="modal" class="btn">' . t( 'intouch.form.close' ). '</button>',
							'<a href="' . $href . '" class="btn btn-' . $type . '">' . $btnlbl . '</a>'
				);
		$this->modals[]	= array(	'id'		=> $id,
									'title'		=> $title,
									'hdr'		=> $header,
									'body'		=> $body,
									'buttons'	=> $btns
				);
	}
	
	
	/**
	 * Gathers the configuration
	 * @access		private
	 * @version		@fileVers@
	 * @param		bool		- $asarray: indicates we want it back as an array
	 * 
	 * @return		array|object of values
	 * @since		2.0.0
	 */
	private function _gatherConfig( $asarray = false )
	{
		$db	= dunloader( 'database', true );
		
		$db->setQuery( "SELECT * FROM mod_themer_settings" );
		$results	= $db->loadObjectList();
		$values		= array();
		
		if ( $asarray ) {
			foreach ( $results as $result ) $values[$result->key] = $result->value;
			return $values;
		}
		
		$values		= (object) $values;
		
		// Set the values up
		foreach ( $results as $result ) {
			$item = $result->key;
			$values->$item = $result->value;//$values->$result->key = $result->value;
		}
		
		return $values;
	}
	
	
	/**
	 * Gathers the body for an admin page
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		string
	 * @since		2.0.0
	 */
	private function _getBody()
	{
		global $action, $whmcs;
		
		$doc	=	dunloader( 'document', true );
		$db		=	dunloader( 'database', true );
		$form	=	dunloader( 'form', true );
		$data	=   null;
		
		switch ( $action ) {
			
			case 'themes' :
				
				$task	= ( array_key_exists( 'task', $whmcs->input ) ? $whmcs->input['task'] : null );
				
				switch ( $task ) {
					
					// Edit a specific theme
					case 'edittheme':
						
						$tid	= $whmcs->input['tid'];
						
						// Pull all the themes from the database
						$db->setQuery( "SELECT * FROM `mod_themer_themes` WHERE `id` = '{$tid}'" );
						$theme	= $db->loadObject();
						$params	= json_decode( $theme->params, true );
						foreach ( $params as $k => $v ) $theme->$k = $v;
						
						$fields	= $form->setValues( $theme, 'themer.theme' );
						$render	= $this->_renderForm( $fields, array() );
						
						$data	.=	'<form action="addonmodules.php?module=themer&task=edittheme&action=themes" class="form-horizontal" method="post">'
								.	$render
								.	'<div class="form-actions"> '
								.	$form->getButton( 'submit', array( 'class' => 'btn btn-primary btn-large span2', 'value' => t( 'themer.form.apply' ), 'name' => 'save' ) ) . ' '
								.	$form->getButton( 'submit', array( 'class' => 'btn btn-large btn-inverse span2', 'value' => t( 'themer.form.saveandclose' ), 'name' => 'saveandclose' ) ) . ' '
								.	'<a href="addonmodules.php?module=themer&action=themes" class="btn btn-large span2 pull-right"> ' . t( 'themer.form.close' ) . '</a>'
								.	'</div>'
								.	'<input type="hidden" name="tid" value="' . $theme->id . '" />'
								.	'<input type="hidden" name="submit" value="1" />'
								.	'</form>';
						
						break;
					// The default action to take
					default:
						
						// Get the current theme being used on the front end
						$db->setQuery( "SELECT `value` FROM `mod_themer_settings` WHERE `key` = 'usetheme'" );
						$tid	= $db->loadResult();
						
						// Build the Add New Theme form
						$data	.=	'<div class="pull-right">'
								.	'	<form action="addonmodules.php?module=themer&action=themes" class="form-inline" method="post">'
								.	'		<input name="name" type="text" placeholder="' . t( 'themer.admin.themer.form.theme.placeholder.name' ) . '"> '
								.	'			<button type="submit" class="btn btn-success">' . t( 'themer.admin.themer.form.theme.button.addnew' ) . '</button>'
								.	'			<input name="submit" value="1" type="hidden" /><input type="hidden" name="task" value="addnew" />'
								.	'	</form>'
								.	'</div>'
								.	'<div style="clear: both; "> </div>';
						
						// Pull all the themes from the database
						$db->setQuery( "SELECT * FROM `mod_themer_themes` ORDER BY `name`" );
						$items	= $db->loadObjectList();
						
						// Cycle through the themes
						foreach ( $items as $item ) {
							
							$data	.=	'<div class="row-fluid well well-small">'
									.	'	<div class="span12">'
									.	( $item->id != '1'
									?	'		<h3><a href="addonmodules.php?module=themer&action=themes&task=edittheme&tid=' . $item->id . '">' . $item->name . '</a></h3>'
									:	'		<h3>' . $item->name . '</h3>' )
									.	'	</div>'
									.	'	<div class="span12">'
									.	'		<p>' . $item->description . '</p>'
									.	'	</div>'
									.	'	<div class="span12">'
									.	'		<a href="addonmodules.php?module=themer&action=themes&submit=1&task=makedefault&tid=' . $item->id . '" class="btn btn-inverse span2 '
									.	( $item->id == $tid
									?	'disabled">' . t( 'themer.admin.themer.form.theme.isselected.theme' )
									:	'">' . t ( 'themer.admin.themer.form.theme.button.makedefault' ) )
									. '</a> '
									.	'		<a href="addonmodules.php?module=themer&action=themes&submit=1&task=copytheme&tid=' . $item->id . '" class="btn btn-inverse span2">' . t( 'themer.admin.themer.form.theme.button.copytheme' ) . '</a>'
									.	( $item->id != '1' 
									?	'<a href="addonmodules.php?module=themer&action=themes&task=edittheme&tid=' . $item->id . '" class="btn btn-primary span1">' . t( 'themer.form.edit' ) . '</a>'
									:	'' )
									.	(! in_array( $item->id, array( '1', $tid ) )
									?	'<a href="addonmodules.php?module=themer&action=themes&submit=1&task=delete&tid=' . $item->id . '" class="btn btn-danger span1">' . t( 'themer.form.delete' ) . '</a>'
									: '' )
									.	'	</div>'
									.	'</div>';
						}	// End Task Switch
				} // End Theme Action Switch
				
				break;
				
			case 'config' :
				
				$db->setQuery( "SELECT * FROM mod_themer_settings" );
				$results	= $db->loadObjectList();
				$values		= array();
				
				// Set the values up
				foreach ( $results as $result ) $values[$result->key] = $result->value;
				$fields = $form->setValues( $values, 'themer.config' );
				
				$data	=	'<form action="addonmodules.php?module=themer&action=config" class="form-horizontal" method="post">'
						.		$this->_renderForm( $fields )
						.		'<div class="form-actions">'
						.			$form->getButton( 'submit', array( 'class' => 'btn btn-primary', 'value' => t( 'themer.form.submit' ), 'name' => 'submit' ) )
						.			$form->getButton( 'reset', array( 'class' => 'btn', 'value' => t( 'themer.form.cancel' ) ) )
						.		'</div>'
						.	'</form>';
				break;
				
			case 'license' :
				
				$lic	= dunloader( 'license', 'themer' );
				$parts	= $lic->getItems();
				
				// Set license
				$config	= dunloader( 'config', 'themer' );
				$config->refresh();
				$form->setValue( 'license', $config->get( 'license' ), 'themer.license' );
				
				// Set status
				if (! array_key_exists( 'supnextdue', $parts ) ) {
					$state = 'important';
				}
				else {
					$state	= ( strtotime( $parts['supnextdue'] ) >= strtotime( date("Ymd") ) ? 'success' : ( $parts['status'] == 'Invalid' ? 'important' : 'warning' ) );
				}
				
				$sttxt	= ( $state == 'success' ? 'Active' : ( $state == 'important' ? 'Invalid License' : 'Expired' ) );
				$form->setValue( 'status', '<span class="label label-' . $state . '"> ' . $sttxt . ' </span>', 'themer.license' );
				
				// Set Branding
				$form->setValue( 'branding', t( 'themer.admin.themer.form.config.branding.' . ( $lic->isBranded() ? 'branded' : 'nobrand' ) ), 'themer.license' );
				
				// Set information
				$info	= array();
				if ( $state != 'important' ) {
					$use	= array( 'registeredname', 'companyname', 'regdate', 'supnextdue' );
					foreach ( $use as $i ) {
						
						// Check to see if we have the item
						if (! array_key_exists( $i, $parts ) ) continue;
						$info[]	= ( $i != 'supnextdue' ? t( 'themer.admin.themer.form.config.info.' . $i, $parts[$i] ) : t( 'themer.admin.themer.form.config.info.supnextdue', $state, $parts[$i] ) );
					}
				}
				else {
					if (! isset( $parts['message'] ) ) {
						$info[]	= t( 'themer.admin.themer.form.config.info.invalidkey' );
					}
					else {
						$info[]	= t( 'themer.admin.themer.form.config.info.invalidmsg', $parts['message'] );
					}
				}
				
				$form->setValue( 'info', $info, 'themer.license' );
				
				// Grab the fields
				$fields = $form->loadForm( 'license', 'themer' );
				
				$data	=	'<form action="addonmodules.php?module=themer&action=license" class="form-horizontal" method="post">'
						.		$this->_renderForm( $fields )
						.		'<div class="form-actions">'
						.			$form->getButton( 'submit', array( 'class' => 'btn btn-primary', 'value' => t( 'themer.form.submit' ), 'name' => 'submit' ) )
						.			$form->getButton( 'reset', array( 'class' => 'btn', 'value' => t( 'themer.form.cancel' ) ) )
						.		'</div>'
						.	'</form>';
				break;
		}
		
		return $data;
	}
	
	
	/**
	 * Method to generate the navigation bar at the top
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		string
	 * @since		2.0.0
	 */
	private function _getNavigation()
	{
		global $action, $whmcs;
		
		$uri	= DunUri :: getInstance('SERVER', true );
		$uri->delVar( 'task' );
		$uri->delVar( 'submit' );
		
		$html	= '<ul class="nav nav-pills">';
		
		foreach( array( 'themes', 'config', 'license' ) as $item ) {
			
			if ( $item == $action ) {
				if ( array_key_exists( 'task', $whmcs->input ) ) {
					if ( $whmcs->input['task'] != 'edittheme' ) {
						$html .= '<li class="active"><a href="#">' . t( 'themer.admin.module.navbar.' . $item ) . '</a></li>';
						continue;
					}
				}
				else {
					$html .= '<li class="active"><a href="#">' . t( 'themer.admin.module.navbar.' . $item ) . '</a></li>';
					continue;
				}
			}
			
			$uri->setVar( 'action', $item );
			$html .= '<li><a href="' . $uri->toString() . '">' . t( 'themer.admin.module.navbar.' . $item ) . '</a></li>';
		}
		
		
		$html	.= '</ul>';
		return $html;
	}
	
	
	/**
	 * Method to generate the title at the top of the page
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		string
	 * @since		2.0.0
	 */
	private function _getTitle()
	{
		global $action, $whmcs;
		
		$task = ( array_key_exists( 'task', $whmcs->input ) && ! empty( $whmcs->input['task'] ) ? '.' . $whmcs->input['task'] : null );
		
		return '<h1>' . t( 'themer.admin.module.title', t( 'themer.admin.module.title.' . $action . $task ) ) . '</h1>';
	}
	
	
	/**
	 * Common method for rendering fields into a form
	 * @access		private
	 * @version		@fileVers@
	 * @param		array		- $fields: contains an array of Field objects
	 * 
	 * @return		string
	 * @since		2.0.0
	 */
	private function _renderForm( $fields = array(), $options = array() )
	{
		$data	= null;
		$foptn	= ( array_key_exists( 'fields', $options ) ? $options['fields'] : array() );
		 
		foreach ( $fields as $field ) {	// Fields of Themes cycle
		
			if ( in_array( $field->get( 'type' ), array( 'wrapo', 'wrapc' ) ) ) {
				$data .= $field->field();
				continue;
			}
		
			$data	.= <<< HTML
<div class="control-group">
	{$field->label( array( 'class' => 'control-label' ) )}
	<div class="controls">
		{$field->field( $foptn )}
		{$field->description( array( 'type' => 'span', 'class' => 'help-block help-inline' ) )}
	</div>
</div>
HTML;
		}
		
		return $data;
	}
	
	
	/**
	 * Method to save the form when saved
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	private function _saveForm()
	{
		global $action, $whmcs;
		
		$db		= dunloader( 'database', true );
		$input	=   $whmcs->input;
		
		switch ( $action ) {
			// Save the theme settings
			case 'themes' :
				
				// Check license and task
				if (! dunloader( 'license', 'themer' )->isValid() ) return;
				if (! array_key_exists( 'task', $input ) ) return;
				
				if ( array_key_exists( 'tid', $input ) ) $tid = $input['tid'];
				
				switch( $input['task'] ) {
					case 'addnew' :
						$db->setQuery( "SELECT `params` FROM `mod_themer_themes` WHERE `id` = '1'" );
						$params	= $db->loadResult();
						
						$db->setQuery( "INSERT INTO `mod_themer_themes` (`name`, `params` ) VALUES ('" . $input['name'] . "', '" . $params . "' ); ");
						$db->query();
						break;
					case 'delete' :
						$db->setQuery( "DELETE FROM `mod_themer_themes` WHERE `id` = '" . $tid . "'" );
						$db->query();
						break;
					case 'makedefault' :
						$db->setQuery( "UPDATE `mod_themer_settings` SET `value` = '" . $tid . "' WHERE `key` = 'usetheme'" );
						$db->query();
						break;
					case 'copytheme' :
						$db->setQuery( "SELECT * FROM `mod_themer_themes` WHERE `id` = '" . $tid . "'" );
						$theme	= $db->loadObject();
						
						$db->setQuery( "INSERT INTO `mod_themer_themes` (`name`, `description`, `params` ) VALUES ('" . $theme->name . " (copy)', '" . $theme->description . "', '" . $theme->params . "' ); ");
						$db->query();
						break;
						
					case 'edittheme' :
						
						$params	= array('fullwidth' => null,'contentbg' => null, 'font' => null,'logo' => null,'bodytype' => null,'bodyoptnsolid'	=> null,'bodyoptnfrom' => null,'bodyoptnto' => null,'bodyoptndir' => null,'bodyoptnpattern' => null,'bodyoptnimage' => null,'alinks'	=> null,'alinksstd' => null,'alinksvis' => null,'alinkshov' => null,'navbarfrom' => null,'navbarto' => null,'navbartxt' => null,'navbarhov' => null,'navbardropbg' => null,'navbardroptxt' => null,'navbardrophl' => null,'txtelemgffont' => null,'txtelemgfsize' => null,'txtelemgfcolor' => null,'txtelemh1font' => null,'txtelemh1size' => null,'txtelemh1color' => null,'txtelemh2font' => null,'txtelemh2size' => null,'txtelemh2color' => null,'txtelemh3font' => null,'txtelemh3size' => null,'txtelemh3color' => null,'txtelemh4font' => null,'txtelemh4size' => null,'txtelemh4color' => null,'txtelemh5font' => null,'txtelemh5size' => null,'txtelemh5color' => null,'txtelemh6font' => null,'txtelemh6size' => null,'txtelemh6color' => null,);
						
						foreach( $input as $key => $value ) {
							if (! array_key_exists( $key, $params ) ) continue;
							$params[$key] = $value;
						}
						
						$paramstring	= json_encode( $params );
						
						$db->setQuery( "UPDATE `mod_themer_themes` SET `name` = '" . $input['name'] . "', `description` = '" . $input['description'] . "', `params` = '{$paramstring}' WHERE `id` = '{$tid}'" );
						$db->query();
						
						// Check for save and close
						if ( array_key_exists( 'saveandclose', $input ) ) {
							$whmcs->input['task'] = null;
						}
						
						break;
						
				}	// End Task Switch;
				
				break;
				
			// Save our configuration settings
			case 'config' :
				
				// Check license
				if (! dunloader( 'license', 'themer' )->isValid() ) return;
				
				if (! array_key_exists( 'restrictuser', $input ) ) $input['restrictuser'] = array();
				
				$config	= array( 'enable', 'restrictip', 'restrictuser', 'fontselect' );
				
				foreach ( $config as $item ) {
					$key = $item; $value = $input[$item];
					if ( is_array( $value ) ) $value = implode( '|', $value );
					$db->setQuery( "UPDATE `mod_themer_settings` SET `value` = " . $db->Quote( $value ) . " WHERE `key` = '{$key}'" );
					$db->query();
				}
				break;
			case 'license' :
				$save = array( 'license' => $input['license'], 'localkey' => null );
				
				foreach ( $save as $key => $value ) {
					$db->setQuery( "UPDATE `mod_themer_settings` SET `value` = '{$value}' WHERE `key` = '{$key}'" );
					$db->query();
				}
				break;
		}
	}
}