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
 * Register the templates
 */
TemplateLoader::addFiles(
[
	'com_default'      => 'system/modules/comments/templates/comments',
	'ce_comments'      => 'system/modules/comments/templates/elements',
	'mod_comment_form' => 'system/modules/comments/templates/modules',
]);
