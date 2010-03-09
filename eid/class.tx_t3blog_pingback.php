<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Snowflake Productions Gmbh (info@snowflake.ch)
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
/**
 * $Id$
 *
 */
 
 /**
  * This class implents a pingback handler
  *
  * @author Dmitry Dulepov <ddulepov@snowflake.ch>
  * @package TYPO3
  * @subpackage tx_t3blog
  */
 
if (version_compare(TYPO3_branch, '4.3.0', '>=')) {
	// See http://bugs.typo3.org/view.php?id=13745
	require_once(PATH_t3lib . 'error/class.t3lib_error_exception.php');
}

require_once(t3lib_extMgm::extPath('t3blog', 'lib/xmlrpc-2.2/lib/xmlrpc.inc'));
require_once(t3lib_extMgm::extPath('t3blog', 'lib/xmlrpc-2.2/lib/xmlrpcs.inc'));

class tx_t3blog_pingback {
	
	/** @var string */
	protected $sourceArticleURI;

	/** @var string */
	protected $t3blogArticleURI;

	
	/** @var string */
	protected $contentTitle;
	
	/** @var string */
	protected $contentExcerpt;

	/**
	 * Checks that target URI is valid
	 * 
	 * @return void
	 */
	protected function checkBlogURI() {
		if (!t3lib_div::isValidUrl($this->t3blogArticleURI)) {
			throw new Exception('The specified target URI cannot be used as a target', 33);
		}

		$urlParts = @parse_url($this->t3blogArticleURI);
		if (!is_array($urlParts) && $parts['host'] !== t3lib_div::getIndpEnv('HTTP_HOST')) {
			throw new Exception('Access denied', 49);
		}
		
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(uid) AS counter',
			'tx_t3blog_pingback',
			'url=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->sourceArticleURI, 'tx_t3blog_pingback') .
			' AND deleted = 0 AND hidden = 0'
		);
        if ($row['counter'] > 0) { # Pingback already registered
			throw new Exception('The pingback has already been registered', 48);
        }
		
		// We can check if it points to a blog or to some other resouce because
		// RealURL can be involved. So we skip this check here.
	}

	/**
	 * Checks if content is suitable for the blog
	 *
	 * @param string $content
	 * @return void
	 */
	protected function checkSourceURIContent($content) {
		if (false === strpos($content, 'href="' . $this->t3blogArticleURI . '"')) {
			if (false === strpos($content, 'href="' . htmlspecialchars($this->t3blogArticleURI) . '"')) {
				throw new Exception('The source URI does not contain a link to the target URI', 17);
			}
		}
	}
	
	/**
	 * Creates URI content excerpt
	 * 
	 * @param string $uriContent
	 * @return void
	 */
	protected function createExcerpt($uriContent) {
		$this->contentTitle = $this->getTitleFromHTML($uriContent);
		$this->contentExcerpt = '<a href="' . 
			htmlspecialchars($this->sourceArticleURI) . '">' .
			htmlspecialchars($this->contentTitle) .
			'</a>';
	}

	/**
	 * Loads source URI content
	 * 
	 * @return string
	 */
	protected function getSourceURIContent() {
		$content = t3lib_div::getURL($this->sourceArticleURI);
		if (!trim($content)) {
			throw new Exception('The source URI does not exist', 16);
		}
		return $content;
	}
	
	/**
	 * Obtains title from html
	 * 
	 * @param string $html
	 * @return string
	 */
	protected function getTitleFromHTML($html) {
		return preg_replace('/^.*<title>(.*)<\/title>.*$/is', '\1', $html);
	}
	
	/**
	 * Handles XML-RPC call
	 * 
	 * @return void
	 */
	protected function handleXmlRpcCall() {
		header('Content-type: text/xml');
		$methods = array(
			'pingback.ping' => array(
				'function' => array($this, 'pingback')
			)
		);
		$server = new xmlrpc_server($methods);
	}
	
	/**
	 * Runs this script
	 * 
	 * @return void
	 */
	public function main() {
		tslib_eidtools::connectDB();

		$this->handleXmlRpcCall();
	}
	
	/**
	 * Processes pingback request
	 * 
	 * @param $message
	 */
	public function pingback(xmlrpcmsg $message) {
		$sourceArticleURI = $message->getParam(0);
		$t3blogArticleURI = $message->getParam(1);
		
		if (!($sourceArticleURI instanceof xmlrpcval)) {
			$response = new xmlrpcresp(0, 16, 'Source URI does not exist');
		}
		else if (!($t3blogArticleURI instanceof xmlrpcval)) {
			$response = new xmlrpcresp(0, 33, 'Target URI is not recognized');
		}
		else {
			$this->sourceArticleURI = $sourceArticleURI->scalarval();
			$this->t3blogArticleURI = $t3blogArticleURI->scalarval();
			$response = $this->processPingback();			
		}
		
		return $response;
	}
	
	/**
	 * Prepares source URI content for adding to the database. This function
	 * will fetch the content and search for the link to our blog
	 * 
	 * @return void
	 * @throws Exception if something is wrong
	 */
	protected function prepareSourceURIContent() {
		$uriContent = $this->getSourceURIContent();
		$this->checkSourceURIContent($uriContent);
		$this->createExcerpt($uriContent);
	}

	/**
	 * Processes pingbacks
	 * 
	 * @return void
	 */
	protected function processPingback() {
		$response = null;
		try {
			$this->checkBlogURI();
			$this->prepareSourceURIContent($response);
			$this->storePingback();
			$response = new xmlrpcresp(new xmlrpcval('Pingback registered. Thank you!', 'string'));
		}
		catch (Exception $e) {
			$response = new xmlrpcresp(0, $e->getCode(), $e->getMessage());
		}

		return $response; 
	}
	
	/**
	 * Stores pingback information to the database
	 */
	protected function storePingback() {
		$time = time();
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_t3blog_pingback',
			array(
				'title' => $this->contentTitle,
				'crdate'=> $time,
				'url'=> $this->sourceArticleURI,
				'text'=> $this->contentExcerpt,
				'date'=> $time
			)
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/eid/class.tx_t3blog_pingback.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/eid/class.tx_t3blog_pingback.php']);
}

$pingback = t3lib_div::makeInstance('tx_t3blog_pingback');
/* @var $pingback tx_t3blog_pingback */
$pingback->main();

?>