<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Snowflake Productions Gmbh <info@snowflake.ch>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
/**
 * $Id$
 */

if (!defined('TYPO3_MODE')) {
	die('blogfunctions: this class should not be called from the browser directly! Did you forget to include typo3/init.php?');
}

/**
  * This class is implements various blog functions. While most of them could
  * be static, we keep them non-static to allow XCLASSing if necessary.
  *
  * @author Snowflake Productions Gmbh <info@snowflake.ch>
  * @package TYPO3
  * @subpackage tx_t3blog
  */
class blogfunctions {

	/**
	 * Get the category names for a post
	 *
	 * @param string $table
	 * @param int $recordId
	 * @return Space-separated category names
	 */
	public function getCategoryNames($table, $recordId) {
		$rsCatNames = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_t3blog_cat.catname as cat_names',
			'tx_t3blog_cat,tx_t3blog_post_cat_mm',
			'deleted=0 AND tx_t3blog_cat.uid = tx_t3blog_post_cat_mm.uid_foreign AND tx_t3blog_post_cat_mm.uid_local =' . intval($recordId));
		while (false != ($dsCatNames = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rsCatNames))) {
			$content .= $dsCatNames['cat_names'] . ' ';
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($rsCatNames);

		return $content;
	}


	/**
	 * Generates a URL string
	 *
	 * @param	string	$altID: id of the page
	 * @param	string	$table: name of the table
	 * @param	string	$exclList: list
	 * @return	string	url
	 */
	public function listURL($altId = '', $table = '') {
		$urlParameters = array(
			'id' => $altId ? $altId : t3lib_div::_GP('id')
		);
		if ($table) {
			$urlParameters['table'] = $table;
		}

		return 'index.php?' . substr(t3lib_div::implodeArrayForUrl('', $urlParameters), 1);
	}


	/**
	 * Creates a partial SQL-Query-String for a free-text search
	 *
	 * @param	string	$table: name of the table
	 * @return	string	search string
	 */
	public function makeSearchString($table){
		// Initialize field array. This array is never empty!
		$searchFields = $this->getSearchFields($table);

		// Free-text search
		$searchField = t3lib_div::_GP('search_field');
		if ($searchField != '') {
			$like = ' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchField . '%', $table);
		}
		else {
			$like = ' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%', $table);
		}
		$queryPart = ' AND (' . implode($like . ' OR ', $searchFields) . $like . ')';

		return $queryPart;
	}

	/**
	 * Creates a Searchbox
	 *
	 * @param	boolean	$addFormFields
	 * @return	string	code for search box
	 */
	public function getSearchBox($wrapIntoForm = true)	{

		// Setting form-elements, if applicable
		$formElements = array('', '');
		if ($wrapIntoForm) {
			$formElements = array(
				'<form action="index.php" method="post">
					<input type="hidden" name="id" value="' . intval(t3lib_div::_GP('id')) . '" />
					<input type="hidden" name="curPage" value="1" />
					<input type="hidden" name="sort" value="' . htmlspecialchars(t3lib_div::_GP('sort')) . '" />
					<input type="hidden" name="sortDir" value="' . htmlspecialchars(t3lib_div::_GP('sortDir')) . '" />
					<input type="hidden" name="cat" value="' . htmlspecialchars(t3lib_div::_GP('cat')) . '" />
					<input type="hidden" name="pid" value="' . htmlspecialchars(t3lib_div::_GP('pid')) . '" />
					',
				'</form>'
			);
		}

		// Table with the search box:
		$searchFieldTitle = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.enterSearchString', true);
		$searchFieldValue = htmlspecialchars(t3lib_div::_GP('search_field'));
		$submitButtonText = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.search', true);
		$content = '
				<!--
					Search box:
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-search">
					<tr>
						<td>' . $searchFieldTitle .'<input type="text" name="search_field" value="' . $searchFieldValue . '"' .
							$GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' /></td>
						<td> </td>
						<td><input type="submit" name="search" value="' . $submitButtonText . '" /></td>
					</tr>

				</table>';

		return $formElements[0] . $content . $formElements[1];
	}

	/**
	 * Truncates the string at the given length and appends ellipses if necessary
	 *
	 * @param string $string
	 * @param int $length
	 * @return string
	 * @deprecated Use t3lib_div::fixed_lgd_cs() instead!
	 */
	public function truncate($string, $length)  {
		if (strlen($string) > $length) {
			$string = t3lib_div::fixed_lgd_cs($string, $length);
		}
		return $string;
	}

	

	/**
	 * Obtains styles declarations from the CSS file
	 *
	 * @return  string
	 */
	public function getCSS() {
		return file_get_contents('../lib/styles.css');
	}

	/**
	 * Renders a TYPO3 href url
	 *
	 * @param    	integer $targetId page id
	 * @param    	string  $blogId Blog id
	 * @return		string  the link url, not being htmlspecialchar'ed yet
	 */
	protected function getBlogURL($pageId, $blogId) {
		if (t3lib_extMgm::isLoaded('pagepath')) {
			t3lib_div::requireOnce(t3lib_extMgm::extPath('pagepath', 'class.tx_pagepath_api.php'));
			$link = tx_pagepath_api::getPagePath($pageId, array(
				'bid' => $blogId
			));
		}
		else {
			$link = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . '/?id=' . $pageId . '&bid=' . $blogId;
		}
		return $link;
	}

	/**
	 * Obtains the text of the blog entry
	 *
	 * @param int $blogEntryUid
	 * @return string
	 */
	protected function getBlogEntryText($blogEntryUid) {
		$where = 'irre_parenttable=\'tx_t3blog_post\' AND ' .
			'irre_parentid=' . intval($blogEntryUid) . ' AND ' .
			'(CType=\'text%\' OR CType=\'textpic\') AND ' .
			'bodytext<>\'\'' .
			t3lib_BEfunc::BEenableFields('tt_content') .
			t3lib_BEfunc::deleteClause('tt_content');
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'header,bodytext', 'tt_content', $where, '', 'sorting', '1');

		// if the post has content, set text
		$result = '';
		if (is_array($row)) {
			$result = trim($row['header'] . ' ' . $row['bodytext']);
		}

		return $result;
	}

	

	/**
	 * Obtains blog entry author name by his id
	 *
	 * @param int $authorId
	 * @return string
	 */
	protected function getBlogEntryAuthorName($authorId) {
		$user = t3lib_BEfunc::getRecord('be_users', $authorId, 'realName');
		if (is_array($user) && $user['realName']) {
			$author = $user['realName'];
		}
		else {
			$author = 'Admin';
		}
		return $author;
	}

	/**
	 * Obtains blog name (page title) from page id
	 *
	 * @param int $pid
	 * @return string
	 */
	protected function getBlogName($pid) {
		$page = t3lib_BEfunc::getRecord('pages', $pid, 'title');
		return is_array($page) ? $page['title'] : '';

	}

	/**
	 * Updates trackback hash value
	 *
	 * @param int $postUid
	 * @param string $trackbacks
	 * @return void
	 */
	protected function updateTrackbackHash($postUid, $trackbacks) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_t3blog_post',
			'uid=' . intval($postUid),
			array(
				'trackback_hash' => md5($trackbacks)
			)
		);
	}

	/**
	 * Obtains searchable fields for the given table. The result is an array
	 * of fields prepended with table name. There is always at least uid field
	 * in the result.
	 *
	 * @param string $table
	 * @return array
	 */
	protected function getSearchFields($table) {
		t3lib_div::loadTCA($table);

		$searchFields[] = $table . '.uid'; // Adding "uid" by default

		// Traverse the configured columns and add all columns that can be searched:
		foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $tceFieldConf) {
			if ($this->isTextField($tceFieldConf)) {
				$searchFields[] = $table . '.' . $fieldName;
			}
		}

		return $searchFields;
	}

	/**
	 * Determines if the field is a text field using its TCA configuration
	 *
	 * @param array $tcaFieldConf
	 * @return boolean
	 */
	protected function isTextField(array $tcaFieldConf) {
		return $tcaFieldConf['config']['type'] == 'text' ||
			($tcaFieldConf['config']['type'] == 'input' && !preg_match('/date|time|int/', $tcaFieldConf['config']['eval']));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod1/class.functions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod1/class.functions.php']);
}

?>