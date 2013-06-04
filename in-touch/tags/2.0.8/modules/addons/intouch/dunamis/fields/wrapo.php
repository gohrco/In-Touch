<?php


class IntouchWrapoDunFields extends DunFields
{
	
	public function __construct( $settings = array() )
	{
		parent :: __construct( $settings );
		
		foreach ( $settings as $key => $value ) {
			$this->attributes[$key] = $value;
		}
	}
	
	
	public function description( $options = array() )
	{
		return null;
	}
	
	
	public function field( $options = array() )
	{
		$name		= $this->name;
		$value		= $this->value;
		$id			= $this->getId();
		$attr		= array_to_string( array_merge( $this->attributes, $options ) );
		
		return '<div id="' . $id . '" ' . $attr . '>';
	}
	
	
	public function label( $options = array() )
	{
		return null;
	}
}