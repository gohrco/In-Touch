<?php


$form	= array(
		'enable'		=> array(
				'order'			=> 10,
				'type'			=> 'toggleyn',
				'value'			=> true,
				'validation'	=> '',
				'labelon'		=> 'intouch.form.toggleyn.enabled',
				'labeloff'		=> 'intouch.form.toggleyn.disabled',
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
		'usewysiwyg'		=> array(
				'order'			=> 30,
				'type'			=> 'toggleyn',
				'value'			=> true,
				'validation'	=> '',
				'labelon'		=> 'intouch.form.toggleyn.enabled',
				'labeloff'		=> 'intouch.form.toggleyn.disabled',
				'label'			=> 'intouch.admin.form.config.label.usewysiwyg',
				'description'	=> 'intouch.admin.form.config.desc.usewysiwyg',
		),
		'fetoenable'		=> array(
				'order'			=> 40,
				'type'			=> 'toggleyn',
				'value'			=> true,
				'validation'	=> '',
				'labelon'		=> 'intouch.form.toggleyn.enabled',
				'labeloff'		=> 'intouch.form.toggleyn.disabled',
				'label'			=> 'intouch.admin.form.config.label.fetoenable',
				'description'	=> 'intouch.admin.form.config.desc.fetoenable',
		),
		'dlid'	=> array(
				'order'			=> 50,
				'type'			=> 'text',
				'value'			=> null,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.config.label.dlid',
				'description'	=> 'intouch.admin.form.config.description.dlid',
		),
		);