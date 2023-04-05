<?php
	/*******************************************************************************
	 *
	 * File:         $Id: create_image.php,v f04ee9202aed 2017/07/05 05:19:28 thurban $
	 * Modified_On:  $Date: 2017/07/05 05:19:28 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	include_once( 'functions.php' ); // Support functions

	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );

	include_once( "./include/global.php" );
	include_once( "./lib/rrd.php" );

	if ( !isset( $_SERVER[ "argv" ][ 0 ] ) || isset( $_SERVER[ 'REQUEST_METHOD' ] ) || isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
		// Web Browser
		$local_graph_id =  filter_input( INPUT_GET, 'lgid', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$rra_id         =  filter_input( INPUT_GET, 'rraid', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$graph_start    =  filter_input( INPUT_GET, 'start', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$graph_end      =  filter_input( INPUT_GET, 'end', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$graph_height   =  filter_input( INPUT_GET, 'height', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$graph_width    =  filter_input( INPUT_GET, 'width', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$secure_key     =  filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
        $graph_theme    =  filter_input( INPUT_GET, 'theme', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );

		if ( $graph_width < 1 ) {
			$graph_width = 800;
		}

		if ( $graph_height < 1 ) {
			$graph_height = 100;
		}

		// echo "Key: [$secure_key]<br>";
		if ( $secure_key == sha1( $local_graph_id . $rra_id . $graph_start . $graph_end . SECURE_URL_KEY) ) {
			// Great. proceed.
			header('Content-Type: image/png');
		} else {
			die( "<br><strong>You are not allowed to call this script via the web-browser.</strong>" );
		}
	}
	else {
		/* process calling arguments */
		$parms = $_SERVER[ 'argv' ];
		array_shift( $parms );
		$local_graph_id = $parms[ 0 ];
		$rra_id         = $parms[ 1 ];
		$graph_start    = $parms[ 2 ];
		$graph_end      = $parms[ 3 ];
		$graph_height   = $parms[ 4 ];
		$graph_width    = $parms[ 5 ];
		$graph_theme    = $parms[ 6 ];
	}

	$graph_data_array = array();
	if ( $graph_start > 0 ) {
		$graph_data_array[ "graph_start" ] = $graph_start;
		$graph_data_array[ "graph_end" ]   = $graph_end;
	}
	$graph_data_array[ "graph_height" ] = $graph_height;
	$graph_data_array[ "graph_width" ]  = $graph_width;
	$graph_data_array[ "graph_theme" ] = $graph_theme;

	if ( $config[ "cacti_version" ] == "0.8.7g" ) {
		$rrdtool_pipe = rrd_init();
		print @rrdtool_function_graph( $local_graph_id, $rra_id, $graph_data_array, $rrdtool_pipe );
		rrd_close( $rrdtool_pipe );
	}
	else {
		print @rrdtool_function_graph( $local_graph_id, $rra_id, $graph_data_array );
	}
