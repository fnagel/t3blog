<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Snowflake Productions Gmbh <typo3@snowflake.ch>
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
 * $Id$
 */

if (!defined('TYPO3_MODE')) {
	die('tx_t3blog_modbase: this file cannot be called directly. Did you forget to include init.php?');
}

require_once(PATH_t3lib.'class.t3lib_scbase.php');

$LANG->includeLLFile('EXT:lang/locallang_core.xml');
$LANG->includeLLFile('EXT:t3blog/lib/locallang.xml');

/**
 * This class is a base class for all t3blog backend modules. It implements
 * common functions for all modules.
 *
 * @author Dmitry Dulepov <ddulepov@snowflake.ch>
 */
class tx_t3blog_modbase extends t3lib_SCbase {

	/**
	 * Default sorting for this module (SQL format)
	 *
	 * @var string
	 */
	protected $defaultSort = '';

	/**
	 * Current page for search results
	 *
	 * @var int
	 */
	protected $currentPage;

	/**
	 * Number of items per page
	 *
	 * @var int
	 */
	protected $numberOfItemsPerPage = 20;

	/**
	 * Page data
	 *
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * Sorting request parameter. If you use that, make sure you set
	 * $this->validSortFields before calling init() method
	 *
	 * @var string
	 */
	protected $sortParameter = '';

	/**
	 * Sorting request parameter.
	 *
	 * @var string
	 */
	protected $sortDirectionParameter = '';

	/**
	 * Sot parameter name in the URL
	 *
	 * @var string
	 */
	protected $sortDirectionParameterName = 'sortDir';

	/**
	 * Sot parameter name in the URL
	 *
	 * @var string
	 */
	protected $sortParameterName = 'sort';

	/**
	 * Comma-separated list of a valid sort fields for this module
	 *
	 * @var string
	 */
	protected $validSortFields = '';

	/**
	 * Initialzes the module
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->content = '';
		$this->currentPage = $this->getCurrentPage();
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);

		$this->sortParameter = $this->getSortParameter();
		$this->sortDirectionParameter = $this->getSortDirectionParameter();
	}

	/**
	 * Creates the content of module
	 *
	 * @return void
	 */
	public function main()	{
		if ($this->hasAccess())	{
			$this->doc = t3lib_div::makeInstance('bigDoc');
			$this->addBlogHeaderData();

			$headerSection =
				$this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']). '<br />'.
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path'). ': '.
				t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'], 50);

			$this->content .=
				$this->doc->startPage($GLOBALS['LANG']->getLL('moduleTitle')).
				$this->doc->header($GLOBALS['LANG']->getLL('moduleTitle')).
				$this->doc->spacer(5).
				$this->doc->section('', $this->doc->funcMenu($headerSection,
					t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]',
						$this->MOD_SETTINGS['function'], $this->MOD_MENU['function']))).
				$this->doc->divider(5);

			$this->moduleContent();	// render content

			if ($GLOBALS['BE_USER']->mayMakeShortcut())	{	// shortcut
				$this->content .= $this->doc->spacer(20) .
					$this->doc->section('',
						$this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
			}

			$this->content .= $this->doc->spacer(10);
		}
		else {
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];

			$this->content .=
				$this->doc->startPage($GLOBALS['LANG']->getLL('moduleTitle')).
				$this->doc->header($GLOBALS['LANG']->getLL('moduleTitle')).
				$this->doc->spacer(5).
				$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return void
	 */
	public function printContent()	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Obtains current page number from the request
	 *
	 * @return int
	 */
	protected function getCurrentPage() {
		$page = t3lib_div::_GP('curPage');
		if (!t3lib_div::testInt($page)) {
			$page = 1;
		}
		return max(1, intval($page));
	}

	/**
	 * Checks if access to this page is allowed to the current user
	 *
	 * @return boolean
	 */
	protected function hasAccess() {
		return $GLOBALS['BE_USER']->user['admin'] || ($this->id && is_array($this->pageinfo));
	}

	/**
	 * Obtains the content for this module
	 *
	 * @return string
	 * @see tx_t3blog_modbase::getModuleContent()
	 */
	protected function getModuleContent() {
		return 'TODO Return your module content from the ' . get_class($this) .
			'::getModuleContent()<br />';
	}

	/**
	 * Obtains number of pages for the current module. By default there is a
	 * single page, which hides pager. This function should be overriden in
	 * all derived classes.
	 *
	 * @return int
	 */
	protected function getNumberOfItems() {
		return 1;
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
			$category = t3lib_div::_GP('cat');
			// Method "get" is necessary for correct functiong of "current settings" (getCurrentUrlWithoutParameters)
			$formElements = array(
				// TODO Get rid of 'pid' when all modules are refactored
				'<form action="index.php" method="get">' .
					'<input type="hidden" name="id" value="' . intval(t3lib_div::_GP('id')) . '" />' .
					($this->sortParameter ? '<input type="hidden" name="' . $this->sortParameterName . '" value="' . htmlspecialchars($this->sortParameter) . '" />' : '') .
					($this->sortDirectionParameter ? '<input type="hidden" name="' . $this->sortDirectionParameterName . '" value="' . htmlspecialchars($this->sortDirectionParameter) . '" />' : '') .
					($category ? '<input type="hidden" name="cat" value="' . htmlspecialchars($category) . '" />' : ''),
				'</form>'
			);
		}

		// Table with the search box:
		$searchFieldTitle = $GLOBALS['LANG']->getLL('labels.enterSearchString', true);
		$searchFieldValue = htmlspecialchars(t3lib_div::_GP('search_field'));
		$submitButtonText = $GLOBALS['LANG']->getLL('labels.search', true);
		$content = '
				<!--
					Search box:
				-->
				<div style="margin: 5px 3px">
					' . $searchFieldTitle . '
					<input type="text" name="search_field" value="' . $searchFieldValue . '" style="width: 150px" />
					<input type="submit" name="search" value="' . $submitButtonText . '" />
				</div>
				';

		return $formElements[0] . $content . $formElements[1];
	}

	/**
	 * Obtains number of pages for the current module. By default there is a
	 * single page, which hides pager.
	 *
	 * @return int
	 */
	protected function getNumberOfPages() {
		$numberOfItems = $this->getNumberOfItems();
		$numberOfPages =  intval($numberOfItems / $this->numberOfItemsPerPage);
		if (($numberOfItems % $this->numberOfItemsPerPage) != 0) {
			$numberOfPages++;
		}
		return $numberOfPages;
	}

	/**
	 * Creates "showing posts x to y" panel
	 *
	 * @return string
	 */
	protected function getCurrentPageInfo() {
		$numberOfItems = $this->getNumberOfItems();
		$firstPost = ($this->currentPage-1)*$this->numberOfItemsPerPage;
		if ($numberOfItems > 0) {
			$firstPost++;
		}
		$lastItem = min($this->currentPage*$this->numberOfItemsPerPage, $numberOfItems);

		$result = '<div class="pagecount">' .
			$GLOBALS['LANG']->getLL('showRecords', true) . ' ' .
			$firstPost . '&ndash;' . $lastItem .
			' (' . $numberOfItems . ') </div>';

		return $result;
	}

	/**
	 * Generates the pager
	 *
	 * @return string
	 */
	protected function getPager() {
		$numberOfPages = $this->getNumberOfPages();
		$pager = '';
		if ($numberOfPages > 1) {
			$urlFormat = $this->getUrlFormatForPager();
			$pages = array();
			for ($pageNumber = 1; $pageNumber <= $numberOfPages; $pageNumber++) {
				if ($pageNumber == $this->currentPage){
					$pages[] = '<strong>' . $pageNumber . '</strong>';
				}else{
					$pages[] = '<a href="' .
						htmlspecialchars(sprintf($urlFormat, $pageNumber)) .
						'">' . $pageNumber . '</a>';
				}
			}
			$pager = '<div class="paging">'. $GLOBALS['LANG']->getLL('pages', true) . ': ' .
				implode(' ' , $pages) .
				'</div>';
		}
		return $pager;
	}

	/**
	 * Creates a partial SQL WHERE statement for the free text search
	 *
	 * @param	string	$table: name of the table
	 * @return	string	search string
	 */
	public function getSearchSQLWhere($table){
		$result = '';
		$searchField = t3lib_div::_GP('search_field');
		if (trim($searchField) != '') {
			$searchFields = $this->getSearchFields($table);
			$like = ' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchField . '%', $table);
			$result = ' AND (' . implode($like . ' OR ', $searchFields) . $like . ')';
		}

		return $result;
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
	 * Obtains list sorting clause from the URL parameters
	 *
	 * @return Sorting clause
	 */
	protected function getListSortClause() {
		$result = $this->defaultSort;

		if ($this->sortParameter != '') {
			$result = trim($this->sortParameter . ' ' . $this->sortDirectionParameter);
		}
		return $result;
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

	/**
	 * Creates URL format string for pager. This function takes into
	 * account various request parameters to build the URL.
	 *
	 * @return string
	 */
	protected function getUrlFormatForPager() {
		$urlParameters = array(
			'id' => $this->id
		);
		if (($searchField = t3lib_div::_GP('search_field'))) {
			$urlParameters['search_field'] = $searchField;
		}
		if (($cat = t3lib_div::_GP('cat'))) {
			$urlParameters['cat'] = $cat;
		}
		if (($sort = t3lib_div::_GP('sort'))) {
			$urlParameters['sort'] = $sort;
		}
		if (($sortDir = t3lib_div::_GP('sortDir'))) {
			$urlParameters['sortDir'] = $sortDir;
		}
		$parameters = substr(t3lib_div::implodeArrayForUrl('', $urlParameters), 1);
		$parameters = str_replace('%', '%%', $parameters);
		return 'index.php?' . $parameters . '&curPage=%1$d&search=%1$d';
	}

	/**
	 * Adds header data to the blog module pages. This function should be called
	 * after $this->doc is initialized but before any page output.
	 *
	 * @return void
	 */
	protected function addBlogHeaderData() {
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		// JavaScript
		$this->doc->JScode = '
			<script language="javascript" type="text/javascript">//<!--
				script_ended = 0;
				function jumpToUrl(URL)	{
					document.location = URL;
				}
			//--></script>
		';
		$this->doc->postCode='
			<script language="javascript" type="text/javascript">//<!--
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = 0;
			//--></script>
		';

		if (version_compare(TYPO3_version, '4.3', '<')) {
			// FIXME: Does not work, returns only empty header data as soon as <link is entered, < link seems to work...
			//  $this->doc->additionalHeaderData['t3blog_css'] = '<link rel="stylesheet" type="text/css" href="' .
			//                       $GLOBALS['BACK_PATH'] . t3lib_extMgm::siteRelPath('t3blog') . 'lib/styles.css" />';
			$this->doc->inDocStyles .= implode('',file('../lib/styles.css'));

		}
		else {
			$this->doc->addStyleSheet('t3blog_css', t3lib_extMgm::extRelPath('t3blog') . 'lib/styles.css');
		}
	}

	/**
	 * Checks if passed parameter is a valid paramter for sorting
	 *
	 * @return void
	 */
	protected function isValidSortParameter($parameter) {
		return $parameter != '' && t3lib_div::inList($this->validSortFields, $parameter);
	}

	/**
	 * Checks if passed parameter is a valid sorting direction parameter
	 *
	 * @return boolean
	 */
	protected function isValidSortDirectionParameter($parameter) {
		return $parameter != '' &&
			(strcasecmp($parameter, 'ASC') == 0 || strcasecmp($parameter, 'DESC') == 0);
	}

	/**
	 * Obtains sort parameter from the request
	 *
	 * @return string
	 */
	protected function getSortDirectionParameter() {
		$value = trim(t3lib_div::_GP($this->sortDirectionParameterName));
		if (!$this->isValidSortDirectionParameter($value)) {
			$value = '';
		}
		return $value;
	}

	/**
	 * Obtains sort parameter from the request
	 *
	 * @return string
	 */
	protected function getSortParameter() {
		$value = trim(t3lib_div::_GP($this->sortParameterName));
		if (!$this->isValidSortParameter($value)) {
			$value = '';
		}
		return $value;
	}

	/**
	 * Creates request URL without parameters. URL parametrers are taken from
	 * the current URL.
	 *
	 * @param string $parametersToExclude Comma-separated list of parameters to exclude from the URL
	 * @param boolean $hsc If true, result is passed through the htmlspecialchars() and ready for A tag's HREF attribute
	 * @return string
	 */
	protected function getCurrentUrlWithoutParameters($parametersToExclude, $hsc = false) {
		$parameterArray = explode(',', $parametersToExclude);
		$requestURI = t3lib_div::getIndpEnv('REQUEST_URI');
		list($scriptPath, $urlParameterList) = explode('?', $requestURI, 2);
		$urlParameterList = t3lib_div::trimExplode('&', $urlParameterList);
		foreach ($parameterArray as $parameter) {
			foreach ($urlParameterList as $key => $urlParameter) {
				$testLength = strlen($parameter) + 1;
				if (substr($urlParameter, 0, $testLength) == ($parameter . '=')) {
					unset($urlParameterList[$key]);
					break;
				}
			}
		}
		$url = $scriptPath . '?' . implode('&', $urlParameterList);
		if ($hsc) {
			$url = htmlspecialchars($url);
		}
		return $url;
	}

	/**
	 * Obtains elements to show in the record filter with garbage icon.
	 * Derieved classes may override this function to provide their own
	 * elements. The result is an array with the following members:
	 * - link => link to filtered output with current parameter excluded
	 * - title => parameter title (such as "Sorting" or "Category")
	 * - value => displayable value of the parameter
	 *
	 * @return array
	 */
	protected function getElementsForRecordFilterDisplay() {
		$elements = array();

		$searchField = t3lib_div::_GP('search_field');
		if ($searchField) {
			$elements[] = array(
				'link' => $this->getCurrentUrlWithoutParameters('search_field,search'),
				'title' => $GLOBALS['LANG']->getLL('search'),
				'value' => t3lib_div::fixed_lgd_cs(t3lib_div::_GP('search_field'), 20)
			);
		}

		if ($this->sortParameter) {
			$elements[] = array(
				'link' => $this->getCurrentUrlWithoutParameters($this->sortParameterName . ',' . $this->sortDirectionParameterName),
				'title' => $GLOBALS['LANG']->getLL('sortBy'),
				'value' => $this->sortParameter . ' ' . $this->getLocalizedSortDirection()
			);
		}

		return $elements;
	}

	/**
	 * Obtains current record filter display
	 *
	 * @return string
	 */
	protected function getRecordFilterDisplay() {
		$html = '';
		$elements = $this->getElementsForRecordFilterDisplay();
		$elementCount = count($elements);
		if ($elementCount > 0) {
			$cellWidth = intval(100/$elementCount);
			$image = t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/garbage.gif', 'width="11" height="12"');
			foreach ($elements as $element) {
				$html .= sprintf(
					'<td class="highlight" width="%1$d%%"><a href="%2$s"><img ' . $image . ' alt="" /><a> <b>%3$s</b>: %4$s</td>',
					$cellWidth,
					htmlspecialchars($element['link']),
					htmlspecialchars($element['title']),
					htmlspecialchars($element['value'])
				);
			}
		}
		return ($html == '' ? '' : '<table cellspacing="2"><tr>' . $html . '</tr></table>');
	}

	/**
	 * Obtains localised sorting string
	 *
	 * @return string
	 */
	protected function getLocalizedSortDirection() {
		$stringId = ($this->sortDirectionParameter == 'ASC' ? 'sort.ascending' : 'sort.descending');
		return $GLOBALS['LANG']->getLL($stringId);
	}

	/**
	 * Generates the module content
	 */
	protected function moduleContent() {
		if ($this->id) {
			$content =
				$this->getNewRecordLink() .
				$this->getModuleContent() .
				$this->getCurrentPageInfo() .
				$this->getPager() .
				$this->getRecordFilterDisplay() .
				$this->getSearchBox();

			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('sectionTitle', true), $content, 0, 1);
		}
		else {
			$this->content .= $this->doc->section(
				$GLOBALS['LANG']->getLL('note', true), $GLOBALS['LANG']->getLL('selABlog', true), 0, 1);
		}
	}

	/**
	 * Obtains information for new "Create new XYZ" link. Memeber os the array are:
	 * - icon => path to the icon file
	 * - iconSize => icon size (such as '16x16')
	 * - table => table name for the record
	 * - title => Title of the link
	 * If empty array is returned, no new record link will be created.
	 *
	 * @return array
	 */
	protected function getNewRecordLinkData() {
		return array();
	}

	/**
	 * Generates a "create new blog roll" link
	 *
	 * @return string
	 */
	protected function getNewRecordLink() {
		$result = '';
		$linkData = $this->getNewRecordLinkData();
		if (count($linkData) > 0 && substr($linkData['table'], 0, 10) == 'tx_t3blog_') {
			list($width, $height) = explode('x', $linkData['iconSize']);
			$result = '<a href="#" class="newRecord" onclick="' .
				htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[' . $linkData['table'] .
				']['. $this->id. ']=new', $this->doc->backPath)) .
				'"><img' . t3lib_iconWorks::skinImg($this->doc->backPath,
				$linkData['icon'],
				'width="' . $width . '" height="' . $height . '"') .' alt="' .
				$GLOBALS['LANG']->getLL('newRecord', true) .
				'" style="vertical-align: middle" />&nbsp;' .
				htmlspecialchars($linkData['title']) . '</a>';
		}
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/lib/class.tx_t3blog_modbase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/lib/class.tx_t3blog_modbase.php']);
}

?>