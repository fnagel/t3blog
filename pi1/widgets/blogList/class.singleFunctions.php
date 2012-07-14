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
class singleFunctions extends blogList {
	var $prefixId      = 'tx_t3blog_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogList/class.singleFunctions.php';	// Path to this script relative to the extension dir.
	var $pi_checkCHash = false;
	var $prevPrefixId = 'blogList';
	protected $uid = 0;

	/** Error message for the comment form processing */
	protected $errorMessage = '';

	protected $requiredFields = array();

	/**
	 * Initializes the widget.
	 *
	 * @param array $conf
	 * @param array $piVars
	 * @return void
	 */
	function init(array $conf, array $piVars) {
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prevPrefixId];
		$this->conf = $conf;

		parent::init();

		$this->setPostUid();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');

		$this->requiredFields = t3lib_div::trimExplode(',', strtolower($this->conf['requiredFields']), true);
	}

	/**
	 * The main method of the widget
	 * Retuns a single Blogpost
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, array $conf, array $piVars) {
		$this->init($conf, $piVars);

		$this->checkForTrackbacks();

		$message = $this->unsubscribeFromComments();
		$message = $this->insertCommentIfNecessary($message);

		// shows the blog entry if a "showUid" is set.
		$content = '';
		if ($this->uid) {
			$this->riseViewNumber();

			if ($this->isBEUserLoggedIn()) {
				// Allow preview for hidden posts
				$showHiddenPosts = 1;
			} else {
				$showHiddenPosts = 0;
			}

			$row = $this->fetchPostDataFromDatabase($showHiddenPosts);

			if (is_array($row)) {
				$this->updatePageTitle($row['title']);
				$this->setSomePiVarValues($row);

				$data = array(
					'uid'			=>	$row['postuid'],
					'blogPid'		=>	t3blog_div::getBlogPid(),
					'title'			=>	$this->getTitleLinked($row['title'], $this->uid, $row['date']),
					'date'			=>	$this->getDate($row['date']),
					'time'			=>	$this->getTime($row['date']),
					'author'		=>	$this->getAuthor($row['realName']),
					'authorId'		=>	$row['author'],
					'gravatar'		=>	!$this->conf['gravatar'] ? '' : $this->getGravatar($row['useruid'], $row['email'], $row['realName']),
					'email' 		=>	$row['email'],
					'category'		=>	$this->getCategoriesLinked($row['postuid']),
					'back'			=>	$this->pi_getLL('back'),
					'trackbackLink'	=>	$this->getTrackbackLink($row['postuid'], $row['date']),
					'comments'		=>	$this->listComments($row['date']),
					'comment_count' => t3blog_db::getNumberOfCommentsByPostUid($row['postuid']),
					'message'		=> 	$message,
					'trackbacks'	=>	$this->listTrackbacks(),
					'tipafriendlinkText'=>	($this->conf['useTipAFriend']?$this->pi_getLL('tipafriendlinkText'):''),
					'blogUrl'		=>	$this->getPermalink($this->uid, $row['date'], true),
					'permalink'		=> 	$this->getPermalink($this->uid,$row['date']),
					'addcomment'	=> $this->showCommentForm($row['allow_comments']),
					'tagClouds'		=>	$row['tagClouds'],
					'number_views'	=>	$row['number_views'],
					'navigation'    => $this->getSingleNavigation($this->uid)
				);

				$content = t3blog_div::getSingle($data, 'single', $this->conf);
				$content = str_replace('###MORE###', '', $content);

				$GLOBALS['TSFE']->showHiddenRecords = $showHiddenRecords;
			}
		}

		return $content;

	}

	/**
	 * Checks if BE user is logged in.
	 *
	 * @return boolean
	 */
	protected function isBEUserLoggedIn() {
		return isset($GLOBALS['BE_USER']) &&
				($GLOBALS['BE_USER'] instanceof t3lib_beUserAuth) &&
				$GLOBALS['BE_USER']->user['uid'];
	}


	/**
	 * Inserts the comment to the database if necessary.
	 *
	 * @param string $message
	 * @return string
	 */
	protected function insertCommentIfNecessary($message) {
		if ($this->localPiVars['insert']) {
			if ($this->insertComment()) {
				// if it first has to be approved, contact the writer
				if (!$this->conf['approved']) {
					$message = $this->pi_getLL('toBeApproved');
				}
			}
		}
		return $message;
	}

	/**
	 * Fetches data for the post from the database.
	 *
	 * @return mixed Array or null if no data
	 */
	protected function fetchPostDataFromDatabase($showHiddenRecords = 0) {
		list($row) = t3blog_db::getRecFromDbJoinTables(
			'tx_t3blog_post, be_users',  //  TABLES
			'tx_t3blog_post.uid as postuid, tx_t3blog_post.title, tx_t3blog_post.tagClouds,tx_t3blog_post.author, tx_t3blog_post.date, tx_t3blog_post.cat, tx_t3blog_post.allow_comments,tx_t3blog_post.number_views, be_users.uid, be_users.username, be_users.email, be_users.admin, be_users.admin, be_users.realName, be_users.uid AS useruid, be_users.lastlogin, be_users.tx_t3blog_avatar',
			'tx_t3blog_post.uid='.t3lib_div::intval_positive($this->uid).' AND (be_users.uid=tx_t3blog_post.author)', '', '', $showHiddenRecords
		);
		return $row;
	}

	/**
	 * Sets some localPiVars necessary for the Typ[oScript renderer
	 *
	 * @param array $postRow
	 * @return void
	 */
	protected function setSomePiVarValues(array $postRow) {
		$dateInfo = getdate($postRow['date']);
		if (!$this->localPiVars['year']) {
			$this->localPiVars['year'] = $dateInfo['year'];
			$this->localPiVars['month'] = $dateInfo['mon'];
			$this->localPiVars['day'] = $dateInfo['mday'];
		}
		if (!$this->localPiVars['showUid'] && $this->localPiVars['showUidPerma']) {
			// Legacy code: update showUid if the old showUidPerma is given
			$this->localPiVars['showUid'] = $this->localPiVars['showUidPerma'];
		}
	}

	/**
	 * Updates page title if necessary
	 *
	 * @param string $title
	 * @return void
	 */
	protected function updatePageTitle($title) {
		if ($this->conf['substitutePagetitle']) {
			$GLOBALS['TSFE']->page['title'] = $title;
			$GLOBALS['TSFE']->indexedDocTitle = $title;
		}
	}

	/**
	 * Unsubscribes from comments and returns HTML code to display a corresponding
	 * message if necessary.
	 *
	 * @return string
	 */
	protected function unsubscribeFromComments() {
		$result = '';
		if ($this->localPiVars['unsubscribe']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_com_nl',
				'code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->localPiVars['code'], 'tx_t3blog_com_nl'),
				array('deleted' => 1));
			$result = '<script>alert("'.$this->pi_getLL('subscribe.unsubscribe.succesfully').'");</script>';
		}
		return $result;
	}

	/**
	 * Sets the uid of the current post from the URL parameters.
	 *
	 * @return void
	 */
	protected function setPostUid() {
		if (isset($this->localPiVars['showUid'])) {
			$this->uid = intval($this->localPiVars['showUid']);
		}
		else if (isset($this->localPiVars['showUidPerma'])) {
			// showUidPerma is deprecated!
			$this->uid = intval($this->localPiVars['showUidPerma']);
		}
	}

	/**
	 * Checks if trackbacks has to be sent
	 *
	 * @return void
	 */
	protected function checkForTrackbacks() {
		if (intval($this->globalPiVars['trackback']) == 1) {
			if (t3lib_div::_GP('title') && t3lib_div::_GP('blog_name') && t3lib_div::_GP('excerpt') && t3lib_div::_GP('url')) {
				$this->trackback();
			}
		}
	}


	/**
	 * shows the Comment Form
	 *
	 * @author 	Nicolas Karrer <nkarrer@snowflake.ch>
	 * @param 	int		$allowComments: status 0,1,2 {0 = all, 1 = none, 2 = only registered users}
	 */
	function showCommentForm($allowComments)	{
		if ($allowComments == 0 || ($allowComments == 2 && $GLOBALS['TSFE']->fe_user->user['uid'])) {
			$result = $this->doShowCommentsForm();
		}
		else {
			$result = $this->commentsNotAllowed($allowComments);
		}
		return $result;
	}

	/**
	 * Generates the comment form
	 *
	 * @return string
	 */
	protected function doShowCommentsForm() {
		$data = array();
		$this->checkForCommentEditing($data);
		$this->setCommentFormFields($data);
		$this->setCaptchaFields($data);

		// captcha

		// subscribe for comments
		if ($this->conf['subscribeForComments'] == 1) {
			$postVars = t3lib_div::_POST('tx_t3blog_pi1');
			if ($postVars['blogList']['subscribe']) {
				$data['subscribe'] = 'checked="checked"';
			}
			else {
				$data['subscribe'] = ' ';
			}
			$data['subscribe_text']	= $this->pi_getLL('subscribe_text');
		}

		$data['readOnly']		= isset($GLOBALS['TSFE']->fe_user->user['uid']) && $this->conf['readOnly'] == 1 ? 'readonly="readonly"' : '';
		$data['parentTitle']    = htmlspecialchars($this->localPiVars['comParentTitle']);
		$data['commentTitle'] 	= $this->pi_getLL('commentFormTitle');
		$data['closeicon'] 		= '<img src="'.t3lib_extMgm::extRelPath('t3blog').'icons/window_close.png" alt="" />';
		$data['closelink'] 		= '';
		unset($this->piVars[$this->prevPrefixId]['createCommentForm']);
		$data['insert'] = 1;
		$data['uid'] = $this->uid;

		$data['action'] = htmlspecialchars($this->getCommentFormAction());

		// display error msg
		if ($this->errorMessage){
			$data['errorMsg'] = $this->errorMessage;
			$data['errorTitle'] = $this->pi_getLL('errorTitle');
			unset($this->localPiVars['errorMsg']);
		}
		$content = t3blog_div::getSingle($data, 'commentForm', $this->conf);

		return '<div id="commentFormNonAjax" class="commentFormStyle">' .
			$content .
			'</div>';
	}
	/**
	 * Sets fields according to the commenter's previous data
	 *
	 * @param array data
	 * @return void
	 */
	protected function setCommentFormFields(array &$data) {
		if ($GLOBALS['TSFE']->fe_user->user['uid']) {
			$data['commentauthor'] = $GLOBALS['TSFE']->fe_user->user['username'];
			$data['commentauthoremail'] = $GLOBALS['TSFE']->fe_user->user['email'];
		}
		foreach ($this->getCommentFormFields() as $fieldName) {
			if (isset($this->localPiVars[$fieldName])) {
				// Must be uncached
				$data[$fieldName] = $this->localPiVars[$fieldName];
			}
			$data[$fieldName.'_label'] = $this->pi_getLL($fieldName);

			if (in_array(strtolower($fieldName), $this->requiredFields)) {
				$data[$fieldName.'_label'] .= ' ' . t3blog_div::getSingle(array(
					'marker' => '*'
				), 'requiredFieldMarkerWrap', $this->conf);
			}
		}
	}

	/**
	 * Checks if we are in the comment edit mode and adjusts local variables
	 * accordingly.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function checkForCommentEditing(array &$data) {
		$editUid = intval($this->localPiVars['editCommentUid']);
		if ($editUid) {
			unset($this->localPiVars['editCommentUid']);
			unset($this->piVars[$this->prevPrefixId]['editCommentUid']);
			if ($this->allowedToEditComment($editUid)) {
				list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*', 'tx_t3blog_com', 'uid=' . $editUid
				);
				// load the previous comment info
				$this->localPiVars['commenttext'] = $row['text'];
				$this->localPiVars['commenttitle'] = $row['title'];
				$this->localPiVars['commentauthoremail'] = $row['email'];
				$this->localPiVars['commentauthorwebsite'] = $row['website'];
				$data['editUid'] = $editUid;
			}
		}
		return $editUid;
	}


	protected function commentsNotAllowed($allowComments) {
		if ($allowComments == 1) {
			// no comments allowed at all
			$result = t3blog_div::getSingle(array(
				'text' => $this->pi_getLL('notAllowedToComment')),
					'noCommentAllowedWrap', $this->conf);
		}
		else {
			// not logged in message
			$returnLink = $this->pi_linkTP_keepPIvars_url(array(),1,0,$GLOBALS['TSFE']->id);
			$result = t3blog_div::getSingle(
				array(
					'text'=>$this->pi_getLL('notAllowedToComment'),
					'loginPid'=>$this->conf['loginPid'],
					'loginLinkText'=>$this->pi_getLL('loginLinkText'),
					'redirect_url'=> t3lib_div::locationHeaderUrl($returnLink)
				), 'noCommentAllowedWrap', $this->conf);
		}
		return $result;
	}

	/**
	 * Adds captcha fields if necessary.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setCaptchaFields(array &$data) {
		if ($this->conf['useCaptcha'] == 1) {
			$data['captcha'] = 'tx_t3blog_pi1[blogList][captcha]';
			$data['captchaimage'] = '<img src="' . t3lib_extMgm::siteRelPath('t3blog') .
				'pi1/widgets/blogList/captcha/captcha.php?' .
				'font=' . htmlspecialchars($this->conf['captchaFont']) .
				'&amp;fontSize=' . htmlspecialchars($this->conf['captchaFontSize']) .
				'&amp;fontColor=' . htmlspecialchars($this->conf['captchaFontColor']) .
				'&amp;fontEreg=' . htmlspecialchars($this->conf['captchaEreg']) .
				'&amp;image=' . htmlspecialchars($this->conf['captchaBackgroundPNGImage']) .
				'&amp;showImage=' . htmlspecialchars($this->conf['captchaShowImage']) .
				'&amp;backgroundColor=' . htmlspecialchars($this->conf['captchaBackgroundColor']) .
				'&amp;lines=' . htmlspecialchars($this->conf['captchaLines']) .
				'" alt="" />';
		}
	}

	/**
	 * Creates a list of fields to fetch from the database for the comment form.
	 *
	 * @return array
	 */
	protected function getCommentFormFields() {
		$commentFormFields = array('commentauthor', 'commenttext','commentauthoremail', 'commentauthorwebsite', 'commenttitle', 'submit');
		if ($this->localPiVars['comParentId'] > 0) {
			$commentFormFields[] = 'comParentId';
		}
		if ($this->conf['useCaptcha'] == 1) {
			array_push($commentFormFields, 'captcha', 'captchaimage');
		}
		if ($this->conf['subscribeForComments'] == 1) {
			array_push($commentFormFields, 'subscribe');
		}
		return $commentFormFields;
	}


	/**
	 * Creates a comment form action URL.
	 *
	 * @return string
	 */
	protected function getCommentFormAction() {
		return t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
	}


	/**
	 * Lists the incoming trackbacks
	 *
	 * @return html listing of the trackbacks
	 */
	function listTrackbacks() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,crdate,fromurl,text,title,blogname',
			'tx_t3blog_trackback',
			'pid = '.t3blog_div::getBlogPid().' AND postid = '.t3lib_div::intval_positive($this->uid).' '.$this->cObj->enableFields('tx_t3blog_trackback'),		// WHERE ...
			'uid',
			'crdate'
		);

		$trackbacks = '';
		for ($i = 0; false != ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)); $i++) {

			$link = '';
			$permaidPos = strpos($row['fromurl'], 'bid=');
			if ($permaidPos !== false) {
				$permaid = intval(substr($row['fromurl'], $permaidPos + 4));
				if ($permaid) {
					$urlParts = @parse_url($row['fromurl']);
					if (is_array($urlParts) && isset($urlParts['host']) && $urlParts['host'] == t3lib_div::getIndpEnv('HTTP_HOST')) {
						// Only if the same t3blog host. Is it necessary at all to calculate this URL again?
						// No htmlspecialchars here!
						$link = $this->getPermalink($permaid,$row['date'],true);
					}
				}
			}
			if (!$link) {
				$link = htmlspecialchars($row['fromurl']);
			}

			$dataTrb = array(
				'uid'		=> $row['uid'],
				'odd'		=> $i%2==0?'odd':'even',
				'title'		=> htmlspecialchars($row['title']),
				'author'	=> htmlspecialchars($row['blogname']),
				'date'		=> $this->getDate($row['crdate']),
				'time'		=> $this->getTime($row['crdate']),
				'url'		=> $link,
				'text'		=> htmlspecialchars(strip_tags($row['text']) . '...')
			);

			$trackbacks .= t3blog_div::getSingle($dataTrb, 'trackback', $this->conf);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		$data = array(
			'pageBrowser' 	=> '',
			'trackbacks' 	=> $trackbacks,
			'title' 		=> $this->pi_getLL('trackbacksTitle'),
		);

		$content = t3blog_div::getSingle($data, 'trackbackList', $this->conf);

		return $content;
	}


	/**
	 * lists all the comments. needed the showUid from the localpivars.
	 *
	 * @author 	Manu Oehler <moehler@snowflake.ch>
	 * @param 	date	$date: send the date of the blogentry
	 *
	 * @return 	string	comment listing
	 *
	 */
	function listComments($date = '') {
		// FIXME pid is not necessary???
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,title,author,email,website,date,text,parent_id',
			'tx_t3blog_com',
			'parent_id = 0 AND fk_post=' . intval($this->uid) .
				' AND pid=' . t3blog_div::getBlogPid() .
				' AND approved=1 AND spam=0 '.
				$this->cObj->enableFields('tx_t3blog_com'),
			'',
			'date'
		);
		$comments = '';
		$numRows = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		$editable = 0;
		for ($i = 0; false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)); $i++) {
			if($this->conf['gravatar'] && $this->conf['gravatarAtComments']){
				$gravatar = $this->getGravatar('', $row['email'], $row['author']);
			}else{
				$gravatar = '';
			}
			// sets the last comment editable if the user is logged in.
			if ($numRows == ($i + 1)) {
				if( $this->allowedToEditComment($row['uid']) ) {
					$editable = 1;
				}
			}
			$dataCom = array(
				'uid'		=> $row['uid'],
				'odd'		=> $i%2==0 ? 'odd' : 'even',
				'title'		=> $row['title'],
				'author'	=> $this->getAuthor($row['author']),
				'gravatar'	=> $gravatar,
				'date'		=> $this->getDate($row['date']),
				'time'		=> $this->getTime($row['date']),
				'email'		=> $row['email'],
				'website'	=> $row['website'],
				'text'		=> $row['text'],
				'blogUid'	=> $this->uid,
				'entrydate'	=> $date,
				'parentcom' => $row['parent_id'] > 0 || $this->conf['commentComments'] == 0 ? '' : $this->pi_getLL('commentComment'),
				'blog_uid'  => t3blog_div::getBlogPid(),
				'blog_year' => $this->localPiVars['year'],
				'blog_month'=> $this->localPiVars['month'],
				'blog_day' 	=> $this->localPiVars['day'],
				'edit' 		=> ($editable?$this->pi_getLL('editLink'):''),
				'parent_id' => $row['parent_id'],
				'fk_post' 	=> $this->localPiVars['showUid'],

			);

			$comments .= t3blog_div::getSingle($dataCom, 'comment', $this->conf);
			$comments .= $this->listCommentedComments($row['uid']);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		$data = array(
			'pageBrowser' 	=> '',
			'comments' 		=> $comments,
			'nrComments'	=> t3blog_db::getNumberOfCommentsByPostUid(t3lib_div::intval_positive($this->uid)),
			'title' 		=> $this->pi_getLL('commentsTitle'),
		);
		$content = t3blog_div::getSingle($data, 'commentList', $this->conf);

		return $content;
	}


	/**
	* Lists all the comments referenced to a parent comment.
	 * @author Thomas Imboden <timboden@snowflake.ch>
	 *
	 * @param       int		$parentId: UID of the parent comment
	 * @return      			comment listing
	 */
	protected function listCommentedComments($parentId){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,title,author,email,website,date,text,parent_id',                                                                                                                                                                                                                                                                                                                                           // SELECT ...
				'tx_t3blog_com',                                                                                                                                                                                                                                                                                                                                                                                                                        // FROM ...
				'parent_id=' . intval($parentId) .
					' AND fk_post=' . $this->uid .
					' AND pid=' . t3blog_div::getBlogPid() .
					' AND approved=1 AND spam=0 ' .
					$this->cObj->enableFields('tx_t3blog_com'), '', 'date'
		);

		$comments = '';
		for ($i = 0; false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)); $i++) {

			$dataCom = array(
				'uid'                   => $row['uid'],
				'odd'                   => $i%2==0 ? 'odd' : 'even',
				'title'                 => $row['title'],
				'author'                => $this->getAuthor($row['author']),
				'gravatar'              => !$this->conf['gravatar'] ? '' : $this->getGravatar('', $row['email'], $row['author']),
				'date'                  => $this->getDate($row['date']),
				'time'                  => $this->getTime($row['date']),
				'email'                 => $row['email'],
				'website'               => $row['website'],
				'text'                  => $row['text'],
				'margin'                => '20px',
			);

			$comments .= t3blog_div::getSingle($dataCom, 'comment', $this->conf);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $comments;
	}

	/**
	 * Checks if the field is required.
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	protected function isFieldRequired($fieldName) {
		return in_array(strtolower($fieldName), $this->requiredFields);
	}


	/**
	 * inserts a comment to the blog entry
	 *
	 * @author manu Oehler <moehler@snowflake.ch>
	 *
	 * @return bool true on success
	 */
	function insertComment() {
		$postUid = intval($this->localPiVars['uid']);
		$editUid = intval($this->localPiVars['editUid']);
		$commentAuthor = strip_tags($this->localPiVars['commentauthor']);
		$commentTitle = strip_tags($this->localPiVars['commenttitle']);
		$authorEmail = $this->localPiVars['commentauthoremail'];
		$authorWebsite = $this->localPiVars['commentauthorwebsite'];
		$commentText = $this->localPiVars['commenttext'];
		$isSpam = $this->isSpam(array($commentAuthor, $commentTitle, $authorWebsite, $authorEmail, $commentText));

		$this->errorMessage = $this->validateCommentSubmission(
			$commentAuthor, $commentTitle, $authorEmail, $authorWebsite, $commentText
		);

		$result = false;

		if ($this->errorMessage == '') {
			$result = true;

			$this->setCommentAuthorCookies($commentAuthor, $authorEmail, $authorWebsite);
			$this->unsetLocalPiVarsBeforeAddingComment();

			$data = $this->prepareCommentData($postUid, $commentAuthor, $commentTitle, $commentText, $authorEmail, $authorWebsite, $isSpam);

			if ($this->allowedToEditComment($editUid)) {
				$this->updateCommentData($editUid, $data);
			}
			else {
				$this->insertNewComment($data);
			}

			// ToDo: and is not SPAM, disabled otherwise no emails are sent, make the "is not spam" button in BE send notification emails
			// if ($this->conf['approved'] && !$isSpam) {
			if ($this->conf['approved']) {
				$this->sendEmailAboutNewComments($postUid);
			}
			
			if ($this->conf['mailReceivedCommentsToAdmin']) {
				$this->adminMailComment($data);
			}

			if (isset($_POST['tx_t3blog_pi1']['blogList']['subscribe'])) {
				$this->subscribeToPostNotifications($postUid, $commentAuthor, $authorEmail);
			}
			
			// if valid but marked as SPAM add notice
			if ($isSpam) {
				$this->errorMessage .= t3blog_div::getSingle(array(
					'value' => $this->pi_getLL('toBeApprovedSpam')
				), 'errorWrap', $this->conf);
			}
		}

		return $result;
	}

	/**
	 * Prepares comment data for insertion/update.
	 *
	 * @param int $postUid
	 * @param string $commentAuthor
	 * @param string $commentTitle
	 * @param string $authorEmail
	 * @param string $authorWebsite
	 * @return array
	 */
	protected function prepareCommentData($postUid, $commentAuthor, $commentTitle, $commentText, $authorEmail, $authorWebsite, $isSPam) {
		$data = array(
			'tstamp'	=> $GLOBALS['EXEC_TIME'],
			'title'		=> $commentTitle,
			'author'	=> $commentAuthor,
			'fe_group'	=> $this->getPostFeGroup($postUid),
			'email'		=> $authorEmail,
			'website'	=> $authorWebsite,
			'text'		=> $this->splitLongWordsInText(strip_tags($commentText)),
			'approved'	=> intval($this->conf['approved']),
			'parent_id' => intval($this->localPiVars['comParentId']),
			'fk_post' => $postUid,
			'spam' => $isSPam
		);
		return $data;
	}

	/**
	 * Inserts a comment to the database and calls hooks.
	 *
	 * @param int $postUid
	 * @param array $data
	 * @return void
	 */
	protected function insertNewComment(array $data) {
		$data['pid'] = t3blog_div::getBlogPid();
		$data['date'] = $data['crdate'] = $GLOBALS['EXEC_TIME'];
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_t3blog_com', $data);
		$commentId = $GLOBALS['TYPO3_DB']->sql_insert_id();
		$this->updateRefIndex('tx_t3blog_com', $commentId);

		$GLOBALS['TSFE']->clearPageCacheContent_pidList(t3blog_div::getBlogPid());

		// Hook after comment insertion
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentinsertion'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentinsertion'] as $userFunc) {
			  $params = array(
					'data' => &$data,
					'table' => 'tx_t3blog_com',
					'postUid' => $data['fk_post'],
					'commentUid' => $commentId
				);
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}
	}


	/**
	 * Unsets some local variables before creating/editing a comment.
	 *
	 * FIXME Dmitry: it is unclean. Why do we need to do this?
	 *
	 * @return void
	 */
	protected function unsetLocalPiVarsBeforeAddingComment() {
		unset($this->piVars[$this->prevPrefixId]['commenttitle']);
		unset($this->piVars[$this->prevPrefixId]['commenttext']);
		unset($this->piVars[$this->prevPrefixId]['uid']);
		unset($this->piVars[$this->prevPrefixId]['editUid']);
		unset($this->localPiVars['commenttext']);
		unset($this->localPiVars['commenttitle']);
		unset($this->localPiVars['editUid']);
		unset($this->localPiVars['uid']);
		unset($this->localPiVars['insert']);
		unset($this->localPiVars['uid']);
	}


	/**
	 * Updates data for the existing comment (comment edit function).
	 *
	 * @param int $editUid
	 * @param array $data
	 * @return void
	 */
	protected function updateCommentData($editUid, $data) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_com', 'uid=' . $editUid, $data);
		$this->updateRefIndex('tx_t3blog_com', $editUid);

		// Hook after comment update
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentupdate'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentupdate'] as $userFunc) {
			  $params = array(
					'data' => &$data,
					'table' => 'tx_t3blog_com',
					'postUid' => $data['fk_post'],
					'commentUid' => $editUid,
				);
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}
	}

	/**
	 * Checks if passed fields contain spam.
	 *
	 * @param array $textFields
	 * @return boolean
	 */
	protected function isSpam(array $textFields) {
		$sfpantispam = t3lib_div::makeInstance('tx_sfpantispam_tslibfepreproc');
		/* @var tx_sfpantispam_tslibfepreproc $sfantispam */
		return !$sfpantispam->sendFormmail_preProcessVariables($textFields, $this);
	}

	/**
	 * Validates comment submission.
	 *
	 * @param string $commentAuthor
	 * @param string $commentTitle
	 * @param string $authorEmail
	 * @param string $authorWebsite
	 * @param string $commentText
	 * @return string Error message (if any)
	 */
	protected function validateCommentSubmission($commentAuthor, $commentTitle, $authorEmail, $authorWebsite, $commentText) {
		$errorMessage = '';

		$testData = array(
			'commentauthor' => array(
				'value' => $commentAuthor,
			),
			'commenttitle' => array(
				'value' => $commentTitle,
			),
			'commentauthoremail' => array(
				'value' => $authorEmail,
				'validator' => array('t3lib_div', 'validEmail')
			),
			'commentauthorwebsite' => array(
				'value' => $authorWebsite,
				'validator' => array('t3lib_div', 'isValidUrl')
			),
			'commenttext' => array(
				'value' => $commentText
			),
		);

		foreach ($testData as $field => $data) {
			$isValid = true;

			$fieldRequired = $this->isFieldRequired($field);
			if ($fieldRequired) {
				$isValid = (trim($data['value']) != '');
			}
			if ($isValid && isset($data['validator']) && ($fieldRequired || $data['value'] != '')) {
				$isValid = call_user_func($data['validator'], $data['value']);
			}
			if (!$isValid) {
				$errorMessage .= t3blog_div::getSingle(array(
						'value' => $this->pi_getLL('error_' . $field)
					), 'errorWrap', $this->conf);
			}
		}

		// captcha
		if ($this->conf['useCaptcha'] == 1) {
			session_start();
			$captchaStr = $_SESSION['tx_captcha_string'];
			$_SESSION['tx_captcha_string'] = '';

			if (!strlen($captchaStr) || $this->localPiVars['captcha'] != $captchaStr) {
				$errorMessage .= t3blog_div::getSingle(array(
					'value' => $this->pi_getLL('error_captcha')
				), 'errorWrap', $this->conf);
			}
		}

		return $errorMessage;
	}

	/**
	 * Sets cookies for the comments author.
	 *
	 * @param string $commentAuthor
	 * @param string $authorEmail
	 * @param string $authorWebsite
	 * @return void
	 */
	protected function setCommentAuthorCookies($commentAuthor, $authorEmail, $authorWebsite) {
		if ($commentAuthor) {
			setcookie('currentCommentAuthor', $commentAuthor, time()+36000, '/');
			setcookie('currentCommentEmail', $authorEmail, time()+36000, '/');
			setcookie('currentCommentWebsite', $authorWebsite, time()+36000, '/');
		}
	}

	/**
	 * Obtains FE user group ID for the post.
	 *
	 * @param int $postUid
	 * @return int
	 */
	protected function getPostFeGroup($postUid) {
		// get allowed fe_group of the post
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'fe_group', 'tx_t3blog_post', 'uid=' . $postUid);
		return is_array($row) ? intval($row['fe_group']) : 0;
	}

	/**
	 * Checks if a user with given e-mail is already subscribed to receive
	 * notifications about new comments.
	 *
	 * @param int $postUid
	 * @param string $email
	 * @return boolean
	 */
	protected function isSubscribedToPost($postUid, $email) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS t',
			'tx_t3blog_com_nl',
			'post_uid=' . $postUid .' AND email=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($email, 'tx_t3blog_com_nl') .
			$this->cObj->enableFields('tx_t3blog_com_nl'));
		return ($row['t'] > 0);
	}

	/**
	 * Subscribes the user to notifications about the post if he is not
	 * subscribed yet.
	 *
	 * @param int $uid
	 * @param string $author
	 * @param string $email
	 * @return void
	 */
	protected function subscribeToPostNotifications($uid, $author, $email) {
		if (!$this->isSubscribedToPost($uid, $email)) {
			$code = $this->insertNewSubscriber($uid, $author, $email);
			$this->sendSubscribtionConfirmationEmail($uid, $email, $code);

		}
	}

	/**
	 * Sends a subscription confirmation email to a new subscriber.
	 *
	 * @param int $postUid
	 * @param string $email
	 * @param string $unsubscribeCode
	 */
	protected function sendSubscribtionConfirmationEmail($postUid, $email, $unsubscribeCode) {
		$receiver = str_replace(array('\n', '\r'), '', $email);
		$postTitle = $this->getPostTitle($postUid);
		$subject = $this->pi_getLL('subscribe.confirmation') . ': ' . $postTitle;
		$unsubscribeLink = $this->getUnsubscribeLink($postUid, $unsubscribeCode);
		$headers = 'From: <' . $this->conf['senderEmail'] . '>' . chr(10) .
			'List-Unsubscribe: ' . $unsubscribeLink;

		$message = $this->pi_getLL('subscribe.confirmationHello') . chr(10) .
			$this->pi_getLL('subscribe.confirmationtext') . chr(10) .
			'<' . $unsubscribeLink . '>' . chr(10);

		// add footer (optional)
		$message .= chr(10) . $this->pi_getLL('subscribe.optionalFooter');

		t3lib_div::plainMailEncoded($receiver, $subject, $message, $headers);
	}

	/**
	 * Inserts a new post subscriber to the database.
	 *
	 * @param int $postUid
	 * @param string $author
	 * @param string $email
	 * @return void
	 */
	protected function insertNewSubscriber($postUid, $author, $email) {
		$code = md5($email . $GLOBALS['EXEC_TIME']);

		$data = array(
			'pid'		=> t3blog_div::getBlogPid(),
			'tstamp'	=> $GLOBALS['EXEC_TIME'],
			'crdate'	=> $GLOBALS['EXEC_TIME'],
			'email'		=> $email,
			'name'		=> $author,
			'post_uid'	=> $postUid,
			'lastsent'	=> $GLOBALS['EXEC_TIME'],
			'code'		=> $code,
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_t3blog_com_nl', $data);

		return $code;
	}

	/**
	 * Sends e-mails about new comments to the post subscribers.
	 *
	 * @param int $postUid
	 * @return void
	 */
	protected function sendEmailAboutNewComments($postUid) {
		$subscribers = $this->getPostSubscribers($postUid);
		$postTitle = $this->getPostTitle($postUid);
		$whereFormat = 'date>%d AND spam=0 AND approved=1' . str_replace('%', '%%', $this->cObj->enableFields('tx_t3blog_com'));

		foreach ($subscribers as $subscriber) {
			list($comment) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title,text', 'tx_t3blog_com',
				sprintf($whereFormat, intval($subscriber['lastsent'])), 'tstamp DESC', '', 1);

			if (is_array($comment)) {
				// assemble email
				$this->sendUnsubscribeEmail($postUid, $postTitle, $subscriber, $comment);
				$this->updateLastSentTimeForSubscriber($subscriber['uid']);
			}
		}
	}

	/**
	 * Obtains the title of the post
	 *
	 * @param int $postUid
	 * @return string
	 */
	protected function getPostTitle($postUid) {
		list($post)	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title', 'tx_t3blog_post',
			'uid=' . $postUid . $this->cObj->enableFields('tx_t3blog_post'));
		return (is_array($post) ? $post['title'] : '');
	}

	/**
	 * Obtains post's date.
	 *
	 * @param $postUid
	 */
	protected function getPostDate($postUid) {
		list($post)	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('date', 'tx_t3blog_post',
			'uid=' . $postUid . $this->cObj->enableFields('tx_t3blog_post'));
		return (is_array($post) ? intval($post['date']) : 0);
	}

	/**
	 * Obtains post subscribers.
	 *
	 * @param int $postUid
	 * @return array
	 */
	protected function getPostSubscribers($postUid) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
			'tx_t3blog_com_nl',
			'post_uid = ' . $postUid . $this->cObj->enableFields('tx_t3blog_com_nl'));
	}

	/**
	 * Creates an e-mail to unsubscribe from the comment.
	 *
	 * @param int $postUid
	 * @param string $postTitle
	 * @param array $subscriber
	 * @param array $comment 'title' and 'text' fields are required
	 * @return void
	 */
	protected function sendUnsubscribeEmail($postUid, $postTitle, $subscriber, $comment) {
		$unsubscribeLink = '<' . $this->getUnsubscribeLink($postUid, $subscriber['code']) . '>' . chr(10);
		$text = '"' . trim($comment['title']) . ': ' . str_replace(array('<br>', '<br />'), chr(10), trim($comment['text'])) .'"' . chr(10);
		$receiver = str_replace(array('\n', '\r'), '', $subscriber['email']);
		$subject = $this->pi_getLL('subscribe.newComment') . ': ' . $postTitle;
		$from = $this->conf['senderEmail'];
		$headers = 'From: <' . $from . '>' . chr(10) .
			'List-Unsubscribe: ' . $unsubscribeLink;

		$message = $this->pi_getLL('subscribe.salutation') . ' ' . $subscriber['name'] . ',' . chr(10) . chr(10);
		$message .= $this->pi_getLL('subscribe.notification') . chr(10) . chr(10);
		$message .= $text . chr(10);
		$message .= $this->pi_getLL('subscribe.optionalTextBeforePermalink');
		$message .= '<' . t3lib_div::locationHeaderUrl($this->getPermalink($postUid, $this->getPostDate($postUid), true)) . '>' . chr(10) . chr(10);

		// unsubscribe
		$message .= $this->pi_getLL('subscribe.unsubscribe') . chr(10);
		$message .= $unsubscribeLink;

		// add footer (optional)
		$message .= chr(10) . $this->pi_getLL('subscribe.optionalFooter');

		// send
		t3lib_div::plainMailEncoded($receiver, $subject, $message, $headers);
	}

	/**
	 * Updates time stamp for the last sent message for the subscriber.
	 *
	 * @param int $subscribeUid
	 * @return void
	 */
	protected function updateLastSentTimeForSubscriber($subscriberUid) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_com_nl',
			 'uid=' . $subscriberUid, array('lastsent' => time()), 'lastsent');
	}

	/**
	 * splits long words in text
	 *
	 * @param 	string	$text: 	text to be splitted
	 *
	 * @return 	string	splitted string
	 */
	function splitLongWordsInText($text) {
		$stringLength = t3lib_div::intval_positive($this->conf['comment.']['splitLongWordsInComment']);
		// if the value is set to 0 return lines unsplitted
		if (!$stringLength) {
			return $text;
		}

		$words = explode(' ', $text);
		$return = '';
		foreach ($words AS $singleWord) {
			if (strlen($singleWord)>$stringLength) {
				$return .= chunk_split($singleWord, $stringLength, ' ') . ' ';
			}
			else{
				$return .= $singleWord . ' ';
			}
		}
		return $return;
	}

	/**
	 * Sends a received comment per email to the given admin's email address
	 * @author kay stenschke <kstenschke@snowflake.ch>
	 */
	function adminMailComment($data)	{
		list($titleRow) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title',
			'tx_t3blog_post', 'uid=' . intval($this->uid)
		);

		$messageText = $this->cObj->fileResource($this->conf['adminsCommentMailTemplate']);
		$markerArray = array(
			'###TITLE###'		=> $data['title'],
			'###TEXT###'		=> $data['text'],
			'###AUTHOR###'		=> $data['author'],
			'###EMAIL###'		=> $data['email'],
			'###WEBSITE###'		=> $data['website'],
			'###IP###'			=> t3lib_div::getIndpEnv('REMOTE_ADDR'),
			'###TSFE###'		=> t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST'),
			'###POSTTITLE###'   => is_array($titleRow) ? $titleRow['title'] : '',
			'###LINK###'		=> $this->getPermalink($this->uid, $this->getPostDate($this->uid), true),
			'###SPAM###'		=> $data['spam']
		);

		foreach ($markerArray as $key => $val) {
			if (strlen(trim($val)) < 1) {
				$markerArray[$key] = '-';
			}
		}
		$messageSubject = $this->cObj->substituteMarkerArray($this->pi_getLL('commentAdminMailSubject'), $markerArray);
		$messageText = $this->cObj->substituteMarkerArray($messageText, $markerArray);

		t3lib_div::plainMailEncoded(
			$this->conf['adminsCommentsEmail'],			//email (receiver)
			$messageSubject,	//subject
			$messageText,								//message
			'From: ' . $this->conf['adminsCommentsEmailFrom']
		);
	}

	/**
	 * Builds the Navigation for the Single view (next/previous entries).
	 *
	 * TODO Refactor this to get previous and next items without loading all the rest! Note that this function works only for the furst 10 items in the list because of the limit imposed on lists!
	 *
	 * @param 	int $current: current navigation point
	 * @return 	string
	 */
	function getSingleNavigation($current)	{
		include_once('class.listFunctions.php');

		$listFunctions = t3lib_div::makeInstance('listFunctions');
		/* @var listFunctions $listFunctions */
		$listFunctions->cObj = $listFunctions->localcObj = $this->cObj;

		$this->conf['numberOfRecords'] = $listFunctions->getNumberOfListItems();

		$posts = $listFunctions->getClosestPosts($current);
		$data = array(
			'backId' => t3blog_div::getBlogPid(),
			'backText' => $this->pi_getLL('backText'),
			'next' => is_array($posts[0]) ? $this->getTitleLinkedFromRow($posts[0]) : '',
			'previous' => is_array($posts[1]) ? $this->getTitleLinkedFromRow($posts[1]) : ''
		);
		return t3blog_div::getSingle($data, 'singleNavigation', $this->conf);
	}

	/**
	 * A wrapper for the getTitleLinkedFromRow that does some magic on row values.
	 *
	 * @param array $postRow
	 * @return array
	 * @see getTitleLinked
	 */
	protected function getTitleLinkedFromRow(array $postRow) {
		$title = $postRow['title'];
		if (strlen($title) > 28) {
			$title = t3lib_div::fixed_lgd_cs($title, 25);
		}
		return $this->getTitleLinked($title, $postRow['uid'],
			$postRow['crdate'], 'singleNavTitleLink', $postRow['title']);
	}

	/**
	 * Checks if the comment is one of the currently logged in user
	 *
	 * @param 	int $editUid: of the comment id $editUid
	 * @return 	true or false
	 */
	function allowedToEditComment($editUid){
		if ($editUid) {
			list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'email, author', 'tx_t3blog_com', 'uid=' . intval($editUid)
			);
			if ($GLOBALS['TSFE']->fe_user->user['email'] == $row['email'] &&
					$GLOBALS['TSFE']->fe_user->user['name'] == $row['author']) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Gets the trackback data and saves it, if necessary
	 *
	 */
	function trackback() {
		$this->trackbackAddData();
		$this->trackbackSendResponse();
		exit;
	}

	/**
	 * Adds trackback data to the database
	 *
	 * @return void
	 */
	function trackbackAddData() {
		// get the trackback parameters
		$trackbackUrl 		= t3lib_div::_GP('url');
		$trackbackTitle 		= t3lib_div::_GP('title');
		$trackbackExcerpt 		= t3lib_div::_GP('excerpt');
		$trackbackBlogName 	= t3lib_div::_GP('blog_name');

		// save trackback or update, into array first
		$table = 'tx_t3blog_trackback';

		// get a similar trackback (same blog with same title from same url)
		list($rowTrackback) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid', $table,
			'fromurl=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($trackbackUrl, $table) .
				' AND title=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($trackbackTitle, $table) .
				' AND blogname='. $GLOBALS['TYPO3_DB']->fullQuoteStr($trackbackBlogName, $table) .
				' AND postid=' . intval($this->uid) .
				$this->cObj->enableFields($table),
			'', 'uid', '0,1'
		);

		$data = array(
			'pid' => $GLOBALS['TSFE']->id,
			'fromurl' => $GLOBALS['TYPO3_DB']->quoteStr($trackbackUrl, $table),
			'title' => $GLOBALS['TYPO3_DB']->quoteStr($trackbackTitle, $table),
			'postid' => intval($this->uid),
			'blogname' => $GLOBALS['TYPO3_DB']->quoteStr($trackbackBlogName, $table),
			'text' => $GLOBALS['TYPO3_DB']->quoteStr(strip_tags($trackbackExcerpt), $table),
		);

		// New if there is no data found, else an update
		if (is_array($rowTrackback)) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . $rowTrackback['uid'], $data);
			$this->updateRefIndex($table, $rowTrackback['uid']);
		}
		else {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $data);
			$this->updateRefIndex($table, $GLOBALS['TYPO3_DB']->sql_insert_id());
		}
	}

	/**
	 * Sends XML response to trackback sender
	 *
	 * @return void
	 */
	function trackbackSendResponse() {
		$response = '<?xml version="1.0" encoding="UTF-8"?>' . chr(10) .
			'<response><error>0</error></response>';

		header('Content-Type: text/xml');
		header('Content-length: ' . strlen($response));

		echo $response;
	}

	/**
	 * Checks if view counter should be increased.
	 *
	 * @return void
	 */
	function checkRiseViewNumber() {
		$rise = false;

		if (!$this->conf['countBEUsersViews'] && $this->isBEUserLoggedIn()) {
			return $rise;
		}

		if (!isset($_COOKIE['t3blog']) || !t3lib_div::inList($_COOKIE['t3blog'], $this->uid)) {
			$rise = true;
		}
		if ($GLOBALS['TSFE']->fe_user->user['uid']) {
			// User is logged in
			$t3blogData = $GLOBALS['TSFE']->fe_user->getKey('user', 't3blog');
			if (is_array($t3blogData)) {
				$rise = !isset($t3blogData['visited'][$this->uid]);
			}
		}

		return $rise;
	}

	/**
	 * Rise number of views as soon as somebody has viewed the post in the single view
	 *
	 * @param int $postUID
	 * @return void
	 */
	protected function riseViewNumber() {
		if ($this->checkRiseViewNumber()) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_post',
				'tx_t3blog_post.uid=' . $this->uid,
				array(
					'number_views' => 'number_views+1'
				), 'number_views');

			if ($GLOBALS['TSFE']->fe_user->user['uid']) {
				// User is logged in
				$t3blogData = $GLOBALS['TSFE']->fe_user->getKey('user', 't3blog');
				if (!is_array($t3blogData)) {
					$t3blogData = array(
						'visited' => array()
					);
				}
				$t3blogData['visited'][$this->uid] = 1;	// '1' instyead of true to save serialized space
			}

			// Save also for anonymous user
			$cookie = isset($_COOKIE['t3blog']) ? $_COOKIE['t3blog'] . ',' : '';
			$cookie .= $this->uid;
			if (strlen($cookie) > 120) {
				// Too long. Shorten it.
				$cookie = substr($cookie, -120);
				$cookie = substr($cookie, strpos($cookie, ',') + 1);
			}
			setcookie('t3blog', $cookie, time() + 60*60*24*30);
		}
	}

	/**
	 * Updates reference index for the table
	 *
	 * @return void
	 */
	protected function updateRefIndex($table, $id) {
		t3lib_div::requireOnce(PATH_t3lib . 'class.t3lib_refindex.php');
		if (!class_exists('t3lib_BEfunc', true)) {
			t3lib_div::requireOnce(PATH_t3lib . 'class.t3lib_refindex.php');
		}
		$refIndex = t3lib_div::makeInstance('t3lib_refindex');
		/* @var $refIndex t3lib_refindex */
		$refIndex->updateRefIndexTable($table, $id);
	}

	/**
	 * Creates a link to unsubscribe from comment notifications
	 *
	 * @return string
	 */
	protected function getUnsubscribeLink($postUid, $code) {
		$additionalParams = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
			'blogList' => array(
				'showUid' => $postUid,
				'unsubscribe' => 1,
				'code' => $code
			)));
		$typoLinkConf = array(
			'additionalParams' => $additionalParams,
			'parameter' => $GLOBALS['TSFE']->id,
			'no_cache' => true
		);
		$link = t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL($typoLinkConf));
		return $link;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.singleFunctions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.singleFunctions.php']);
}
?>
