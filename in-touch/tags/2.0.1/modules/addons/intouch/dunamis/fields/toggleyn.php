<?php



class IntouchToggleynDunFields extends DunFields
{

	public function __construct( $settings = array() )
	{
		parent :: __construct( $settings );

		foreach ( $settings as $key => $value ) {
			$this->attributes[$key] = $value;
		}
	}


	public function field()
	{
		$name	=   $this->name;
		$value	=   $this->value;
		$args	=	array_to_string( $this->attributes );
		
		$class	=	str_replace('[', '', str_replace(']', '', $name ) );
		
		// Add the js / css to the header
		$this->_addJavascript( $class );
		
		$field	=	'<div class="toggle tog-'.$class.'" data-enabled="' . t( 'intouch.form.toggleyn.enabled' ) . '" data-disabled="' . t( 'intouch.form.toggleyn.disabled' ) . '" data-toggle="toggle">'
				.	'<input type="hidden" name="' . $name . '" value="0" />'
				.	'<input type="checkbox" name="' . $name . '" class="checkbox" id="' . $name . '" value="1" '
				.	( $value == 1 ? 'checked="checked" ' : '' )
				. $args . ' />'
				.	'<label class="check" for="' . $name . '"></label>'
				.	'</div>';
		
		return $field;
	}
	
	
	private function _addJavascript( $class = null )
	{
		static $data = false;
		
		$doc	= dunloader( 'document', true );
		
		if (! $data ) {
			$data	= true;
			$baseurl = get_baseurl( 'intouch' );
			
			$doc->addStyleSheet( $baseurl . 'assets/bootstrap-toggle.css' );
			$doc->addScript( $baseurl . 'assets/bootstrap-toggle.js' );
		}
		
		$js = (! empty( $this->arguments['onclick'] ) ? $this->arguments['onclick'] : null );
		
		$javascript	=	"jQuery('.tog-".$class."').toggle({"
					.	"onClick: function (event, status) { " . $js . " },"
					.	"    text: {"
					.	"      enabled: '" . t( 'intouch.form.toggleyn.enabled' ) . "',"
					.	"      disabled: '" . t( 'intouch.form.toggleyn.disabled' ) . "'"
					.	"    },"
					.	"    style: {"
					.	"      enabled: 'success',"
					.	"      disabled: 'danger'"
					.	"    }"
					.	"  });";
		
		$doc->addScriptDeclaration( $javascript );
	}
}