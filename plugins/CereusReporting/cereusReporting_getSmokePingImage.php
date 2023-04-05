<?php
	/*******************************************************************************
	 *
	 * File:         $Id: cereusReporting_getSmokePingImage.php,v afc11c4d72ad 2016/07/14 09:30:20 thurban $
	 * Modified_On:  $Date: 2016/07/14 09:30:20 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/


	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	include_once( 'functions.php' );  // Support functions

	chdir( $mainDir );
	include_once( "./include/global.php" );
	include_once( './include/config.php' );
	chdir( $dir );

	// Get DB Instance
	$db = DBCxn::get();

	$tmp_dir = my_sys_get_temp_dir();
	if ( version_compare( PHP_VERSION, '5.2.1' ) >= 0 ) {
		$tmp_dir = sys_get_temp_dir();
	}

	$debug  = FALSE;
	$hostId = '';
	$debug  = '';
	$start  = '';
	$end    = '';
	$secure_key     = '';

	if ( !isset( $_SERVER[ "argv" ][ 0 ] ) || isset( $_SERVER[ 'REQUEST_METHOD' ] ) || isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
		// Web Browser
		$hostId = filter_input( INPUT_GET, 'hostId', FILTER_SANITIZE_NUMBER_INT);
		$debug  = filter_input( INPUT_GET, 'debug', FILTER_SANITIZE_NUMBER_INT);
		$start  = filter_input( INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT);
		$end    = filter_input( INPUT_GET, 'end', FILTER_SANITIZE_NUMBER_INT);
		$secure_key     = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		header( 'Content-Type: image/png' );

		// echo "Key: [$secure_key]<br>";
		if ( $secure_key == sha1( $hostId . $start . $end . SECURE_URL_KEY ) ) {
			// Great. proceed.
		}
		else {
			die( "<br><strong>You are not allowed to call this script via the web-browser.</strong>" );
		}
	}
	else {
		$parms = $_SERVER[ 'argv' ];
		array_shift( $parms );
		$hostId = $parms[ 0 ];
		$start  = $parms[ 1 ];
		$end    = $parms[ 2 ];
		if ( isset( $parms[ 3 ] ) ) {
			$debug = $parms[ 3 ];
		}
	}


	/* Create Connection to the DB */

	$smokepingServer = getPreparedDBValue( 'SELECT nwmgmt_smokeping_server FROM host WHERE nwmgmt_settings LIKE \'%s1%\' AND id = ?', array($hostId) );
	$server          = getPreparedDBValue( 'SELECT name FROM settings WHERE value= ?;', array($smokepingServer) );
	$target          = getPreparedDBValue( 'SELECT nwmgmt_smokeping_path FROM host WHERE nwmgmt_settings LIKE \'%s1%\' AND id = ?', array($hostId) );
	$graphtype       = readConfigOption( 'nmid_spgraphtype' );

	$url      = readConfigOption( $server ) . readConfigOption( 'nmid_spurl' );
	$userId   = readConfigOption( 'nmid_spuser' );
	$password = readConfigOption( 'nmid_sppwd' );

	$sp_url      = '';
	$real_target = '';

	$encoded = '';

	if ( $graphtype == "detail" ) {
		$encoded .= urlencode( 'displaymode' ) . '=n&';
		$encoded .= urlencode( 'start' ) . '=' . urlencode( $start ) . '&';
		$encoded .= urlencode( 'end' ) . '=' . urlencode( $end ) . '&';
		$encoded .= urlencode( 'target' ) . '=' . urlencode( $target );
		$sp_url = $url; //.'?displaymode=n;start='.$start.';end='.$end.';target='.$target;
	}
	else {
		$real_target = $target;
		$target_data = preg_split( '/\./', $real_target );
		$real_target = '';
		$pathSize    = sizeof( $target_data );
		foreach ( $target_data as $data ) {
			$real_target .= $data . '.';
		}
		$real_target = ~preg_replace( '/\.$/', '', $real_target );
		$encoded .= urlencode( 'target' ) . '=' . urlencode( $real_target );
		$sp_url = $url; //.'?target='.$real_target;
	}
	$mainServer = '';

	if ( preg_match( "/(http:.*)\/cgi-bin\/smokeping\.cgi.*/i", $url, $matches ) ) {
		$mainServer = $matches[ 1 ];
	}
	elseif ( preg_match( "/(http:.*)\/smokeping\.cgi.*/i", $url, $matches ) ) {
		$mainServer = $matches[ 1 ];
	}
	$mainServer  = $mainServer . '/';
	$filePart    = '';
	$responseStr = getUrl( $sp_url, $userId, $password, $encoded );

	if ( $debug ) {
		print "HostID: " . $hostId . "\n";
		print "Main Server : " . $mainServer . "\n";
		print "SP URL : " . $sp_url . "\n";
		print "Encoded : " . $encoded . "\n";
		print "<h3>Response Str\n-------------------------------------\n" . $responseStr;
		print "-------------------------------------\n";
	}

	$filePart = '';
	if ( $graphtype == "detail" ) {
		$filePart = $end . '_' . $start . '.png';
	}
	else {
		$filePart = $target . '_mini.png';
	}

	if ( $debug ) {
		print "File Part:" . $filePart . "\n";
	}


	$imageUrl = '';
	if ( preg_match( "/src=\"\.\.([^\s]*$filePart)\"/i", $responseStr, $matches ) ) {
		$imageUrl = $matches[ 1 ];
	}
	elseif ( preg_match( "/src=\"([^\s]*$filePart)\"/i", $responseStr, $matches ) ) {
		$imageUrl = $matches[ 1 ];
	}

	if ( $debug ) {
		print "Image URL:" . $imageUrl . "\n";
	}

	$localfile = $imageUrl;
	if ( $graphtype == "detail" ) {
		$localfile = ~preg_replace( "/.*__navcache\//i", '', $localfile );
	}
	else {
		$localfile = ~preg_replace( "/.*\/$real_target\//i", '', $localfile );
	}

	if ( $debug ) {
		print "Local File: " . $localfile . "\n";
	}

	$localfile = $tmp_dir . $localfile;

	if ( preg_match( '/^http/', $imageUrl ) == 0 ) {
		$imageUrl = $mainServer . $imageUrl;
	}


	$filename = getUrl( $imageUrl, $userId, $password, '', TRUE );
	if ( $debug ) {
		print "Image URL:" . $imageUrl . "\n";
		print "Filename: $filename\n";
	}

	print $filename;

	function getUrl( $url, $userid = '', $password = '', $encoded = '', $saveFile = FALSE )
	{
		global $tmp_dir;
		$ch = curl_init( $url );
		// chop off last ampersand
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $encoded );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_USERPWD, $userid . ":" . $password );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $tmp_dir . 'cookie.txt' );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $tmp_dir . 'cookie.txt' );
		$output = curl_exec( $ch );

		if ( $saveFile ) {
			$filename = tempnam( $tmp_dir, '_smokeping_' );
			unlink( $filename );
			$filename = $filename . '.png';
			$fh       = fopen( $filename, 'w' );
			fwrite( $fh, $output );
			fclose( $fh );
			curl_close( $ch );
			return $filename;
		}
		curl_close( $ch );
		return $output;
	}

	function my_sys_get_temp_dir()
	{
		if ( $temp = getenv( 'TMP' ) ) {
			return $temp;
		}
		if ( $temp = getenv( 'TEMP' ) ) {
			return $temp;
		}
		if ( $temp = getenv( 'TMPDIR' ) ) {
			return $temp;
		}
		$temp = tempnam( __FILE__, '' );
		if ( file_exists( $temp ) ) {
			unlink( $temp );
			return dirname( $temp );
		}
		return NULL;
	}
