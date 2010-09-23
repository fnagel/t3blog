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
 * This widget shows posts sorted by amount of views.
 *
 * @author	snowflake <typo3@snowflake.ch>
 * @package	TYPO3
 * @subpackage	tx_t3blog
 */
class views extends tslib_pibase {
	var $prefixId      = 'views';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/views/class.views.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.

	/**
	 * The main method of the PlugIn
	 * @author 	Meile Simon <smeile@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->conf = $conf;
		$this->pi_loadLL();
		$numberOfItems = $this->conf['numberOfItems']?$this->conf['numberOfItems']:5;

		$content = '';
		$list = t3blog_db::getPostByWhere('pid = '.t3blog_div::getBlogPid(),'number_views DESC', '0,'.$numberOfItems );
		if ($list) {
			$listElements = '';
			foreach ($list as $row){
				$data = array(
					'title'=> t3blog_div::getSingle(
						array(
							'text'		=> $row['title'],
							'uid'		=> $row['uid'],
							'author'	=> $this->pi_getLL('author').t3blog_div::getAuthorByUid($row['author']),
							'date'		=> $row['date'],
							'blogUid'	=> t3blog_div::getBlogPid(),
						),
						'link', $this->conf),
					'date'=> $row['date'],
					'number_views'		=> $row['number_views'],
				);
				$listElements.= t3blog_div::getSingle($data, 'listItem', $this->conf);
			}

			$content = t3blog_div::getSingle(array('title'=>$this->pi_getLL('viewsTitle'),'listItems'=>$listElements), 'list', $this->conf);
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/views/class.views.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/views/class.views.php']);
}

?>