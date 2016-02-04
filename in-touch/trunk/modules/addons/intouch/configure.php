<?php defined('DUNAMIS') OR exit('No direct script access allowed');



class IntouchConfigureDunModule extends IntouchAdminDunModule
{
	
	public function initialise()
	{
		$this->action = 'configure';
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
		
		// Check license
		if (! dunloader( 'license', 'intouch' )->isValid() ) {
			$this->setAlert( 'alert.license.invalid', 'block' );
			return;
		}
		
		switch ( $this->task ):
		case 'save' :
			
				$config	= array( 'enable' => '0', 'apiuser' => '1', 'usewysiwyg' => '1', 'fetoenable' => '0', 'dlid' => '', 'preservedb' => '0' );
				$mycnfg	=	dunloader( 'config', 'intouch' );
				
				foreach ( $config as $item => $default ) {
					$key = $item; $value = $input->getVar( $item, $default );
					if ( is_array( $value ) ) $value = implode( '|', $value );
					
					if ( $mycnfg->has( $item ) ) {
						$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = " . $db->Quote( $value ) . " WHERE `key` = '{$key}'" );
					}
					else {
						$db->setQuery( "INSERT INTO `mod_intouch_settings` ( `value`, `key` ) VALUES ( " . $db->Quote( $value ) . ", '{$key}' )" );
					}
					
					$mycnfg->set( $key, $value );
					$db->query();
				}
				
				$this->setAlert( 'alert.configure.saved' );
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
		$form	=	dunloader( 'form', true );
		$db		=	dunloader( 'database', true );
		
		$db->setQuery( "SELECT * FROM mod_intouch_settings" );
		$results	= $db->loadObjectList();
		$values		= array();
		
		// Set the values up
		foreach ( $results as $result ) $values[$result->key] = $result->value;
		
		$views	=	dunloader( 'views', 'intouch' );
		$views->setData( array( 'fields' => $form->setValues( $values, 'intouch.config' ) ) );
		
		return parent :: render( $views->render( 'configure' ) );
	}
}