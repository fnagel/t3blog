<?php

########################################################################
# Extension Manager/Repository config file for ext "t3blog".
#
# Auto generated 28-09-2011 22:02
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'T3BLOG',
	'description' => 'Modular blog extension - easy to install and very flexible',
	'category' => 'module',
	'author' => 'snowflake productions GmbH',
	'author_email' => 'typo3@snowflake.ch',
	'shy' => '',
	'dependencies' => 'dam,typoscripttools,sfpantispam,pagebrowse',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1,mod2,mod3,mod4,mod5',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'snowflake productions GmbH',
	'version' => '1.1.2',
	'constraints' => array(
		'depends' => array(
			'dam' => '',
			'typoscripttools' => '',
			'sfpantispam' => '',
			'pagebrowse' => '',
			'typo3' => '4.2.0-4.99.999',
			'php' => '5.2.1-5.3.999',
		),
		'suggests' => array(
		),
		'conflicts' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:193:{s:9:"ChangeLog";s:4:"53d8";s:10:"README.txt";s:4:"e775";s:24:"RealURLConfiguration.txt";s:4:"4e45";s:1:"a";s:4:"ef4c";s:27:"class.tx_t3blog_realurl.php";s:4:"0ecf";s:21:"ext_conf_template.txt";s:4:"3a2b";s:12:"ext_icon.gif";s:4:"47c9";s:17:"ext_localconf.php";s:4:"e8f0";s:14:"ext_tables.php";s:4:"31b8";s:14:"ext_tables.sql";s:4:"dcc0";s:16:"flexform_pi2.xml";s:4:"a2f5";s:13:"locallang.xml";s:4:"0f5f";s:16:"locallang_db.xml";s:4:"adc0";s:15:"t3blog-xss.diff";s:4:"ef4c";s:14:"t3mootools.txt";s:4:"389f";s:7:"tca.php";s:4:"51e6";s:14:"doc/manual.sxw";s:4:"f0ab";s:32:"eid/class.tx_t3blog_pingback.php";s:4:"0e95";s:33:"hooks/class.tx_t3blog_tcemain.php";s:4:"7869";s:20:"icons/arrow_down.png";s:4:"60c7";s:23:"icons/arrow_refresh.png";s:4:"6b95";s:18:"icons/arrow_up.png";s:4:"34ce";s:16:"icons/avatar.jpg";s:4:"577c";s:17:"icons/bell_go.png";s:4:"c067";s:20:"icons/bin_closed.png";s:4:"c5b3";s:15:"icons/blank.png";s:4:"ae86";s:22:"icons/button_arrow.gif";s:4:"e7bf";s:21:"icons/button_hide.gif";s:4:"eed4";s:23:"icons/button_unhide.gif";s:4:"4ba5";s:19:"icons/cat_close.png";s:4:"9bbc";s:18:"icons/cat_open.png";s:4:"a366";s:28:"icons/chart_organisation.png";s:4:"c8df";s:32:"icons/chart_organisation_add.png";s:4:"36e2";s:35:"icons/chart_organisation_delete.png";s:4:"f940";s:33:"icons/chart_organisation_edit.png";s:4:"c652";s:33:"icons/chart_organisation_hide.png";s:4:"cbfd";s:35:"icons/chart_organisation_unhide.png";s:4:"8906";s:15:"icons/clear.png";s:4:"eb3d";s:17:"icons/comment.png";s:4:"a520";s:21:"icons/comment_add.png";s:4:"a9bf";s:24:"icons/comment_delete.png";s:4:"2832";s:22:"icons/comment_edit.png";s:4:"d8c4";s:22:"icons/comment_icon.png";s:4:"2d27";s:18:"icons/comments.png";s:4:"7f92";s:25:"icons/delicious.small.gif";s:4:"0d49";s:20:"icons/flag_green.png";s:4:"17aa";s:18:"icons/flag_red.png";s:4:"d5c6";s:19:"icons/heart_add.png";s:4:"2786";s:22:"icons/heart_delete.png";s:4:"2306";s:20:"icons/helpbubble.gif";s:4:"7877";s:18:"icons/helpbulb.png";s:4:"86cd";s:33:"icons/icon_tx_t3blog_blogroll.png";s:4:"16fb";s:34:"icons/icon_tx_t3blog_trackback.gif";s:4:"ac79";s:34:"icons/icon_tx_t3blog_trackback.png";s:4:"46a9";s:14:"icons/link.png";s:4:"a5dc";s:18:"icons/link_add.png";s:4:"4846";s:20:"icons/link_arrow.png";s:4:"a617";s:21:"icons/link_delete.png";s:4:"cb01";s:19:"icons/link_edit.png";s:4:"6b5a";s:19:"icons/magnifier.png";s:4:"a81f";s:24:"icons/nav_arrow_next.png";s:4:"eff5";s:24:"icons/nav_arrow_prev.png";s:4:"7fb7";s:20:"icons/new_rss091.png";s:4:"8e4c";s:19:"icons/new_rss20.png";s:4:"f22b";s:20:"icons/nopic_50_f.jpg";s:4:"577c";s:14:"icons/page.png";s:4:"60ab";s:18:"icons/page_add.png";s:4:"148a";s:21:"icons/page_delete.png";s:4:"8178";s:19:"icons/page_edit.png";s:4:"4737";s:13:"icons/rss.png";s:4:"52b8";s:16:"icons/rss091.png";s:4:"f779";s:15:"icons/rss20.png";s:4:"2460";s:16:"icons/script.png";s:4:"11c8";s:23:"icons/script_delete.png";s:4:"bc37";s:14:"icons/stop.png";s:4:"1488";s:19:"icons/tab-close.png";s:4:"3fa0";s:18:"icons/tab-open.png";s:4:"6aa2";s:20:"icons/thumb_down.png";s:4:"5470";s:18:"icons/thumb_up.png";s:4:"b6c1";s:26:"icons/tree-spacer-last.png";s:4:"c57a";s:21:"icons/tree-spacer.png";s:4:"091e";s:22:"icons/window_close.png";s:4:"4249";s:56:"icons/t3blog_component_icons/icon_tx_t3blog_blogroll.png";s:4:"16fb";s:57:"icons/t3blog_component_icons/icon_tx_t3blog_trackback.gif";s:4:"ac79";s:57:"icons/t3blog_component_icons/icon_tx_t3blog_trackback.png";s:4:"46a9";s:31:"lib/class.tx_t3blog_modbase.php";s:4:"77c2";s:46:"lib/class.tx_t3blog_modfunc_selecttreeview.php";s:4:"4c44";s:37:"lib/class.tx_t3blog_sendtrackback.php";s:4:"82fa";s:46:"lib/class.tx_t3blog_tcefunc_selecttreeview.php";s:4:"df9c";s:32:"lib/class.tx_t3blog_treeview.php";s:4:"1256";s:17:"lib/locallang.xml";s:4:"20c9";s:14:"lib/styles.css";s:4:"ff52";s:26:"lib/xmlrpc-2.2/version.php";s:4:"967e";s:29:"lib/xmlrpc-2.2/lib/xmlrpc.inc";s:4:"53f4";s:38:"lib/xmlrpc-2.2/lib/xmlrpc_wrappers.inc";s:4:"3485";s:30:"lib/xmlrpc-2.2/lib/xmlrpcs.inc";s:4:"5fa6";s:13:"mod1/conf.php";s:4:"15ee";s:14:"mod1/index.php";s:4:"b2ce";s:18:"mod1/locallang.xml";s:4:"1160";s:22:"mod1/locallang_mod.xml";s:4:"f1f3";s:19:"mod1/moduleicon.gif";s:4:"8c3f";s:13:"mod2/conf.php";s:4:"709a";s:14:"mod2/index.php";s:4:"a6c5";s:18:"mod2/locallang.xml";s:4:"fa78";s:22:"mod2/locallang_mod.xml";s:4:"6347";s:19:"mod2/moduleicon.gif";s:4:"8074";s:13:"mod3/conf.php";s:4:"d07f";s:14:"mod3/index.php";s:4:"69c1";s:18:"mod3/locallang.xml";s:4:"a19f";s:22:"mod3/locallang_mod.xml";s:4:"e1df";s:19:"mod3/moduleicon.gif";s:4:"8074";s:13:"mod4/conf.php";s:4:"7c0f";s:14:"mod4/index.php";s:4:"62ab";s:18:"mod4/locallang.xml";s:4:"28f4";s:22:"mod4/locallang_mod.xml";s:4:"9968";s:19:"mod4/moduleicon.gif";s:4:"7232";s:13:"mod5/conf.php";s:4:"59d2";s:14:"mod5/index.php";s:4:"4623";s:18:"mod5/locallang.xml";s:4:"8f21";s:22:"mod5/locallang_mod.xml";s:4:"56a6";s:19:"mod5/moduleicon.gif";s:4:"8074";s:27:"pi1/class.tx_t3blog_pi1.php";s:4:"a671";s:17:"pi1/locallang.xml";s:4:"92aa";s:27:"pi1/lib/class.t3blog_db.php";s:4:"12b2";s:28:"pi1/lib/class.t3blog_div.php";s:4:"5472";s:25:"pi1/lib/trackback_cls.php";s:4:"e572";s:37:"pi1/widgets/archive/class.archive.php";s:4:"a7a9";s:33:"pi1/widgets/archive/locallang.xml";s:4:"d517";s:29:"pi1/widgets/archive/setup.txt";s:4:"8e64";s:35:"pi1/widgets/blogList/adminemail.txt";s:4:"b313";s:39:"pi1/widgets/blogList/class.blogList.php";s:4:"f3c1";s:44:"pi1/widgets/blogList/class.listFunctions.php";s:4:"a2e6";s:46:"pi1/widgets/blogList/class.singleFunctions.php";s:4:"e030";s:34:"pi1/widgets/blogList/locallang.xml";s:4:"beb3";s:30:"pi1/widgets/blogList/setup.txt";s:4:"c78f";s:40:"pi1/widgets/blogList/captcha/captcha.php";s:4:"6bdf";s:43:"pi1/widgets/blogList/captcha/font/bedsa.ttf";s:4:"6705";s:46:"pi1/widgets/blogList/captcha/font/creature.ttf";s:4:"6c69";s:46:"pi1/widgets/blogList/captcha/font/glazkrak.ttf";s:4:"ffa3";s:42:"pi1/widgets/blogList/captcha/font/vera.ttf";s:4:"785d";s:45:"pi1/widgets/blogList/captcha/font/x-files.ttf";s:4:"1a01";s:46:"pi1/widgets/blogList/captcha/image/captcha.png";s:4:"241f";s:47:"pi1/widgets/blogrollList/class.blogrollList.php";s:4:"16f0";s:38:"pi1/widgets/blogrollList/locallang.xml";s:4:"1640";s:34:"pi1/widgets/blogrollList/setup.txt";s:4:"7bab";s:39:"pi1/widgets/calendar/class.calendar.php";s:4:"a775";s:34:"pi1/widgets/calendar/locallang.xml";s:4:"a323";s:30:"pi1/widgets/calendar/setup.txt";s:4:"f547";s:43:"pi1/widgets/categories/class.categories.php";s:4:"0788";s:36:"pi1/widgets/categories/locallang.xml";s:4:"c9f6";s:32:"pi1/widgets/categories/setup.txt";s:4:"15ae";s:57:"pi1/widgets/latestCommentsNav/class.latestCommentsNav.php";s:4:"1ee2";s:43:"pi1/widgets/latestCommentsNav/locallang.xml";s:4:"19ac";s:39:"pi1/widgets/latestCommentsNav/setup.txt";s:4:"cfa9";s:49:"pi1/widgets/latestPostNav/class.latestPostNav.php";s:4:"9dd0";s:39:"pi1/widgets/latestPostNav/locallang.xml";s:4:"6b99";s:35:"pi1/widgets/latestPostNav/setup.txt";s:4:"8b5e";s:29:"pi1/widgets/rss/class.rss.php";s:4:"8127";s:29:"pi1/widgets/rss/locallang.xml";s:4:"7460";s:25:"pi1/widgets/rss/setup.txt";s:4:"a7e9";s:41:"pi1/widgets/searchBox/class.searchBox.php";s:4:"35af";s:35:"pi1/widgets/searchBox/locallang.xml";s:4:"1aef";s:31:"pi1/widgets/searchBox/setup.txt";s:4:"1223";s:53:"pi1/widgets/socialBookmarks/class.socialBookmarks.php";s:4:"317e";s:41:"pi1/widgets/socialBookmarks/locallang.xml";s:4:"eb63";s:37:"pi1/widgets/socialBookmarks/setup.txt";s:4:"27d5";s:39:"pi1/widgets/tagCloud/class.tagCloud.php";s:4:"65b2";s:34:"pi1/widgets/tagCloud/locallang.xml";s:4:"265c";s:30:"pi1/widgets/tagCloud/setup.txt";s:4:"fc1d";s:33:"pi1/widgets/views/class.views.php";s:4:"79b0";s:31:"pi1/widgets/views/locallang.xml";s:4:"6a1a";s:27:"pi1/widgets/views/setup.txt";s:4:"8e62";s:14:"pi2/ce_wiz.gif";s:4:"47c9";s:27:"pi2/class.tx_t3blog_pi2.php";s:4:"81da";s:47:"pi2/class.tx_t3blog_pi2_addFieldsToFlexForm.php";s:4:"1357";s:35:"pi2/class.tx_t3blog_pi2_wizicon.php";s:4:"47b3";s:13:"pi2/clear.gif";s:4:"cc11";s:17:"pi2/locallang.xml";s:4:"19b5";s:14:"pi2/readme.txt";s:4:"bb9d";s:19:"static/tsconfig.txt";s:4:"ff74";s:24:"static/js/globalFuncs.js";s:4:"1dce";s:24:"static/js/mooexamples.js";s:4:"ca28";s:30:"static/js/mootools/mootools.js";s:4:"5c9c";s:23:"static/t3blog/setup.txt";s:4:"2a5c";s:31:"static/t3blog/pi1/constants.txt";s:4:"9e0e";s:27:"static/t3blog/pi1/setup.txt";s:4:"1a17";s:27:"static/t3blog/pi2/setup.txt";s:4:"a1fc";s:28:"static/t3blog/styling/bg.png";s:4:"0931";s:38:"static/t3blog/styling/blog_header1.png";s:4:"9ee0";s:39:"static/t3blog/styling/header_bottom.png";s:4:"15ba";s:31:"static/t3blog/styling/setup.txt";s:4:"df91";s:36:"static/t3blog/template/constants.txt";s:4:"fd94";s:32:"static/t3blog/template/setup.txt";s:4:"6290";}',
);

?>