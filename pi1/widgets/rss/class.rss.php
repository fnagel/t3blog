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

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(PATH_typo3 . 'contrib/RemoveXSS/RemoveXSS.php');

/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class rss extends tslib_pibase {
	var $prefixId      = 'rss';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/rss/class.rss.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = false;
	var $rss_091_IconName = 'new_rss091.png';
	var $rss_020_IconName = 'new_rss20.png';
	var $specialContent = 'xmlns:content="http://purl.org/rss/1.0/modules/content/"';
	var $localPiVars;
	var $globalPiVars;
	var $conf;

	/** @var tslib_cObj */
	public $cObj;

	protected $feedType;

	protected $rssVersion = '2.0';

	protected $XMLdebug = false;

	protected $XMLIndent = 0;
	protected $XML_recFields = array();
	protected $content = '';
	protected $footer = '';
	protected $lines = array();
	protected $Icode = '';

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content The PlugIn content
	 * @param	array		$conf The PlugIn configuration
	 * @param	array	$piVars
	 * @return	string The content that is displayed on the website
	 */
	function main($content,$conf,$piVars) {
		$this->globalPiVars = $piVars;
		$this->localPiVars 	= $piVars[$this->prefixId];
		$this->feedType = ($this->localPiVars['feed_type'] ? $this->localPiVars['feed_type'] : 'post');
		$this->conf = $conf;
		$this->init();

		// frontend output of RSS-links for the blog
		if(!t3lib_div::_GP('type') || t3lib_div::_GP('type') == 0) {

			$siteRelPath = t3lib_extMgm::siteRelPath('t3blog');
			$data = array(
				'title'				=>	$this->pi_getLL('rss_click_here'),
				'src091'    		=>	$siteRelPath . 'icons/new_rss091.png',
				'postLinkTitle'		=>	$this->pi_getLL('rss_click_post'),
				'pid'				=> 	t3blog_div::getBlogPid(),
				'valuePost091'		=>	$this->pi_getLL('rss_click_post'),
				'valueComments091'	=>	$this->pi_getLL('rss_click_comment'),
				'feed091'			=>	'0.91',
				'commentLinkTitle'	=>	$this->pi_getLL('rss_click_comment'),
				'src20'        		=>  $siteRelPath . 'icons/new_rss20.png',
				'feed20'			=>	'2.0',
				'valuePost20'		=>	$this->pi_getLL('rss_click_post'),
				'valueComments20'	=>	$this->pi_getLL('rss_click_comment'),
			);

			// typoscript function
			$content .= t3blog_div::getSingle($data, 'list', $this->conf);

			// the navigation content
			$content = $this->pi_wrapInBaseClass($content);
		} else {
			// the xml content
			$content = $this->make_xml();
		}
		return $content;
	}


	/**
	 * Create XML for RSS-Feed
	 *
	 * @return	string xml-rss feed
	 */
	function make_xml() {
		if (!empty($this->localPiVars['feed_id'])) {
			$this->rssVersion = $this->localPiVars['feed_id'];
		}

		$this->XMLdebug = 0;

		// Creating header object
		$this->renderHeader();


		// get the posts
		if ($this->localPiVars['feed_type'] == 'post' || empty($this->localPiVars['feed_type'])) {
			$this->setRecFields('tx_t3blog_post','title,author,uid,date,text');
			// Add page content information
			$rows = $this->getPostRows();
			$this->renderRecords('tx_t3blog_post', $rows);
		}
		else {
			// get the comments
			if ($this->localPiVars['feed_type'] == 'comment') {
				$this->setRecFields('tx_t3blog_com','title,author,uid,fk_post,date,text');
				// Add page content information
				$rows = $this->getCommentRows();
				$this->renderRecords('tx_t3blog_com', $rows);
			}
		}

		// Add footer information
		$this->renderFooter();

		return $this->getResult();

	}

	/**
	 * Obtains comment rows. Note that the order in WHERE statement is significant
	 * because it affects database indexes.
	 *
	 * @return array
	 */
	protected function getCommentRows() {
		$where = 'pid=' . t3blog_div::getBlogPid() .
			' AND approved=1 AND spam=0' .
			$this->cObj->enableFields('tx_t3blog_com');

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
			'tx_t3blog_com', $where, '', $this->getOrderBy(), $this->getLimit());

		return $rows;
	}

	/**
	 * Obtains post rows. Note that the order in WHERE statement is significant
	 * because it affects database indexes.
	 *
	 * @return array
	 */
	protected function getPostRows() {
		$where = 'pid=' . t3blog_div::getBlogPid() .
			$this->cObj->enableFields('tx_t3blog_post');

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
			'tx_t3blog_post', $where, '', $this->getOrderBy(), $this->getLimit());

		foreach ($rows as &$row) {
			$row['text'] = $this->fetchContentForBlogPost($row['uid'], $row['pid']);
		}

		return $rows;
	}

	/**
	 * Fetches rendered contenbt for the post.
	 *
	 * @param int $postId
	 * @param int $blogPid
	 * @return string
	 */
	protected function fetchContentForBlogPost($postId, $blogPid) {
		$conf = array(
			'select.' => array(
				'orderBy' => 'sorting',
				'pidInList' => $blogPid,
				'andWhere' => 'irre_parenttable=\'tx_t3blog_post\' AND irre_parentid=' . $postId
			),
			'table' => 'tt_content'
		);
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		/** @var tslib_cObj $cObj */
		$cObj->start($GLOBALS['TSFE']->page, 'pages');
		$content = $cObj->cObjGetSingle('CONTENT', $conf);

		return $content;
	}

	/**
	 * Obtains the limit statement for data selection
	 *
	 * @return string
	 */
	protected function getLimit() {
		$limit = '';
		if (t3lib_div::testInt($this->conf['postItemCount'])) {
			$limit = $this->conf['postItemCount'];
			if ($this->rssVersion == '0.91' && $limit > 15) {
				$limit = 15;
			}
		}
		return $limit;
	}

	/**
	 * Obtains the orderBy statement for data rows
	 *
	 * @return string
	 */
	protected function getOrderBy() {
		return $this->conf['postItemOrderBy'] ? $this->conf['postItemOrderBy'] : 'crdate DESC';
	}

	function setRecFields($table,$list)	{
		$this->XML_recFields[$table]=$list;
	}

	/**
	 * XML File with XML-Tags
	 *
	 * @return string the content
	 */
	function getResult()	{
		$this->content = implode(chr(10),$this->lines);
		$this->content .= $this->footer;

		return $this->output();
	}

	/**
	 * The XML-Header for RSS.091 / RSS 2.0
	 */
	function renderHeader()	{
		if(strlen($this->conf['feedImage']) < 10){
			$feedimage = $this->conf['feedLink'] . '/' . t3lib_extMgm::siteRelPath('t3blog') . '/icons/rss.png';
		} else {
			$feedimage = $this->conf['feedImage'];
		}
		// the XML structure
		$this->lines[] = '<?xml version="1.0" encoding="' . $this->getCharset() . '"?>';

		if($this->rssVersion == '2.0') {
			$this->lines[].='<rss version="'.$this->rssVersion.'" '.$this->specialContent.' xmlns:atom="http://www.w3.org/2005/Atom">';
		} else {
			$this->lines[].='<rss version="'.$this->rssVersion.'">';
		}

		$feedLanguage = $this->conf['feedLanguage'] != '' ? $this->conf['feedLanguage'] : 'en-en';

		$this->lines[].='<channel>
		<title>'.htmlspecialchars(substr($this->conf['feedTitle'],0,100)).'</title>
		<link>'.substr($this->conf['feedLink'],0,500).'</link>
		<description>'.substr(htmlspecialchars($this->conf['feedDescription']),0,$this->conf['feedItemDescLength']).'</description>
		<language>'.$feedLanguage.'</language>
		<generator>'.$this->conf['generator'].' '.$GLOBALS['TYPO_VERSION'].'</generator>
		';
				if ($this->rssVersion=='2.0') {
					$this->lines[].='<docs>http://blogs.law.harvard.edu/tech/rss</docs>';
				} else {
					$this->lines[].='<docs>http://backend.userland.com/rss091</docs>';
				}

		if ($this->rssVersion == '2.0') {
			$this->lines[] = '<atom:link href="' . htmlspecialchars(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL')) . '" rel="self" type="application/rss+xml" />';
		}

		$this->lines[].='
		<copyright>'.$this->conf['feedCopyright'].'</copyright>
		<managingEditor>'.$this->conf['feedManagingEditor'].'</managingEditor>
		<webMaster>'.$this->conf['feedWebMaster'].'</webMaster>
		<image>
			<title>'.substr($this->conf['feedTitle'],0,100).'</title>
			<url>'.$feedimage.'</url>
			<link>'.substr($this->conf['feedLink'],0,500).'</link>
			<description>'.substr($this->conf['feedDescription'],0,$this->conf['feedItemDescLength']).'</description>
		</image>
		';

	}


	/**
	 * Renders the XML footer
	 */
	function renderFooter()	{
		$this->footer = '
		</channel>
		</rss>';
	}

	/**
	 * Outputs the xml
	 *
	 * @return string returns the xml output
	 */
	function output()	{
		if ($this->XMLdebug)	{
			return '<pre>'.$this->content.'</pre>
			<hr /><div style="color:red;">size: '.strlen($this->content).'</div>';
		} else {
			return $this->content;
		}
	}


	/**
	 * a XML stream reformatter written in ANSI
	 *
	 * @param 	boolean	$forward
	 * @return	string	reformatted xml
	 */
	function indent($forward)	{
		if ($forward)	{
			$this->XMLIndent++;
		}
		else {
			$this->XMLIndent--;
		}
		$this->Icode='';
		for ($a = 0;$a < $this->XMLIndent; $a++)	{
			$this->Icode.=chr(9);
		}
		return $this->Icode;
	}


	/**
	 * Renders records
	 *
	 * @param 	string	$table: table with records
	 * @param	array	$rows
	 */
	function renderRecords($table, array $rows) {
		foreach ($rows as $row) {
			$this->addRecord($table, $row);
		}
	}


	/**
	 * Adds records
	 *
	 * @param 	string	$table: table with records
	 * @param	string	$row: row to save records
	 */
	function addRecord($table,$row) {
		$this->lines[] = '<item>';
			$this->indent(1);
			$this->getRowInXML($table, $row);
			$this->indent(0);
		$this->lines[] = '</item>';
	}


	/**
	 * Gets row in xml
	 *
	 * @param 	string	$table: table with records
	 * @param	string	$row: row to save records
	 */
	function getRowInXML($table, $row) {
		$fields = t3lib_div::trimExplode(',',$this->XML_recFields[$table],1);
		foreach ($fields as $field) {
			$this->lines[] = $this->Icode . $this->fieldWrap($field, $row);
		}
	}


	/**
	 * Substitutes new line with character
	 *
	 * @param 	string	$string: string to be substituted
	 * @return	string substituted string
	 */
	function substNewline($string) {
		return str_replace(chr(10), '', $string);
	}


	/**
	 * Gets posted categories
	 *
	 * @param 	int		$value: uid of the category
	 * @return	string
	 */
	function getPostCategories($value) {
		$query = 'SELECT catname FROM tx_t3blog_cat WHERE uid in (' .
			'SELECT uid_foreign FROM tx_t3blog_post_cat_mm WHERE 1=1' .
			'tx_t3blog_post_cat_mm.uid_local=' . intval($value) . ') ' .
			$this->cObj->enableFields('tx_t3blog_cat') . ' ' .
			'ORDER BY catname';
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$data = '';
		while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))) {
			$data .= '<category>' . htmlspecialchars($row[0]) . '</category>';
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $data;
	}


	/**
	 * Takes the Date from the database
	 *
	 * @param 	int		$value: uid of the post
	 * @return 	string the date string
	 */
	function getDate($value) {
		list($data) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('date','tx_t3blog_post',
			'uid=' . t3lib_div::intval_positive($value) .
			$this->cObj->enableFields('tx_t3blog_post'));

		// format the timestamp
		$formatedDate = date('Y/m/d', $data['date']);

		// the new date string
		return $formatedDate;
	}


	/**
	 * Takes the author from the database
	 *
	 * @param 	int $value: uid of the be user
	 * @return 	string the realName
	 */
	function getAuthorByPost($value) {
		list($data) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
				'be_users', 'uid=' . intval($value));
		return is_array($data) ? $data : array();
	}


	/**
	 * Gets the post title
	 *
	 * @param 	int 	$value: uid of the post
	 * @return 	string the realName of the post
	 */
	function getFkPost($value) {
		$result = '';
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title', 'tx_t3blog_post',
			'uid=' . intval($value) . $this->cObj->enableFields('tx_t3blog_post'));
		if (is_array($row)) {
			$result = $row['title'];
		}
		return $result;
	}

	/**
	 * Gets the post uid
	 *
	 * @param 	int 	$value: uid of the comment
	 * @return 	int the uid of the post
	 */
	function getFkPostID($value) {
		$result = 0;
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('fk_post', 'tx_t3blog_com',
			'uid=' . intval($value));
		if (is_array($row)) {
			$result = $row['fk_post'];
		}
		return intval($result);
	}


	/**
	 * Wraps the fields given
	 *
	 * @param	string	$field: given field
	 * @param 	array	$row
	 *
	 * @return string
	 */
	function fieldWrap($field,array $row) {
		$value = $this->substNewline($row[$field]);
		$date = (int)$row['date'];

		switch($field) {
			case 'author':
				if ($value != '') {
					if ($this->feedType == 'comment') {
						return '<author>' . htmlspecialchars($value) . '</author>';
					}
					else {
						$author = $this->getAuthorByPost($value);
						//list($email) = $this->cObj->getMailTo($author['email'], '');
						//$email = substr($email, 7);
						return '<author>' . htmlspecialchars($author['email']) .
							' (' . htmlspecialchars($author['realName']) . ')' . '</author>';
					}
				}
				break;

			case 'fk_post':
				if ($value != '') {
					return '<fk_post>'.htmlspecialchars($this->getFkPost($value)).'</fk_post>';
				}
				break;

			case 'uid':
				$postid		= $this->feedType == 'comment' ? $this->getFkPostID(intval($value)) : $value;
				$day 	= strftime('%d', $date);
				$month 	= strftime('%m', $date);
				$year	= strftime('%Y', $date);

				$typoLinkConf = array(
					'additionalParams' => t3lib_div::implodeArrayForUrl('tx_t3blog_pi1[blogList]', array(
						'year' => $year,
						'month' => $month,
						'day' => $day,
						'showUid' => $this->conf['feedItemLinkPrefix'] . $postid
					)),
					'parameter' => t3blog_div::getBlogPid(),
					'returnLast' => 'url',
					'useCacheHash' => true
				);
				if ($this->feedType == 'comment') {
					// FIXME Hard-coded! See also pi1/blogList/setup.txt, "comment" object near line 1132
					$typoLinkConf['section'] = 'comment_' . $value;
				}
				$url = htmlspecialchars(t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL($typoLinkConf)));
				$link = '<link>'.$url.'</link>';
				$guid = '<guid>'.$url.'</guid>';
				$category = '';
				if ($this->feedType == 'post') {
					$category = $this->getPostCategories($value);
				}

				return $link."\n".$guid."\n".$category;

			case 'text':
				$description = $this->cleanString(trim($value));
				$descriptionLength = $this->conf[($this->rssVersion != '2.0' ? 'feedItemDescLength091' : 'feedItemDescLength20')];
				$descriptionSubstr = mb_substr($description, 0, $descriptionLength, 'UTF-8');
				if (mb_strlen($description, 'UTF-8') != mb_strlen($descriptionSubstr, 'UTF-8')) {
					$descriptionSubstr .= '...';
				}
				$result = '<description>' . htmlspecialchars($descriptionSubstr) . '</description>';

				if ($this->rssVersion == '2.0') {
					$result .= '<content:encoded><![CDATA[' . $this->getContentEncoded($row['text']) . ']]></content:encoded>';
				}
				return $result;

			case 'date':
				return '<pubDate>' . date('r', $value) . '</pubDate>';

			default:
				return '<'.$field.'>'.htmlspecialchars(strip_tags($value)).'</'.$field.'>';
		}
		return '';
	}

	/**
	 * Creates the encoded content.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function getContentEncoded($text) {
		$text = str_replace('###MORE###', '', $text);
		$text = str_replace('<', ' <', $text);
		$text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $text);
		$text = preg_replace('/(<[^>]*\s)on[a-z]+="[^"]*"/', '\1', $text);

		$text = strip_tags($text, '<a><b><br><em><hr><i><li><ol><p><strong><ul><img>');

		// Remove tags with empty content
		do {
			$oldText = $text;
			$text = preg_replace('/<[^\/>]*>([\s]?)*<\/[^>]*>/s', '', $text);
		} while ($oldText != $text);

		$text = str_replace(' class="bodytext"', '', $text);
		$text = preg_replace('/style="[^"]*"/', ' ', $text);
		$text = preg_replace('/\s{2,}/', ' ', $text);
		$text = preg_replace('/\s*>/', '>', $text);

		if ($GLOBALS['TSFE']->config['config']['baseURL']) {
			$basePrefix = $GLOBALS['TSFE']->config['config']['baseURL'];
		}
		elseif (@parse_url($GLOBALS['TSFE']->config['config']['absRefPrefix'], PHP_URL_SCHEME) != '') {
			$basePrefix = $GLOBALS['TSFE']->config['config']['absRefPrefix'];
		}
		else {
			$basePrefix = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		}
		$text = preg_replace('/"((?:(?:fileadmin|typo3conf|typo3temp|uploads|typo3)\/)|index\.php)/', '"' . $basePrefix . '\1', $text);

		return $text;
	}

	/**
	 * Cleans the string and removes the &
	 *
	 * @param	string	$string: string
	 * @return  string  $string: cleaned string
	 */
	function cleanString($string){
		$string = ' '.str_replace('###MORE###','',strip_tags($string));
		$search = array('&quot;','&nbsp;');
		$replace = array('"',' ');

		$string = str_replace($search,$replace,$string);


		// final cleaning
		$search = array('&');
		$replace = array('+');
		$string = str_replace($search,$replace,$string);

		$string = str_replace(chr(13), chr(10), $string);
		$string = str_replace(chr(10), ' ', $string);

		return($string);

	}


	/**
	 * Initialisation Method
	 *
	 * @return bool
	 */
	function init() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->pi_loadLL();

		return true;
	}

	/**
	 * Obtains the charset for the RSS feed
	 *
	 * @return string
	 */
	protected function getCharset() {
		$result = 'UTF-8';

		if ($GLOBALS['TSFE']->metaCharset) {
			$result = $GLOBALS['TSFE']->metaCharset;
		}
		elseif ($GLOBALS['TSFE']->renderCharset) {
			$result = $GLOBALS['TSFE']->renderCharset;
		}

		return $result;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/rss/class.rss.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/rss/class.rss.php']);
}

?>
