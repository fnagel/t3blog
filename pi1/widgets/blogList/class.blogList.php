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
 * Plugin 'T3BLOG' for the 't3blog' extension. Listing the Blog entries.
 * Includes a Single and a listview, this class switches them depending on the showUid pivar.
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class blogList extends tslib_pibase {
	var $prefixId      = 'blogList';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogList/class.blogList.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = true;
	var $localPiVars;
	var $globalPiVars;
	var $conf;

	/**
	 * The main method of the PlugIn
	 * @author 	snowflake <typo3@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @param	array		$pivars: The piVars of the pi1class
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->globalPiVars = $piVars;	// global pivars
		$this->localPiVars = $piVars[$this->prefixId];	// pivars of this widget
		$this->conf = $conf;
		$this->init();
		$this->pi_USER_INT_obj=0;


		/*******************************************************/
		//example pivar for communication interface
		//$this->piVars['widgetname']['action'] = "value";
		/*******************************************************/

		// show list or single functions.
		if($this->localPiVars['showUid'] || $this->localPiVars['showUidPerma']){

			//show single view
			t3lib_div::requireOnce(t3lib_extMgm::extPath('t3blog', 'pi1/widgets/blogList/class.singleFunctions.php'));
			$singleFunctions = t3lib_div::makeInstance('singleFunctions');
			$content = $singleFunctions->main($content, $this->conf, $piVars);

		}else{
			// showlist view's
			t3lib_div::requireOnce(t3lib_extMgm::extPath('t3blog', 'pi1/widgets/blogList/class.listFunctions.php'));
			$listFunctions = t3lib_div::makeInstance('listFunctions');
			$content = $listFunctions->main($content, $this->conf, $piVars);
		}

		return $content;
	}


	/**
	 * Initial Method
	 */
	function init(){
		$this->fixLL();
		$this->pi_loadLL();
		$this->localcObj = t3lib_div::makeInstance('tslib_cObj');
	}


	/**
	 * Styles the title link with the typoscript titleLink
	 *
	 * @author 	snowflake <typo3@snowflake.ch>
	 *
	 * @param 	string 	$title: title of the post
	 * @param 	int		$uid: uid of the post
	 * @param	string	$date: date of the post
	 * @param	string	$wrap: wrapclass
	 * @param	string	$longTitle: long title of the post
	 *
	 * @return	string
	 */
	function getTitleLinked($title, $uid = 0, $date = '', $wrap = 'titleLink', $longTitle = ''){
		$data = array(
			'title'	=> $title,
			'uid'	=> $uid,
			'date'	=> $date,
			'longTitle' => $longTitle
		);

		return t3blog_div::getSingle($data, $wrap, $this->conf);
	}


	/**
	 * returns the text formated in the typoscript textRow
	 * available uid in typoscript
	 *
	 * @author 	snowflake <typo3@snowflake.ch>
	 * @param 	text $text: text to get
	 *
	 * @return 	string (html)
	 */
	function getText($text){
		$text = str_replace('###MORE###', '', $text);
		$data = array(
			'text'	=> $text,
		);

		return t3blog_div::getSingle($data, 'textFormat', $this->conf);
	}


	/**
	 * returns the date formated with the config timeformat or G:i:s a'
	 *
	 * @author 	snowflake <typo3@snowflake.ch>
	 * @param 	date 	$date: date to be formated
	 *
	 * @return 	string
	 */
	function getTime($date){
		$format = ($this->conf['timeformat']) ? $this->conf['timeformat'] : 'G:i:s a';
		$data 	= array(
			'time'	=> $date
		);
		return t3blog_div::getSingle($data, 'time', $this->conf);
	}


	/**
	 * Returns the date formated by the typoscript 'date' object
	 *
	 * @author 	snowflake <typo3@snowflake.ch>
	 * @param 	date 	$date: date to be formated
	 *
	 * @return 	string
	 */
	function getDate($date) {
		$data = array(
			'date'	=> $date
		);

		return t3blog_div::getSingle($data, 'date', $this->conf);
	}


	/**
	 * returns the comments link with the numbers of comments in front of it.
	 * Link generated in typoscript commentsLink
	 *
	 * @author 	snowflake <typo3@snowflake.ch>
	 * @param  	int		$uid: uid of the comment
	 * @param  	date	$date: date of the comment
	 *
	 * @return 	string
	 */
	function getCommentsLink($uid,$date=''){
		$data = array(
				'uid' 			=> $uid,
				'commentsNr'	=> t3blog_db::getNumberOfCommentsByPostUid($uid),
				'commentText' 	=> $this->pi_getLL('comments'),
				'date'			=> $date
		);

		return t3blog_div::getSingle($data, 'commentsLink', $this->conf);
	}


	/**
	 * returns the categories as link. therefore the catLink template is used.
	 *
	 * @author 	snowflake <typo3@snowflake.ch>
	 * @param  	int		$uid: uid of the category
	 *
	 * @return 	string
	 */
	function getCategoriesLinked($uid){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_t3blog_cat.uid as uid, tx_t3blog_cat.catname as catname',								// SELECT ...
			'tx_t3blog_post',																			// LOCAL TABLE ...
			'tx_t3blog_post_cat_mm',																	// MM TABLE ...
			'tx_t3blog_cat',																			// FOREIGN TABLE ...
			' AND uid_local  = '.$uid.' AND tx_t3blog_cat.hidden = 0 AND tx_t3blog_cat.deleted = 0',	// WHERE ...
			'tx_t3blog_cat.uid',																		// GROUP BY ...
			'tx_t3blog_cat.catname ASC'																	// ORDER BY ...
		);

		$catDivider = $this->conf['catLinkDivider'];
		if (isset($this->conf['catLinkDivider.'])) {
			$catDivider = $this->localcObj->stdWrap($catDivider, $this->conf['catLinkDivider.']);
		}
		$catlist = '';
		$index = 1;
		$items = array();
		while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$data = array(
				'index' => $index ++,
				'categories'	=> $row['uid'],
				'text'			=> $row['catname']
			);

			$items[] = t3blog_div::getSingle($data, 'catLink', $this->conf);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		$catlist = implode($catDivider, $items);

		$data['catLink'] = $catlist;
		return t3blog_div::getSingle($data, 'catList', $this->conf);
	}


	/**
	 * get the author formated
	 *
	 * @param 	string 	$author: name of the author
	 * @return 	string 	name of the author
	 */
	function getAuthor($author){
		$data = array(
			'name' => $author
		);

		return t3blog_div::getSingle($data, 'author', $this->conf);
	}


	/**
	 * Get Avatar pic, if local avatar is set then display that, else display the global gravatar (@ http://site.gravatar.com)
	 * Method is used for the avatars@posts as well as for the avatars@comments
	 *
	 * @param	int 	$userUid: id of the user
	 * @param 	string	$email: email of the user
	 * @param 	string	$username: name of the user
	 * @return 	string 	(wrapped img)
	 */
	function getGravatar($userUid, $email, $username) {

		// userUid only specified when BE User
		if ($userUid){
			$this->localcObj->data['uid'] 	= $userUid;
			$avatar 						= $this->localcObj->cObjGetSingle($this->conf['avatarImg'], $this->conf['avatarImg.']);
		}
		if(!$avatar){

			// Default needed if user don't have a gravatar and don't have a local pic, but email is stated
			$default 	= t3lib_div::locationHeaderUrl(t3lib_extMgm::siteRelPath($this->extKey). 'icons/nopic_50_f.jpg');
			$size 		= $this->conf['gravatarsize']?$this->conf['gravatarsize']:50;
			$grav_url 	= 'http://www.gravatar.com/avatar/'. md5($email).	'?d='. urlencode($default).'&amp;s='.intval($size).'&amp;r='.$this->conf['gravatarRating'];
			$avatar 	= '<img src="'. $grav_url. '" alt="Gravatar: '. htmlspecialchars($username) . '" title="Gravatar: '. htmlspecialchars($username) . '" />';
		}

		// if local avatar is set then display that, else display the global gravatar @ site.gravatar.com
		return ($avatar);
	}


	/**
	* Instantiates an IMAGE object (see TSREF for more info on that) and returns the according string
	* ready for use in your HTML.
	*
	* @param    string  $imagePath: The image's path. Typically uploads/tx_pluginname/filename
	* @param	string	$title: titel of the image
	* @param    array   $conf: Configuration for the image. See TSREF IMAGE for more info.
	* @param	boolean	$icon: whether icon is set or not
	* @return   string	An image string.
	*/
	function getImage($imagePath, $title = '', $conf = array(), $icon = false)    {
		$image = $conf;

		if ($icon) {
			$image['file'] = $this->extensionPath . $imagePath;
		} else {
			$image['file'] = $this->uploadPath . $imagePath;
		}

		$image['titleText'] = $title;
		$image['altText'] = $title;
		$imagestring = $this->cObj->IMAGE($image);

		return $imagestring;
	}


/**
	* returns a link to the blog entry. or only the url.
	*
	* @param 	int		$uid: blogEntryUid
	* @param 	date	$date: date of the blog entry
	* @param 	boolean	$onlyUrl: whether to display only the url or with a link
	* @return 	string	permalink
	*/
	function getPermalink($uid, $date, $onlyUrl = false) {
		$dateInfo = getdate($date);
		$trackBackParameters = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
			'blogList' => array(
				'day' => sprintf('%02d', $dateInfo['mday']),
				'month' => sprintf('%02d', $dateInfo['mon']),
				'year' => $dateInfo['year'],
				'showUid' => $uid
			)
		));
		$typolinkConf = array(
			'additionalParams' => $trackBackParameters,
			'parameter' => t3blog_div::getBlogPid(),
			'title' => $this->pi_getLL('permalinkDesc'),
			'useCacheHash' => true
		);
		if ($onlyUrl) {
			$typolinkConf['returnLast'] = 'url';
		}

		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$permaLink = $cObj->typoLink($this->pi_getLL('permalinkTitle'), $typolinkConf);

		return $permaLink;
}

/**
	* returns the number of views by each post
	*
	*/
	function getNumberOfViews($numberOfViews) {

		return($numberOfViews);

	}

	protected function fixLL() {
		if (isset($this->conf['_LOCAL_LANG.'])) {
			// Walk each language
			foreach ($this->conf['_LOCAL_LANG.'] as $lang => $LL) {
				// If any label is set...
				if (count($LL)) {
					$ll = array();
					$this->fixLL_internal($LL, $ll);
					$this->conf['_LOCAL_LANG.'][$lang] = $ll;
				}
			}
		}
	}

	/**
	 * Helper function for fixLL. Called recursively.
	 *
	 * @param	array		$LL	Current array
	 * @param	array		$ll	Result array
	 * @param	string		$prefix	Prefix
	 * @return	void
	 */
	function fixLL_internal($LL, &$ll, $prefix = '') {
		while (list($key, $val) = each($LL)) {
			if (is_array($val))	{
				$this->fixLL_internal($val, $ll, $prefix . $key);
			} else {
				$ll[$prefix.$key] = $val;
			}
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.blogList.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.blogList.php']);
}
?>