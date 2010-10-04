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
class listFunctions extends blogList {
	var $prefixId      = 'listFunctions';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogList/class.listFunctions.php';	// Path to this script relative to the extension dir.
	var $pi_checkCHash = false;
	var $prevPrefixId = 'blogList';
	var $localPiVars;
	var $globalPiVars;

	protected $fields = array(
		'tx_t3blog_post.uid',
		'tx_t3blog_post.tagClouds',
		'tx_t3blog_post.title',
		'tx_t3blog_post.author',
		'tx_t3blog_post.date',
		'tx_t3blog_post.cat',
		'tx_t3blog_post.number_views',
		'be_users.email',
		'be_users.uid AS useruid',
		'be_users.username',
		'be_users.realName',
		'be_users.tx_t3blog_avatar'
	);
	protected $tables = array(
		'tx_t3blog_post',
		'be_users /*! FORCE INDEX (PRIMARY) */'
	);
	protected $tagTitle = '';
	protected $back = '';

	/**
	 * The main method of the PlugIn
	 * @author 	snowflake <typo3@snowflake.ch>
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prevPrefixId];
		$this->conf = $conf;

		$this->init();

		$data = array(
			'pageBrowser' => $this->getPageBrowser(),
			'listItems' => $this->getListItems()
		);

		return t3blog_div::getSingle($data, 'list', $this->conf);
	}

	/**
	 * Creates the page browser.
	 *
	 * @return string
	 */
	protected function getPageBrowser() {
		return t3blog_div::getPageBrowser(
			$this->getNumberOfListItems(),
			'tx_t3blog_post', $this->prefixId, array(
				'previous' => $this->pi_getLL('previous'),
				'next' => $this->pi_getLL('next')
			), $this->localPiVars, $this->conf,
			$this->conf['numberOfRecords'], $this->conf['maxPages']);
	}

	/**
	 * Creates where condition for the query. This function may modify a list
	 * of tables! Call it before $this->getTables()!
	 *
	 * @return string
	 */
	protected function getWhere() {
		$where = 'tx_t3blog_post.pid=' . t3blog_div::getBlogPid();
		$where .= ' AND be_users.uid = tx_t3blog_post.author';
		$where .= $this->localcObj->enableFields('tx_t3blog_post');
		$where .= $this->getDateCondition();
		$where .= $this->getAuthorCondition();
		$where .= $this->getCategoryCondition();
		$where .= $this->getTagCondition();
		$where .= $this->getSearchCondition();

		return $where;
	}

	/**
	 * Obtains a number of items in the list.
	 *
	 * @return int
	 */
	public function getNumberOfListItems() {
		$where = $this->getWhere();
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'COUNT(tx_t3blog_post.uid) AS t',
			implode(',', $this->tables),
			$where
		);
		$result = is_array($row) ? $row['t'] : 0;
		return intval($result);
	}

	/**
	 * Obtains database rows for the list.
	 *
	 * @return array
	 * @deprecated There is no need to use this function at all.
	 */
	public function getDatabaseRowsForList() {
		$where = $this->getWhere();
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			implode(',', $this->fields),
			implode(',', $this->tables),
			$where,
			$this->getGroupBy(),
			$this->getOrderBy(),
			$this->getListItemsLimit()
		);
	}

	/**
	 * Obtainst posts closest to the current. The result is an array with the
	 * first member is a previous post and the second member is a next post.
	 * Any of these two can be null.
	 *
	 * @param int $currentPostId
	 * @return array
	 */
	public function getClosestPosts($currentPostId) {
		$sorting = $this->getSortingForPost(intval($currentPostId));

		$result = array(
			$this->getPostBySorting('<', $sorting),
			$this->getPostBySorting('>', $sorting),
		);
		return $result;
	}

	/**
	 * Obtains sorting value for the given post id.
	 *
	 * @param string $postId
	 * @return int
	 */
	protected function getSortingForPost($postId) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'crdate', 'tx_t3blog_post',
			'uid=' . $postId, '', '', '1');
		return is_array($row) ? intval($row['crdate']) : 0;
	}

	/**
	 * Obtains a post by sorting operator and value.
	 *
	 * @param string $operator '<' or '>'
	 * @param int $sorting
	 * @return array
	 */
	protected function getPostBySorting($operator, $sorting) {
		$where = $this->getWhere();
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tx_t3blog_post.uid,tx_t3blog_post.crdate,tx_t3blog_post.title',
			implode(',', $this->tables),
			'tx_t3blog_post.crdate' . $operator . $sorting . ' AND ' . $where,
			'tx_t3blog_post.crdate ' . ($operator == '<' ? 'DESC' : 'ASC'),
			'', '1');
		return $row;
	}



	/**
	 * Lists the blog entries and prepares the data.
	 * possible piVars: groupBy, orderBy, orderByDir, catIn, datefrom, dateto, pointer
	 *
	 * @author 	snowflake <typo3@snowflake.ch>
	 *
	 * @param 	boolean 	$justNumOfItems
	 * @param 	boolean 	$justItemArray
	 *
	 * @return string with the content html.
	 */
	function getListItems($justNumOfItems = false, $justItemArray = false){
		if ($justNumOfItems) {
			die('Please, use getNumberOfListItems() instead of getListItems() to get number of items from now on.');
		}
		elseif ($justItemArray) {
			die('Please, use getDatabaseRowsForList() instead of getListItems() to get rows from now on.');
		}

		$result = $this->getListItemHeader();
		$entryCount = 0;

		$where = $this->getWhere();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			implode(',', $this->fields),
			implode(',', $this->tables),
			$where,
			$this->getGroupBy(),
			$this->getOrderBy(),
			$this->getListItemsLimit()
		);
		while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$result .= $this->renderSingleListPost($row, $entryCount);
			$entryCount++;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		// if no results have been found and any kind of filters have been sent...
		if ($entryCount == 0 && (count($this->globalPiVars) > 0 || count($this->localPiVars) > 0)) {
			$result .= t3blog_div::getSingle(array('text'=>$this->pi_getLL('noResult')), 'noResultWrap', $this->conf);
		}

		return $result;
	}

	/**
	 * Renders a single post for the list view.
	 *
	 * @param array $row
	 * @param int $entryCount
	 * @return string
	 */
	protected function renderSingleListPost(array $row, $entryCount) {
		$contentUidArray = array(); $hasDivider = false; $textBeforeDivider = '';
		t3blog_div::fetchContentData($row['uid'], $contentUidArray, $hasDivider, $textBeforeDivider);

		$data = array(
			'uid'			=> $row['uid'],
			'blogPid'		=> t3blog_div::getBlogPid(),
			'oddeven'		=> ($entryCount % 2 ? 'even' : 'odd'),
			'title'			=> $this->getTitleLinked($row['title'], $row['uid'], $row['date']),
			'date'			=> $this->getDate($row['date']),
			'author'		=> $this->getAuthor($row['realName']),
			'authorId'		=> $row['author'],
			'gravatar'		=> $this->getGravatar($row['useruid'], $row['email'], $row['realName']),
			'email' 		=> $row['email'],
			'showMore'		=> $hasDivider ? $this->getShowMore($textBeforeDivider, $this->getTitleLinked($this->pi_getLL('moreText'),$row['uid'],$row['date'],'moreLink')) : '',
			'contentUids'	=> implode(',', $contentUidArray),
			'time'			=> $this->getTime($row['date']),
			'categories'	=> $this->getCategoriesLinked($row['uid']),
			'comments'		=> $this->getCommentsLink($row['uid'], $row['date']),
			'tipafriendlinkText'=>	($this->conf['useTipAFriend']?$this->pi_getLL('tipafriendlinkText'):''),
			'blogUrl'		=> $this->getPermalink($row['uid'], $row['date'], true),
			'permalink'		=> $this->getPermalink($row['uid'], $row['date']),
			'trackbackLink' => $this->getTrackbackLink($row['uid'], $row['date']),
			'back'			=> $this->back,
			'tagClouds'		=> $row['tagClouds'],
			'number_views'	=> $row['number_views'],
			'entryCounter'  => $entryCount
		);
		$result = t3blog_div::getSingle($data, 'listItem', $this->conf);

		return $result;
	}

	/**
	 * Crerates a '[more]' section (including the cropped text.
	 *
	 * @param string $text
	 * @param string $moreLink
	 * @return string
	 */
	protected function getShowMore($text, $moreLink) {
		return t3blog_div::getSingle(array(
			'text' => $text,
			'moreLink' => $moreLink,
		), 'showMore', $this->conf);
	}

	/**
	 * Obtains gravatar if enabled in the configuration.
	 *
	 * @param string $userUid
	 * @param string $email
	 * @param string $username
	 * @return string
	 */
	function getGravatar($userUid, $email, $username) {
		if ($this->conf['gravatar']) {
			$result = parent::getGravatar($userUid, $email, $username);
		}
		return $result;
	}

	/**
	 * Creates header for the list of items.
	 *
	 * @return string
	 */
	protected function getListItemHeader() {
		$singleData = array('tags' => $this->tagTitle);
		if ($this->globalPiVars) {
			$singleData['filtered'] = $this->getFilteredBy();
			$singleData['text']	= $this->pi_getLL('filteredByText');
			$singleData['resetText'] = $this->pi_getLL('resetText');
		}

		return t3blog_div::getSingle($singleData, 'titelListItem', $this->conf);
	}

	/**
	 * Checks if the given field definition represents a valid database field.
	 *
	 * @param string $fieldDefinition
	 * @return boolean
	 */
	protected function isValidFieldName($fieldDefinition) {
		// TODO Check over $TCA, remember about possible table name!
		return preg_match('/^[a-z_\.]+$/i', $fieldDefinition);
	}

	/**
	 * Creates the order by statement.
	 *
	 * @return string
	 */
	protected function getOrderBy() {
		$result = 'tx_t3blog_post.date DESC';
		if ($this->isValidFieldName($this->localPiVars['orderBy'])) {
			$result = $this->localPiVars['orderBy'];
		}
		if ($this->localPiVars['orderByDir'] && preg_match('/^(?:A|DE)SC$/i', $this->localPiVars['orderByDir'])) {
			$result .= ' ' . $this->localPiVars['orderByDir'];
		}
		return $result;
	}

	/**
	 * Obtains 'group by' statement.
	 *
	 * @return string
	 */
	protected function getGroupBy() {
		$result = '';
		if ($this->isValidFieldName($this->localPiVars['groupBy'])) {
			$result = $this->localPiVars['groupBy'];
		}
		return $result;
	}

	/**
	 * Creates limit condition for searched phrase.
	 *
	 * @return string
	 */
	protected function getSearchCondition() {
		$result = '';
		if (trim($this->globalPiVars['sword'])) {
			$searchWord = $GLOBALS['TYPO3_DB']->quoteStr($this->globalPiVars['sword'], 'tx_t3blog_post');

			$this->tables['tt_content'] = 'tt_content';
			$result .= ' AND tt_content.irre_parentid = tx_t3blog_post.uid ' .
				'AND tt_content.irre_parenttable = \'tx_t3blog_post\'';
			$result .= ' AND (';
			$result .= ' tt_content.header LIKE \''.$searchWord.'%\' ';
			$result .= ' OR tt_content.bodytext LIKE \''.$searchWord.'%\' ';
			$result .= ' OR tx_t3blog_post.title LIKE \''.$searchWord.'%\' ';
			$result .= ' OR tx_t3blog_post.tagClouds LIKE \''.$searchWord.'%\' ';
			$result .= ' ) ';
			$result .= $this->localcObj->enableFields('tt_content');
		}
		return $result;
	}


	/**
	 * Creates tag limit condition.
	 *
	 * @return string
	 */
	protected function getTagCondition() {
		$result = '';
		if ($this->localPiVars['tags']) {
			$tags = $this->localPiVars['tags'];
			$result = ' AND (tagClouds LIKE \'%'. $GLOBALS['TYPO3_DB']->quoteStr($tags, 'tx_t3blog_post'). '%\') ';
			$this->tagTitle = 'Tag '. $tags;
			$this->back = $this->pi_getLL('back');
		}
		return $result;
	}

	/**
	 * Obtains category limit condition
	 *
	 * @return string
	 */
	protected function getCategoryCondition() {
		$result = '';
		if ($this->localPiVars['category']) {
			$this->tables['tx_t3blog_post_cat_mm'] = 'tx_t3blog_post_cat_mm as mm';
			$uidList = $this->localPiVars['category'];
			$this->getCommaSeparatedCategories($uidList, $uidList);
			$result = ' AND tx_t3blog_post.uid=mm.uid_local AND mm.uid_foreign IN ('.
				$GLOBALS['TYPO3_DB']->cleanIntList($uidList). ')';
		}
		return $result;
	}

	/**
	 * Gets author limit condition.
	 *
	 * @return string
	 */
	protected function getAuthorCondition() {
		$result = '';
		if (t3lib_div::testInt($this->localPiVars['author'])) {
			$result = ' AND tx_t3blog_post.author=' . $this->localPiVars['author'];
		}
		return $result;
	}

	/**
	 * Converts a date (YYY-mm-dd) in $this->localPiVars to a Unix time stamp.
	 *
	 * @param string $piVarName
	 * @return int
	 */
	protected function getUnixTstampFromPiVar($piVarName) {
		$result = 0;

		if (isset($this->localPiVars[$piVarName])) {
			$year = $month = $day = 0;
			if (sscanf($this->localPiVars[$piVarName], '%4d-%2d-%2d', $year, $month, $day) == 3) {
				$result = mktime(0, 0, 0, $month, $day, $year);
			}
		}

		return $result;
	}

	/**
	 * Obtains date limit condition for posts from two dates.
	 *
	 * @return string
	 */
	protected function getDateCondition() {
		$result = '';

		$fromDate = $this->getUnixTstampFromPiVar('datefrom');
		$toDate = $this->getUnixTstampFromPiVar('dateto');

		if ($toDate < $fromDate) {
			list($fromDate, $toDate) = array($toDate, $fromDate);
		}

		if ($fromDate != 0) {
			if ($fromDate == $toDate) {
				// Until the end of date.
				$toDate += 24*60*60;
			}

			$result .= ' AND date>=' . $fromDate;
		}

		if ($toDate != 0) {
			$result .= ' AND date<=' . $toDate;
		}

		return $result;
	}


	/**
	 * Obtains filter information for the current URL
	 *
	 * @return string
	 */
	protected function getFilteredBy() {
		$result = '';

		if ($this->globalPiVars['sword']) {
			$result = htmlspecialchars($this->globalPiVars['sword']);
		}
		elseif ($this->localPiVars['tags']) {
			$result = htmlspecialchars($this->localPiVars['tags']);
		}
		elseif ($this->localPiVars['author']) {
			$result = t3blog_div::getAuthorByUid($this->localPiVars['author']);
		}
		elseif ($this->localPiVars['category']) {
			$result = t3blog_div::getCategoryNameByUid($this->localPiVars['category']);
		}
		elseif ($this->localPiVars['datefrom']) {
			if (function_exists('strptime')) {
				$tm = strptime($this->localPiVars['datefrom'], '%Y-%m-%d');
				$date = mktime(0, 0, 0, $tm['tm_mon'] + 1, $tm['tm_mday'], $tm['tm_year'] + 1900);
			}
			else {
				$date = strtotime($this->localPiVars['datefrom']);
			}
			$result = strftime($this->pi_getLL('filter_date_format'), $date);
		}
		return $result;
	}

	/**
	 * gets the hierarchic categories and putsthem in the commaseparated list
	 *
	 * FIXME This is an exact duplicate of ../categories/class.categories.php
	 *
	 * @author snowflake <typo3@snowflake.ch>
	 *
	 * @param 	int 	$parent
	 * @param 	string 	$uidList
	 */
	protected function getCommaSeparatedCategories($parent, &$uidList)	{
		$table = 'tx_t3blog_cat';
		$fields = 'uid';
		$where = 'parent_id='. intval($parent);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$uidList.= ',' . $row['uid'];
			$this->getCommaSeparatedCategories($row['uid'], $uidList);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * Obtains the "limit" clause for item list
	 *
	 * @return string
	 */
	protected function getListItemsLimit() {
		$postPointer = t3lib_div::_GET('tx_t3blog_post_pointer');
		if (t3lib_div::testInt($postPointer)) {
			$limit = intval($postPointer) * $this->conf['numberOfRecords'];
		}
		else {
			$limit = '0';
		}
		$limit .= ',';
		if (t3lib_div::testInt($this->conf['numberOfRecords'])) {
			$limit .= $this->conf['numberOfRecords'];
		}
		else {
			$limit .= '10';
		}
		return $limit;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.listFunctions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.listFunctions.php']);
}
?>