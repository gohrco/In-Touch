<?php


$form	= array(
		'license'	=> array(
				'order'			=> 10,
				'type'			=> 'text',
				'value'			=> null,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.config.label.license',
				'description'	=> 'intouch.admin.form.config.description.license',
		),
		'status' => array(
				'order'			=> 20,
				'type'			=> 'information',
				'value'			=> array(),
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.config.label.status',
				'description'	=> 'intouch.admin.form.config.description.status',
				),
		'info'	=> array(
				'order'			=> 40,
				'type'			=> 'information',
				'value'			=> array(),
				'nodesc'		=> true,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.config.label.info',
		),
);