<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Manu Oehler <moehler@snowflake.ch>
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


/**
 * Class that adds the wizard icon.
 *
 * @author    Manu Oehler <moehler@snowflake.ch>
 * @package    TYPO3
 * @subpackage    tx_t3blog
 */
class tx_t3blog_pi2_wizicon {

	/**
	 * Processing the wizard items array
	 *
	 * @param array $wizardItems The wizard items
	 * @return array Modified array with wizard items
	 */
	function proc($wizardItems) {
		$wizardItems['plugins_tx_t3blog_pi2'] = array(
			'icon' => t3lib_extMgm::extRelPath('t3blog') . 'pi2/ce_wiz.gif',
			'title' => $GLOBALS['LANG']->sL('EXT:t3blog/locallang.xml:pi2_title'),
			'description' => $GLOBALS['LANG']->sL('EXT:t3blog/locallang.xml:pi2_plus_wiz_description', $LL),
			'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=t3blog_pi2'
		);

		return $wizardItems;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi2/class.tx_t3blog_pi2_wizicon.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi2/class.tx_t3blog_pi2_wizicon.php']);
}

?>