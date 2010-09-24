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
require_once(t3lib_extMgm::extPath('t3blog') . 'pi1/lib/class.t3blog_div.php');
require_once(t3lib_extMgm::extPath('t3blog') . 'pi1/lib/class.t3blog_db.php');


/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 *
 * @author	snowflake <typo3@snowflake.ch>
 * @package	TYPO3
 * @subpackage	tx_t3blog
 */
class tx_t3blog_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_t3blog_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_t3blog_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = true;
	var $widgetFolder;

	/**
	 * The content object for use in widgets.
	 *
	 * @var tslib_cObj
	 */
	var $localcObj;


	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @param	bool		$exclusiveWidget: render just one widget exclusively? (evoked from pi2 than)
	 * @param	string		$widgetname: name of widget to be rendered exclusively
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf)	{
		$this->conf = $conf;
		$this->init();
		$data = array();
		$js = '';

		$mbEncoding = mb_internal_encoding();
		mb_internal_encoding($GLOBALS['TSFE']->metaCharset);

		if (is_array($conf['widget.'])) {	// get widgets from TS:
			foreach ($conf['widget.'] as $widgetname => $widgetconf){
				if(strpos($widgetname, '.')){
					$widgetname = trim($widgetname, '.');
					$content = $this->callWidget($widgetname, $widgetconf);
					if ($content) {
						$data[$widgetname] = $content;
					}
					if ($widgetconf['jsFiles.']) {	//get js files
						foreach ($widgetconf['jsFiles.'] as $file){
							$js .= $this->includeJavaScript($this->widgetFolder . $widgetname. '/', $file);
						}
					}
				}
			}
		}
		if($js){
			$GLOBALS['TSFE']->additionalHeaderData[$this->extKey] = $js;
		}

		$GLOBALS['TSFE']->additionalHeaderData['t3b_pingback'] = '<link rel="pingback" href="' . htmlspecialchars($this->getPingbackUrl()) . '" />';
		$content = t3blog_div::getSingle($data, 'template', $this->conf);

		mb_internal_encoding($mbEncoding);

		return $content;
	}


	/**
	 * Initial Method
	 *
	 */
	function init(){
		$this->widgetFolder = t3lib_extMgm::siteRelPath('t3blog') . 'pi1/widgets/';
		$this->localcObj = t3lib_div::makeInstance('tslib_cObj');
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
	}

	/**
	 * gets the widget content. uses the widgetname from the ts.
	 *
	 * @param 	string 	$widgetname
	 * @param 	array 	$widgetconf
	 * @return 	string 	html content
	 */
	function callWidget($widgetName, array $widgetConf){
		$content = '';

		$widgetPath = $this->getWidgetPath($widgetName, $widgetConf);
		if (is_file($widgetPath)) {
			t3lib_div::requireOnce($widgetPath);
			$widget = t3lib_div::makeInstance($widgetName);
			$content = $widget->main('', $widgetConf, $this->piVars, $this->localcObj);
		}
		return $content;
	}

	/**
	 * Obtains a path to the widget
	 *
	 * @param string $widgetKey
	 * @param array $widgetConf
	 * @return A path to the widget
	 */
	protected function getWidgetPath($widgetKey, array $widgetConf) {
		if (isset($widgetConf['includeLibs'])) {
			$widgetPath = $GLOBALS['TSFE']->tmpl->getFileName($widgetConf['includeLibs']);
		}
		else {
			$widgetPath = $this->widgetFolder . $widgetKey . '/class.' . $widgetKey . '.php';
		}
		return $widgetPath;
	}

	/**
	 * render js include tag to embed an external js file via src-param.
	 *
	 * @param string $path
	 * @param string $file
	 * @return string
	 */
	function includeJavaScript($path, $file)	{
		$rc = '<script src="' .	htmlspecialchars($path . $file) . '" type="application/javascript"></script>';

		return $rc;
	}

	/**
	 * Parses data through typoscript.
	 *
	 * @param	String[]	$data: Data which will be passed to the typoscript.
	 * @param	String		$typoScriptProperty: The typoscript which will be called.
	 */
	function getSingle(array $data, $typoScriptProperty) {
		$this->localCobj->data = $data;
		return $this->localCobj->cObjGetSingle($this->conf[$typoScriptProperty], $this->conf[$typoScriptProperty . '.']);
	}

	/**
	 * Generates a pingback URL
	 *
	 * @return string
	 */
	protected function getPingbackUrl() {
		// Note typoLink does not work on eID links! It is better to avoid that.
		return t3lib_div::locationHeaderUrl('index.php?id=' . $GLOBALS['TSFE']->id .
			'&eID=t3b_pingback');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/class.tx_t3blog_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/class.tx_t3blog_pi1.php']);
}

?>