<?php


class IntouchAdminareapagesDunModuleTest extends PHPUnit_Framework_TestCase
{
	public $dunamis	=	null;
	
	public function setUp()
	{
		$this->dunamis	=	get_dunamis( 'dunamis' );
		$input	=	dunloader( 'input', true );
		$input->setVar( 'task', 'phpunit' );
	}
	
	
	public function tearDown()
	{
		
	}
	
	
	/**
	 * @covers IntouchAdminareapagesDunModule :: initialize
	 */
	public function testInitialize()
	{
		$module	=	dunmodule( 'intouch.adminareapages' );
		$this->assertTrue( function_exists( 'get_intouch_version' ) );
		$this->assertTrue( get_class( $module ) == 'IntouchAdminareapagesDunModule' );
		$this->assertObjectHasAttribute( 'area', $module );
		return $module;
	}
	
	
	/**
	 * @covers IntouchAdminareapagesDunModule :: display
	 * @depends testInitialize
	 */
	public function testDisplay( $module )
	{
		//<script src="http://localhost/mods/whmcs/includes/dunamis/core/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
		//<script src="http://localhost/mods/whmcs/includes/dunamis/core/assets/js/bootstrapSwitch.js" type="text/javascript"></script>
		$doc	=	dunloader( 'document', true );
		$before	=	$doc->renderFootData();
		
		$quotes	=	$module->display( 'quotes', 'manage' );
		
		$after	=	$doc->renderFootData();
		
		$this->assertTrue ( strlen( $after ) > strlen( $before ) );
	}
}