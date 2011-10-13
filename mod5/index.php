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
require_once($BACK_PATH . 'init.php');
require_once($BACK_PATH . 'template.php');

require_once(t3lib_extMgm::extPath('t3blog', 'lib/class.tx_t3blog_modbase.php'));

$LANG->includeLLFile('EXT:t3blog/mod5/locallang.xml');

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * Returning the Blogroll administration
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class tx_t3blog_module5 extends tx_t3blog_modbase {

	/**
	 * Number of blogrolls for the current view
	 *
	 * @var int
	 */
	protected $numberOfBlogRolls;

	/**
	 * Initializes the module.
	 *
	 * @return void
	 */
	public function init() {
		$this->defaultSort = 'sorting';
		$this->validSortFields = 'title,description,url';

		parent::init();

		if ($this->hasAccess())	{
			$this->numberOfBlogRolls = $this->getNumberOfBlogRolls();
		}
	}

	/**
	 * Obtains the content for this module
	 *
	 * @return string
	 * @see tx_t3blog_modbase::getModuleContent()
	 */
	protected function getModuleContent() {
		return $this->getBlogRoll();
	}

	/**
	 * Returns a function bar for the record list
	 *
	 * @param array $row Data row
	 * @param int $previousUid Previous record uid
	 * @param int $nextUid Next record uid
	 * @return Function bar
	 */
	protected function getFunctions(array $row, $previousUid, $nextUid){

		// "edit" link:
		$params = '&edit[tx_t3blog_blogroll]['. $row['uid']. ']=edit';
		$title = $GLOBALS['LANG']->getLL('cm.edit', true);
		$cells .= '<a href="#" title="' . $title . '" onclick="' .
			htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->doc->backPath)). '">' .
			'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/edit2.gif',
			'width="16" height="16"') .
			' alt="' . $title . '" /></a>';

		// "hide/unhide" links:
		if ($row['hidden'])	{
			$params = '&data[tx_t3blog_blogroll]['. $row['uid'] . '][hidden]=0';
			$title = $GLOBALS['LANG']->getLL('cm.unhide', true);
            $image = 'button_unhide.gif';
		}
		else {
			$params = '&data[tx_t3blog_blogroll]['. $row['uid'] . '][hidden]=1';
			$title = $GLOBALS['LANG']->getLL('cm.hide', true);
            $image = 'button_hide.gif';
		}
		$cells .= '<a href="#" title="' . $title . '" onclick="' .
				htmlspecialchars('return jumpToUrl(\'' . $this->doc->issueCommand($params) . '\');'). '">' .
				'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/' . $image,
				'width="11" height="10"') . ' alt="'. $title . '" /></a>';

		// "delete" blogroll:
		$params = '&cmd[tx_t3blog_blogroll]['. $row['uid']. '][delete]=1';
		$title = $GLOBALS['LANG']->getLL('cm.delete', true);
		$prompt = sprintf($GLOBALS['LANG']->getLL('mess.delete'), htmlspecialchars($row['title']));
		$cells .= '<a href="#" onclick="' .
				htmlspecialchars('if (confirm('. $GLOBALS['LANG']->JScharCode($prompt) .
				')) {jumpToUrl(\'' . $this->doc->issueCommand($params). '\');} return false;') . '">' .
				'<img'. t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/garbage.gif', 'width="11" height="12"') .
				' alt="'. $title . '" /></a>';

		// Move up&down only if no forced sorting
		if (!$this->sortParameter) {

			// move up & down:
			if ($previousUid != 0) {

				// TODO Need other filters here!
				$params = '&cmd[tx_t3blog_blogroll][' . $row['uid'] . '][move]=' . $previousUid;
				$title = $GLOBALS['LANG']->getLL('moveUp', true);
				$cells .='<a href="#" title="' . $title . '" onclick="' .
						htmlspecialchars('return jumpToUrl(\'' . $this->doc->issueCommand($params) . '\');') . '">' .
						'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/button_up.gif',
						'width="11" height="10"') .' alt="' . $title . '" /></a>';
			}
			else {
				$cells .= '<img'. t3lib_iconWorks::skinImg($this->doc->backPath,
						'clear.gif', 'width="16" height="16"'). ' alt="" />';
			}

			if ($nextUid != 0) {
				$params='&cmd[tx_t3blog_blogroll][' . $row['uid'] . '][move]=' . $nextUid;
				$title = $GLOBALS['LANG']->getLL('moveDown', true);
				$cells .= '<a href="#" title="' . $title . '" onclick="' .
						htmlspecialchars('return jumpToUrl(\'' . $this->doc->issueCommand($params) . '\');').'">' .
						'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/button_down.gif',
						'width="11" height="10"') . ' alt="' . $title . '" /></a>';
			}
		}

		return $cells;
	}

	/**
	 * Creates the xfn names comma-separated
	 *
	 * @param string $xfnIds
	 * @return string
	 */
	protected function getXfnNames($xfnIds){
		$result = array();
		if($xfnIds) {
			$arrIds = explode(',', $xfnIds);
			foreach ($arrIds as $id) {
				$result[] = $GLOBALS['LANG']->getLL('xfn.I.' . $id);
			}
		}
		return implode(', ', $result);
	}

	/**
	 * Obtains information for new "Create new XYZ" link
	 *
	 * @return array
	 * @see tx_t3blog_modbase::getNewRecordLinkData()
	 */
	protected function getNewRecordLinkData() {
		return array(
			'icon' => t3lib_extMgm::extRelPath('t3blog'). 'icons/link_add.png',
			'iconSize' => '16x16',
			'table' => 'tx_t3blog_blogroll',
			'title' => $GLOBALS['LANG']->getLL('createNewBlogroll')
		);
	}

	/**
	 * Obtains number of blog rolls from the database
	 *
	 * @return int
	 */
	protected function getNumberOfBlogRolls() {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'count(*) as counter', 'tx_t3blog_blogroll',
			'pid=' . $this->id .
			t3lib_BEfunc::deleteClause('tx_t3blog_blogroll') .
			$this->getSearchSQLWhere('tx_t3blog_blogroll')
		);
		return $row['counter'];
	}

	/**
	 * Obtains number of items for this view.
	 *
	 * @return int
	 * @see tx_t3blog_modbase::getNumberOfItems()
	 */
	protected function getNumberOfItems() {
		return $this->numberOfBlogRolls;
	}

	/**
	 * Obtains URL format the record table header
	 *
	 * @return string
	 */
	protected function getUrlFormatForTableHeader() {
		$urlFields = array(
			'id' => $this->id
		);
		if ($this->currentPage > 1) {
			$urlFields['curPage'] = $this->currentPage;
		}
		$searchField = t3lib_div::_GP('search_field');
		if ($searchField) {
			$urlFields['search_field'] = $searchField;
		}

		$parameters = htmlspecialchars(t3lib_div::implodeArrayForUrl('', $urlFields));
		$parameters = str_replace('%', '%%', $parameters);
		return 'index.php?sort=%1$s&amp;sortDir=%2$s' . $parameters;
	}

	/**
	 * Obtains table header for blog roll listing
	 *
	 * @return string
	 */
	protected function getTableHeader() {
		$urlFormat = $this->getUrlFormatForTableHeader();
		$upIcon = '<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/redup.gif', 'width="11" height="12"') . ' title="ASC" alt="" />';
		$downIcon = '<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/reddown.gif', 'width="11" height="12"') . ' title="ASC" alt="" />';
		return '<table cellspacing="0" cellpadding="0" class="recordlist">
			<tr>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('title', true) . '</b>
					<a href=' . sprintf($urlFormat, 'title', 'ASC') . '>' . $upIcon . '</a>
					<a href=' . sprintf($urlFormat, 'title', 'DESC') . '>' . $downIcon . '</a>
				</th>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('description', true) . '</b>
					<a href=' . sprintf($urlFormat, 'description', 'ASC') . '>' . $upIcon . '</a>
					<a href=' . sprintf($urlFormat, 'description', 'DESC') . '>' . $downIcon . '</a>
				</th>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('url', true) . '</b>
					<a href=' . sprintf($urlFormat, 'url', 'ASC') . '>' . $upIcon . '</a>
					<a href=' . sprintf($urlFormat, 'url', 'DESC') . '>' . $downIcon . '</a>
				</th>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('xfn', true) . '</b>
			</th>
			<th><b>' . $GLOBALS['LANG']->getLL('functions', true) . '</b></th>
		</tr>';
	}

	/**
	 * Obtains table rows with blog roll items
	 *
	 * @return string
	 */
	protected function getBlogRollList() {
		$start = ($this->currentPage-1)*$this->numberOfItemsPerPage;
		$limit = $start . ',' . $this->numberOfItemsPerPage;
		$dataRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*', 'tx_t3blog_blogroll',
			'pid=' . $this->id .
			$this->getSearchSQLWhere('tx_t3blog_blogroll') .
			t3lib_BEfunc::deleteClause('tx_t3blog_blogroll'),
			'',
			$this->getListSortClause(),
			$limit
		);

		$rows = array();
		$dataRowsCount = count($dataRows);
		for ($i = 0; $i < $dataRowsCount; $i++) {
			$oddEven = ((count($rows) % 2) == 0 ? 'even' : 'odd');
			$xfnNames = $this->getXfnNames($dataRows[$i]['xfn']);
			if ($i == 0) {
				// No previous uid
				$previousUid = 0;
			}
			elseif ($i == 1) {
				// 1st position on the page
				$previousUid = $dataRows[$i]['pid'];
			}
			else {
				// Insert after the item, which is this item minus 2 (TCEmain strange language)
				$previousUid = -$dataRows[$i - 2]['uid'];
			}
			if ($i == $dataRowsCount - 1) {
				// No previous uid
				$nextUid = 0;
			}
			else {
				// Insert after the item, which is this item minus 2 (TCEmain strange language)
				$nextUid = -$dataRows[$i + 1]['uid'];
			}
			$description = htmlspecialchars(t3lib_div::fixed_lgd_cs($dataRows[$i]['description'], 50));
			$url = $dataRows[$i]['url'];
			if (!preg_match('/^https?:\/\//i', $url)) {
				$url = 'http://' . $url;
			}
			$rows[] = '<tr class="' . $oddeven . '">
				<td>' . htmlspecialchars(t3lib_div::fixed_lgd_cs($dataRows[$i]['title'], 80)) . '</td>
				<td>'. ($description ? $description : '&nbsp;') . ' </td>
				<td><a href="'. htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($url, 50)) . '</a></td>
				<td title="'. htmlspecialchars($xfnNames) . '">' . ($xfnNames ? htmlspecialchars(t3lib_div::fixed_lgd_cs($xfnNames, 50)) : '&nbsp;') . '</td>
				<td>' . $this->getFunctions($dataRows[$i], $previousUid, $nextUid) . '</td>
			</tr>';
		}

		return implode(chr(10), $rows);
	}

	/**
	 * Gets full blog roll table
	 *
	 * @return string
	 */
	protected function getBlogRoll() {
		return $this->getTableHeader() . $this->getBlogRollList() . '</table>';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod5/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod5/index.php']);
}

// make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module5');
$SOBE->init();

// include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>