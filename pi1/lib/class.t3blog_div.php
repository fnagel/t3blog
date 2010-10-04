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

class t3blog_div {


	/**
	 * Crops the text at divider's position with respect to the HTML structure.
	 *
	 * @param array $row
	 * @param int $dividerPosition
	 * @return string
	 */
	static public function cropText(array $row, $dividerPosition) {
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		if (method_exists($cObj, 'cropHTML')) {
			// Algorithm:
			// - render text correctly
			// - make sure there is no empty paragraphs for ###MORE###
			$renderedText = t3blog_div::getSingle($row, 'tt_content', $GLOBALS['TSFE']->tmpl->setup);
			$textBeforeDivider = $cObj->cropHTML($renderedText,
				($dividerPosition + 10). '|');
			$regExp = '/<p(?:\s[^>]*)?>\s*###MORE###\s*<\/p>/';
			if (preg_match($regExp, $textBeforeDivider)) {
				$textBeforeDivider = preg_replace($regExp, '', $textBeforeDivider);
			}
			else {
				$textBeforeDivider = str_replace('###MORE###', '', $textBeforeDivider);
			}
		}
		else {
			$textBeforeDivider = substr($row['bodytext'], 0, $dividerPosition);
		}
		return $textBeforeDivider;
	}

	/**
	 * Fetches content data for the current post.
	 *
	 * @param int $postId
	 * @param array $contentUidArray
	 * @param boolean $hasDivider
	 * @param string $textBeforeDivider
	 */
	static public function fetchContentData($postId, array &$contentUidArray, &$hasDivider, &$textBeforeDivider) {
		static $cache = array();

		if (isset($cache[$postId])) {
			$contentUidArray = $cache[$postId]['contentUidArray'];
			$hasDivider = $cache[$postId]['hasDivider'];
			$textBeforeDivider = $cache[$postId]['textBeforeDivider'];
		}
		else {
			$cObj = t3lib_div::makeInstance('tslib_cObj');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,bodytext,CType', 'tt_content',
				'irre_parentid=' . $postId .
					' AND irre_parenttable=\'tx_t3blog_post\'' .
					$cObj->enableFields('tt_content'),
				'', 'sorting'
			);
			$contentUidArray = array();
			$hasDivider = false;
			$textBeforeDivider = '';
			while (false !== ($rowContent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				if ($rowContent['CType'] == 'text' || $rowContent['CType'] == 'textpic') {
					$dividerPosition = strpos($rowContent['bodytext'], '###MORE###');
					if ($dividerPosition !== false) {
						$textBeforeDivider = self::cropText($rowContent, $dividerPosition);
						$hasDivider = true;
						break;
					}
				}
				$contentUidArray[] = $rowContent['uid'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

			$cache[$postId] = array(
				'contentUidArray' => $contentUidArray,
				'hasDivider' => $hasDivider,
				'textBeforeDivider' => $textBeforeDivider
			);
		}
	}


	/**
	 * Parses data through typoscript.
	 *
	 * @param array $data Data which will be passed to the typoscript.
	 * @param string $tsObjectKey The typoscript which will be called.
	 * @param array $tsObjectConf TS object configuration
	 * @return string
	 */
	static public function getSingle(array $data, $tsObjectKey, array $tsObjectConf) {
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$cObj->data = $data;
		return $cObj->cObjGetSingle($tsObjectConf[$tsObjectKey], $tsObjectConf[$tsObjectKey . '.']);
	}

	/**
	 * Checks if it is a valid email. Use t3lib_div::validEmail() instead!
	 *
	 * @param 	string 	$email: emailaddress
	 * @return 	boolean	true if error
	 * @deprecated
	 */
	static public function checkEmail($email){
		return !t3lib_div::validEmail($email);
	}

	/**
	 * Checks if it is a valid http:// url
	 * adds "http://" string if there is none.
	 *
	 * @param 	string 	$url: url-address
	 * @return 	boolean	true if error
	 * @deprecated Use t3lib_div::isValidUrl()
	 */
	static public function checkExternalUrl($url) {
		return t3lib_div::isValidUrl($url);
	}

	/**
	 * Returns the username (realname) from be_user by a uid
	 *
	 * @param  int $uid uid of the be_user
	 * @return string Real name of the be_user
	 */
	static public function getAuthorByUid($uid) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('realName', 'be_users',
			'uid= ' . intval($uid), '', '', '1'
		);
		return (is_array($row) ? $row['realName'] : '');
	}

	/**
	 * Obtains the category name by its uid
	 *
	 * @param int $uid uid of a specific category
	 * @return string name of the category
	 */
	static public function getCategoryNameByUid($uid) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'catname', 'tx_t3blog_cat', 'uid=' . intval($uid),
			'', '', '1'
		);
		return (is_array($row) ? $row['catname'] : '');
	}


	/**
	 * returns the page browser of given table.
	 *
	 * @author snowflake <typo3@snowflake.ch>
	 *
	 * @param int 			$numOfEntries: total of all elements of a table
	 * @param string 		$ident: identifier for pointer. e.g. recently editet as more than 1 page browser on the site.
	 * @param $prefixId 	$todo: functionname of what-is-to-do-after-page-click-function
	 *
	 * @return string 		HTML-Content to the browser
	 */
	static public function getPageBrowser($numOfEntries, $ident, $prefixId, $llarray, $piVars,$conf, $limit = 10, $maxPages = 20)	{
  		// Get default configuration
  		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_pagebrowse_pi1.'];
		if (!is_array($conf) || !isset($conf['userFunc'])) {
			// Hardcoded because:
			// - language labels are unavailable from here
			// - this message is for installers only, not for end users
			$result = 'Warning: "pagebrowse" extension TypoScript is unavailable! Did you forget to include it before t3blog\'s TypoScript?';
		}
		else {
			$pages = intval($numOfEntries/$limit) + (($numOfEntries % $limit) == 0 ? 0 : 1);
			if ($pages == 0) {
				$result = '';
			}
			else {
				// Modify this configuration
				$conf = array_merge($conf, array(
					'pageParameterName' => 'tx_t3blog_post_pointer',
					'numberOfPages' => $pages,
				));

				self::setPageBrowserFilters($conf);

				// Get page browser
				$cObj = t3lib_div::makeInstance('tslib_cObj');
				/* @var $cObj tslib_cObj */
				$cObj->start(array(), '');
				$result = $cObj->cObjGetSingle('USER', $conf);
			}
		}
		return $result;
	}

	/**
	 * Adds extra conditions to the page browser link
	 *
	 * @param array $conf
	 * @return void
	 */
	static protected function setPageBrowserFilters(array &$conf) {
		$postVars = t3lib_div::_GP('tx_t3blog_pi1');
		if (is_array($postVars) && isset($postVars['sword'])) {
			$conf['extraQueryString'] = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
				'sword' => $postVars['sword']
			));
		}
	}

	/**
	 * Sets an alternative blog Pid.
	 *
	 * @param 	integer		$pid: pid of the record storage page
	 */
	static public function setAlternativeBlogPid($pid) {
		$GLOBALS['alternativeBlogPid'] = $pid;
	}

	/**
	 * Returns the blog storage folder pid
	 *
	 * @return integer 	pid of the storage folder
	 */
	static public function getBlogPid(){
		static $cachedPid = 0;

		if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_t3blog_pi1.']['blogPid']) &&
				t3lib_div::testInt($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_t3blog_pi1.']['blogPid'])) {
			return $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_t3blog_pi1.']['blogPid'];
		}

		// get pid
		if (isset($GLOBALS['alternativeBlogPid']) && $GLOBALS['alternativeBlogPid'] > 0) {
			return intval($GLOBALS['alternativeBlogPid']);
		}

		if ($cachedPid != 0) {
			$pid = $cachedPid;
		}
		else {
			// get the Rootline
			$rootline = array_reverse($GLOBALS['TSFE']->tmpl->rootLine);

			// go through rootline until a blogPid is found
			$pidList = array();
			foreach ($rootline as $page) {
				$pidList[] = $page['uid'];
			}
			$pidString = implode(',', $pidList);
			list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages',
				'uid IN (' . $pidString . ') AND module=\'t3blog\'' .
				$GLOBALS['TSFE']->sys_page->enableFields('pages'),
				'', 'FIELD(uid,' . $pidString . ')', 1);
			if (is_array($row)) {
				$pid = $row['uid'];
			}
			else {
				$pid = $GLOBALS['TSFE']->id;
			}
			$cachedPid = $pid;
		}
		return intval($pid);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_div.php']);
}

?>