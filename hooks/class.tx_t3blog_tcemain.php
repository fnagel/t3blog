<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Snowflake Productions Gmbh (info@snowflake.ch)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * This class implements a hook to TCEmain to ensure that data is correctly
 * inserted to pages when using TemplaVoila. It disables automatic TemplaVoila
 * element referencing for content elements that are part of t3blog post.
 */
class tx_t3blog_tcemain {

	/**
	 * Processes t3blog item deletion
	 *
	 * @param string $command
	 * @param string $table
	 * @param mixed $id
	 * @return void
	 */
	public function processCmdmap_postProcess($command, $table, $id) {
		if ($table == 'tx_t3blog_post' && $command == 'delete') {
			$this->deletePostData(intval($id));
		}
	}
	
	/**
	 * Generate a different preview link
	 *
	 * @param string $status status
	 * @param string $table tablename
	 * @param integer $recordUid id of the record
	 * @param array $fieldArray fieldArray
	 * @param t3lib_TCEmain $parentObject parent Object
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $recordUid, array $fieldArray, t3lib_TCEmain $parentObject) {
			// Preview link
		if ($table === 'tx_t3blog_post') {

				// direct preview
			if (!is_numeric($recordUid)) {
				$recordUid = $parentObject->substNEWwithIDs[$recordUid];
			}

			if (isset($GLOBALS['_POST']['_savedokview_x']) && !$fieldArray['type'] && !$GLOBALS['BE_USER']->workspace) {
					// if "savedokview" has been pressed and current article has "type" 0 (= normal news article)
					// and the beUser works in the LIVE workspace open current record in single view
				$pagesTsConfig = t3lib_BEfunc::getPagesTSconfig($GLOBALS['_POST']['popViewId']);
				if ($pagesTsConfig['tx_t3blog_pi1.']['singlePid']) {
					$record = t3lib_BEfunc::getRecord('tx_t3blog_post', $recordUid);

					$params = '&no_cache=1';
					if ($record['sys_language_uid'] > 0) {
						if ($record['l10n_parent'] > 0) {
							$params .= '&tx_t3blog_pi1[blogList][showUidPerma]=' . $record['l10n_parent'];
						} else {
							$params .= '&tx_t3blog_pi1[blogList][showUidPerma]=' . $record['uid'];
						}

						$params .= '&L=' . $record['sys_language_uid'];
					} else {
							$params .= '&tx_t3blog_pi1[blogList][showUidPerma]=' . $record['uid'];
					}

					$GLOBALS['_POST']['popViewId_addParams'] = $params;
					$GLOBALS['_POST']['popViewId'] = $pagesTsConfig['tx_t3blog_pi1.']['singlePid'];
				}
			}
		}
	}

	/**
	 * Checks if TemplaVoila references should be disabled for this record
	 *
	 * @param string $status
	 * @param string $table
	 * @param int $id
	 * @param array $fieldArray
	 * @param t3lib_TCEmain $pObj
	 * @see t3lib_TCEmain::process_datamap()
	 * @see tx_templavoila_tcemain::processDatamap_afterDatabaseOperations()
	 */
	public function processDatamap_preProcessFieldArray(array $incomingFieldArray, $table, $id, t3lib_TCEmain &$pObj) {
		if (t3lib_extMgm::isLoaded('templavoila' &&
				!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'])) ||
				!$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage']) {
			$disableReferencing = false;
			if ($table == 'tx_t3blog_post') {
				$disableReferencing = true;
			}
			elseif ($table == 'tt_content' && $this->isT3BlogPage($this->getPid($incomingFieldArray, $id))) {
				$disableReferencing = true;
			}
			if ($disableReferencing) {
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_tcemain']['doNotInsertElementRefsToPage'] = true;
			}
		}
	}

	/**
	 * Deletes all data associated with the post when post is deleted
	 *
	 * @param int $id
	 * @return void
	 */
	protected function deletePostData($id) {
		$command = array(
			'tx_t3blog_com' => $this->getDeleteArrayForTable($id, 'tx_t3blog_com', 'fk_post'),
			'tx_t3blog_com_nl' => $this->getDeleteArrayForTable($id, 'tx_t3blog_com_nl', 'post_uid'),
			'tx_t3blog_trackback' => $this->getDeleteArrayForTable($id, 'tx_t3blog_trackback', 'postid'),
			'tt_content' => $this->getDeleteArrayForTable($id, 'tt_content', 'irre_parentid', ' AND irre_parenttable=\'tx_t3blog_post\'')
		);
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		/* @var $tce t3lib_TCEmain */
		$tce->start(array(), $command);
		$tce->process_cmdmap();
	}

	protected function getDeleteArrayForTable($postId, $tableName, $fieldName, $extraWhere = '') {
		$command = array();
		$where = $fieldName . '=' . $postId . t3lib_BEfunc::deleteClause($tableName) . $extraWhere;
		$data = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $tableName, $where);
		foreach ($data as $record) {
			$command[$record['uid']]['delete'] = 1;
		}
		return $command;
	}

	/**
	 * Obtains page if for the record
	 *
	 * @param array $incomingFieldArray
	 * @param mixed $recordId
	 * @return void
	 */
	protected function getPid(array $incomingFieldArray, $recordId) {
		$pid = 0;
		if (isset($incomingFieldArray['pid']) &&
				t3lib_div::testInt($incomingFieldArray['pid']) &&
				$incomingFieldArray['pid'] > 0) {
			$pid = $incomingFieldArray['pid'];
		}
		elseif (t3lib_div::testInt($recordId) && $recordId > 0) {
			$record = t3lib_BEfunc::getRecord('tt_content', $recordId,  'pid');
			$pid = $record['pid'];
		}
		return $pid;
	}

	/**
	 * Checks if this page is a t3blog page
	 *
	 * @return boolean
	 */
	protected function isT3BlogPage($pageId) {
		$pageRecord = t3lib_BEfunc::getRecord('pages', $pageId, 'module');
		return (is_array($pageRecord) && $pageRecord['module'] == 't3blog');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/hooks/class.tx_t3blog_tcemain.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/hooks/class.tx_t3blog_tcemain.php']);
}

?>