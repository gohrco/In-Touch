<?php


class IntouchAdminDunModuleTest extends PHPUnit_Framework_TestCase
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
	 * @covers InTouchAdminDunModule :: initialize
	 */
	public function testInitialize()
	{
		$module	=	dunmodule( 'intouch.admin' );
		$this->assertTrue( function_exists( 'get_intouch_version' ) );
		$this->assertTrue( get_class( $module ) == 'IntouchAdminDunModule' );
		return $module;
	}
	
	
	/**
	 * @covers InTouchAdminDunModule :: getAdminConfig
	 * @depends testInitialize
	 */
	public function testGetAdminConfig( $module )
	{
		$config	=	$module->getAdminConfig();
		
		$this->assertArrayHasKey( 'name', $config );
		$this->assertCount( 7, $config );
		$this->assertTrue( $config['name'] == 'In Touch', $config['name'] );
	}
	
	
	/**
	 * @covers InTouchAdminDunModule :: render
	 * @depends testInitialize
	 */
	public function testRender( $module )
	{
		$render	=	$module->render();
		$this->assertStringStartsWith( '<div style="float:left;width:100%;">', $render );
	}
	
	
	/**
	 * @covers InTouchAdminDunModule :: renderAdminOutput
	 * @depends testInitialize
	 */
	public function testRenderAdminOutput( $module )
	{
		$render	=	$module->renderAdminOutput();
		$this->assertStringStartsWith( '<div style="float:left;width:100%;">', $render );
	}
	
	
	/**
	 * @covers InTouchAdminDunModule :: buildAlerts
	 * @depends testInitialize
	 */
	public function testBuildAlerts( $module )
	{
		$title	=	$module->buildAlerts();
		$this->assertTrue( $title == null );
	}
	
	
	/**
	 * @covers InTouchAdminDunModule :: buildModals
	 * @depends testInitialize
	 */
	public function testBuildModals( $module )
	{
		$title	=	$module->buildModals();
		$this->assertTrue( $title == null );
	}
	
	
	/**
	 * @covers InTouchAdminDunModule :: buildNavigation
	 * @depends testInitialize
	 */
	public function testBuildNavigation( $module )
	{
		$title	=	$module->buildNavigation();
		$this->assertStringStartsWith( '<ul class="nav nav-pills">', $title );
	}
	
	
	/**
	 * @covers InTouchAdminDunModule :: buildTitle
	 * @depends testInitialize
	 */
	public function testBuildTitle( $module )
	{
		$title	=	$module->buildTitle();
		$this->assertStringStartsWith( '<h1>', $title );
	}
}