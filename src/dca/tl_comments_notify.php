<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Comments
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Table tl_comments_notify
 */
$GLOBALS['TL_DCA']['tl_comments_notify'] =
[

	// Config
	'config' =>
	[
		'dataContainer'               => 'Table',
		'closed'                      => true,
		'notEditable'                 => true,
		'sql' =>
		[
			'keys' =>
			[
				'id' => 'primary',
				'source' => 'index',
				'parent' => 'index',
				'tokenConfirm' => 'index',
				'tokenRemove' => 'index'
			]
		]
	],

	// Fields
	'fields' =>
	[
		'id' =>
		[
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		],
		'tstamp' =>
		[
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		],
		'source' =>
		[
			'sql'                     => "varchar(32) NOT NULL default ''"
		],
		'parent' =>
		[
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		],
		'name' =>
		[
			'sql'                     => "varchar(128) NOT NULL default ''"
		],
		'email' =>
		[
			'sql'                     => "varchar(128) NOT NULL default ''"
		],
		'url' =>
		[
			'sql'                     => "varchar(255) NOT NULL default ''"
		],
		'addedOn' =>
		[
			'sql'                     => "varchar(10) NOT NULL default ''"
		],
		'ip' =>
		[
			'sql'                     => "varchar(64) NOT NULL default ''"
		],
		'tokenConfirm' =>
		[
			'sql'                     => "varchar(32) NOT NULL default ''"
		],
		'tokenRemove' =>
		[
			'sql'                     => "varchar(32) NOT NULL default ''"
		]
	]
];
