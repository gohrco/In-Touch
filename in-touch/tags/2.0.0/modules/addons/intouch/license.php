<?php defined('DUNAMIS') OR exit('No direct script access allowed');



class IntouchLicenseDunModule extends IntouchAdminDunModule
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
		$this->action = 'license';
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
		
		switch ( $this->task ):
		case 'save' :
			
			// Catch missing license
			if (! ( $license = $input->getVar( 'license', false ) ) ) break;
			
			$save = array( 'license' => $license, 'localkey' => null );
			
			foreach ( $save as $key => $value ) {
				$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = '{$value}' WHERE `key` = '{$key}'" );
				$db->query();
			}
			
			$this->setAlert( 'alert.license.saved' );
			
			break;
		endswitch;
		
		// Check license
		if (! dunloader( 'license', 'intouch' )->isValid() ) {
			$this->setAlert( 'alert.license.invalid', 'block' );
		}
		
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
		$form	=	dunloader( 'form', true );
		$lic	=	dunloader( 'license', 'intouch' );
		$parts	=	$lic->getItems();
		
		// Set license
		$config	= dunloader( 'config', 'intouch' );
		$config->refresh();
		$form->setValue( 'license', $config->get( 'license' ), 'intouch.license' );
		
		// Set status
		if (! array_key_exists( 'supnextdue', $parts ) ) {
			$state = 'important';
		}
		else {
			$state	= ( strtotime( $parts['supnextdue'] ) >= strtotime( date("Ymd") ) ? 'success' : ( $parts['status'] == 'Invalid' ? 'important' : 'warning' ) );
		}
		
		$sttxt	= ( $state == 'success' ? 'Active' : ( $state == 'important' ? 'Invalid License' : 'Expired' ) );
		$form->setValue( 'status', '<span class="label label-' . $state . '"> ' . $sttxt . ' </span>', 'intouch.license' );
		
		// Set information
		$info	= array();
		if ( $state != 'important' ) {
			$use	= array( 'registeredname', 'companyname', 'regdate', 'supnextdue' );
			foreach ( $use as $i ) {
		
				// Check to see if we have the item
				if (! array_key_exists( $i, $parts ) ) continue;
				$info[]	= ( $i != 'supnextdue' ? t( 'intouch.admin.form.config.info.' . $i, $parts[$i] ) : t( 'intouch.admin.form.config.info.supnextdue', $state, $parts[$i] ) );
			}
		}
		else {
			if (! isset( $parts['message'] ) ) {
				$info[]	= t( 'intouch.admin.form.config.info.invalidkey' );
			}
			else {
				$info[]	= t( 'intouch.admin.form.config.info.invalidmsg', $parts['message'] );
			}
		}
		
		$form->setValue( 'info', $info, 'intouch.license' );
		
		// Grab the fields
		$fields = $form->loadForm( 'license', 'intouch' );
		
		$data	=	'<form action="addonmodules.php?module=intouch&action=license&task=save" class="form-horizontal" method="post">'
		.		$this->renderForm( $fields )
		.		'<div class="form-actions">'
		.			$form->getButton( 'submit', array( 'class' => 'btn btn-primary span2', 'value' => t( 'intouch.form.submit' ), 'name' => 'submit' ) )
		.			$form->getButton( 'reset', array( 'class' => 'btn span2', 'value' => t( 'intouch.form.cancel' ), 'style' => 'margin-left: 15px;' ) )
		.		'</div>'
		.	'</form>';
		
		return $data;
	}
}