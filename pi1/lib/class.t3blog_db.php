<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 snowflake <typo3@snowflake.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class t3blog_db {

	/**
	 * @var tslib_cObj
	 */
	static protected $cObj = null;

	/**
	 * Creates an instance of this class
	 *
	 * @return void
	 */
	static public function init() {
		if (self::$cObj == null) {
			self::$cObj = t3lib_div::makeInstance('tslib_cObj');
		}
	}

	/**
	 * Select and fetch associate query to db.
	 *
	 * FIXME Single use function! Move to pi1/widgets/blogList/class.singleFunctions.php
	 *
	 * @param	string	$tabel: name of the table to be selected
	 * @param	string	$select_fields: name of the fields
	 * @param 	string	$where: sql-where clause
	 * @param	string	$order: sql-order clause
	 * @param	string	$limit: sql-limit clause
	 * @return 	array	if has, db records from the db, else return false boolean
	 */
	static public function getRecFromDbJoinTables($table, $selectFields , $where = '1=1', $order='', $limit = '', $showHidden = 0){
		$tablePost = 'tx_t3blog_post';
		$where .= self::$cObj->enableFields($tablePost, $showHidden);

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($selectFields,
			$table, $where, '', $order, $limit
		);

		return (count($result) > 0 ? $result : false);
	}

	/**
	 * Select and fetch associate query to db
	 * @author Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param 	string	$where: sql-where clause
	 * @param	string	$order: sql-order clause
	 * @param	string	$limit: sql-limit clause
	 * @return 	array	if has, db records from the db, else return false boolean
	 */
	static public function getPostByWhere($where = '1=1', $order='', $limit = '') {
		$fields = '*';
		$table = 'tx_t3blog_post';

		$where.= self::$cObj->enableFields($table);
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where,
			'', $order, $limit
		);

		return (count($result) > 0 ? $result : false);
	}

	/**
	 * Select and fetch associate query to db
	 * @author Meile Simon <smeile@snowflake.ch>
	 *
	 * @param 	string	$where: sql-where clause
	 * @param	string	$order: sql-order clause
	 * @param	string	$limit: sql-limit clause
	 * @return 	array	if has, db records from the db, else return false boolean
	 */
	static public function getCommentsByWhere($where = '1=1', $order='', $limit = '') {
		$fields = '*';
		$table = 'tx_t3blog_com';
		$where .= self::$cObj->enableFields($table);

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where,
			'', $order, $limit
		);

		return (count($result) > 0 ? $result : false);
	}

	/**
	 * Returns the number of Comment for this post.
	 * *
	 * @param 	int 	$uid: uid of the post
	 * @return	int		amount of comments
	 */
	static public function getNumberOfCommentsByPostUid($uid){
		list($result) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('count(uid) as counted',
			'tx_t3blog_com', 'fk_post=' . intval($uid) . ' AND spam=0 AND approved=1' .
			self::$cObj->enableFields('tx_t3blog_com')
		);
		return $result['counted'];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_db.php']);
}

t3blog_db::init();

?>