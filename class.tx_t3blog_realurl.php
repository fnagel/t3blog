<?php
class tx_t3blog_realurl {

	/**
	 * Generates additional RealURL configuration and merges it with provided configuration
	 *
	 * @paramarray$paramsDefault configuration
	 * @paramtx_realurl_autoconfgen$pObjParent object
	 * @returnarrayUpdated configuration
	 */

	function addConfig($params, &$pObj) {

			//add custom config
		$config = array (
			'postVarSets' => array (
				'_DEFAULT' => array (
					'blog-post' => array(
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][year]',
						),
						 array(
							'GETvar' => 'tx_t3blog_pi1[blogList][month]' ,
						),
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][day]',
						),
						array (
							'GETvar' => 'tx_t3blog_pi1[blogList][showUid]',
							'lookUpTable' => array(
								'table' => 'tx_t3blog_post',
								'id_field' => 'uid',
								'alias_field' => 'title',
								'addWhereClause' => ' AND deleted !=1 AND hidden !=1',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array(
									'strtolower' => 1,
									'spaceCharacter' => '-',
								)
							)
						),
						array (
							'GETvar' => 'tx_t3blog_pi1[blogList][comParentId]',
							'lookUpTable' => array(
								'table' => 'tx_t3blog_com',
								'id_field' => 'uid',
								'alias_field' => 'title',
								'addWhereClause' => ' AND deleted !=1 AND hidden !=1',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array(
									'strtolower' => 1,
									'spaceCharacter' => '-',
								)
							)
						),
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][comParentTitle]',
						),
					),
					'response' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][comParentId]',
							'lookUpTable' => array(
								'table' => 'tx_t3blog_com',
								'id_field' => 'uid',
								'alias_field' => 'title',
								'addWhereClause' => ' AND deleted !=1 AND hidden !=1',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array(
									'strtolower' => 1,
									'spaceCharacter' => '-',
								)
							)
						),

					),
					'on-comment' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][comParentTitle]',
						),

					),
					'blog-category' => array (
						array (
							'GETvar' => 'tx_t3blog_pi1[blogList][category]',
							'lookUpTable' => array (
								'table' => 'tx_t3blog_cat',
								'id_field' => 'uid',
								'alias_field' => 'catname',
								'addWhereClause' => ' AND deleted !=1 AND hidden !=1',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array(
									'strtolower' => 1,
									'spaceCharacter' => '-',
								)
							)
						)
					),
					'blog-from' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][datefrom]',
						)
					),
					'tags' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][tags]',
						)
					),
					'author' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][author]',
							'lookUpTable' => array(
								'table' => 'be_users',
								'id_field' => 'uid',
								'alias_field' => 'realName',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array(
									'strtolower' => 1,
									'spaceCharacter' => '-',
								)
							)
						)
					),
					'tstmp' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[tstmp]',
						)
					),
					'blog-to' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][dateto]',
						)
					),
					'rss' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[rss][feed_id]',
						),
						array(
							'GETvar' => 'tx_t3blog_pi1[rss][feed_type]',
						)
					),
					'rssContent' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[rss][value]',
						)
					),
					'blog-browser' => array (
						array (
							'GETvar' => 'tx_t3blog_post_pointer'
						)
					),
					'trackback' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[trackback]',
						)
					),
					'insert' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][insert]',
							'valueMap' => array (
								'no' => '0',
								'comment' => '1',
							)
						)
					),
					'editComment' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][editCommentUid]',
						)
					),
					'into' => array (
						array(
							'GETvar' => 'tx_t3blog_pi1[blogList][uid]',
						)
					)
				)
			)
		);

		return array_merge_recursive($params['config'], $config);
	}
}
?>