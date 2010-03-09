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
	public $cObj;

	protected $feedType;

	/**
	 * The main method of the PlugIn
	 * @author snowflake <typo3@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars) {

		$this->globalPiVars = $piVars;
		$this->localPiVars 	= $piVars[$this->prefixId];
		$this->feedType = $this->localPiVars['feed_type'];


		$this->conf 		= $conf;
		$this->init();

		// frontend output of RSS-links for the blog
		if(!t3lib_div::_GP('type') or t3lib_div::_GP('type') == 0) {

			$data = array(
				'title'				=>	$this->pi_getLL('rss_click_here'),
				'src091'    		=>	'typo3conf/ext/t3blog/icons/new_rss091.png',
				'postLinkTitle'		=>	$this->pi_getLL('rss_click_post'),
				'pid'				=> 	t3blog_div::getBlogPid(),
				'valuePost091'		=>	$this->pi_getLL('rss_click_post'),
				'valueComments091'	=>	$this->pi_getLL('rss_click_comment'),
				'feed091'			=>	'0.91',
				'commentLinkTitle'	=>	$this->pi_getLL('rss_click_comment'),
				'src20'        		=>  'typo3conf/ext/t3blog/icons/new_rss20.png',
				'feed20'			=>	'2.0',
				'valuePost20'		=>	$this->pi_getLL('rss_click_post'),
				'valueComments20'	=>	$this->pi_getLL('rss_click_comment'),
			);

			// typoscript function
			$content .= t3blog_div::getSingle($data, 'list', $this->conf);

			// the navigation content
			return $this->pi_wrapInBaseClass($content);

		} else {
			// the xml content
			return $this->make_xml($content,$conf);

		}
	}


	 /**
	 * Create XML for RSS-Feed
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	xml-rss feed
	 */
	function make_xml($content,$conf) {


		$this->conf = $conf;
		$this->feed_id = $this->localPiVars['feed_id'];

		// default feed is 2.0
		if (empty($this->feed_id)) {
			$this->feed_id = '2.0';
		}

		// FIXME It makes instance of itself!!! Using 'clone' here to copy all fields of the current object.
		$xmlObj = clone $this;//t3lib_div::makeInstance('rss');

		$xmlObj->init();

		$xmlObj->XMLdebug = 0;
		$xmlObj->conf = $conf;

		// get RSS version
		$xmlObj->rssversion = $this->feed_id;

		// Creating header object
		$xmlObj->renderHeader();


		// get the posts
		if ($this->localPiVars['feed_type'] == 'post' || empty($this->localPiVars['feed_type'])) {
			$xmlObj->setRecFields('tx_t3blog_post','title,author,uid,date,text');
			// Add page content information
			$res = $this->getContentResult('tx_t3blog_post',$xmlObj->rssversion);
			$xmlObj->renderRecords('tx_t3blog_post', $res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

		}
		else {
			// get the comments
			if ($this->localPiVars['feed_type'] == 'comment') {

				$xmlObj->setRecFields('tx_t3blog_com','title,author,uid,fk_post,date,text');
				// Add page content information
				$res = $this->getContentResult('tx_t3blog_com',$xmlObj->rssversion);
				$xmlObj->renderRecords('tx_t3blog_com', $res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}

		// Add footer information
		$xmlObj->renderFooter();

		return $xmlObj->getResult();

	}



	/**
	 * Gets content of requested table
	 *
	 * @param	string		$table: Name of the table
	 * @param	string		$rssversion: Version of the rss-feed
	 *
	 * @return	xml-rss feed
	 */
	function getContentResult($table,$rssversion) {
		if ($GLOBALS['TCA'][$table]) {

			$select = $table.'.* ';

			if ($this->conf['postItemOrderBy'] == '') {
				$orderBy = '';
			} else {
				$orderBy = ' ORDER BY '.$table.'.'.$this->conf['postItemOrderBy'];
			}

			// only 15 items in RSS 0.91
			$limit = $this->conf['postItemCount'];

			if ($this->conf['postItemCount'] == '') {
				$limit = '';
			}
			else {
				if ($limit > 15 and $rssversion=='0.91'){
					$limit = 15;
				}
				$limit 		= ' LIMIT 0,'.$limit;
			}

			if ($limit == '' && $rssversion=='0.91'){
				$limit 		= ' LIMIT 0,15';
			}

			$groupBy 		= '';
			$orderBy_limit 	= '  '.$orderBy.$limit;

			$where = ' WHERE ' . $table . '.pid=' . t3blog_div::getBlogPid();
			$where .= $this->cObj->enableFields($table);

			// FIXME This function is not supposed to know about table details!
			if ($table == 'tx_t3blog_post') {
				$select 	.= ', CONCAT(tt_content.header, \' \', tt_content.bodytext) AS text ';
				$table 		.= ' JOIN tt_content ON ( tt_content.irre_parentid = tx_t3blog_post.uid AND tt_content.irre_parenttable = \'tx_t3blog_post\' )';
				$where 		.= $this->cObj->enableFields('tt_content');
				$groupBy 	.= ' GROUP BY tx_t3blog_post.uid ';
			}
			elseif ($table == 'tx_t3blog_com') {
				$where .= ' AND tx_t3blog_com.approved=1 AND tx_t3blog_com.spam=0';
			}

			$query = 'SELECT ' . $select . ' FROM ' . $table . $where . $groupBy . $orderBy_limit;
			$res = $GLOBALS['TYPO3_DB']->sql_query($query);
			return $res;
		}
	}

	function setRecFields($table,$list)	{
		$this->XML_recFields[$table]=$list;
	}

	/**
	 * XML File with XML-Tags
	 *
	 * @return the content
	 */
	function getResult()	{
		$this->content = implode(chr(10),$this->lines);
		$this->content .= $this->footer;

		return $this->output($this->content);
	}

	/**
	 * The XML-Header for RSS.091 / RSS 2.0
	 */
	function renderHeader()	{
		if(strlen($this->conf['feedImage']) < 10){
			$feedimage = $this->conf['feedLink'].'/typo3conf/ext/t3blog/icons/rss.png';
		}else{
			$feedimage = $this->conf['feedImage'];
		}
		// the XML structure
		$this->lines[] = '<?xml version="1.0" encoding="' . $this->getCharset() . '"?>';

		if($this->rssversion == '2.0') {
			$this->lines[].='<rss version="'.$this->rssversion.'" '.$this->specialContent.'>';
		} else {
			$this->lines[].='<rss version="'.$this->rssversion.'">';
		}

		$feedLanguage = $this->conf['feedLanguage'] != '' ? $this->conf['feedLanguage'] : 'en-en';

		$this->lines[].='<channel>
		 <title>'.htmlspecialchars(substr($this->conf['feedTitle'],0,100)).'</title>
		 <link>'.substr($this->conf['feedLink'],0,500).'</link>
		 <description>'.substr(htmlspecialchars($this->conf['feedDescription']),0,$this->conf['feedItemDescLength']).'</description>
		 <language>'.$feedLanguage.'</language>
		 <generator>'.$this->conf['generator'].' '.$GLOBALS['TYPO_VERSION'].'</generator>
		 ';
				if ($this->rssversion=='2.0') {
					$this->lines[].='<docs>http://blogs.law.harvard.edu/tech/rss</docs>';
				} else {
					$this->lines[].='<docs>http://backend.userland.com/rss091</docs>';
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
		$this->footer='
		</channel>
		</rss>';
	}

	/**
	 * Outputs the xml
	 *
	 * @return returns the xml output
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
	 * @param 	boolean	$b
	 * @return	string	reformatted xml
	 */
	function indent($b)	{
		if ($b)	$this->XMLIndent++; else $this->XMLIndent--;
		$this->Icode='';
		for ($a=0;$a<$this->XMLIndent;$a++)	{
			$this->Icode.=chr(9);
		}
		return $this->Icode;
	}


	/**
	 * Renders records
	 *
	 * @param 	string	$table: table with records
	 * @param	array	$res: ressource
	 */
	function renderRecords($table,$res) {
		while($row = mysql_fetch_assoc($res))	{
			$this->addRecord($table,$row);
		}
	}


	/**
	 * Adds records
	 *
	 * @param 	string	$table: table with records
	 * @param	string	$row: row to save records
	 */
	function addRecord($table,$row)	{
		$this->lines[]='<item>';
			$this->indent(1);
			$this->getRowInXML($table,$row);
			$this->indent(0);
		$this->lines[]='</item>';
	}


	/**
	 * Gets row in xml
	 *
	 * @param 	string	$table: table with records
	 * @param	string	$row: row to save records
	 */
	function getRowInXML($table,$row)	{

		$fields = t3lib_div::trimExplode(',',$this->XML_recFields[$table],1);
		reset($fields);
		unset($this->item);
		while(list(,$field)=each($fields))	{
			$this->lines[]=$this->Icode.$this->fieldWrap($field,$this->substNewline($row[$field]),$row['date']);
		}
	}


	/**
	 * Substitutes new line with character
	 *
	 * @param 	string	$string: string to be substituted
	 * @return	substituted string
	 */
	function substNewline($string)	{
		return str_replace(chr(10), '', $string);
	}


	/**
	 * Gets posted categories
	 *
	 * @param 	int		$value: uid of the category
	 * @return	categories
	 */
	function getPostCategories($value) {

		$fields	= 'uid_foreign';
		$table	= 'tx_t3blog_post_cat_mm';
		$where .= ' tx_t3blog_post_cat_mm.uid_local=' . intval($value);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$data = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($data != '') {
				$data .= ', ';
			}
			$data .= t3blog_div::getCategoryNameByUid($row['uid_foreign']);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $data;
	}


	/**
	 * Takes the Date from the database
	 *
	 * @param 	int		$value: uid of the post
	 * @return 	the date string
	 */
	function getDate($value)	{

		list($data) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('date','tx_t3blog_post',
			'uid=' . t3lib_div::intval_positive($value) .
			$this->cObj->enableFields('tx_t3blog_post'));

		// format the timestamp
		// FIXME What if there is no row?
		$formatedDate = date('Y/m/d', $data['date']);

		// the new date string
		return $formatedDate;
	}


	/**
	 * Takes the author from the database
	 *
	 * @param 	int $value: uid of the be user
	 * @return 	the realName
	 */
	function getAuthorByPost($value) {
		list($data) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('email, realName',
				'be_users', 'uid=' . intval($value));
		return is_array($data) ? $data : array();
	}


	/**
	 * Gets the post title
	 *
	 * @param 	int 	$value: uid of the post
	 * @return 	the realName of the post
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
	 * @return 	the uid of the post
	 */
	function getFkPostID($value) {
		$result = 0;
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('fk_post', 'tx_t3blog_com',
			'uid=' . intval($value));
		if (is_array($row)) {
			$result = $row['fk_post'];
		}
		return $result;
	}


	/**
	 * Wraps the fields given
	 *
	 * @param	string	$field: given field
	 * @param 	int 	$value: uid
	 * @param	int		$date: date in timestamp
	 *
	 * @return Wraps the fields
	 */
	function fieldWrap($field,$value,$date)	{
		switch($field) {
			case 'author':
				if ($value != '') {
					if ($this->feedType == 'comment') {
						return '<author>' . htmlspecialchars($value) . '</author>';
					}
					else {
						$author = $this->getAuthorByPost($value);
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
				$newDate = $this->getDate($value);
				$postid		= $this->feedType == 'comment' ? $this->getFkPostID($value) : $value;
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
				if ($this->feedType == 'post') {
					$category = '<category>'.htmlspecialchars($this->getPostCategories($value)).'</category>';
				}

				return $link."\n".$guid."\n".$category;
                break;

			case 'text':
				$description = $this->cleanString($value);

				if ($this->rssversion != '2.0') {
					return '<description>'.htmlspecialchars(mb_substr($description,0,$this->conf['feedItemDescLength091'],'UTF-8')).'</description>';
				}
				else {
					return '<description>'.htmlspecialchars(mb_substr($description,0,$this->conf['feedItemDescLength20'],'UTF-8')).'</description>
					<content:encoded><![CDATA['.$description.']]></content:encoded>';
				}
				break;

			case 'date':
				setlocale (LC_TIME, $this->conf['feedTimeLocale']);
				return '<pubDate>'.strftime($this->conf['feedStrftime'], $value).'</pubDate>';
				break;

			default:
				return '<'.$field.'>'.htmlspecialchars($value).'</'.$field.'>';
		}

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

		return($string);

	}


	/**
	 * Initial Method
	 */
	function init(){
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