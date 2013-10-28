<?php

/**
 * The Specialpage is a list of ten most prominent editors of use and view to an article page
 * by Michael McCouman jr (Nexus) 2008 (Release2103/ 17.x-18.0 -2011-2012)
 */

/*
*	@name	UserSiteEdits
*	@nversion	1.6
*	@nauthor	Michael [[User:McCouman|McCouman]] jr. (Michael Kaufmann)
*	@ndescription	Summarises the main contributors to an articles in MediaWiki
*	@ndescriptionmsg usersiteedits-desc 
*	@nurl http://www.wikiunity.com,
*	@lic Copyright Michael Kaufmann, 2012
**/
if( defined( 'MEDIAWIKI' ) ) {

	$wgExtensionFunctions[] = 'efUserSiteEdits';
	$wgExtensionCredits['specialpage'][] = array(
		'path' => __FILE__,
		'name' => 'UserSiteEdits',
		'version' => '1.6',
		'author' => 'Michael [[User:McCouman|McCouman]] jr.',
		'description' => 'Summarises the main contributors to an articles',
		'descriptionmsg' => 'usersiteedits-desc',
		'url' => 'http://www.wikiunity.com',
	);

	$dir = dirname(__FILE__) . '/';
	$wgExtensionMessagesFiles['UserSiteEdits'] = $dir . 'UserSiteEdits.i18n.php';
	$wgExtensionAliasesFiles['UserSiteEdits'] = $dir . 'UserSiteEdits.alias.php';
	$wgAutoloadClasses['SpecialUserSiteEdits'] = $dir . 'UserSiteEdits.page.php';
	$wgSpecialPages['UserSiteEdits'] = 'SpecialUserSiteEdits';
	$wgSpecialPageGroups['UserSiteEdits'] = 'pages';
	$wgContributorsLimit = 10;
	$wgContributorsThreshold = 2;

	function efUserSiteEdits() {
		global $wgHooks;

		wfLoadExtensionMessages( 'UserSiteEdits' );
		$wgHooks['ArticleDeleteComplete'][] = 'efCUserSiteEditsInvalidateCache';
		$wgHooks['ArticleSaveComplete'][] = 'efUserSiteEditsInvalidateCache';
		$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'efUserSiteEditsNavigation';
		$wgHooks['SkinTemplateToolboxEnd'][] = 'efUserSiteEditsToolbox';
	}

	function efUserSiteEditsInvalidateCache( &$article ) {
		global $wgMemc;
		$wgMemc->delete( wfMemcKey( 'usersiteedits', $article->getId() ) );

		return true;
	}

	function efUserSiteEditsNavigation( &$skintemplate, &$nav_urls, &$oldid, &$revid ) {
		if ( $skintemplate->mTitle->getNamespace() === NS_MAIN && $revid !== 0 )
			$nav_urls['usersiteedits'] = array(
				'text' => wfMsg( 'usersiteedits-toolbox' ),
				'href' => $skintemplate->makeSpecialUrl( 'UserSiteEdits', "target=" . wfUrlEncode( "{$skintemplate->thispage}" ) )
			);
		return true;
	}

	function efUserSiteEditsToolbox( &$monobook ) {
		if ( isset( $monobook->data['nav_urls']['usersiteedits'] ) )
			if ( $monobook->data['nav_urls']['usersiteedits']['href'] == '' ) {
				?><li id="t-isusersiteedits"><?php echo $monobook->msg( 'usersiteedits-toolbox' ); ?></li><?php
			} else {
				?><li id="t-usersiteedits"><?php
					?><a href="<?php echo htmlspecialchars( $monobook->data['nav_urls']['usersiteedits']['href'] ) ?>"><?php
						echo $monobook->msg( 'usersiteedits-toolbox' );
					?></a><?php
				?></li><?php
			}
		return true;
	}

} else {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	exit( 1 );
}

