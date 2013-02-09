<?php defined('DUNAMIS') OR exit('No direct script access allowed');



class IntouchDefaultDunModule extends IntouchAdminDunModule
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
		$this->action = 'default';
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
		$doc	= dunloader( 'document', true );
		$doc->addStyleDeclaration( "#intouch .row .well-small h3 {margin: 0; padding: 0; }" );
		$doc->addStyleDeclaration( "#intouch .icon-hang {margin-left: -20px; }" );
		
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
		$widgets	=	$this->_getWidgets();
		$data		=	'<div class="row">'
					.	'	<div class="well span8">'
					.	'		' . t( 'intouch.admin.default.body' )
					.	'	</div>'
					.	'	<div class="span4">'
					.	'		' . $widgets
					.	'	</div>'
					.	'</div>';
		return $data;
	}
	
	
	/**
	 * Method for checking the configuration status
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		object
	 * @since		2.0.0
	 */
	private function _getConfigstatus()
	{
		$data	= new stdClass();
		$data->header = t ( 'intouch.admin.widget.header.status' );
		$data->status	= '-success';
		$data->body		= t( 'intouch.admin.widget.body.status.good' );
		
		// Lets check licensing first
		$license	= dunloader( 'license', 'intouch' );
		if (! $license->isValid() ) {
			$data->status	=	'-danger';
			$data->body		=	t( 'intouch.admin.widget.body.status.license' );
			return $data;
		}
		
		// Now lets see if we have the product enabled
		$config	= dunloader( 'config', 'intouch' );
		if ( $config->get( 'enable' ) != '1' ) {
			$data->status	=	'-danger';
			$data->body		=	t( 'intouch.admin.widget.body.status.enable' );
			return $data;
		}
		
		// Lets check for groups
		$db	= dunloader( 'database', true );
		$db->setQuery( "SELECT * FROM `mod_intouch_groups`" );
		if ( $db->loadResult() == null ) {
			$data->status	=	'';
			$data->body		=	t( 'intouch.admin.widget.body.status.nogroups' );
			return $data;
		}
		
		// Lets check for active groups
		$db->setQuery( "SELECT * FROM `mod_intouch_groups` WHERE `active` = '1'" );
		if ( $db->loadResult() == null ) {
			$data->status	=	'';
			$data->body		=	t( 'intouch.admin.widget.body.status.noactivegrps' );
			return $data;
		}
		
		// Lets check for duplicate active groups
		$db->setQuery( "SELECT count( * ) as 'count' FROM `mod_intouch_groups` WHERE `active` = '1' GROUP BY `group`" );
		$result	= $db->loadObjectList();
		foreach ( $result as $res ) {
			if ( $res->count > 1 ) {
				$data->status	=	'';
				$data->body		=	t( 'intouch.admin.widget.body.status.dupgroups' );
				return $data;
			} 
		}
		
		return $data;
	}
	
	
	/**
	 * Method for getting the widgets for the dashboard
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $widget: contains the widget to retrieve
	 * 
	 * @return		html formatted string
	 * @since		2.0.0
	 */
	private function _getWidgets( $widget = 'all' )
	{
		$widgets	=	array( 'status', 'updates', 'license', 'likeus' );
		$data		=	null;
		
		if ( $widget == 'all' ) {
			foreach ( $widgets as $widget ) {
				$data	.= $this->_getWidgets( $widget );
			}
			return $data;
		}
		
		$result	= (object) array( 'status' => null, 'header' => null, 'body' => null );
		$result->header = t ( 'intouch.admin.widget.header.' . $widget );
		
		switch ( $widget ) {
			case 'updates' :
				$updates	=	dunloader( 'updates', 'intouch' );
				$version	=	$updates->updatesExist();
				$error		=	$updates->hasError();
				
				if ( $version ) {
					$result->status = '';
					$result->body	= t( 'intouch.admin.widget.body.updates.exist', $version );
				}
				else if ( $error ) {
					$result->status = '-danger';
					$result->body	= t( 'intouch.admin.widget.body.updates.error', $error );
				}
				else {
					$result->status = '-success';
					$result->body	= t( 'intouch.admin.widget.body.updates.none' );
				}
				
				break;
			case 'status' :
				$result	= $this->_getConfigstatus();
				break;
			case 'license':
				$license	= dunloader( 'license', 'intouch' );
				$isvalid	= $license->isValid();
				
				if ( $isvalid ) {
					if ( $license->isCurrent() ) {
						$result->status = '-success';
						$result->body	=	t( 'intouch.admin.widget.body.license.success' );
					}
					else {
						$result->status = '';
						$result->body	=	t( 'intouch.admin.widget.body.license.alert', $license->get( 'supnextdue' ) );
					}
				}
				else {
					$result->status	=	'-danger';
					$result->body	=	t( 'intouch.admin.widget.body.license.danger' );
				}
				break;
			case 'likeus' :
				$result->status	=	'-info';
				$result->body	=	t( 'intouch.admin.widget.body.likeus' );
				break;
		}
		
		$data	=	'<div class="well well-small alert' . $result->status . '">'
				.	'	<h3>' . $result->header . '</h3>'
				.	'	' . $result->body
				.	'</div>';
		
		return $data;
	}
}