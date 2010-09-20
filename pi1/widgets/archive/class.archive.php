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
class archive extends tslib_pibase {
	var $prefixId      = 'archive';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/archive/class.archive.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.

	/** Internal: contains a collection of posts by year */
	protected $years = array();

	/** Internal: contains the start of the period for fetching posts */
	protected $periodStart;

	/** Internal: contains the end of the period for fetching posts */
	protected $periodEnd;

	/** Internal: contains the current month number for fetching posts */
	protected $currentMonth;

	/** Internal: contains the current year for fetching posts */
	protected $currentYear;

	/**
	 * The main method of the PlugIn
	 * @author 	snowflake <typo3@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf, $piVars){
		$this->conf = $conf;
		$this->init();

		list($firstYear, $lastYear) = $this->getFirstAndLastYear();
		for ($this->currentYear = $firstYear; $this->currentYear >= $lastYear; $this->currentYear--) {
			$this->processOneYear();
		}

		$content = $this->assembleContent();

		return $content;
	}

	/**
	 * Processes posts for a given year.
	 *
	 * @return void
	 */
	protected function processOneYear() {
		$this->years[$this->currentYear] = array(
			'entries' => 0
		);
		$allYearPosts = '';
		for ($this->currentMonth = 12; $this->currentMonth > 0; $this->currentMonth--) {
			$allYearPosts .= $this->createPostListForMonth();
		}

		$dataUlMonths = array(
			'class'	=> 'months',
			'text'	=> $allYearPosts,
			'id'	=> $this->currentYear,
			'js'	=> $this->getYearToggleJS($this->currentYear)
		);
		$this->years[$this->currentYear]['data'] = t3blog_div::getSingle($dataUlMonths, 'listWrap', $this->conf);
	}

	/**
	 * Creates a unix time stamp for the start of the month.
	 *
	 * @param int $month
	 * @param int $year
	 * @return int
	 */
	protected function getMonthStart($month, $year) {
		return mktime(0, 0, 0, $month, 1, $year);
	}


	/**
	 * Creates a unix time stamp for the end of the month.
	 *
	 * @param int $month
	 * @param int $year
	 * @return int
	 */
	protected function getMonthEnd($month, $year) {
		return mktime(0, 0, -1, $month + 1, 1, $year);
	}

	/**
	 * Processes posts for a current month and year.
	 *
	 * @param int $currentMonth
	 * @param int $currentYear
	 * @return string
	 */
	protected function createPostListForMonth() {
		$content = '';

		$this->periodStart = $this->getMonthStart($this->currentMonth, $this->currentYear);
		$this->periodEnd = $this->getMonthEnd($this->currentMonth, $this->currentYear);

		$postListForMonth = $this->getPostListForThePeriod();
		$entriesInTheMonth = count($postListForMonth);

		if ($entriesInTheMonth > 0) {
			$this->years[$this->currentYear]['entries'] += $entriesInTheMonth;
			$content .= $this->outerWrapPostListForMonth($postListForMonth);
		}
		return $content;
	}

	/**
	 * Applies a secondary warap (toggle) for month entries.
	 *
	 * @param string $postListForMonth
	 * @return string
	 */
	protected function outerWrapPostListForMonth(array $postListForMonth) {
		$monthHtmlId = $this->currentYear . $this->currentMonth;

		$dataCatLinkMonth = array(
			'text'		=> $this->pi_getLL('month_' . $this->currentMonth),
			'datefrom'	=> $this->periodStart,
			'dateto'	=> $this->periodEnd,
			'entries'	=> count($postListForMonth),
			'id'		=> $monthHtmlId,
			'blogUid'	=> t3blog_div::getBlogPid(),
		);
		$dataMonthLi = array(
			'class'	=> 'month',
			'text'	=> t3blog_div::getSingle($dataCatLinkMonth, 'catLink', $this->conf) .
					$this->wrapPostListForMonth($postListForMonth)
		);
		return t3blog_div::getSingle($dataMonthLi, 'itemWrap', $this->conf);
	}

	/**
	 * Wraps the list of entries.
	 *
	 * @param int $currentMonth
	 * @param int $currentYear
	 * @param array $postListForMonth
	 * @return string
	 */
	protected function wrapPostListForMonth(array $postListForMonth) {
		$monthHtmlId = $this->currentYear . $this->currentMonth;
		$dataUlEntries = array(
			'class'		=> 'entries',
			'text'		=> implode('', $postListForMonth),
			'id'		=> $monthHtmlId,
			'js'		=> $this->getMonthToggleJS($monthHtmlId)

		);
		return t3blog_div::getSingle($dataUlEntries, 'listWrap', $this->conf);
	}

	/**
	 * Creates a list of entries for the given period.
	 *
	 * @return array
	 */
	protected function getPostListForThePeriod() {
		$postList = array();

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_t3blog_post',
			'pid='. t3blog_div::getBlogPid() . ' AND date<=' . $this->periodEnd .
				' AND date>=' . $this->periodStart . $this->cObj->enableFields('tx_t3blog_post'),
			'', 'date ASC');
		while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$postList[] = $this->getBlogPostLink($row);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $postList;
	}

	/**
	 * Obtains a link to the post (HTML representation).
	 *
	 * TODO Extract all ways to generate a link to the post to a single place
	 * in order to produce all links in the same way and avoid duplicate content in Google.
	 *
	 * @param array $postRow
	 * @return string
	 */
	protected function getBlogPostLink(array $postRow) {
		$data = array(
			'uid'		=> $postRow['uid'],
			'date'		=> $postRow['date'],
			'title'		=> $postRow['title'],
			'blogUid'	=> t3blog_div::getBlogPid(),
		);
		$dataLi = array(
			'class'	=> 'blogentry',
			'text'	=> t3blog_div::getSingle($data, 'titleLink', $this->conf)
		);
		return t3blog_div::getSingle($dataLi, 'itemWrap', $this->conf);
	}

	/**
	 * Obtains the first and the last years in the post list.
	 *
	 * @return array
	 */
	protected function getFirstAndLastYear() {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'year(from_unixtime(max(date))) AS first_year, year(from_unixtime(min(date))) AS last_year',
			'tx_t3blog_post', '1=1' . $this->cObj->enableFields('tx_t3blog_post'));
		return array(intval($row['first_year']), intval($row['last_year']));
	}

	/**
	 * Creates a common javascript for toggling nodes.
	 *
	 * @param  string $id
	 * @return string
	 */
	protected function getToggleJS($id) {
		$js = '/*<![CDATA[*/
				var mySlide'. $id. ' = new Fx.Slide($(\'archive_'. $id. '\'));
				if(Cookie.get("mySlide'. $id. '")==1){
					mySlide'. $id. '.toggle();
					if($(\'toggle'. $id. '\').firstChild.nodeValue == "[+]")	{
						$(\'toggle'. $id. '\').firstChild.nodeValue = "[-]";
					} else {
						$(\'toggle'.$id.'\').firstChild.nodeValue = "[+]";
					}
				}

				$(\'toggle'. $id. '\').addEvent(\'click\', function(e) {
					e = new Event(e);
					mySlide'. $id. '.toggle();
					if($(\'toggle'. $id. '\').firstChild.nodeValue == "[+]")	{
						Cookie.remove("mySlide'. $id. '");
						Cookie.set("mySlide'. $id. '","0",{path:"/"});
						$(\'toggle'. $id. '\').firstChild.nodeValue = "[-]";
					} else {
						Cookie.set("mySlide'. $id. '","1",{path:"/"});
						$(\'toggle'. $id. '\').firstChild.nodeValue = "[+]";
					}
					e.stop();
				}

				);
			/*]]>*/';
		return $js;
	}

	/**
	 * Creates a javascript to toggle visibility of the the month node.
	 *
	 * @param  string $id
	 * @return string
	 */
	protected function getMonthToggleJS($id) {
		return $this->getToggleJS($id);
	}

	/**
	 * Creates a javascript to toggle visibility of the the year node.
	 *
	 * @param  string $id
	 * @return string
	 */
	protected function getYearToggleJS($id) {
		return $this->getToggleJS($id);
	}

	/**
	 * Creates the content from collected post information through years.
	 *
	 * @return string
	 */
	protected function assembleContent() {
		$content = '';
		if (count($this->years) > 0) {
			foreach ($this->years as $year => $row) {	//wrap year li
				$dataCatLinkYear = array(
					'text'		=> $year,
					'datefrom'	=> $this->getMonthStart(1, $year),
					'dateto'	=> $this->getMonthEnd(12, $year),
					'entries'	=> $row['entries'],
					'id'		=> $year
				);
				$dataYearLi = array(
					'class'	=> 'year',
					'text'	=> t3blog_div::getSingle($dataCatLinkYear, 'catLink', $this->conf) . $row['data']
				);
				$yearsInArchive .= t3blog_div::getSingle($dataYearLi, 'itemWrap', $this->conf);
			}

			$dataYearUl = array(
				'class'	=> 'archive',
				'text'	=> $yearsInArchive
			);
			$content = t3blog_div::getSingle($dataYearUl, 'listWrap', $this->conf);
			$content = t3blog_div::getSingle(
				array(
					'categoryTree'	=> $content,
					'title'	=> $this->pi_getLL('title')
				), 'globalWrap', $this->conf
			);
		}
		return $content;
	}

	/**
	 * Initializes the widget.
	 *
	 * @return void
	 */
	function init() {
		$this->pi_loadLL();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/archive/class.archive.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/archive/class.archive.php']);
}

?>