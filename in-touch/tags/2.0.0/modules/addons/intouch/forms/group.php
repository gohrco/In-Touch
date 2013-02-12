<?php


$form	= array(
		'name'	=> array(
				'order'	=> 10,
				'type' => 'text',
				'value' => null,
				'label' => 'intouch.admin.form.group.label.name',
				'description' => 'intouch.admin.form.group.desc.name',
				),
		'group' => array(
				'order' => 20,
				'type' => 'whmcsclientgroups',
				'value' => null,
				'allownogroup' => true,
				'label' => 'intouch.admin.form.group.label.group',
				'description' => 'intouch.admin.form.group.desc.group',
				),
		'active'		=> array(
				'order'			=> 30,
				'type'			=> 'toggleyn',
				'value'			=> true,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.group.label.active',
				'description'	=> 'intouch.admin.form.group.desc.active',
				),
		// Begin button breakout
		'params' => array(
				'order'			=> 20,
				'type'			=> 'togglebtn',
				'value'			=> array( '1' ),
				'validation'	=> '',
				'options'		=> array(
						array( 'id' => '1', 'name' => 'intouch.admin.form.group.params.optn.emails' ),
						array( 'id' => '2', 'name' => 'intouch.admin.form.group.params.optn.invoices' ),
						array( 'id' => '3', 'name' => 'intouch.admin.form.group.params.optn.quotes' )
					),
				'label'			=> 'intouch.admin.form.group.params.label',
				),
		'paramsoptn1'	=> array( 'order'	=> 100, 'class'	=> 'well well-small', 'type'	=> 'wrapo' ),
		'emailcss' => array(
				'order'			=> 110,
				'type'			=> 'textarea',
				'value'			=> null,
				'label'			=> 'intouch.admin.form.group.label.emailcss',
				'description'	=> 'intouch.admin.form.group.desc.emailcss',
				'style'			=> 'width:95%;',
				'rows'			=> '2',
		),
		'emailheader' => array(
				'order'			=> 120,
				'type'			=> 'wysiwyg',
				'value'			=> null,
				'label'			=> 'intouch.admin.form.group.label.emailheader',
				'description'	=> 'intouch.admin.form.group.desc.emailheader',
				'style'			=> 'width:95%;',
				'rows'			=> '5',
		),
		'emailsig' => array(
				'order'			=> 130,
				'type'			=> 'wysiwyg',
				'value'			=> null,
				'label'			=> 'intouch.admin.form.group.label.emailsig',
				'description'	=> 'intouch.admin.form.group.desc.emailsig',
				'style'			=> 'width:95%;',
				'rows'			=> '5',
		),
		'emailfooter' => array(
				'order'			=> 140,
				'type'			=> 'wysiwyg',
				'value'			=> null,
				'label'			=> 'intouch.admin.form.group.label.emailfooter',
				'description'	=> 'intouch.admin.form.group.desc.emailfooter',
				'style'			=> 'width:95%;',
				'rows'			=> '5',
		),
		'emaillegal' => array(
				'order'			=> 150,
				'type'			=> 'wysiwyg',
				'value'			=> null,
				'label'			=> 'intouch.admin.form.group.label.emaillegal',
				'description'	=> 'intouch.admin.form.group.desc.emaillegal',
				'style'			=> 'width:95%;',
				'rows'			=> '5',
		),
		'paramsoptn1c'	=> array( 'order'	=> 199, 'type'	=> 'wrapc' ),
		'paramsoptn2'	=> array( 'order'	=> 200, 'class'	=> 'well well-small', 'type'	=> 'wrapo' ),
		'invoiceenabled'		=> array(
				'order'			=> 210,
				'type'			=> 'toggleyn',
				'value'			=> true,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.group.label.invoiceenabled',
				'description'	=> 'intouch.admin.form.group.desc.invoiceenabled',
		),
		'invoicelogo'	=> array(
				'order'		=> 220,
				'type'		=> 'text',
				'value'		=> '/images/placeholder.png',
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.group.label.invoicelogo',
				'description'	=> 'intouch.admin.form.group.desc.invoicelogo',
				),
		'invoiceadd' => array(
				'order'			=> 230,
				'type'			=> 'textarea',
				'value'			=> 'Your custom address goes here...',
				'label'			=> 'intouch.admin.form.group.label.invoiceadd',
				'description'	=> 'intouch.admin.form.group.desc.invoiceadd',
				'style'			=> 'width:50%;',
				'rows'			=> '6',
		),
		'paramsoptn2c'	=> array( 'order'	=> 299, 'type'	=> 'wrapc' ),
		'paramsoptn3'	=> array( 'order'	=> 300, 'class'	=> 'well well-small', 'type'	=> 'wrapo' ),
		'quoteenabled'		=> array(
				'order'			=> 310,
				'type'			=> 'toggleyn',
				'value'			=> true,
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.group.label.quoteenabled',
				'description'	=> 'intouch.admin.form.group.desc.quoteenabled',
		),
		'quotelogo'	=> array(
				'order'		=> 320,
				'type'		=> 'text',
				'value'		=> '/images/placeholder.png',
				'validation'	=> '',
				'label'			=> 'intouch.admin.form.group.label.quotelogo',
				'description'	=> 'intouch.admin.form.group.desc.quotelogo',
		),
		'quoteadd' => array(
				'order'			=> 330,
				'type'			=> 'textarea',
				'value'			=> 'Your custom address goes here...',
				'label'			=> 'intouch.admin.form.group.label.quoteadd',
				'description'	=> 'intouch.admin.form.group.desc.quoteadd',
				'style'			=> 'width:50%;',
				'rows'			=> '6',
		),
		'paramsoptn3c'	=> array( 'order'	=> 399, 'type'	=> 'wrapc' ),
		'gid' => array(
				'order' => 10000,
				'type' => 'hidden',
				'value' => 0,
				),
);