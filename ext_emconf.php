<?php
$EM_CONF[$_EXTKEY] = array(
	'title'				=> 'Page Advanced',
	'description'		=> 'Adds a preview of a page\'s edition directly by using the "Page" module. Meaning you can for example modify categories, title, etc.',
	'category'			=> 'backend',
	'author'			=> 'Romain CANON',
	'author_email'		=> 'romain.canon@exl-group.com',
	'author_company'	=> 'EXL Group',
	'shy'				=> '',
	'priority'			=> '',
	'module'			=> '',
	'state'				=> 'beta',
	'internal'			=> '',
	'uploadfolder'		=> '0',
	'createDirs'		=> '',
	'modify_tables'		=> '',
	'clearCacheOnLoad'	=> 1,
	'lockType'			=> '',
	'version'			=> '0.1.0',
	'constraints'		=> array(
		'depends'			=> array(
			'extbase'				=> '6.2',
			'fluid'					=> '6.2',
			'typo3'					=> '6.2'
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);