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

namespace Contao;

use Contao\Model\Collection;


/**
 * Reads and writes comments subscriptions
 *
 * @package   Models
 * @author    Leo Feyer <https://github.com/leofeyer>
 * @copyright Leo Feyer 2005-2014
 */
class CommentsNotifyModel extends Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_comments_notify';


	/**
	 * Find a subscription by its tokens
	 *
	 * @param string $strToken   The token string
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Model|null The subscription model or null
	 */
	public static function findByTokens($strToken, array $arrOptions=[])
	{
		$t = static::$strTable;
		return static::findOneBy(["($t.tokenConfirm=? OR $t.tokenRemove=?)"], [$strToken, $strToken], $arrOptions);
	}


	/**
	 * Find a subscription by its tokens
	 *
	 * @param string  $strSource  The source element
	 * @param integer $intParent  The parent ID
	 * @param string  $strEmail   The e-mail address
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Model|null The subscription model or null
	 */
	public static function findBySourceParentAndEmail($strSource, $intParent, $strEmail, array $arrOptions=[])
	{
		$t = static::$strTable;
		return static::findOneBy(["$t.source=? AND $t.parent=? AND $t.email=?"], [$strSource, $intParent, $strEmail], $arrOptions);
	}


	/**
	 * Find published comments by their source table and parent ID
	 *
	 * @param string  $strSource  The source element
	 * @param integer $intParent  The parent ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|null A collection of models or null if there are no active subscriptions
	 */
	public static function findActiveBySourceAndParent($strSource, $intParent, array $arrOptions=[])
	{
		$t = static::$strTable;
		return static::findBy(["$t.source=? AND $t.parent=? AND tokenConfirm=''"], [$strSource, $intParent], $arrOptions);
	}
}
