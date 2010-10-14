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

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class categories extends tslib_pibase {
	var $prefixId      = 'categories';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/categories/class.categories.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = false;
	var $localPiVars;
	var $globalPiVars;
	var $conf;


	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->globalPiVars 	= $piVars;
		$this->localPiVars 		= $piVars[$this->prefixId];
		$this->conf 			= $conf;
		$this->cObj 			= t3lib_div::makeInstance('tslib_cObj');

		$this->pi_loadLL();
		$this->pi_USER_INT_obj	= 0;
		$categories = array(
			'content' => $this->listCategories(),
			'header' => $this->pi_getLL('title')
		);

		$content = t3blog_div::getSingle($categories, 'categories', $this->conf);

		return $content;
	}



	/**
	 * function to list the categories in FE.
	 * 	- tree view
	 * 	- open/closes subcategory - part with mootools-slide-object
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>
	 *
	 * @param 	int 	$parent
	 * @param 	int 	$level
	 *
	 * @return array
	 */
	protected function listCategories($parent = 0, $level = 1) {
		$javascript = '';

		$data = array(
			'content' => '',
			'id' => 'togglecat' . $parent,
			'level' => $level,
		);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_t3blog_cat',
			'pid=' . t3blog_div::getBlogPid() . ' AND parent_id=' . $parent .
				$this->cObj->enableFields('tx_t3blog_cat'), '', 'catname');
		while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$subcategories = $this->listCategories($row['uid'], $level + 1);
			$toggleId = 'togglecat' . $row['uid'];

			$row['postnum'] = $this->getNumberOfEntriesForCategory($row['uid']);

			if ($subcategories) {
				$row['catname'] = '<a href="#" id="img' . $toggleId . '" class="iconbeforetext">' .
					$this->conf['toggle.']['close'] . '</a>' .
					$this->getCategoryLink($row);
				$row['subcategories'] = $subcategories;
				$javascript .= $this->getToggleJavaScript($toggleId);
			}
			else {
				$row['catname'] = $this->getCategoryLink($row);
			}

			$data['content'] .= t3blog_div::getSingle($row, 'listItem', $this->conf);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		if (method_exists('t3lib_div', 'minifyJavaScript')) {
			$javascript = t3lib_div::minifyJavaScript($javascript);
		}
		$data['javascript'] = $javascript;

		$result = t3blog_div::getSingle($data, 'list', $this->conf);

		return $result;
	}

	/**
	 * Creates a script to toggle categories using JavaScript.
	 *
	 * @param int $toggleId
	 * @return string
	 */
	protected function getToggleJavaScript($toggleId) {
		$closeMarkup = $this->conf['toggle.']['close'];
		$openMarkup = $this->conf['toggle.']['open'];
		return 'var mySlide' . $toggleId . ' = new Fx.Slide($(\'' . $toggleId . '\'));
				if(Cookie.get("mySlide' . $toggleId . '")==1){
					mySlide' . $toggleId . '.toggle();
					if($(\'img' . $toggleId . '\').innerHTML == "' . $openMarkup . '")	{
						$(\'img' . $toggleId . '\').innerHTML = "' . $closeMarkup . '";
					} else {
						$(\'img' . $toggleId . '\').innerHTML = "' . $openMarkup . '";
					}
				}

				$(\'img' . $toggleId . '\').addEvent(\'click\', function(e) {
					e = new Event(e);
					mySlide' . $toggleId . '.toggle();
					if($(\'img' . $toggleId . '\').innerHTML == "' . $openMarkup . '")	{
						Cookie.remove("mySlide' . $toggleId . '");
						Cookie.set("mySlide' . $toggleId . '","0",{path:"/"});
						$(\'img' . $toggleId . '\').innerHTML = "' . $closeMarkup . '";
					} else {
						Cookie.set("mySlide' . $toggleId . '","1",{path:"/"});
						$(\'img' . $toggleId . '\').innerHTML = "' . $openMarkup . '";
					}
					e.stop();
				}
			);
		';
	}

	/**
	 * Creates a category link.
	 *
	 * @param array $row
	 * @return string
	 */
	protected function getCategoryLink(array $row) {
		$dataLink = array(
			'catname'	=>	$row['catname'],
			'uid'		=>	$row['uid'],
			'blogPid'	=>	t3blog_div::getBlogPid()
		);
		return t3blog_div::getSingle($dataLink, 'catLink', $this->conf);
	}


	/**
	 * Gets the number of entries per category (and sub)
	 *
	 * @author	Nicolas Karrer <nkarrer@snowflake.ch>
	 *
	 * @param 	int 	$categoryId
	 * @return 	int
	 */
	protected function getNumberOfEntriesForCategory($categoryId)	{
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS counter',
			'tx_t3blog_post, tx_t3blog_post_cat_mm as mm',
			'tx_t3blog_post.uid = mm.uid_local AND uid_foreign IN (' .
				implode(',', $this->getCommaSeparatedCategories($categoryId)) .
				')' . $this->cObj->enableFields('tx_t3blog_post'));

		return $row['counter'];
	}


	/**
	 * Gets the hirarchy of categories and puts it into the comma-separated list.
	 *
	 * FIXME See also similar function in ../blogList/class.listFunctions.php
	 *
	 * @author 	Nicolas Karrer <nkarrer@snowflake.ch>
	 *
	 * @param int $parent
	 * @param array
	 */
	protected function getCommaSeparatedCategories($parent, array $uidList = array()) {
		$uidList[] = $parent;
		// Note: no intval because the function gets only integers!
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_t3blog_cat', 'parent_id=' . $parent);
		while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$newList = $this->getCommaSeparatedCategories($row['uid'], $uidList);
			$uidList = array_unique(array_merge($uidList, $newList));
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $uidList;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/categories/class.categories.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/categories/class.categories.php']);
}
?>