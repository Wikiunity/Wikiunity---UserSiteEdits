<?php
/*
*	@name	UserSiteEdits
*	@nversion	1.6
*	@nauthor	Michael [[User:McCouman|McCouman]] jr. (Michael Kaufmann)
*	@ndescription	Summarises the main contributors to an articles in MediaWiki
*	@ndescriptionmsg usersiteedits-desc 
*	@nurl http://www.wikiunity.com,
*	@lic Copyright Michael Kaufmann, 2012
**/
class SpecialUserSiteEdits extends IncludableSpecialPage {

	protected $target;

	public function __construct() {
		parent::__construct( 'UserSiteEdits' );
	}
	
	public function execute( $target ) {
		wfProfileIn( __METHOD__ );
		global $wgOut, $wgRequest;
		$this->setHeaders();
		$this->determineTarget( $wgRequest, $target );
		if( $this->including() ) {
			$this->showInclude();
		} elseif( $wgRequest->getText( 'action' ) == 'raw' ) {
			$this->showRaw();
		} else {
			$wgOut->addHTML( $this->makeForm() );
			if( is_object( $this->target ) )
				$this->showNormal();
		}
		
		wfProfileOut( __METHOD__ );	
	}
	
	private function showInclude() {
		wfProfileIn( __METHOD__ );

		global $wgOut, $wgContentLang;

		if( is_object( $this->target ) ) {
			if( $this->target->exists() ) {
				$names = array();
				list( $usersiteedits, $others ) = self::getMainUserSiteEdits($this->target);
				foreach( $usersiteedits as $username => $info )
					$names[] = $username;
				$output = $wgContentLang->listToText( $names );
				if( $others > 0 )
					$output .= wgMsgForContent( 'word-separator' ) . wfMsgForContent( 'usersiteedits-others', $wgContentLang->formatNum( $others ) );
				$wgOut->addHTML( htmlspecialchars( $output ) );
			} else {
				$wgOut->addHTML( '<p>' . htmlspecialchars( wfMsgForContent( 'usersiteedits-nosuchpage', $this->target->getPrefixedText() ) ) . '</p>' );
			}
		} else {
			$wgOut->addHTML( '<p>' . htmlspecialchars( wfMsgForContent( 'usersiteedits-badtitle' ) ) . '</p>' );
		}
		wfProfileOut( __METHOD__ );	
	}
	
	private function showRaw() {
		wfProfileIn( __METHOD__ );
		global $wgOut;
		$wgOut->disable();
		if( is_object( $this->target ) && $this->target->exists() ) {
			foreach( $this->getUserSiteEdits() as $username => $info ) {
				list( $userid, $count ) = $info;
				header( 'Content-type: text/plain; charset=utf-8' );
				echo( htmlspecialchars( "{$username} = {$count}\n" ) );
			}
		} else {
			header( 'Status: 404 Not Found', true, 404 );
			echo( 'The requested target page does not exist.' );
		}
		wfProfileOut( __METHOD__ );	
	}
	
	private function showNormal() {
		wfProfileIn( __METHOD__ );
		global $wgOut, $wgUser, $wgLang;
		if( $this->target->exists() ) {
			$total = 0;
			$skin =& $wgUser->getSkin();
			$link = $skin->makeKnownLinkObj( $this->target );
			$wgOut->addHTML( '<h2>' . wfMsgHtml( 'usersiteedits-subtitle', $link ) . '</h2>' );
			list( $usersiteedits, $others ) = self::getMainUserSiteEdits($this->target);
			$wgOut->addHTML( '<ul>' );
			foreach( $usersiteedits as $username => $info ) {
				list( $id, $count ) = $info;
				$line = $skin->userLink( $id, $username ) . $skin->userToolLinks( $id, $username );
				$line .= ' [' . $wgLang->formatNum( $count ) . ']';
				$wgOut->addHTML( '<li>' . $line . '</li>' );
			}
			$wgOut->addHTML( '</ul>' );
			if( $others > 0 ) {
				$others = $wgLang->formatNum( $others );
				$wgOut->addWikiText( wfMsgNoTrans( 'usersiteedits-others-long', $others ) );
			}
		} else {
			$wgOut->addHTML( '<p>' . htmlspecialchars( wfMsg( 'usersiteedits-nosuchpage', $this->target->getPrefixedText() ) ) . '</p>' );
		}
		wfProfileOut( __METHOD__ );	
	}
	

	public static function getMainUserSiteEdits($title) {
		wfProfileIn( __METHOD__ );
		global $wgUserSiteEditsLimit, $wgUserSiteEditsThreshold;
		$total = 0;
		$ret = array();
		$all = self::getUserSiteEdits($title);
		foreach( $all as $username => $info ) {
			list( $id, $count ) = $info;
			if( $total >= $wgUserSiteEditsLimit && $count < $wgUserSiteEditsThreshold )
				break;
			$ret[$username] = array( $id, $count );
			$total++;
		}
		$others = count( $all ) - count( $ret );
		wfProfileOut( __METHOD__ );	
		return array( $ret, $others );
	}

	public static function getUserSiteEdits($title) {
		wfProfileIn( __METHOD__ );
		global $wgMemc;
		$k = wfMemcKey( 'usersiteedits', $title->getArticleId() );
		$usersiteedits = $wgMemc->get( $k );
		if( !$usersiteedits ) {
			$usersiteedits = array();
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'revision',
				array(
					'COUNT(*) AS count',
					'rev_user',
					'rev_user_text',
				),
				self::getConditions($title),
				__METHOD__,
				array(
					'GROUP BY' => 'rev_user_text',
					'ORDER BY' => 'count DESC',
				)
			);
			if( $res && $dbr->numRows( $res ) > 0 ) {
				while( $row = $dbr->fetchObject( $res ) )
					$usersiteedits[ $row->rev_user_text ] = array( $row->rev_user, $row->count );
			}
			$wgMemc->set( $k, $usersiteedits, 84600 );
		}
		wfProfileOut( __METHOD__ );
		return $usersiteedits;
	}
	

	/*protected static function getUserSiteEdits($title) {
		global $wgVersion;
		$conds['rev_page'] = $title->getArticleId();
		if( version_compare( $wgVersion, '1.11', '>=' ) )
			$conds[] = 'rev_deleted & ' . Revision::DELETED_USER . ' = 0';
		return $conds;
	}*/
	
	private function determineTarget( &$request, $override ) {
		$target = $request->getText( 'target', $override );
		$this->target = Title::newFromURL( $target );
	}
	
	private function makeForm() {
		global $wgScript;
		$self = parent::getTitleFor( 'UserSiteEdits' );
		$target = is_object( $this->target ) ? $this->target->getPrefixedText() : '';
		$form  = '<form method="get" action="' . htmlspecialchars( $wgScript ) . '">';
		$form .= Xml::hidden( 'title', $self->getPrefixedText() );
		$form .= '<fieldset><legend>' . wfMsgHtml( 'usersiteedits-legend' ) . '</legend>';
		$form .= '<table><tr>';
		$form .= '<td><label for="target">' . wfMsgHtml( 'usersiteedits-target' ) . '</label></td>';
		$form .= '<td>' . Xml::input( 'target', 40, $target, array( 'id' => 'target' ) ) . '</td>';
		$form .= '</tr><tr>';
		$form .= '<td>&nbsp;</td>';
		$form .= '<td>' . Xml::submitButton( wfMsg( 'usersiteedits-submit' ) ) . '</td>';
		$form .= '</tr></table>';
		$form .= '</fieldset>';
		$form .= '</form>';
		return $form;
	}

}

