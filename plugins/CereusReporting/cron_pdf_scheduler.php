#!/usr/bin/php
<?php
	/*******************************************************************************
	 *
	 * File:         $Id: cron_pdf_scheduler.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
	 * Modified_On:  $Date: 2017/07/18 06:44:34 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	* Encoding:     UTF-8
	* Status:       -
	* License:      Commercial
	* Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/


	/* take time and log performance data */
	list( $micro, $seconds ) = explode( " ", microtime() );
	$start = $seconds + $micro;

	$no_http_headers = true;

	ini_set( 'max_execution_time', 0 );
	set_time_limit(0);

	$mainDir = preg_replace( "@plugins.CereusReporting@", "", __DIR__ );
	chdir( $mainDir );
	include('./include/global.php');
	include_once( __DIR__.'/functions.php' );

	if ( file_exists( sys_get_temp_dir() . '/cronisrunning' ) && ( ( time() - filemtime( sys_get_temp_dir() . '/cronisrunning' ) ) < 7200 ) ) {
		CereusReporting_logger( 'Scheduled Job is already running.', "info", "cron" );
		// Report Generation is already running
		exit();
	}
	else {
		touch( sys_get_temp_dir() . '/cronisrunning' );
	}

    CereusReporting_logger( 'Starting Scheduled Report Check.', "info", "cron" );

    ini_set( 'max_execution_time', 0 );
    set_time_limit(0);

    // Check Report Schedule
    CereusReporting_process_schedule( 'CRON' );
    set_time_limit(0);

    CereusReporting_logger( 'Scheduled Report Check Finished.', "info", "cron" );

	unlink( sys_get_temp_dir() . '/cronisrunning' );

	/* take time and log performance data */
	list( $micro, $seconds ) = preg_split( "/\s/", microtime() );
	$end = $seconds + $micro;

	$cacti_stats = sprintf( "Time:%01.4f ", round( $end - $start, 4 ) );

	//cacti_log( "CEREUSREPORTING STATS: " . $cacti_stats, TRUE, "SYSTEM" );
	CereusReporting_logger( "CEREUSREPORTING STATS: " . $cacti_stats, "info", "system" );