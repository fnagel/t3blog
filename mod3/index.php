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
 * $Id$
 */
unset($MCONF);
require_once('conf.php');
require_once($GLOBALS['BACK_PATH'] . 'init.php');
require_once($GLOBALS['BACK_PATH'] . 'template.php');

require_once(t3lib_extMgm::extPath('t3blog', 'lib/class.tx_t3blog_modbase.php'));

$LANG->includeLLFile('EXT:t3blog/mod3/locallang.xml');

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * Returns the Comments administration
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class tx_t3blog_module3 extends tx_t3blog_modbase {

	/**
	 * Number of comments for this view
	 *
	 * @var int
	 */
	protected $numberOfComments;

	/**
	 * Initializes the module.
	 *
	 * @return void
	 */
	public function init() {
		$this->defaultSort = 'date DESC';
		$this->validSortFields = 'date,title,text,author,post_title';

		parent::init();

		if ($this->hasAccess())	{
			$this->numberOfComments = $this->getNumberOfComments();
		}
	}

	/**
	 * Obtains the content for this module
	 *
	 * @return string
	 * @see tx_t3blog_modbase::getModuleContent()
	 */
	protected function getModuleContent() {
		return $this->getComments();
	}

	/**
	 * Obtains a tital number of items for this view
	 *
	 * @return int
	 * @see tx_t3blog_modbase::getNumberOfItems()
	 */
	protected function getNumberOfItems() {
		return $this->numberOfComments;
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

		// Approve / Unapprove button
		if ($row['approved'] == 1)	{

			$this->emailApprovedCommentsToSubscribers();

			$params	= '&data['. $table. ']['. $row['uid']. ']['.'approved'.']=0';
			$cells .= $this->makeCells($params, 'thumb_up.png', 'unapprove');
		}
		else {
			$params	= '&data['. $table. ']['.$row['uid'].']['.'approved'.']=1';
			$cells .= $this->makeCells($params, 'thumb_down.png', 'approve');
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

		// Spam / not spam
		$spamFieldFormat = sprintf('&data[%s][%d]['.'spam'.']=%%d', $table, $row['uid']);
		if ($row['spam'] == 1)	{
			$params	= sprintf($spamFieldFormat, '0');
			$cells .= $this->makeCells($params, 'flag_red.png', 'markAsNotSpam');
		} else {
			$params	= sprintf($spamFieldFormat, '1');
			$cells .= $this->makeCells($params, 'flag_green.png', 'markAsSpam');
		}

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
			'table' => 'tx_t3blog_com',
			'title' => $GLOBALS['LANG']->getLL('createNewComment')
		);
	}

	/**
	 * Creates additional SQL WHERE statement if we have to limit comments by
	 * certain post
	 *
	 * @return string
	 */
	protected function getCommentLimitByPost() {
		$commentUid = intval(t3lib_div::_GP('linkCom'));
		return ($commentUid ? ' AND tx_t3blog_post.uid=' . $commentUid : '');
	}

	/**
	 * Creates SQL where statement for comments
	 *
	 * @return string
	 */
	protected function getSQLWhereForComments() {
		return 'tx_t3blog_com.fk_post=tx_t3blog_post.uid' .
			' AND tx_t3blog_com.pid=' . $this->id .
			$this->getCommentLimitByPost() .
			$this->getSearchSQLWhere('tx_t3blog_com') .
			t3lib_BEfunc::deleteClause('tx_t3blog_com') .
			t3lib_BEfunc::deleteClause('tx_t3blog_post');
	}

	/**
	 * Obtains a total visible number of comments for this page
	 *
	 * @return int
	 */
	protected function getNumberOfComments() {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'COUNT(DISTINCT(tx_t3blog_com.uid)) as counter',
			'tx_t3blog_post,tx_t3blog_com',
			$this->getSQLWhereForComments()
		);
		return $row['counter'];
	}

	/**
	 * Obtains a list of comments
	 *
	 * @return string
	 */
	protected function getCommentList() {
		$start = ($this->currentPage - 1)*$this->numberOfComments;
		$limit = $start . ',' . $this->numberOfComments;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'distinct tx_t3blog_com.uid as uid,tx_t3blog_com.fk_post as fk_post, ' .
				'tx_t3blog_com.title as title, tx_t3blog_com.text as text, ' .
				'tx_t3blog_com.date as date, tx_t3blog_com.author as author, ' .
				'tx_t3blog_post.title as post_title, tx_t3blog_com.hidden as hidden, ' .
				'tx_t3blog_com.approved as approved, tx_t3blog_com.spam as spam, ' .
				'tx_t3blog_com.tstamp as tstamp, tx_t3blog_com.crdate as crdate',
			'tx_t3blog_post,tx_t3blog_com',
			$this->getSQLWhereForComments(),
			'',
			$this->getListSortClause(),
			$limit
		);

		$rows = array();
		while (false != ($data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$oddEven = ((count($rows) % 2) == 0 ? 'even' : 'odd');

			// update unseen comments. Is it necessary?
                        // FIXME: Why should we do this? Anyway, this does not work because $dsNormalList['uid'] is empty
			/*
                         * $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_com',

				'uid=' . $dsNormalList['uid'], array(
					'tstamp' => time()

			));
                         *
                         */

			// FIXME Fixed date format
			$id = $data['uid'];
			$rows[] = '<tr class="' . $oddeven . '">
				<td title="id=' . $id . '">' . date('d.m.y H:i:s', $data['date']) . '</td>
				<td title="id=' . $id . '">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($data['title'], 20)) . '</td>
				<td title="id=' . $id . '">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($data['text'], 50)) . '</td>
				<td title="id=' . $id . '">' . htmlspecialchars($data['author']) . '</td>
				<td title="id=' . $data['fk_post'] . '">' . htmlspecialchars($data['post_title']) . '</td>
				<td>' . $this->getFunctions('tx_t3blog_com', $data) . '</td>
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
	 * Generates table header for comments
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
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'date', 'ASC')) . '">' . $iconAsc . '</a>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'date', 'DESC')) . '">' . $iconDesc . '</a>
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
					<b>' . $GLOBALS['LANG']->getLL('author', true) . '</b>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'date', 'ASC')) . '">' . $iconAsc . '</a>
					<a href="' . htmlspecialchars(sprintf($urlFormat, 'date', 'DESC')) . '">' . $iconDesc . '</a>
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
	 * Obtains a table with comments
	 *
	 * @return string
	 */
	protected function getComments() {
		return $this->getTableHeader() . $this->getCommentList() . '</table>';
	}

	/**
	 * Sends a notification about approved comments to subscribers
	 *
	 * FIXME There is something wrong with logic here. Questions:
	 * - Why does it have to be called on each tool display?
	 * - Why does it have a flag to run only once?
	 * - Why does it have to be in this module at all?
	 *
	 * FIXME This function is not refactored yet!
	 *
	 * @return void
	 */
	protected function emailApprovedCommentsToSubscribers() {
		// email new approved comment to subscribed users
		static $done = false;	// What does it do exactly???
		if(!$indicator) {
			// select all unapproved comments
			$table	= 'tx_t3blog_com';
			$field	= 'DISTINCT uid, fk_post';
			$where	= 'pid = '.$this->id .' AND approved = 0 AND hidden = 0 AND deleted = 0';
			$com	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field, $table, $where);

			// get distinct posts
			foreach($com as $key => $value) {

				if(!strlen(strstr($posts, $value['fk_post'].','))) {
					$posts.= $value['fk_post'].',';
				}
			}

			// implode posts
			$posts	= explode(',', $posts);
			foreach($posts as $key => $value) {
				if($value == "") {
					unset($posts[$key]);
				}
			}

			$posts = array_values($posts);

			// get all subscribers for this post
			foreach($posts as $key => $postuid) {

				// get users that subscribed to this post
				$table_send	= 'tx_t3blog_com_nl';
				$field_send	= '*';
				$where_send	= 'post_uid = '.$postuid .' AND hidden = 0 AND deleted = 0';
				$subscriber	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_send, $table_send, $where_send);

				// get name of the post
				$table_post	= 'tx_t3blog_post';
				$field_post	= 'title';
				$where_post = 'uid ='.$postuid.' AND hidden = 0 AND deleted = 0';
				$post		= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_post, $table_post, $where_post);
				$posttitle	= $post['0']['title'];

				foreach($subscriber as $key => $value) {

					$table_com	= 'tx_t3blog_com';
					$field_com	= '*';
					$where_com	= 'date > '.$value['lastsent']. ' AND hidden = 0 AND deleted = 0 AND spam = 0 AND approved = 1';
					$comments	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_com, $table_com, $where_com);
					$message 	= '';

					if(count($comments) > 0) {

						// assemble email
						$unsubscribe	= '<http://'.$_SERVER['SERVER_NAME'].'/index.php?id='.$this->id.'&tx_t3blog_pi1[blogList][showUid]='.$postuid.'&tx_t3blog_pi1[blogList][unsubscribe]=1&tx_t3blog_pi1[blogList][code]='.$value['code'].'&no_cache=1>' ."\n";
						$text			= '"'.trim($comments['0']['title']). ': '. trim($comments['0']['text']).'"'. "\n";
						$address		= str_replace(array('\\n', '\\r'), '', $value['email']);
						$receiver   	= $address;
						$subject		= $GLOBALS['LANG']->getLL('subscribe.newComment').': '.$posttitle;
						$headers    	= 'From: ' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['sendermail'];

						$message       .= $GLOBALS['LANG']->getLL('subscribe.salutation') .' '.$value['name'].','. "\n";
						$message       .= $GLOBALS['LANG']->getLL('subscribe.notification') . "\n\n";
						$message       .= $text . "\n";

						// unsubscribe
						$message       .= $GLOBALS['LANG']->getLL('subscribe.unsubscribe') ."\n";
						$message	   .= $unsubscribe;

						// send
						t3lib_div::plainMailEncoded($receiver,$subject,$message,$headers);

						// update lastsent to the last comment time
						$where_lastsent		= 'uid = '.$value['uid'];
						$fields_lastsent 	= array(
							'lastsent' => time(),
						);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table_send, $where_lastsent, $fields_lastsent);
					}

				}
			}
		}

		$indicator = true;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod3/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod3/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module3');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>