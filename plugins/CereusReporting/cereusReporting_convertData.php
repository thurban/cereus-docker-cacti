<?php
	/*******************************************************************************
	 *
	 * File:         $Id: cereusReporting_convertData.php,v bf53c8f13e3c 2017/01/09 10:01:05 thurban $
	 * Modified_On:  $Date: 2017/01/09 10:01:05 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
 *******************************************************************************/
	
    global $config, $database_type, $database_default,$database_hostname,$database_username,$database_password,$database_port;



    $dir = dirname(__FILE__);
	$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
	$logType = 'SYSTEM';

    chdir($mainDir);
    include_once("./include/global.php");
    include_once("./lib/rrd.php");
    include_once('./include/config.php');
    include_once("./lib/database.php" );

	$start_id = $argv[ 1 ];
	$end_id   = $argv[ 2 ];
	if ( $end_id < $start_id ) {
		$end_id = 999999999;
	}
	if ( $end_id == $start_id ) {
		$end_id = $start_id+1; }

	cacti_log( 'NMID CereusReporting - Starting Data conversion', true, $logType );
	$start_id    = $start_id - 1;
	$a_hostPolls = db_fetch_assoc( 'select id from host where id > ' . $start_id . ' AND id < ' . $end_id . ' ORDER BY id;' );
	foreach ($a_hostPolls as $a_poll) {
		$deviceId = $a_poll[ 'id' ];
		convert_data( $deviceId, TRUE );
	}


	//cacti_log('NMID CereusReporting - Deleting old data',true, $logType);
	//$saveTimeStamp = read_config_option('nmid_avail_PollMaxRawData');
	//$lastTimeStampToSave = time() - $saveTimeStamp;
	//db_execute("DELETE FROM `plugin_nmidCreatePDF_Availability_Table` WHERE typeId = 'p' AND timeStamp < $lastTimeStampToSave;");

	cacti_log( 'NMID CereusReporting - End of Data conversion', true, $logType );


	function convert_data( $deviceId )
	{
		global $logType;

		$poller_inverval = read_config_option( 'poller_interval' );

		cacti_log( 'NMID CereusReporting - Processing device [' . $deviceId . ']', TRUE, $logType );

		$current_table = 'plugin_nmidCreatePDF_Availability_Table_' . $deviceId;

		// Process raw data:
		$host_data_array = db_fetch_assoc( "select * from " . $current_table . " ORDER BY timeStamp DESC" );
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			echo "Processing device [$deviceId] - Raw data ...\n";
		}
		$lowest_timestamp = 99999999999999;
		$i                = 0;
		foreach ( $host_data_array as $host_data ) {
			$i++;
			if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
				// WIndows
			}
			else {
				progressBar( $i, count( $host_data_array ), "Device [$deviceId] Raw Data" );
			}
			$orig_total_polls  = $host_data[ 'orig_total_polls' ];
			$orig_failed_polls = $host_data[ 'orig_failed_polls' ];
			$typeId            = $host_data[ 'typeId' ];
			$timeStamp         = $host_data[ 'timeStamp' ];
			$total_polls       = $host_data[ 'total_polls' ];
			$failed_polls      = $host_data[ 'failed_polls' ];
			if ( $total_polls > 1 ) {
				$total_polls = 1;
			}
			if ( $failed_polls > 1 ) {
				$failed_polls = 1;
			}
			// Add Raw Polling Data
			db_execute( "
                INSERT INTO `" . $current_table . "`
                    (
                     `deviceId`, `typeId`,`timeStamp`, `total_polls`, `failed_polls`, `orig_total_polls`, `orig_failed_polls`,`isLastEntry`,`isAggregated`
                    )
                VALUES
                    (
                     '$deviceId', '$typeId','$timeStamp','$total_polls','$failed_polls','$orig_total_polls','$orig_failed_polls','0','0'
                    )
            " );
			if ( $timeStamp < $lowest_timestamp ) {
				$lowest_timestamp = $timeStamp;
			}
		}

		// Process hourly data:
		$host_data_array = db_fetch_assoc( "select * from plugin_nmidCreatePDF_Availability_Table where typeId='h' AND timeStamp<" . $lowest_timestamp . " AND deviceId=" . $deviceId . " ORDER BY timeStamp DESC" );
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			echo "Processing device [$deviceId] - Hourly data ...\n";
		}
		foreach ( $host_data_array as $host_data ) {
			$orig_total_polls  = $host_data[ 'orig_total_polls' ];
			$orig_failed_polls = $host_data[ 'orig_failed_polls' ];
			$typeId            = 'p';
			$timeStamp         = $host_data[ 'timeStamp' ];
			$total_polls       = $host_data[ 'total_polls' ];
			$failed_polls      = $host_data[ 'failed_polls' ];
			if ( $total_polls > 1 ) {
				$total_polls = 1;
			}
			if ( $failed_polls > 1 ) {
				$failed_polls = 1;
			}
			// Add hourly Polling Data
			$hour = date( 'H d-M-Y', $timeStamp );
			for ( $i = 1; $i <= 60; $i++ ) {
				if ( $timeStamp > $lowest_timestamp ) {
					$i = 60;
				}
				elseif ( date( 'H d-M-Y', $timeStamp ) != $hour ) {
					$i = 60;
				}
				else {
					db_execute( "
                INSERT INTO `" . $current_table . "`
                    (
                     `deviceId`, `typeId`,`timeStamp`, `total_polls`, `failed_polls`, `orig_total_polls`, `orig_failed_polls`,`isLastEntry`,`isAggregated`
                    )
                VALUES
                    (
                     '$deviceId', '$typeId','$timeStamp','$total_polls','$failed_polls','$orig_total_polls','$orig_failed_polls','0','0'
                    )               
                " );
				}
				if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
					// WIndows
				}
				else {
					progressBar( $i, 60, "Device [$deviceId] Hourly (" . $hour . ") Data" );
				}
				$timeStamp = $timeStamp + $poller_inverval;

			}
			if ( $timeStamp < $lowest_timestamp ) {
				$lowest_timestamp = $timeStamp - $poller_inverval;
			}
		}

		// Process daily data:
		$host_data_array = db_fetch_assoc( "select * from plugin_nmidCreatePDF_Availability_Table where typeId='d' AND timeStamp<" . $lowest_timestamp . " AND deviceId=" . $deviceId . " ORDER BY timeStamp DESC" );
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			echo "Processing device [$deviceId] - Daily data ...\n";
		}
		foreach ( $host_data_array as $host_data ) {
			$orig_total_polls  = $host_data[ 'orig_total_polls' ];
			$orig_failed_polls = $host_data[ 'orig_failed_polls' ];
			$typeId            = 'p';
			$timeStamp         = $host_data[ 'timeStamp' ];
			$total_polls       = $host_data[ 'total_polls' ];
			$failed_polls      = $host_data[ 'failed_polls' ];
			if ( $total_polls > 1 ) {
				$total_polls = 1;
			}
			if ( $failed_polls > 1 ) {
				$failed_polls = 1;
			}
			// Add hourly Polling Data
			$day = date( 'd M Y', $timeStamp );
			for ( $i = 1; $i <= 1440; $i++ ) {
				if ( $timeStamp > $lowest_timestamp ) {
					$i = 1440;
				}
				elseif ( date( 'd M Y', $timeStamp ) != $day ) {
					$i = 1440;
				}
				else {
					db_execute( "
	                INSERT INTO `" . $current_table . "`
	                    (
	                     `deviceId`, `typeId`,`timeStamp`, `total_polls`, `failed_polls`, `orig_total_polls`, `orig_failed_polls`,`isLastEntry`,`isAggregated`
	                    )
	                VALUES
	                    (
	                     '$deviceId', '$typeId','$timeStamp','$total_polls','$failed_polls','$orig_total_polls','$orig_failed_polls','0','0'
	                    )               
                " );
				}
				if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
					// WIndows
				}
				else {
					progressBar( $i, 1440, "Device [$deviceId] Daily (" . $day . ")Data" );
				}
				$timeStamp = $timeStamp + $poller_inverval;

			}
			if ( $timeStamp < $lowest_timestamp ) {
				$lowest_timestamp = $timeStamp - $poller_inverval;
			}
		}

		// Process weekly data:
		$host_data_array = db_fetch_assoc( "select * from plugin_nmidCreatePDF_Availability_Table where typeId='w' AND timeStamp<" . $lowest_timestamp . " AND deviceId=" . $deviceId . " ORDER BY timeStamp DESC" );
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			echo "Processing device [$deviceId] - Weekly data ...\n";
		}
		foreach ( $host_data_array as $host_data ) {
			$orig_total_polls  = $host_data[ 'orig_total_polls' ];
			$orig_failed_polls = $host_data[ 'orig_failed_polls' ];
			$typeId            = 'p';
			$timeStamp         = $host_data[ 'timeStamp' ];
			$total_polls       = $host_data[ 'total_polls' ];
			$failed_polls      = $host_data[ 'failed_polls' ];
			if ( $total_polls > 1 ) {
				$total_polls = 1;
			}
			if ( $failed_polls > 1 ) {
				$failed_polls = 1;
			}
			// Add hourly Polling Data
			$week = date( 'W Y', $timeStamp );
			for ( $i = 1; $i <= 10080; $i++ ) {
				if ( $timeStamp > $lowest_timestamp ) {
					$i = 10080;
				}
				elseif ( date( 'W Y', $timeStamp ) != $week ) {
					$i = 10080;
				}
				else {
					db_execute( "
                INSERT INTO `" . $current_table . "`
                    (
                     `deviceId`, `typeId`,`timeStamp`, `total_polls`, `failed_polls`, `orig_total_polls`, `orig_failed_polls`,`isLastEntry`,`isAggregated`
                    )
                VALUES
                    (
                     '$deviceId', '$typeId','$timeStamp','$total_polls','$failed_polls','$orig_total_polls','$orig_failed_polls','0','0'
                    )               
                " );
				}
				if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
					// WIndows
				}
				else {
					progressBar( $i, 10080, "Device [$deviceId] Weekly (" . $week . ") Data" );
				}
				$timeStamp = $timeStamp + $poller_inverval;
			}
			if ( $timeStamp < $lowest_timestamp ) {
				$lowest_timestamp = $timeStamp - $poller_inverval;
			}
		}

		// Process monhtly data:
		$host_data_array = db_fetch_assoc( "select * from plugin_nmidCreatePDF_Availability_Table where typeId='m' AND timeStamp<" . $lowest_timestamp . " AND deviceId=" . $deviceId . " ORDER BY timeStamp DESC" );
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			echo "Processing device [$deviceId] - Monthly data ...\n";
		}
		foreach ( $host_data_array as $host_data ) {
			$orig_total_polls  = $host_data[ 'orig_total_polls' ];
			$orig_failed_polls = $host_data[ 'orig_failed_polls' ];
			$typeId            = 'p';
			$timeStamp         = $host_data[ 'timeStamp' ];
			$total_polls       = $host_data[ 'total_polls' ];
			$failed_polls      = $host_data[ 'failed_polls' ];
			if ( $total_polls > 1 ) {
				$total_polls = 1;
			}
			if ( $failed_polls > 1 ) {
				$failed_polls = 1;
			}
			$month = date( 'M Y', $timeStamp );
			// Add monthly Polling Data
			for ( $i = 1; $i <= 44640; $i++ ) {
				if ( $timeStamp > $lowest_timestamp ) {
					$i = 44640;
				}
				elseif ( date( 'M Y', $timeStamp ) != $month ) {
					$i = 44640;
				}
				else {
					db_execute( "
		                INSERT INTO `" . $current_table . "`
		                    (
		                     `deviceId`, `typeId`,`timeStamp`, `total_polls`, `failed_polls`, `orig_total_polls`, `orig_failed_polls`,`isLastEntry`,`isAggregated`
		                    )
		                VALUES
		                    (
		                     '$deviceId', '$typeId','$timeStamp','$total_polls','$failed_polls','$orig_total_polls','$orig_failed_polls','0','0'
		                    )               
		                " );
				}
				if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
					// WIndows
				}
				else {
					progressBar( $i, 44640, "Device [$deviceId] Monhtly (" . $month . ") Data" );
				}
				$timeStamp = $timeStamp + $poller_inverval;
			}
			if ( $timeStamp < $lowest_timestamp ) {
				$lowest_timestamp = $timeStamp - $poller_inverval;
			}
		}

		// Process yearly data:
		$host_data_array = db_fetch_assoc( "select * from plugin_nmidCreatePDF_Availability_Table where typeId='y' AND timeStamp<" . $lowest_timestamp . " AND deviceId=" . $deviceId . " ORDER BY timeStamp DESC" );
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			echo "Processing device [$deviceId] - Yearly data ...\n";
		}
		foreach ( $host_data_array as $host_data ) {
			$orig_total_polls  = $host_data[ 'orig_total_polls' ];
			$orig_failed_polls = $host_data[ 'orig_failed_polls' ];
			$typeId            = 'p';
			$timeStamp         = $host_data[ 'timeStamp' ];
			$total_polls       = $host_data[ 'total_polls' ];
			$failed_polls      = $host_data[ 'failed_polls' ];
			if ( $total_polls > 1 ) {
				$total_polls = 1;
			}
			if ( $failed_polls > 1 ) {
				$failed_polls = 1;
			}
			// Add yearly Polling Data
			$year = date( 'Y', $timeStamp );
			for ( $i = 1; $i <= 525600; $i++ ) {
				if ( $timeStamp > $lowest_timestamp ) {
					$i = 525600;
				}
				elseif ( date( 'Y', $timeStamp ) != $year ) {
					$i = 525600;
				}
				else {
					db_execute( "
                		INSERT INTO `" . $current_table . "`
                    (
                     `deviceId`, `typeId`,`timeStamp`, `total_polls`, `failed_polls`, `orig_total_polls`, `orig_failed_polls`,`isLastEntry`,`isAggregated`
                    )
                        VALUES
                    (
                     '$deviceId', '$typeId','$timeStamp','$total_polls','$failed_polls','$orig_total_polls','$orig_failed_polls','0','0'
                    )               
                " );
				}
				if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
					// WIndows
				}
				else {
					progressBar( $i, 525600, "Device [$deviceId] Yearly (" . $year . ") Data" );
				}
				$timeStamp = $timeStamp + $poller_inverval;
			}
			if ( $timeStamp < $lowest_timestamp ) {
				$lowest_timestamp = $timeStamp - $poller_inverval;
			}
		}
		echo "\n";
	}

	function progressBar( $current = 0, $total = 100, $label = "", $size = 50 )
	{

		//Don't have to call $current=0
		//Bar status is stored between calls
		static $bars;
		if ( !isset( $bars[ $label ] ) ) {
			$new_bar = TRUE;
			fputs( STDOUT, "$label Progress:\n" );
		}
		if ( $current == $bars[ $label ] ) {
			return 0;
		}

		$perc = round( ( $current / $total ) * 100, 2 );        //Percentage round off for a more clean, consistent look
		for ( $i = strlen( $perc ); $i <= 4; $i++ ) {
			$perc = ' ' . $perc;
		}    // percent indicator must be four characters, if shorter, add some spaces

		$total_size = $size + $i + 3;
		// if it's not first go, remove the previous bar
		if ( !$new_bar ) {
			for ( $place = $total_size; $place > 0; $place-- ) {
				echo "\x08";
			}    // echo a backspace (hex:08) to remove the previous character
		}

		$bars[ $label ] = $current; //saves bar status for next call
		// output the progess bar as it should be
		for ( $place = 0; $place <= $size; $place++ ) {
			if ( $place <= ( $current / $total * $size ) ) {
				echo '[42m [0m';
			}    // output green spaces if we're finished through this point
			else {
				echo '[47m [0m';
			}                    // or grey spaces if not
		}

		// end a bar with a percent indicator
		echo " $perc%";

		if ( $current == $total ) {
			echo "\n";        // if it's the end, add a new line
			unset( $bars[ $label ] );
		}
		ob_flush();
		flush();
	}