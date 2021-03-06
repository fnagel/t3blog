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
class tagCloud extends tslib_pibase {
	var $prefixId = 'tagCloud';
	var $scriptRelPath = 'pi1/widgets/tagCloud/class.tagCloud.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $tagArray; 			// array([tag]=>(string)NAME, [count]=>(int)COUNT)
	var $link; 				// string
	var $minsize; 			// float
	var $maxsize; 			// float
	var $maxTagsToShow;     // int
	var $unit; 				// string
	var $thresholds; 		// int
	var $mincolor; 			// string
	var $maxcolor; 			// string
	var $count; 			// int
	var $sort; 				// string
	var $sortingMode; 			// string
	var $linkTitle; 		// string
	var $distribution; 		// string
	var $string; 			// boolean
	var $tag_array = array();


	/**
	 * The main method of the PlugIn
	 * @author 	Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars) {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->conf = $conf;
		$this->link = '/%tag%';
		$this->minsize = $conf['minFontSize'];
		$this->maxsize = $conf['maxFontSize'];
		$this->maxTagsToShow = $conf['maxTagsToShow'];
		$this->unit = $conf['unit'];
		$this->thresholds = 8;
		$this->mincolor = $conf['minColor'];
		$this->maxcolor = $conf['maxColor'];
		$this->sort = 'count';	// FIXME Always to to this value, never changed!
		$this->sortingMode = 'asc';
		$this->linkTitle = '%count% items';
		$this->distribution = $conf['renderingAlgorithm'];

		mb_internal_encoding($GLOBALS['TSFE']->renderCharset);

		$this->extractAndCountTags();
		$content = $this->calculateTagCloud();

		$this->pi_loadLL();
		$title = $this->pi_getLL('tagCloudTitle');

		return t3blog_div::getSingle(array('data'=>$content,'title'=>$title), 'globalWrap', $this->conf);
	}

	/**
	 * Generates a tag clound array.
	 *
	 * @return void
	 */
	function extractAndCountTags() {
		$this->tagArray = array();

		$posts = $GLOBALS['TYPO3_DB']->exec_SELECTquery('TRIM(tagClouds) AS tags', 'tx_t3blog_post',
            'pid='.t3blog_div::getBlogPid() . ' AND TRIM(tagClouds)<>\'\'' . $this->cObj->enableFields('tx_t3blog_post'));
		while (false !== ($resPost = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($posts))) {
			$tags = explode(',' , mb_strtolower($resPost['tags']));
			foreach ($tags as $tag) {
				$this->collectTag($tag);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($posts);
	}

	/**
	 * Adds the tag top the tag array and updates the count as appropriate.
	 *
	 * @param string $tag
	 * @return void
	 */
	protected function collectTag($tag) {
		$tag = trim($tag);
		$key = strtolower($tag);
		if (isset($this->tagArray[$key])) {
			$this->tagArray[$key]['count']++;
		}
		else {
			$this->tagArray[$tag] = array(
				'tag' => $tag,
				'count' => 1,
			);
		}
	}

	/**
	 * main function to calculate tags size and color
	 *
	 * @return string cloud
	 */
	function calculateTagCloud() {
		$tags = $this->tagArray;

		if (strcasecmp($this->sort, 'tag') == 0) {
			$tags = $this->arraySort2D($tags, 'tag', $this->sortingMode);
		}
		elseif (strcasecmp($this->sort, 'count') == 0) {
			usort($tags, array($this, 'countCompare'));
		}
		else {
			shuffle($tags);
		}

		$maxcount = -PHP_INT_MAX; $mincount = PHP_INT_MAX;
		foreach ($tags as $k => $_v) {
			if ($_v['count'] > $maxcount) {
				$maxcount = $_v['count'];
			}
			if ($_v['count'] < $mincount) {
				$mincount = $_v['count'];
			}
		}

		if (t3lib_div::inList('em,ex,cm,in', $this->unit)) {
			$this->maxsize *= 100;
			$this->minsize *= 100;
		}

		if (empty($this->thresholds)) {
			$this->thresholds = $this->maxsize - $this->minsize + 1;
		}

		if (!t3lib_div::inList('em,ex,cm,in,pt,px,mm,pc,%', $this->unit)) {
			$this->maxsize = $this->minsize + $this->thresholds - 1;
		}
		elseif (!empty($this->mincolor) && !empty($this->maxcolor)) {
			$_colors = $this->getColorThresholds($this->mincolor, $this->maxcolor, $this->thresholds);
		}

		$_s = array();
		$_c = array();
		$cloud = '';

		if ($this->conf['sortBy'] == 'tag') {
			uasort($tags, array($this, 'sortByTag'));
		}
		else {
			uasort($tags, array($this, 'sortByCountValue'));
			array_splice($tags, $this->maxTagsToShow);
		}
		// End

		foreach ($tags as $k => $v) {
			$count = $v['count'];
			if (empty($_s[$count])) {
				$func = ($this->distribution == 'lin' ? 'getTagSizeLinear' : 'getTagSizeLogarithmic');
				$_s[$count] = $this->$func($count, $mincount, $maxcount, $this->minsize, $this->maxsize, $this->thresholds);
				if ($_colors) {
					$_c[$count] = $this->$func($count, $mincount, $maxcount, 1, $this->thresholds, $this->thresholds);
				}
			}

			$l 	= str_replace('%tag%', $v['tag'], $this->link);
			$lt = str_replace('%tag%', $v['tag'], $this->linkTitle);
			$lt = str_replace('%count%', $count, $lt);
			if (in_array(mb_strtolower($this->unit), array('pt','px','mm','pc','%'))) {
				$s 	= "style=\"font-size:".$_s[$count].strtolower($this->unit).";".($_colors?"color:".$_colors[$_c[$count]-1].";":"")."\"";
			}
			elseif (in_array(mb_strtolower($this->unit), array('em','ex','cm','in'))) {
				$s 	= "style=\"font-size:".($s_[$count]/=100).strtolower($this->unit).";".($_colors?"color:".$_colors[$_c[$count]-1].";":"")."\"";
			}
			else {
				$s 	= "class=\"".$this->unit."-".$_s[$count]."\"";
			}

			$l 	= substr($l, 1);
			if ($v['tag']) {
				$cloud .= t3blog_div::getSingle(array('text'=>$this->getLink($v['tag'], array('tags' => $l), $s.(empty($lt)?"":" title=\"".$lt."\""))), 'item', $this->conf);
			}
		}

		$cloud = t3blog_div::getSingle(array('text'=>$cloud), 'list', $this->conf);
		return $cloud;
	}

	/**
	 * Compare arrays by "count" attribute
	 *
	 * @return int
	 */
	function sortByCountValue($a, $b) {
		return ($b['count'] - $a['count']);
	}

	/**
	 * Compare arrays by "count" attribute
	 *
	 * @return int
	 */
	function sortByTag($a, $b) {
		return strcmp($a['tag'], $b['tag']);
	}

	/**
	 * Gets link to cloud
	 *
	 * @param 	string		$str: name of the cloud
	 * @param	array		$overrulePIVars
	 * @param	string		$additionalATagParams
	 *
	 * @return	link to cloud
	 *
	 */
	function getLink($str, $overrulePIVars, $additionalATagParams) {
		$overrulePIVars = t3lib_div::array_merge_recursive_overrule($this->piVars, $overrulePIVars);
		$conf = array(
			'additionalParams' => t3lib_div::implodeArrayForUrl('tx_t3blog_pi1',array('blogList' => $overrulePIVars),'',1),
			'ATagParams' => $additionalATagParams,
			'parameter' => t3blog_div::getBlogPid(),
			'useCacheHash' => 1
		);

		return $this->cObj->typoLink(htmlspecialchars($str), $conf, 1);
	}


	/**
	 * Sorts an array
	 *
	 * @param 	array		$_array: array to be sorted
	 * @param	string		$key
	 * @param	string		$sorting
	 *
	 * @return	sorted array
	 *
	 */
	function arraySort2D($array, $key, $sorting='asc') {
		if (!is_array($array) || empty($array)) {
			return FALSE;
		}
		$a = array();
		foreach ($array as $k => $v) {
			$a[$k] = $v[$key];
		}
		natcasesort($a);
		if ($sorting == 'desc') {
			$a = array_reverse($a, TRUE);
		}
		$arr = array();
		foreach ($a as $k => $v) {
			array_push($arr, $array[$k]);
		}
		return $arr;
	}


	/**
	 * Compares counts
	 *
	 * @param 	array		$_a
	 * @param	array		$_b
	 *
	 * @return	amount
	 */
	function countCompare($_a, $_b) {
		if ($_a['count'] == $_b['count']) {
			return strnatcasecmp($_a['tag'], $_b['tag']);
		} else {
			$da = ($this->sortingMode == 'asc') ? 1 : -1;
			return ($_a['count'] < $_b['count']) ? -1*$da : 1*$da;
		}
	}


	/**
	 * Gets tag size
	 *
	 * @param 	string		$count
	 * @param	string		$mincount
	 * @param	string		$maxcount
	 * @param	string		$minsize
	 * @param	string		$maxsize
	 * @param	string		$thresholds
	 *
	 * @return	rounded tag size
	 */
	function getTagSizeLinear($count, $mincount, $maxcount, $minsize, $maxsize, $thresholds) {
		if (!is_int($thresholds) || $thresholds<2) {
			$thresholds = $maxsize-$minsize;
			$threshold = 1;
		}
		else {
			$threshold = ($maxsize-$minsize)/($thresholds-1);
		}
		if (intval($maxcount-$mincount) != 0) {
			$a = round((($thresholds-1)*($count-$mincount)) / ($maxcount-$mincount), 0);
		}
		else {
			$a = round((($thresholds-1)*($count-$mincount)));
		}
		return round($minsize+$a*$threshold,0);
	}


	/**
	 * Gets tag size logarithmic
	 *
	 * @param 	string		$count
	 * @param	string		$mincount
	 * @param	string		$maxcount
	 * @param	string		$minsize
	 * @param	string		$maxsize
	 * @param	string		$thresholds
	 *
	 * @return	rounded tag size
	 */
	function getTagSizeLogarithmic($count, $mincount, $maxcount, $minsize, $maxsize, $thresholds) {
		if (!is_int($thresholds) || $thresholds<2) {
			$thresholds = $maxsize-$minsize;
			$threshold = 1;
		}
		else {
			$threshold = ($maxsize-$minsize)/($thresholds-1);
		}
		$a = $thresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
		return round($minsize+round($a)*$threshold);
	}


	/**
	 * Gets color of threshold
	 *
	 * @param 	string		$c1
	 * @param	string		$c2
	 * @param	string		$thresholds
	 *
	 * @return	color of threshold
	 */
	function getColorThresholds($c1, $c2, $thresholds) {
		$c1 = str_replace('#', '', $c1);
		$c2 = str_replace('#', '', $c2);
		if (!preg_match('/^[a-f0-9]{6}$/i', $c1) || !preg_match('/^[a-f0-9]{6}$/i', $c2)) {
			return FALSE;
		}
		$_c1 = array(hexdec(substr($c1,0,2)), hexdec(substr($c1,2,2)), hexdec(substr($c1,4,2)));
		$_c2 = array(hexdec(substr($c2,0,2)), hexdec(substr($c2,2,2)), hexdec(substr($c2,4,2)));
		$colors = array();
		for ($t = 0; $t < $thresholds && $thresholds > 1; $t++) {
			$color = '#';
			foreach ($_c1 as $k => $v) {
				$delta = ($_c2[$k]-$_c1[$k])/($thresholds-1);
				$b = round($_c1[$k]+$t*$delta);
				if ($b < 0) {
					$b = 0;
				}
				if ($b > 255) {
					$b = 255;
				}
				$color .= (strlen($b) == 1) ? '0' . dechex($b) : dechex($b);
			}
			$colors[] = $color;
		}
		return $colors;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/tagCloud/class.tagCloud.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/tagCloud/class.tagCloud.php']);
}

?>