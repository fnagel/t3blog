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

/* $Id$ */

/**
 * This class sends trackback notifications to other blogs
 *
 * FIXME The logic in this class is incorrect. It will send trackbacks to
 * all sites again if a new link is added to the post. In practice it should
 * send only new links. The best solution is to get rid of tilde�separated
 * list and make a serialized array with send/not�sent flag -- Dmitry.
 */
class tx_t3blog_sendtrackback {

	/**
	 * Sends trackbacks for the given blog
	 *
	 * @return boolean true if one or more trackbacks were sent
	 */
	public function sendTrackbacks($blogUid, $uid) {
		$count = 0;

		require_once(t3lib_extMgm::extPath('t3blog') . 'pi1/lib/trackback_cls.php');
		$row = $this->getBlogPostForTrackback($uid);

		// if a post is available
		if (is_array($row) && strlen($row['trackback']) > 0 && $row['trackback_hash'] != md5($row['trackback'])) {

			// split urls by tilde (chr126)
			$urls[] = explode(chr(126), $row['trackback']);

			if (count($urls) > 0 && $urls[0][0]!=''){

				$title = $row['title'];
				$text = $this->getBlogEntryText($row['uid']);
				$text = t3lib_div::fixed_lgd_cs($text, 250);
				$permalink = $this->getBlogPostURL($blogUid, $row['uid']);
				$author = $this->getBlogEntryAuthorName($row['author']);
				$t3blogName = $this->getBlogName($blogUid);

				// initialize trackback
				$trackback 	= new Trackback($t3blogName, $author, 'UTF-8');

				foreach ($urls[0] AS $url){
					if (filter_var($url, FILTER_VALIDATE_URL)) {
						if ($trackback->ping($url, $permalink, $title, $text)) {
							$count++;
						}
					}
				}

				$this->updateTrackbackHash($uid, $row['trackback']);
			}
		}
		return $count;
	}
	/**
	 * Renders a TYPO3 href url
	 *
	 * @param    	integer $targetId page id
	 * @param    	string  $blogId Blog id
	 * @return		string  the link url, not being htmlspecialchar'ed yet
	 */
	protected function getBlogPostURL($pageId, $blogId) {	
		// get blog post date 
		$date = $this->getBlogEntryDate($blogId);
		$day 	= strftime('%d', $date);
		$month 	= strftime('%m', $date);
		$year	= strftime('%Y', $date);	
		$permaLinkParameters = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
			'blogList' => array(
				'day' => $day,
				'month' => $month,
				'year' => $year,
				'showUid' => $blogId
			)
		));		
		if (t3lib_extMgm::isLoaded('pagepath')) {
			t3lib_div::requireOnce(t3lib_extMgm::extPath('pagepath', 'class.tx_pagepath_api.php'));
			$link = tx_pagepath_api::getPagePath($pageId, $permaLinkParameters);			
		}
		else {		
			// BUG link does not work with wordpress cause of [] braces
			$link = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . '?id=' . $pageId . $permaLinkParameters;
		}
		return $link;
	}
	
	/**
	 * Obtains the date of the blog entry
	 *
	 * @param int uid of the blog entry
	 * @return timetamp int
	 */
	protected function getBlogEntryDate($blogEntryUid) {		
		$where = 'uid=' . intval($blogEntryUid) . 
			t3lib_BEfunc::BEenableFields('tx_t3blog_post') .
			t3lib_BEfunc::deleteClause('tx_t3blog_post');
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'date', 'tx_t3blog_post', $where);
			
		$result = '';
		if (is_array($row)) {
			$result = $row['date'];
		}

		return $result;
	}

	/**
	 * Obtains the text of the blog entry
	 *
	 * @param int $blogEntryUid
	 * @return string
	 */
	protected function getBlogEntryText($blogEntryUid) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'TRIM(CONCAT(header, \' \', bodytext)) AS text',
			'tt_content', 'irre_parentid=' . intval($blogEntryUid) .
			' AND irre_parenttable=\'tx_t3blog_post\'' .
			' AND CType IN (\'text\', \'textpic\')' .
			' AND TRIM(bodytext)<>\'\'' .
			t3lib_BEfunc::BEenableFields('tt_content') .
			t3lib_BEfunc::deleteClause('tt_content'), '', 'sorting', 1);
			
		// if the post has content, set text
		$result = '';
		if (is_array($row)) {
			// we dont like typo3 rte tags in our text
			$result = strip_tags($row['text']);
		}

		return $result;
	}

	/**
	 * Obtains a single blog post for the trackback generation
	 *
	 * @param int $uid id of the blog post
	 * @return mixed Blog post fields array or null if not found
	 */
	protected function getBlogPostForTrackback($uid) {
		$where = 'tx_t3blog_post.uid=' . intval($uid) .
			t3lib_BEfunc::BEenableFields('tx_t3blog_post') .
			t3lib_BEfunc::deleteClause('tx_t3blog_post');
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, trackback, trackback_hash, author, title',
			'tx_t3blog_post', $where
		);
		return $row;
	}

	/**
	 * Obtains blog entry author name by his id
	 *
	 * @param int $authorId
	 * @return string
	 */
	protected function getBlogEntryAuthorName($authorId) {
		$user = t3lib_BEfunc::getRecord('be_users', $authorId, 'realName');
		if (is_array($user) && $user['realName']) {
			$author = $user['realName'];
		}
		else {
			$author = 'Admin';
		}
		return $author;
	}

	/**
	 * Obtains blog name (page title) from page id
	 *
	 * @param int $pid
	 * @return string
	 */
	protected function getBlogName($pid) {
		$page = t3lib_BEfunc::getRecord('pages', $pid, 'title');
		return is_array($page) ? $page['title'] : '';

	}

	/**
	 * Updates trackback hash value
	 *
	 * @param int $postUid
	 * @param string $trackbacks
	 * @return void
	 */
	protected function updateTrackbackHash($postUid, $trackbacks) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_t3blog_post',
			'uid=' . intval($postUid),
			array(
				'trackback_hash' => md5($trackbacks)
			)
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/lib/class.tx_t3blog_sendtrackback.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/lib/class.tx_t3blog_sendtrackback.php']);
}

?>