<?php


$form	= array(
		'enable'		=> array(
				'order'			=> 10,
				'type'			=> 'toggleyn',
				'value'			=> true,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.config.label.enable',
				'description'	=> 'intouch.admin.form.config.desc.enable',
				),
		'apiuser'	=> array(
				'order'			=> 20,
				'type'			=> 'whmcsadmins',
				'value'			=> null,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.config.label.apiuser',
				'description'	=> 'intouch.admin.form.config.desc.apiuser',
		),
		);