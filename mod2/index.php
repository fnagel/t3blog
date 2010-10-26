<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Snowflake Productions Gmbh <typo3@snowflake.ch>
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

require_once (t3lib_extMgm::extPath('t3blog', 'lib/class.tx_t3blog_sendtrackback.php'));

require_once(t3lib_extMgm::extPath('t3blog', 'lib/class.tx_t3blog_modbase.php'));
$GLOBALS['LANG']->includeLLFile('EXT:t3blog/mod2/locallang.xml');

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * returns the Blog entry administration
 *
 * @author		Snowflake Productions Gmbh <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class tx_t3blog_module2 extends tx_t3blog_modbase {

	/**
	 * Total number of available blog posts that match all search criterias
	 *
	 * @var int
	 */
	protected $numberOfPosts;

	/**
	 * Initializes the module.
	 *
	 * @return void
	 */
	public function init() {
		$this->defaultSort = 'date DESC';
		$this->validSortFields = 'date,title,category';

		parent::init();

		if ($this->hasAccess()) {
			$this->filter = $this->getCategoryFilter() . $this->getSearchSQLWhere('tx_t3blog_post');
			$this->numberOfPosts = $this->getMaximumNumberOfPosts();
		}
	}

	/**
	 * Obtains the content for this module
	 *
	 * @return string
	 * @see tx_t3blog_modbase::getModuleContent()
	 */
	protected function getModuleContent() {
		return $this->getCategorySelector() . $this->getPosts();
	}

	/**
	 * Returns a function bar for the record list
	 *
	 * @param string $table Table name
	 * @param array $row Data row
	 * @return string Function bar
	 */
	protected function getFunctions($table, array $row){
			// "Edit" link:
		$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
		$title = $GLOBALS['LANG']->getLL('cm.edit', true);
		$cells .= '<a href="#" title="' . $title .
			'" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->doc->backPath)) . '">' .
			'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog') .
			'icons/page_edit.png', 'width="16" height="16"') . ' alt="' . $title . '" />' .
			'</a>';

			// "Hide/Unhide" links:
		if ($row['hidden'])	{
			$params = '&data[' . $table . '][' . $row['uid'] . '][' . 'hidden' . ']=0';
			$title = $GLOBALS['LANG']->getLL('cm.unhide', true);
			$cells .= '<a href="#" title="' . $title . '" onclick="' . htmlspecialchars('return jumpToUrl(\'' .
				$GLOBALS['SOBE']->doc->issueCommand($params) . '\');') . '">' .
				'<img' . t3lib_iconWorks::skinImg($this->doc->backPath,
				'gfx/button_unhide.gif', 'width="11" height="10"') .
				' alt="' . $title . '" /></a>';
		}
		else {
			$params = '&data[' . $table . '][' . $row['uid'] . '][' . 'hidden' . ']=1';
			$title = $GLOBALS['LANG']->getLL('cm.hide', true);
			$cells .= '<a href="#" title="' . $title . '" onclick="' . htmlspecialchars('return jumpToUrl(\'' .
				$GLOBALS['SOBE']->doc->issueCommand($params) . '\');') . '">' .
				'<img' . t3lib_iconWorks::skinImg($this->doc->backPath,
				'gfx/button_hide.gif', 'width="11" height="10"') .
				' alt="' . $title . '" /></a>';
		}

			// "Delete" link:
		$params = '&cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
		$title = $GLOBALS['LANG']->getLL('cm.delete', true);
		$prompt = sprintf($GLOBALS['LANG']->getLL('mess.delete'), htmlspecialchars($row['title']));
		$cells .= '<a href="#" title="' . $title . '" onclick="' . htmlspecialchars('if (confirm(' .
			$GLOBALS['LANG']->JScharCode($prompt) .
			')) {jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params) .
			'\');} return false;') . '">' .
			'<img' . t3lib_iconWorks::skinImg($this->doc->backPath,
			'gfx/garbage.gif', 'width="11" height="12"') . ' alt="' . $title . '" />' .
			'</a>';

			// Add comment link:
		$title = $GLOBALS['LANG']->getLL('addComment', true);
		$cells .= '<a href="#" title="' . $title . '" onclick="' . htmlspecialchars(
			t3lib_BEfunc::editOnClick('&edit[tx_t3blog_com][' . $this->id .
			']=new&defVals[tx_t3blog_com][fk_post]=' . $row['uid'], $this->doc->backPath)) . '">' .
			'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog') .
			'icons/comment_add.png', 'width="16" height="16"') .
			' alt="' . $title . '" /></a>';

			// Preview link:
		$title = $GLOBALS['LANG']->getLL('cm.view', true);
		$cells .= '<a href="#" title="' . $title . '" onclick="' . htmlspecialchars(
			$this->getPostViewURL($row['uid'], $row['date'])) . '">' .
			'<img' . t3lib_iconWorks::skinImg($this->doc->backPath,
			t3lib_extMgm::extRelPath('t3blog') . 'icons/magnifier.png',
			'width="16" height="16"') . ' alt="' . $title . '" /></a>';

		return $cells;
	}

	/**
	 * Creates a URL to the blog post for viewing from BE.
	 *
	 * @param int $postUid
	 * @param int $postDate
	 * @return string
	 */
	protected function getPostViewURL($postUid, $postDate) {
		$date = getdate($postDate);
		$url = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
			'blogList' => array(
				'year' => $date['year'],
				'month' => $date['mon'],
				'day' => $date['mday'],
				'showUid' => $postUid
			)
		));
		return t3lib_BEfunc::viewOnClick($this->id, $this->doc->backPath, '', $url . '#blogentry');
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
		if (($cat = t3lib_div::_GP('cat'))) {
			$urlParameters['cat'] = $cat;
		}
		$parameters = t3lib_div::implodeArrayForUrl('', $urlParameters);
		$parameters = str_replace('%', '%%', $parameters);
		return 'index.php?sort=%1$s&sortDir=%2$s' . $parameters;
	}

	/**
	 * Creates table header for the module
	 *
	 * @return string
	 */
	protected function createTableHeader() {
		$urlFormat = $this->getUrlFormatForHeader();
		$header = '<tr>' .
				$this->getHeaderWithSorting('dateAndTime', 'date', $urlFormat) .
				$this->getHeaderWithSorting('title', 'title', $urlFormat) . '
				<th>
					'.$GLOBALS['LANG']->getLL('category').'
				</th>' .
				$this->getHeaderWithSorting('nrOfComments', 'comments', $urlFormat) . '
				<th>
					' . $GLOBALS['LANG']->getLL('functions') . '
				</th>
			</tr>';
		return $header;
	}

	/**
	 * Creates one sortable header
	 *
	 * @param string $fieldLabel
	 * @param string $field
	 * @param string $urlFormat
	 * @return string
	 */
	protected function getHeaderWithSorting($fieldLabel, $field, $urlFormat) {
		$header = '<th>
				' . $GLOBALS['LANG']->getLL($fieldLabel) . '
				<a href="' . htmlspecialchars(sprintf($urlFormat, $field, 'ASC')) .'">
					<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="7" height="4"').' title="ASC" alt="" />
				</a>
				<a href="' . htmlspecialchars(sprintf($urlFormat, $field, 'DESC')) .'">
					<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="7" height="4"').' title="DESC" alt="" />
				</a>
			</th>';
		return $header;
	}

	/**
	 *	Obtains a part of SQL where statement depending on the request arguments
	 *
	 *	@return string
	 */
	protected function getCategoryFilter() {
		$categoryName = t3lib_div::_GP('cat');
		if ($categoryName) {
			$filter = ' AND tx_t3blog_cat.catname='.
					$GLOBALS['TYPO3_DB']->fullQuoteStr($categoryName, 'tx_t3blog_cat');
		}
		else {
			$categoryId = intval(t3lib_div::_GP('linkCat'));
			if ($categoryId) {
				$filter = ' AND tx_t3blog_post_cat_mm.uid_foreign=' . $categoryId;
			}
		}
		if ($filter != '') {
			$filter = ' AND tx_t3blog_post.uid IN (SELECT uid_local
				FROM tx_t3blog_cat, tx_t3blog_post_cat_mm
				WHERE tx_t3blog_cat.uid = tx_t3blog_post_cat_mm.uid_foreign' .
				$filter . ')';
		}

		return $filter;
	}

	/**
	 * Generates category selector for the module
	 *
	 * @return string
	 */
	protected function getCategorySelector() {
		$urlFormat = $this->getUrlFormatForCategorySelector();
		$options = array(
			'<option value="' .
				htmlspecialchars(sprintf($urlFormat, '')) .
				'">' . $GLOBALS['LANG']->getLL('filterByCat', true) . '</option>'
		);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('catname',
			'tx_t3blog_cat',
			'pid=' . $this->id .
			t3lib_BEfunc::deleteClause('tx_t3blog_cat'), '', 'catname');

		$currentCategory = t3lib_div::_GP('cat');
		while (false != ($data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			if ($data['catname'] == $currentCategory) {
				$selected = ' selected="selected"';
			}
			else {
				$selected = '';
			}

			// Populate the form with category names
			$options[] = '<option value="' .
				htmlspecialchars(sprintf($urlFormat, rawurlencode($data['catname']))) .
				'"' . $selected. '>' .
				htmlspecialchars($data['catname']) .
				'</option>';
		}
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

		$selectorCode = '<select onchange="window.location.href=this.options[this.selectedIndex].value" style="margin-bottom: 7px">' .
			implode('', $options) .
			'</select>';

		return $selectorCode;
	}

	/**
	 * Creates URL format string for category selector. This function takes into
	 * account various request parameters to build the URL.
	 *
	 * @return string
	 */
	protected function getUrlFormatForCategorySelector() {
		$urlParameters = array(
			'id' => $this->id
		);
		if ($this->currentPage > 1) {
			$urlParameters['curPage'] = $this->currentPage;
		}
		if (($searchField = t3lib_div::_GP('search_field'))) {
			$urlParameters['search_field'] = $searchField;
		}
		if ($this->sortParameter != '') {
			$urlParameters[$this->sortParameterName] = $this->sortParameter;
		}
		if ($this->sortDirectionParameter) {
			$urlParameters[$this->sortDirectionParameterName] = $this->sortDirectionParameter;
		}
		$parameters = substr(t3lib_div::implodeArrayForUrl('', $urlParameters), 1);
		$parameters = str_replace('%', '%%', $parameters);
		return 'index.php?' . $parameters . '&cat=%1$s';
	}

	/**
	 * Obtains a maximum number of posts for this filter
	 *
	 * @return int
	 */
	protected function getMaximumNumberOfPosts() {
		$tables = 'tx_t3blog_post';
		$where = 'tx_t3blog_post.pid=' . $this->id .
			t3lib_BEfunc::deleteClause('tx_t3blog_post');
		if ($this->filter) {
			// FIXME Internal knowledge is bad!!!
			$tables .= ', tx_t3blog_post_cat_mm, tx_t3blog_cat';
			$where .= $this->filter .
				t3lib_BEfunc::deleteClause('tx_t3blog_post');
		}
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'count(distinct tx_t3blog_post.uid) as counter',
			$tables, $where);
		return is_array($row) ? intval($row['counter']) : 0;
	}

	/**
	 * Generates a list if posts
	 *
	 * @return string
	 */
	protected function getPostList() {
		$start = ($this->currentPage-1)*$this->numberOfItemsPerPage;
		$limit = $start . ',' . $this->numberOfItemsPerPage;

		// FIXME Need proper enableFields and deleteClause for all tables!
		$databaseRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'distinct tx_t3blog_post.uid as uid, tx_t3blog_post.title as title, tx_t3blog_post.date as date, tx_t3blog_post.hidden as hidden',
			'tx_t3blog_post',
			'tx_t3blog_post.deleted=0 AND tx_t3blog_post.pid=' . $this->id . $this->filter,
			'uid',
			$this->getListSortClause(),
			$limit, 'uid'
		);

		$rows = array();
		if (count($databaseRows) > 0) {
			$postIdList = array_keys($databaseRows);
			$commentCounts = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('fk_post, COUNT(*) AS comments', 'tx_t3blog_com',
				'fk_post IN (' . implode(',', $postIdList) . ') AND deleted=0', 'fk_post', '', '', 'fk_post');
			$categoryNames = $this->getCategoriesForPosts($postIdList);

			foreach ($databaseRows as $data) {
				$oddEven = ((count($rows) % 2) == 0 ? 'even' : 'odd');

				// only if not hidden
				if($data['hidden'] == 0){
					$this->sendTrackbacks($this->id,$data['uid']);
				}

				// FIXME Hard-coded date format
				$rows[] = '<tr class="' . $oddEven . '">
						<td>' . date('d.m.y H:i:s', $data['date']) . '</td>
						<td>' . htmlspecialchars($data['title']) . '</td>
						<td>' . htmlspecialchars($this->getCategoryNamesFromList($data['uid'], $categoryNames)) . '</td>
						<td><a href="../mod3/index.php?linkCom=' . $data['uid'] . '&amp;id=' . $this->id . '" title="' . $GLOBALS['LANG']->getLL('seeComments', true) . '">' . intval($commentCounts[$data['uid']]) . ' <img' . t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog') . 'icons/comments.png','width="16" height="16"').' alt="' . $GLOBALS['LANG']->getLL('seeComments', true) . '" /></a></td>
						<td>'. $this->getFunctions('tx_t3blog_post', $data) . ' <!-- trackbacks sent: '. $trackbacksSent . '--></td>
					</tr>';
			}
		}

		return implode(chr(10), $rows);
	}

	/**
	 * Obtains category names for the given post from the list.
	 *
	 * @param int $postId
	 * @param array $postList
	 * @return string
	 */
	protected function getCategoryNamesFromList($postId, array $categoryNames) {
		$result = array();
		foreach ($categoryNames as $data) {
			if ($data['uid'] == $postId) {
				$result[] = $data['catname'];
			}
		}
		return implode(', ', $result);
	}

	/**
	 * Obtains all category names for the given post list.
	 *
	 * @param array $postList
	 * @return array
	 */
	protected function getCategoriesForPosts(array $postList) {
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('tx_t3blog_post_cat_mm.uid_local AS uid,catname',
			'tx_t3blog_cat,tx_t3blog_post_cat_mm',
			'tx_t3blog_cat.uid=tx_t3blog_post_cat_mm.uid_foreign AND ' .
			'tx_t3blog_post_cat_mm.uid_local IN (' . implode(',', $postList) . ')' .
			t3lib_BEfunc::deleteClause('tx_t3blog_cat'));
		return $result;
	}

	/**
	 * Gets category names for a post
	 *
	 * @param int $blogUid
	 * @return Space-separated category names
	 */
	public function getCategoryNames($postUid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_t3blog_cat.catname',
			'tx_t3blog_cat,tx_t3blog_post_cat_mm',
			'tx_t3blog_cat.uid=tx_t3blog_post_cat_mm.uid_foreign AND ' .
			'tx_t3blog_post_cat_mm.uid_local=' . $postUid .
			t3lib_BEfunc::deleteClause('tx_t3blog_cat'));
		$list = array();
		while (false != ($data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$list[] = $data['catname'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return implode(', ', $list);
	}

	/**
	 * Obtains information for new "Create new XYZ" link
	 *
	 * @return array
	 * @see tx_t3blog_modbase::getNewRecordLinkData()
	 */
	protected function getNewRecordLinkData() {
		return array(
			'icon' => t3lib_extMgm::extRelPath('t3blog'). 'icons/page_add.png',
			'iconSize' => '16x16',
			'table' => 'tx_t3blog_post',
			'title' => $GLOBALS['LANG']->getLL('createNewBlogPost')
		);
	}

	/**
	 * Generates a complete posts list with header
	 *
	 * @return string
	 */
	protected function getPosts() {
		$result = '<table cellspacing="0" cellpadding="0" class="recordlist">';
		$result .= $this->createTableHeader();
		$result .= $this->getPostList();
		$result .= '</table>';

		return $result;
	}

	/**
	 * Obtains a total number of items for this view
	 *
	 * @return int
	 * @see tx_t3blog_modbase::getNumberOfItems()
	 */
	protected function getNumberOfItems() {
		return $this->numberOfPosts;
	}

	/**
	 * Obtains elements for the record filter display
	 *
	 * @return string
	 * @see tx_t3blog_modbase::getElementsForCurrentSettings()
	 */
	protected function getElementsForRecordFilterDisplay() {
		$elements = parent::getElementsForRecordFilterDisplay();

		$categoryName = trim(t3lib_div::_GP('cat'));
		if ($categoryName) {
			$elementCount = count($elements);
			if ($elementCount > 1) {
				$categoryName = t3lib_div::fixed_lgd_cs($categoryName, 20);
			}
			elseif ($elementCount == 1) {
				$categoryName = t3lib_div::fixed_lgd_cs($categoryName, 40);
			}
			$elements[] = array(
				'link' => $this->getCurrentUrlWithoutParameters('cat'),
				'title' => $GLOBALS['LANG']->getLL('filterCategory'),
				'value' => $categoryName
			);
		}

		return $elements;
	}

	/**
	 * Sends new trackbacks for the given blog
	 *
	 * @return boolean true if one or more trackbacks were sent
	 */
	protected function sendTrackbacks($blogUid,$uid) {
		$trackbackSender = t3lib_div::makeInstance('tx_t3blog_sendtrackback');
		/* @var $trackbackSender tx_t3blog_sendtrackback */
		return $trackbackSender->sendTrackbacks($blogUid,$uid);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod2/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod2/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module2');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>