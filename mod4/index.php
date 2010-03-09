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

unset($MCONF);
require_once('conf.php');
require_once($GLOBALS['BACK_PATH']. 'init.php');
require_once($GLOBALS['BACK_PATH']. 'template.php');

require_once(t3lib_extMgm::extPath('t3blog', 'lib/class.tx_t3blog_modfunc_selecttreeview.php'));
require_once(t3lib_extMgm::extPath('t3blog', 'lib/class.tx_t3blog_modbase.php'));

$GLOBALS['LANG']->includeLLFile('EXT:t3blog/mod4/locallang.xml');

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * Returning the Category administration
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class  tx_t3blog_module4 extends tx_t3blog_modbase {

	/**
	 * Tree view object
	 *
	 * @var tx_t3blog_tceFunc_selectTreeView
	 */
	protected $treeViewObj;

	/**
	 * Generates the module content
	 */
	protected function moduleContent() {
		if ($this->id) {
			$content = '<!-- CATEGORY SELECTION made by karrer nicolas -->
				<hr />' . $this->getNewRecordLink() . $this->makeTree();

			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('sectionTitle', true), $content, 0, 1);
		}
		else {
			$this->content .= $this->doc->section(
				$GLOBALS['LANG']->getLL('note'), $GLOBALS['LANG']->getLL('selABlog', true), 0, 1);
		}
	}

	/**
	 * Obtains information for new "Create new XYZ" link
	 *
	 * @return array
	 * @see tx_t3blog_modbase::getNewRecordLinkData()
	 */
	protected function getNewRecordLinkData() {
		return array(
			'icon' => t3lib_extMgm::extRelPath('t3blog'). 'icons/chart_organisation_add.png',
			'iconSize' => '16x16',
			'table' => 'tx_t3blog_cat',
			'title' => $GLOBALS['LANG']->getLL('newCategory')
		);
	}

	/**
	 * Makes the Categorie tree in the module content.
	 * Uses class: tx_t3blog_modfunc_selecttreeview from the lib-folder
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>	 *
	 * @return string
	 */
	protected function makeTree()	{
		$this->treeViewObj = t3lib_div::makeInstance('tx_t3blog_modfunc_selecttreeview');
		$this->treeViewObj->table = 'tx_t3blog_cat';

		$where   = ' AND sys_language_uid = 0 AND l18n_parent = 0 AND tx_t3blog_cat.pid = '.$this->id;
		$orderBy = 'catname';

		$this->treeViewObj->init($where, $orderBy);
		$this->treeViewObj->parentField  = 'parent_id';
		$this->treeViewObj->expandAll    = 0;
		$this->treeViewObj->expandFirst  = 1;
		$this->treeViewObj->fieldArray   = array('uid', 'catname as title', 'catname as categoriename'); // those fields will be filled to the array $treeViewObj->tree
		$this->treeViewObj->ext_IconMode = '1'; // no context menu on icons
		$this->treeViewObj->title = $GLOBALS['LANG']->getLL('sectionTitle');
		$this->treeViewObj->thisScript = 'index.php?id='.$this->id;

		return $this->treeViewObj->getBrowsableTree();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod4/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod4/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module4');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>