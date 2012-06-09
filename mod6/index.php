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

/*
 * $Id: index.php 30926 2010-03-09 13:19:37Z dmitry $
 */
unset($MCONF);
require_once('conf.php');
require_once($GLOBALS['BACK_PATH'] . 'init.php');
require_once($GLOBALS['BACK_PATH'] . 'template.php');

require_once(t3lib_extMgm::extPath('t3blog', 'lib/class.tx_t3blog_modbase.php'));

$LANG->includeLLFile('EXT:t3blog/mod6/locallang.xml');

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * Returns the Trackback administration
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class tx_t3blog_module6 extends tx_t3blog_modbase {

	/**
	 * Number of trackbacks for this view
	 *
	 * @var int
	 */
	protected $numberOfTrackbacks;

	/**
	 * Initializes the module.
	 *
	 * @return void
	 */
	public function init() {
		$this->defaultSort = 'crdate DESC';
		$this->validSortFields = 'crdate,title,text,blogname,post_title';

		parent::init();

		if ($this->hasAccess())	{
			$this->numberOfTrackbacks = $this->getNumberOfTrackbacks();
		}
	}

	/**
	 * Obtains the content for this module
	 *
	 * @return string
	 * @see tx_t3blog_modbase::getModuleContent()
	 */
	protected function getModuleContent() {
		return $this->getTrackbacks();
	}

	/**
	 * Obtains a tital number of items for this view
	 *
	 * @return int
	 * @see tx_t3blog_modbase::getNumberOfItems()
	 */
	protected function getNumberOfItems() {
		return $this->numberOfTrackbacks;
	}

	/**
	 * Returns a function bar for the record list
	 *
	 * @param 	string 			$table: Table name
	 * @param 	string 			$row: Datarow
	 * @return 	function bar
	 */
	function getFunctions($table, $row){

		// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		$params	= '&edit['. $table. ']['. $row['uid']. ']=edit';
		$editTitle = $GLOBALS['LANG']->getLL('cm.edit', true);
		$cells .= '<a href="#" title="' . $editTitle . '" onclick="' .
					htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->doc->backPath)) . '">' .
					'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,
					t3lib_extMgm::extRelPath('t3blog') . 'icons/comment_edit.png',
					'width="16" height="16"') . ' alt="' . $editTitle . '" />'.
					'</a>';

		// "Hide/Unhide" links:
		if ($row['hidden'])	{
			$params	= '&data['. $table. ']['. $row['uid']. ']['. 'hidden'. ']=0';
			$cells .= $this->makeCells($params, 'button_unhide.gif', 'cm.unhide');

		} else {

			$params	=	'&data['. $table.']['. $row['uid']. ']['. 'hidden'. ']=1';
			$cells .= 	$this->makeCells($params, 'button_hide.gif', 'cm.hide');
		}

		// "Delete" link:
		$deleteMessage = sprintf($GLOBALS['LANG']->getLL('mess.delete'), htmlspecialchars($row['title']));
		$params	= '&cmd['.$table.']['.$row['uid'].'][delete]=1';
		$deleteTitle = $GLOBALS['LANG']->getLL('cm.delete', true);
		$cells .= '<a href="#" title="' . $deleteTitle . '" onclick="' .
					htmlspecialchars('if (confirm(' .
					$GLOBALS['LANG']->JScharCode($deleteMessage) .
					')) {jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params) .
					'\');} return false;').'">'.
					'<img'.
					t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/garbage.gif',
					'width="11" height="12"') .
					' alt="' . $deleteTitle . '" />'.
					'</a>';

		return $cells;
	}

	/**
	 * Makes specific links with icons
	 * @author 	Thomas Imboden <timboden@snowflake.ch>
	 *
	 * @param string $params Parameters for this icon
	 * @param string $icon Name of the icon inclusive datatype
	 * @param string $alt Alt tag language code
	 *
	 * @return	string	link with images
	 */
	protected function makeCells($params, $icon, $alt) {
		$title = $GLOBALS['LANG']->getLL($alt, true);
		return '<a href="#" title="' . $title . '" onclick="' .
				htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params) .'\');') . '">' .
					'<img '. t3lib_iconWorks::skinImg($this->doc->backPath,
					t3lib_extMgm::extRelPath('t3blog') . 'icons/' . $icon,' width="18" height="16"') .
					' alt="' . $title . '" /></a>';
	}

	/**
	 * Obtains information for new "Create new XYZ" link
	 *
	 * @return array
	 * @see tx_t3blog_modbase::getNewRecordLinkData()
	 */
	protected function getNewRecordLinkData() {
		return array(
			'icon' => t3lib_extMgm::extRelPath('t3blog'). 'icons/comment_add.png',
			'iconSize' => '16x16',
			'table' => 'tx_t3blog_trackback',
			'title' => $GLOBALS['LANG']->getLL('createNewTrackback')
		);
	}

	/**
	 * Creates additional SQL WHERE statement if we have to limit trackback by
	 * certain post
	 *
	 * @return string
	 */
	protected function getTrackbackLimitByPost() {
		$trackbackUid = intval(t3lib_div::_GP('linkTra'));
		return ($trackbackUid ? ' AND tx_t3blog_post.uid=' . $trackbackUid : '');
	}

	/**
	 * Creates SQL where statement for trackbacks
	 *
	 * @return string
	 */
	protected function getSQLWhereForTrackbacks() {
		return 'tx_t3blog_trackback.postid=tx_t3blog_post.uid' .
			' AND tx_t3blog_trackback.pid=' . $this->id .
			$this->getTrackbackLimitByPost() .
			$this->getSearchSQLWhere('tx_t3blog_trackback') .
			t3lib_BEfunc::deleteClause('tx_t3blog_trackback') .
			t3lib_BEfunc::deleteClause('tx_t3blog_post');
	}

	/**
	 * Obtains a total visible number of trackbacks for this page
	 *
	 * @return int
	 */
	protected function getNumberOfTrackbacks() {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'COUNT(DISTINCT(tx_t3blog_trackback.uid)) as counter',
			'tx_t3blog_post,tx_t3blog_trackback',
			$this->getSQLWhereForTrackbacks()
		);
		return $row['counter'];
	}

	/**
	 * Obtains a list of trackbacks
	 *
	 * @return string
	 */
	protected function getTrackbackList() {
		$start = ($this->currentPage - 1)*$this->numberOfTrackbacks;
		$limit = $start . ',' . $this->numberOfTrackbacks;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'distinct tx_t3blog_trackback.uid as uid,tx_t3blog_trackback.postid as postid, ' .
				'tx_t3blog_trackback.title as title, tx_t3blog_trackback.text as text, ' .
				'tx_t3blog_trackback.blogname as blogname, ' .
				'tx_t3blog_post.title as post_title, ' .
				'tx_t3blog_trackback.hidden as hidden, ' .
				'tx_t3blog_trackback.tstamp as tstamp, tx_t3blog_trackback.crdate as crdate',
			'tx_t3blog_post,tx_t3blog_trackback',
			$this->getSQLWhereForTrackbacks(),
			'',
			$this->getListSortClause(),
			$limit
		);

		$rows = array();
		while (false != ($data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$oddEven = ((count($rows) % 2) == 0 ? 'even' : 'odd');

			// FIXME Fixed date format
			$id = $data['uid'];
			$rows[] = '<tr class="' . $oddeven . '">
				<td title="id=' . $id . '">' . date('d.m.y H:i:s', $data['crdate']) . '</td>
				<td title="id=' . $id . '">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($data['title'], 20)) . '</td>
				<td title="id=' . $id . '">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($data['text'], 50)) . '</td>
				<td title="id=' . $id . '">' . htmlspecialchars($data['blogname']) . '</td>
				<td title="id=' . $data['postid'] . '">' . htmlspecialchars($data['post_title']) . '</td>
				<td>' . $this->getFunctions('tx_t3blog_trackback', $data) . '</td>
			</tr>';
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return implode(chr(10), $rows);
	}


	/**
	 * Creates URL format string for header. This function takes into account
	 * various request parameters to build the URL.
	 *
	 * @return string
	 */
	protected function getUrlFormatForHeader() {
		$urlParameters = array(
			'id' => $this->id
		);
		if ($this->currentPage > 1) {
			$urlParameters['curPage'] = $this->currentPage;
		}
		if (($searchField = t3lib_div::_GP('search_field'))) {
			$urlParameters['search_field'] = $searchField;
		}
		$parameters = t3lib_div::implodeArrayForUrl('', $urlParameters);
		$parameters = str_replace('%', '%%', $parameters);
		return 'index.php?' . $this->sortParameterName . '=%1$s&' . $this->sortDirectionParameterName . '=%2$s' . $parameters;
	}

	/**
	 * Generates table header for trackbacks
	 *
	 * @return void
	 */
	protected function getTableHeader() {
		$urlFormat = $this->getUrlFormatForHeader();
		$iconAsc = '<img' . t3lib_iconWorks::skinImg($this->doc->backPath,
						'gfx/redup.gif', 'width="11" height="12"') .' title="ASC" alt="" />';
		$iconDesc = '<img' . t3lib_iconWorks::skinImg($this->doc->backPath,
						'gfx/reddown.gif', 'width="11" height="12"') .' title="ASC" alt="" />';
		$result = '<table cellspacing="0" cellpadding="0" class="recordlist">
			<tr>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('dateAndTime', true) . '</b>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'crdate', 'ASC')) . '">' . $iconAsc . '</a>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'crdate', 'DESC')) . '">' . $iconDesc . '</a>
				</th>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('title', true) . '</b>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'title', 'ASC')) . '">' . $iconAsc . '</a>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'title', 'DESC')) . '">' . $iconDesc . '</a>
				</th>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('text', true) . '</b>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'text', 'ASC')) . '">' . $iconAsc . '</a>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'text', 'DESC')) . '">' . $iconDesc . '</a>
				</h>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('blogname', true) . '</b>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'blogname', 'ASC')) . '">' . $iconAsc . '</a>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'blogname', 'DESC')) . '">' . $iconDesc . '</a>
				</th>
				<th>
					<b>' . $GLOBALS['LANG']->getLL('post', true) . '</b>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'post_title', 'ASC')) . '">' . $iconAsc . '</a>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'post_title', 'DESC')) . '">' . $iconDesc . '</a>
			</th>
			<th><b>' . $GLOBALS['LANG']->getLL('functions', true) . '</b></th>
		</tr>';
		return $result;
	}

	/**
	 * Obtains a table with trackbacks
	 *
	 * @return string
	 */
	protected function getTrackbacks() {
		return $this->getTableHeader() . $this->getTrackbackList() . '</table>';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod6/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod6/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module6');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>