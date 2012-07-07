<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);
/**
 * Sender Mail:
 */
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['sendermail'] = $_EXTCONF['sendermail'];

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_t3blog_pi1.php','_pi1','',1);
t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_t3blog_pi2.php','_pi2','list_type',1);

	// RealURL autoconfiguration
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['t3blog'] = 'EXT:t3blog/class.tx_t3blog_realurl.php:tx_t3blog_realurl->addConfig';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_t3blog_tcemain.php:&tx_t3blog_tcemain';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_t3blog_tcemain.php:&tx_t3blog_tcemain';

// eID
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['t3b_pingback'] = 'EXT:' . $_EXTKEY . '/eid/class.tx_t3blog_pingback.php';

t3lib_extMgm::addPageTSConfig(file_get_contents(t3lib_extMgm::extPath('t3blog', 'static/tsconfig.txt')));

// BE search
$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['t3blog'] = 'tx_t3blog_post';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['t3blog_blogroll'] = 'tx_t3blog_blogroll';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['t3blog_com'] = 'tx_t3blog_com';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['t3blog_pingback'] = 'tx_t3blog_pingback';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['t3blog_trackback'] = 'tx_t3blog_trackback';
?>