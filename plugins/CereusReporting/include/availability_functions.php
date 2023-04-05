<?php
	/*******************************************************************************
	 * File:         $Id: availability_functions.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 *
	 *******************************************************************************/


	function CereusReporting_getTotalPollsFromDevice( $deviceId, $startTime, $endTime )
	{
		$device_total_polls = getPreparedDBValue('SELECT total_polls from host where id=?',array($deviceId) );
		// TODO: Retrieve the polling interval for this device
		$pollingInterval = readConfigOption( 'poller_interval' );

		// Get the timeframe
		$timeframe = ( $endTime - $startTime );

		// Get a timeframe which is divideable by the polling interval
		$timeframe_fixed = $timeframe - ( $timeframe % $pollingInterval );

		// Calculate the total polls during the timeframe
		$myTotalPolls = ($timeframe_fixed / $pollingInterval);
		if ( $myTotalPolls > $device_total_polls ) {
			return $device_total_polls;
		} else {
			return $myTotalPolls;
		}
	}

	function CereusReporting_getFailedPollsFromDevice( $deviceId, $startTime, $endTime )
	{
		$where_clause = CereusReporting_buildTimeStampQuery( $startTime, $endTime );
		$myFailedPolls = db_fetch_cell( "
            SELECT
                sum(failed_polls)
            FROM
                plugin_nmidCreatePDF_AvailabilityFailedPolls_Table
            WHERE
               $where_clause
               AND
                deviceId = " . $deviceId . ";"
		);
		return $myFailedPolls;
	}

	function CereusReporting_buildTimeStampQuery( $startTime, $endTime )
	{
		$where_clause = "timeStamp BETWEEN " . $startTime . " AND " . $endTime ;
		return $where_clause;
	}

	function CereusReporting_getFailedPollInSLA( $timestamp, $slaTimeFrame, $failedPolls )
	{
		// Get DB Instance
		$db = DBCxn::get();

		// Check for SLA TimeFrame
		$s_timeframes_sql = "
            SELECT
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`
            FROM
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
            WHERE
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=" . $slaTimeFrame . "
        ";
		$sth = $db->prepare( $s_timeframes_sql );
		$sth->execute();
		$dayString = date( "D", $timestamp );
		$skipData  = TRUE;
		// Check if the data is in the SLA timeframe
		while ( ( $tfRow = $sth->fetch(PDO::FETCH_ASSOC) ) !== false ) {
			if ( preg_match( "/$dayString/", $tfRow[ 'defaultDays' ] ) ) {
				// The time is within the SLA relevant day so let's look into it in more detail
				$a_defaultStartTimeItemsList = preg_split( "/,/", $tfRow[ 'defaultStartTime' ] );
				$a_defaultEndTimeItemsList   = preg_split( "/,/", $tfRow[ 'defaultEndTime' ] );
				for ( $listCount = 0; $listCount < sizeof( $a_defaultStartTimeItemsList ); $listCount++ ) {

					// Get the start of the SLA timeframe
					$a_defaultStartTimeItems = preg_split( "/:/", $a_defaultStartTimeItemsList[ $listCount ] );
					$s_defaultStartTime      = mktime( $a_defaultStartTimeItems[ 0 ], $a_defaultStartTimeItems[ 1 ], 0, date( "m", $timestamp ), date( "d", $timestamp ), date( "Y", $timestamp ) );

					// Get the end of the SLA timeframe
					$a_defaultEndTimeItems = preg_split( "/:/", $a_defaultEndTimeItemsList[ $listCount ] );
					$s_defaultEndTime      = mktime( $a_defaultEndTimeItems[ 0 ], $a_defaultEndTimeItems[ 1 ], 0, date( "m", $timestamp ), date( "d", $timestamp ), date( "Y", $timestamp ) );
					if ( ( $timestamp > $s_defaultStartTime - 1 ) && ( $timestamp < $s_defaultEndTime + 1 ) ) {
						// if the time of the outtage is within the start and end date then we need to take it into account.
						$skipData = FALSE;
					}
				}
			}
		}
		$sth->closeCursor();

		// Check for SLA TimeFrame Items - Defined outtages or other descriptions
		$s_timeframes_sql = "
            SELECT
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`startTimeStamp`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`endTimeStamp`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`slaEnabled`
            FROM
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`
            WHERE
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`slaTimeFrameId`=" . $slaTimeFrame . "
        ";

		$sth = $db->prepare( $s_timeframes_sql );
		$sth->execute();
		$prevSkipVar = $skipData;
		while ( ( $tfRow = $sth->fetch(PDO::FETCH_ASSOC) ) !== false ) {
			if ( $timestamp > $tfRow[ 'startTimeStamp' ] - 1 ) {
				if ( $timestamp < ( $tfRow[ 'endTimeStamp' ] + 1 ) ) {
					if ( $tfRow[ 'slaEnabled' ] == 'on' ) {
						$skipData = FALSE;
						// SLA Relevant Data needs to be included
					}
				}
			}
		}
		$sth->closeCursor();

		if ( ( $skipData == FALSE ) || ( $prevSkipVar == FALSE ) ) {
			return $failedPolls;
		}
		else {
			return 0;
		}
	}

	/*
Get Failed Windows Service Polls
*/
	function CereusReporting_getFailedServicePolls( $deviceId = 0, $ldid = 0, $startTime = 0, $endTime = 0 )
	{
		// Get DB Instance
		$db = DBCxn::get();

		$failedServicePolls    = 0;
		$nonFailedServicePolls = 0;
		// Get the default SLA Timeframe for this host (e.g. 24x7 or 5x8 )
		$slaTimeFrame = CereusReporting_getSlaTimeFrame( $deviceId );

		// Add service outtages:
		$serviceSql = "
            SELECT
                failed_polls,
                timeStamp
            FROM
                plugin_nmidCreatePDF_AvailabilityFailedPolls_Table
            WHERE
                timeStamp > $startTime
            AND
                timeStamp < $endTime
            AND
                ldid = " . $ldid;

		$sth = $db->prepare( $serviceSql );
		$sth->execute();
		while ( ( $a_hostServiceState = $sth->fetch(PDO::FETCH_ASSOC) ) != false ) {
			$failedServicePolls += CereusReporting_getFailedPollInSLA( $a_hostServiceState[ 'timeStamp' ], $slaTimeFrame, $a_hostServiceState[ 'failed_polls' ] );
		}
		$sth->closeCursor();
		return ( $failedServicePolls );
	}


	function CereusReporting_process_service_states()
	{
		$logType = 'POLLER';
		//CereusReporting_logger( 'NMID CereusReporting - Checking for Win Service State Fails', "info", "winservice" );

		$a_hostServiceStates = db_fetch_assoc( "
            SELECT
                `data_template_data`.local_data_id,
                `data_template_data`.name_cache,
                `data_local`.host_id,
                `plugin_storeLastPoll_data`.key,
                `plugin_storeLastPoll_data`.value
            FROM
                `data_template_data`,
                `data_local`,
                `plugin_storeLastPoll_data`
            WHERE
                `data_local`.id = `data_template_data`.local_data_id
            AND
                `plugin_storeLastPoll_data`.local_data_id = `data_local`.id
            AND
                `plugin_storeLastPoll_data`.key = 'service_state'
        " );
		foreach ( $a_hostServiceStates as $a_hostServiceState ) {
			if ( $a_hostServiceState[ 'value' ] == 0 ) {
				// Win Service failed
				db_execute( "
                    INSERT INTO plugin_nmidCreatePDF_AvailabilityFailedPolls_Table
                        (`deviceId`, `timeStamp`, `failed_polls`, `ldid`)
                    VALUES
                        (" . $a_hostServiceState[ 'host_id' ] . ",'" . time() . "',1," . $a_hostServiceState[ 'local_data_id' ] . ")
                " );
			}
		}
		//CereusReporting_logger( 'NMID CereusReporting - End of Win Service State Fails Check ', "info", "winservice" );
	}

	function CereusReporting_process_polls()
	{
		global $database_default;
		$logType = 'POLLER';
		$a_hostPolls = db_fetch_assoc( 'select id,total_polls,failed_polls from host;' );
		CereusReporting_logger( 'Retrieveing polling data from the host table', 'debug', 'availability' );

		foreach ( $a_hostPolls as $a_poll ) {

			$deviceId          = $a_poll[ 'id' ];
			$typeId            = 'p';
			$timeStamp         = time();
			$orig_total_polls  = $a_poll[ 'total_polls' ];
			$orig_failed_polls = $a_poll[ 'failed_polls' ];
			CereusReporting_logger( 'Retrieving previous polling data from device ['.$deviceId.']', 'debug', 'availability' );

			$host_data      = db_fetch_assoc( 'SELECT deviceId,orig_total_polls,orig_failed_polls FROM plugin_CereusAvailability_Table WHERE deviceId=' . $deviceId );
			if (is_array($host_data)) {
			    if (isset($host_data[0])) {
                    $host_data = $host_data[ 0 ];
                }

			    if (isset($host_data['orig_total_polls'])) {
                    $lastTotalPolls = $host_data[ 'orig_total_polls' ];
                    $lastTotalFails = $host_data[ 'orig_failed_polls' ];

                    if ( $host_data[ 'deviceId' ] > 0 ) {
                        $total_polls  = $orig_total_polls - $lastTotalPolls;
                        $failed_polls = $orig_failed_polls - $lastTotalFails;
                        CereusReporting_logger( 'Existing Device. Checking polling data for device [' . $deviceId . '] - Failed Polls: [' . $failed_polls . '] - Total Polls: [' . $total_polls . ']', 'debug', 'availability' );

                        if ( $failed_polls > 0 ) {
                            db_execute( "
                            INSERT INTO `plugin_nmidCreatePDF_AvailabilityFailedPolls_Table`
                            (
                             `deviceId`, `timeStamp`, `failed_polls`, `ldid`
                            )
                            VALUES
                                (
                                 '$deviceId', '$timeStamp', '1', '0'
                                )
                            " );
                        }
                        // Add Raw Polling Data
                        db_execute( "
                    UPDATE `plugin_CereusAvailability_Table`
                    SET 
                         `total_polls` = '$total_polls',
                         `failed_polls` = '$failed_polls',
                         `orig_total_polls` = '$orig_total_polls',
                         `orig_failed_polls` = '$orig_failed_polls'
                    WHERE `deviceId` = '$deviceId'
                    " );
                    } else {
                        CereusReporting_logger( 'New Device. Adding initial polling entry to datbase for device [' . $deviceId . ']', 'debug', 'availability' );
                        $typeId       = 'i';
                        $total_polls  = $orig_total_polls;
                        $failed_polls = $orig_failed_polls;
                        // Add Raw Polling Data
                        db_execute( "
                    INSERT INTO `plugin_CereusAvailability_Table`
                        (
                         `deviceId`, `total_polls`, `failed_polls`, `orig_total_polls`, `orig_failed_polls`
                        )
                    VALUES
                        (
                         '$deviceId','$total_polls','$failed_polls','$orig_total_polls','$orig_failed_polls'
                        )
                    " );
                    }
                }
            }
		}
	}

	function get_device_availability( $host_id = 0, $startTime = 0, $endTime = 1 )
	{
		$globalSLA = readConfigOption( 'nmid_avail_globalSla' );
		$host_sla  = db_fetch_cell( "select nmid_host_sla from host where id=$host_id" );
		$slaValue  = $globalSLA;
		$slaString = 'Global';
		if ( $host_sla > 0 ) {
			$slaValue  = $host_sla;
			$slaString = 'Host';
		}

		$where_clause = CereusReporting_buildTimeStampQuery( $startTime, $endTime );
		$totalPolls   = CereusReporting_getTotalPollsFromDevice( $host_id, $startTime, $endTime );
		$failedPolls  = CereusReporting_getFailedPollsFromDevice( $host_id, $startTime, $endTime );
		CereusReporting_logger( 'Retrieveing Total Polls from host ['.$host_id.'] =  [' . $totalPolls . ']', 'debug', 'availability' );
		CereusReporting_logger( 'Retrieveing Failed Polls from host ['.$host_id.'] =  [' . $failedPolls . ']', 'debug', 'availability' );

		$sql = "
            SELECT
              plugin_nmidCreatePDF_Availability_Change_Table.Id,
              plugin_nmidCreatePDF_Availability_Change_Table.deviceId,
              plugin_nmidCreatePDF_Availability_Change_Type.decreaseAvailability,
              plugin_nmidCreatePDF_Availability_Change_Table.changeTypeId,
              plugin_nmidCreatePDF_Availability_Change_Table.startTimeStamp,
              plugin_nmidCreatePDF_Availability_Change_Table.endTimeStamp,
              plugin_nmidCreatePDF_Availability_Change_Table.shortDescription
            FROM
              plugin_nmidCreatePDF_Availability_Change_Table INNER JOIN
              plugin_nmidCreatePDF_Availability_Change_Type ON plugin_nmidCreatePDF_Availability_Change_Table.changeTypeId
            = plugin_nmidCreatePDF_Availability_Change_Type.Id
            WHERE
                plugin_nmidCreatePDF_Availability_Change_Table.deviceId = " . $host_id . "
            ORDER BY startTimeStamp";

		$noSLA_TotalPolls  = 0;
		$noSLA_FailedPolls = 0;
		$rows              = db_fetch_assoc( $sql );
		foreach ( $rows as $row ) {
			if ( $row[ "decreaseAvailability" ] == 0 ) {
				$startTime         = $row[ "startTimeStamp" ];
				$endTime           = $row[ "endTimeStamp" ];
				$myTotalPolls      = CereusReporting_getTotalPollsFromDevice( $host_id, $startTime, $endTime );
				$myFailedPolls     = CereusReporting_getFailedPollsFromDevice( $host_id, $startTime, $endTime );
				$noSLA_TotalPolls  = $noSLA_TotalPolls + $myTotalPolls;
				$noSLA_FailedPolls = $noSLA_FailedPolls + $myFailedPolls;
			}

		}
		CereusReporting_logger( 'Retrieveing Decrease Polls from host ['.$host_id.'] =  [' . $noSLA_FailedPolls . ']', 'debug', 'availability' );

		$sql = "
            SELECT
            timeStamp,
            failed_polls
            FROM
            plugin_nmidCreatePDF_AvailabilityFailedPolls_Table
            WHERE
                $where_clause
            AND
            deviceId = " . $host_id . " 
            ORDER BY `timeStamp`";

		$skipData                = TRUE;
		$slaTimeFrame            = readConfigOption( 'nmid_avail_globalSlaTimeFrame' );
		$nmid_host_sla_timeframe = getDBValue( 'nmid_host_sla_timeframe', 'select nmid_host_sla_timeframe from host where id=' . $host_id . ';' );
		if ( $nmid_host_sla_timeframe > 0 ) {
			$slaTimeFrame = $nmid_host_sla_timeframe;
		}
		$rows             = db_fetch_assoc( $sql );
		$s_timeframes_sql = "
        SELECT
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`
        FROM
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
        WHERE
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=" . $slaTimeFrame . "
        ";
		$tfRows           = db_fetch_assoc( $s_timeframes_sql );
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$dayString = date( "D", $row[ 'timeStamp' ] );
				$skipData  = TRUE;
				foreach ( $tfRows as $tfRow ) {
					// CereusReporting_logger( 'Checking day ['.$dayString.'] against row day ['.$tfRow[ 'defaultDays' ].']', 'debug', 'availability' );
					if ( preg_match( "/$dayString/i", $tfRow[ 'defaultDays' ] ) ) {
						$a_defaultStartTimeItemsList = preg_split( "/,/", $tfRow[ 'defaultStartTime' ] );
						$a_defaultEndTimeItemsList   = preg_split( "/,/", $tfRow[ 'defaultEndTime' ] );
						for ( $listCount = 0; $listCount < sizeof( $a_defaultStartTimeItemsList ); $listCount++ ) {
							$a_defaultStartTimeItems = preg_split( "/:/", $a_defaultStartTimeItemsList[ $listCount ] );
							$s_defaultStartTime      = mktime( $a_defaultStartTimeItems[ 0 ], $a_defaultStartTimeItems[ 1 ], 0, date( "m", $row[ 'timeStamp' ] ), date( "d", $row[ 'timeStamp' ] ), date( "Y", $row[ 'timeStamp' ] ) );
							$a_defaultEndTimeItems   = preg_split( "/:/", $a_defaultEndTimeItemsList[ $listCount ] );
							$s_defaultEndTime        = mktime( $a_defaultEndTimeItems[ 0 ], $a_defaultEndTimeItems[ 1 ], 0, date( "m", $row[ 'timeStamp' ] ), date( "d", $row[ 'timeStamp' ] ), date( "Y", $row[ 'timeStamp' ] ) );
							if ( $row[ 'timeStamp' ] > $s_defaultStartTime - 1 ) {
								$skipData = FALSE;
								//CereusReporting_logger( 'Not skipping data from host ['.$host_id.']', 'debug', 'availability' );
							}
							if ( $row[ 'timeStamp' ] + 1 > $s_defaultEndTime ) {
								$skipData = TRUE;
								//CereusReporting_logger( 'Skipping Data from host ['.$host_id.']', 'debug', 'availability' );
							}
						}
					}
				} // end foreach tfRows
				if ( $skipData == TRUE ) {
					$noSLA_FailedPolls = $noSLA_FailedPolls + $row[ 'failed_polls' ];
					//CereusReporting_logger( 'Retrieveing Decrease Polls from host ['.$host_id.'] =  [' . $noSLA_FailedPolls . ']', 'debug', 'availability' );
				}
			} // end foreach rows
		} // end is_array

		// $totalPolls  = $totalPolls - $noSLA_TotalPolls;
		$failedPolls = $failedPolls - $noSLA_FailedPolls;
		CereusReporting_logger( 'Retrieveing Failed Polls after proc from host ['.$host_id.'] =  [' . $failedPolls . ']', 'debug', 'availability' );

		if ( $totalPolls < 0 ) {
			$totalPolls = 0;
		}
		if ( $failedPolls < 0 ) {
			$failedPolls = 0;
		}

		if ( $failedPolls > $totalPolls ) {
			$failedPolls = 0;
		}

		$availability = 100;
		if ( $totalPolls > 0 ) {
			$availability = ( 100 * ( $totalPolls - $failedPolls ) ) / $totalPolls;
		}

		return array( $availability, $totalPolls, $failedPolls );
	}

	function CereusReporting_getSlaTimeFrame( $deviceId )
	{
		// Get the default SLA Timeframe for this host (e.g. 24x7 or 5x8 )
		$slaTimeFrame            = 1;
		$nmid_host_sla_timeframe = getDBValue( 'nmid_host_sla_timeframe', 'select nmid_host_sla_timeframe from host where id=' . $deviceId . ';' );
		if ( $nmid_host_sla_timeframe > 0 ) {
			$slaTimeFrame = $nmid_host_sla_timeframe;
		}
		if ( !( $slaTimeFrame > 0 ) ) {
			$slaTimeFrame = 1;
		}
		return $slaTimeFrame;
	}

	// Availability Report Generation - Multi Report
	function printAvailabilityCombinedGraph( $pdf, $reportId, $data, $startTime, $endTime, $tier )
	{
		global $phpBinary, $config;

		$abcArray           = array( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
			'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z' );
		$sqlArray           = preg_split( "/;/", $data );
		$tree_id            = $sqlArray[ 0 ];
		$leaf_id            = $sqlArray[ 1 ];
		$slaTime_id         = $sqlArray[ 2 ];
		$printOriginalGraph = TRUE;

		// Get DB Instance
		$db = DBCxn::get();

		if ( isNumber( $slaTime_id ) == FALSE ) {
			$slaTime_id = -1;
		}
		if ( readConfigOption( 'nmid_avail_addGraph' ) ) {
			if ( $leaf_id > 0 ) {
				if ( readConfigOption( 'nmid_avail_sort_option' ) == "abc" ) {
					if ( function_exists('top_header')) {
						// Get all items for this tree
						$a_item_array = array();
						$a_item_array  = cr_get_graph_items( $a_item_array, $tree_id, $leaf_id, 0,  1 );
						$deviceCount = count($a_item_array);
					} else {
						$orderKey    = getDBValue( 'order_key', 'SELECT order_key FROM graph_tree_items WHERE id=' . $leaf_id . ';' );
						$hostId      = getDBValue( 'host_id', 'SELECT host_id FROM graph_tree_items WHERE id=' . $leaf_id . ';' );
						$orderKey    = preg_replace( "/0{3,3}/", "", $orderKey );
						$sql         = "SELECT COUNT(host_id) AS counter FROM graph_tree_items WHERE graph_tree_id='" . $tree_id . "' AND order_key LIKE '" . $orderKey . "%';";
						$deviceCount = getDBValue( 'counter', $sql );
					}
					if ( $deviceCount > readConfigOption( 'nmid_avail_maxDevices' ) ) {
						$printOriginalGraph = FALSE;
						foreach ( $abcArray as $key => $value ) {
							if ( function_exists('top_header')) {
								$device_array = array();
								foreach ($a_item_array as $device) {
									$local_stmt = $db->prepare( "select description from host where id = :hostid" );
									$local_stmt->bindValue( ':hostid', $device[ 'host_id' ] );
									$local_stmt->execute();
									$hostname = strtoupper($local_stmt->fetchColumn());
									$local_stmt->closeCursor();
									$sortedChar = strtoupper( $value );
									if ( substr($hostname,0,1) == $sortedChar ) {
										CereusReporting_logger( 'Found ABC device ' . '[' . $hostname . '] starting with ['.substr($hostname,0,1).'] for char ['.$sortedChar.']', 'debug', 'availability' );
										$device_array[ $hostname ] = $device;
									} else {
										// Skipping
										CereusReporting_logger( 'Skipping ABC device ' . '[' . $hostname . '] starting with ['.substr($hostname,0,1).'] for char ['.$sortedChar.']', 'debug', 'availability' );
									}
								}
								$specialDeviceCount = count($device_array);
							} else {
								$sql                = "SELECT COUNT(host_id) AS counter FROM graph_tree_items,host WHERE graph_tree_id='" . $tree_id . "' AND order_key LIKE '" . $orderKey . "%' AND graph_tree_items.host_id = host.id  AND UPPER(host.description) LIKE '" . strtoupper( $value ) . "%';";
								$specialDeviceCount = getDBValue( 'counter', $sql );
							}
							if ( $specialDeviceCount > 0 ) {
								$image_file = $pdf->nmidGetWorkerDir() . '/' . $value . '-' . $leaf_id . '-' . $tree_id . '-' . $reportId . '-' . $slaTime_id . '_availabilityCombined.png';
								$command    = ''; // $phpBinary . " cereusReporting_serverAvailabilityChartCLI.php $leaf_id $tree_id $slaTime_id $startTime $endTime 2 $value > " . $image_file;
								$title      = 'Availability Report ( ' . strtoupper( $value ) . ' )';
								$lgid       = 0;
								if ( $slaTime_id > 0 ) {
									$sla_short_descr = db_fetch_cell( "SELECT `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`
                                        FROM
                                        `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
                                        WHERE
                                        `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=$slaTime_id" );
									$title           = 'Availability Report ( ' . strtoupper( $value ) . ' ) - ( ' . $sla_short_descr . ' )';
								}
								$content = $pdf->nmidGetWorkerFileContent() . 'availability_combined' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
								$pdf->nmidSetWorkerFileContent( $content );

								$secure_key      = sha1( $leaf_id . $tree_id . $slaTime_id . $startTime . $endTime . SECURE_URL_KEY );
								$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChartCLI.php?key=' . $secure_key . '&leafId=' . $leaf_id . '&treeId=' . $tree_id . '&slaTimeId=' . $slaTime_id . '&isSorted=2&sortedFrom=' . $value . '&sortedTo=no&start=' . $startTime . '&end=' . $endTime;
								CereusReporting_logger( 'Adding availability_combined ' . '[' . $graph_image_url . ']', 'debug', 'availability' );
								curl_download( $graph_image_url, $image_file );
							}
						}
					}
				}
				else {
					// Device count based grouping
					if ( function_exists('top_header')) {
						// Get all items for this tree
						$a_item_array = array();
						$a_item_array  = cr_get_graph_items( $a_item_array, $tree_id, $leaf_id, 0,  1 );
						$deviceCount = count($a_item_array);
					} else {
						$orderKey    = getDBValue( 'order_key', 'SELECT order_key FROM graph_tree_items WHERE id=' . $leaf_id . ';' );
						$hostId      = getDBValue( 'host_id', 'SELECT host_id FROM graph_tree_items WHERE id=' . $leaf_id . ';' );
						$orderKey    = preg_replace( "/0{3,3}/", "", $orderKey );
						$sql         = "SELECT COUNT(id) AS counter FROM graph_tree_items WHERE graph_tree_id='" . $tree_id . "' AND host_id > 0 AND order_key LIKE '" . $orderKey . "%';";
						$deviceCount = getDBValue( 'counter', $sql );
					}
					if ( $deviceCount > readConfigOption( 'nmid_avail_maxDevices' ) ) {
						$printOriginalGraph       = FALSE;
						$sortedFrom               = 1;
						$sortedTo                 = readConfigOption( 'nmid_avail_maxDevices' );
						$finishedDeviceProcessing = FALSE;
						while ( $finishedDeviceProcessing == FALSE ) {
							$image_file      = $pdf->nmidGetWorkerDir() . '/' . $sortedFrom . '-' . $sortedTo . '-' . $leaf_id . '-' . $tree_id . '-' . $reportId . '-' . $slaTime_id . '_availabilityCombined.png';
							$command         = ''; // $phpBinary . " cereusReporting_serverAvailabilityChartCLI.php $leaf_id $tree_id $slaTime_id $startTime $endTime 1 $sortedFrom $sortedTo > " . $image_file;
							$sortedFromTitle = $sortedFrom;
							$sortedToTitle   = $sortedFrom + $sortedTo - 1;

							$secure_key      = sha1( $leaf_id . $tree_id . $slaTime_id . $startTime . $endTime . SECURE_URL_KEY );
							$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChartCLI.php?key=' . $secure_key . '&leafId=' . $leaf_id . '&treeId=' . $tree_id . '&slaTimeId=' . $slaTime_id . '&isSorted=1&sortedFrom=' . $sortedFrom . '&sortedTo=' . $sortedTo . '&start=' . $startTime . '&end=' . $endTime;
							CereusReporting_logger( 'Adding availability_combined ' . '[' . $graph_image_url . ']', 'debug', 'availability' );
							curl_download( $graph_image_url, $image_file );

							$sortedFrom      = $sortedFrom + $sortedTo;
							if ( $sortedFrom >= $deviceCount ) {
								$sortedFrom               = $deviceCount;
								$finishedDeviceProcessing = TRUE;
							}
							$title = 'Availability Report ( ' . $sortedFromTitle . ' - ' . $sortedToTitle . ' )';
							$lgid  = 0;
							if ( $slaTime_id > 0 ) {
								$sla_short_descr = db_fetch_cell( "SELECT `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`
                                 FROM
                               `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
                               WHERE
                               `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=$slaTime_id" );

								$title           = 'Availability Report ( ' . $sortedFromTitle . ' - ' . $sortedToTitle . ' ) - ( ' . $sla_short_descr . ' )';

							}
							$content = $pdf->nmidGetWorkerFileContent() . 'availability_combined' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
							$pdf->nmidSetWorkerFileContent( $content );
						}
					}
				}
			}
			if ( $printOriginalGraph ) {
				$image_file      = $pdf->nmidGetWorkerDir() . '/' . $leaf_id . '-' . $tree_id . '-' . $reportId . '-' . $slaTime_id . '_availabilityCombined.png';
				$command         = ''; // $phpBinary . " cereusReporting_serverAvailabilityChartCLI.php $leaf_id $tree_id $slaTime_id $startTime $endTime > " . $image_file;
				$title           = 'Availability Report';
				$sla_short_descr = db_fetch_cell( "SELECT `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`
            FROM
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
              WHERE
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=$slaTime_id" );
				$lgid            = 0;
				$content         = $pdf->nmidGetWorkerFileContent() . 'availability_combined' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
				$pdf->nmidSetWorkerFileContent( $content );

				$secure_key      = sha1( $leaf_id . $tree_id . $slaTime_id . $startTime . $endTime . SECURE_URL_KEY );
				$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChartCLI.php?key=' . $secure_key . '&leafId=' . $leaf_id . '&treeId=' . $tree_id . '&slaTimeId=' . $slaTime_id . '&isSorted=0&start=' . $startTime . '&end=' . $endTime;
				CereusReporting_logger( 'Adding availability_combined ' . '[' . $graph_image_url . ']', 'debug', 'availability' );
				curl_download( $graph_image_url, $image_file );
			}
		}
		if ( $leaf_id > 0 ) {
			if ( $pdf->nmidGetShowDetailedTable() ) {
				printAvailabilityCombinedTable( $pdf, $tree_id, $leaf_id, $startTime, $endTime, $tier, $slaTime_id );
			}
			if ( $pdf->nmidGetShowDetailedFailedTable()  ) {
				printDetailedAvailabilityCombinedTable( $pdf, '-1', $tree_id, $leaf_id, $startTime, $endTime, $tier, $slaTime_id );
			}
		}
		else {
			if ( $pdf->nmidGetShowDetailedTable() ) {
				printAvailabilityCombinedTreeTable( $pdf, $reportId, $tree_id, $startTime, $endTime, $tier, $slaTime_id );
			}
			if ( $pdf->nmidGetShowDetailedFailedTable() ) {
				printDetailedAvailabilityCombinedTreeTable( $pdf, $reportId, $tree_id, $startTime, $endTime, $tier, $slaTime_id );
			}
		}
	}

	// Availability Report Generation - Multi Report
	function printAvailabilityWinServiceCombinedGraph( $pdf, $reportId, $data, $startTime, $endTime, $tier )
	{
		global $phpBinary;

		// Get DB Instance
		$db = DBCxn::get();

		$sqlArray   = preg_split( "/;/", $data );
		$tree_id    = $sqlArray[ 0 ];
		$leaf_id    = $sqlArray[ 1 ];
		$slaTime_id = $sqlArray[ 2 ];
		if ( isNumber( $slaTime_id ) == FALSE ) {
			$slaTime_id = -1;
		}
		if ( readConfigOption( 'nmid_avail_addGraph' ) ) {
			$image_file      = $pdf->nmidGetWorkerDir() . '/' . $leaf_id . '-' . $tree_id . '-' . $reportId . '-' . $slaTime_id . '_availabilityWinServiceCombined.png';
			$sla_short_descr = db_fetch_cell( "SELECT `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`
        FROM
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
          WHERE
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=$slaTime_id" );
			//$title = 'Availability Report ( '.$sla_short_descr.' )';
			$title = '';
			$command = ''; // $phpBinary . " cereusReporting_winserviceAvailabilityChartCLI.php $leaf_id $tree_id $slaTime_id $startTime $endTime \"$title\"> " . $image_file;
			$title   = 'Windows Services Availability Report';
			$lgid    = 0;
			//$tier = -1;
			CereusReporting_logger( 'Adding AvailabilityWinServiceCOmbined ' . $title . ' -> ' . $command, 'debug', 'system' );
			$content = $pdf->nmidGetWorkerFileContent() . 'availability_winservice' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			$pdf->nmidSetWorkerFileContent( $content );
		}
	}

	function printAvailabilityTholdGraph( $pdf, $reportId, $data, $startTime, $endTime, $tier )
	{
		global $phpBinary;

		// Get DB Instance
		$db = DBCxn::get();

		$sqlArray   = preg_split( "/;/", $data );
		$thold_data = $sqlArray[ 0 ];
		if ( readConfigOption( 'nmid_avail_addGraph' ) ) {
			$image_file = $pdf->nmidGetWorkerDir() . '/' . $thold_data . '_availabilityTholdGraph.png';
			$command    = $phpBinary . " cereusReporting_TholdAvailabilityChartCLI.php $thold_data $startTime $endTime > " . $image_file;
			$title      = 'Thold Availability Report';
			$lgid       = 0;
			//$tier = -1;
			CereusReporting_logger( 'Adding AvailabilityThold ' . $title . ' -> ' . $command, 'debug', 'system' );
			$content = $pdf->nmidGetWorkerFileContent() . 'availability_thold' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			$pdf->nmidSetWorkerFileContent( $content );
		}
	}

	function printAvailabilityTholdSumGraph( $pdf, $reportId, $data, $startTime, $endTime, $tier )
	{
		global $phpBinary;

		// Get DB Instance
		$db = DBCxn::get();

		$sqlArray   = preg_split( "/;/", $data );
		$thold_data = $sqlArray[ 0 ];
		if ( readConfigOption( 'nmid_avail_addGraph' ) ) {
			$image_file = $pdf->nmidGetWorkerDir() . '/' . $thold_data . '_availabilityTholdTreeSumGraph.png';
			$command    = $phpBinary . " cereusReporting_TholdAvailabilityTreeSumChartCLI.php $thold_data $startTime $endTime > " . $image_file;
			$title      = 'Thold Availability Summary Report';
			$lgid       = 0;
			//$tier = -1;
			CereusReporting_logger( 'Adding AvailabilityTholdSummary ' . $title . ' -> ' . $command, 'debug', 'system' );
			$content = $pdf->nmidGetWorkerFileContent() . 'availability_thold_tree_sum' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			$pdf->nmidSetWorkerFileContent( $content );
		}
	}

	function printAvailabilityTreeSumGraph( $pdf, $reportId, $data, $startTime, $endTime, $tier )
	{
		global $phpBinary, $config;

		// Get DB Instance
		$db = DBCxn::get();

		$sqlArray = preg_split( "/;/", $data );
		$tree_id  = $sqlArray[ 0 ];
		$leaf_id  = $sqlArray[ 1 ];
		if ( readConfigOption( 'nmid_avail_addGraph' ) ) {
			$image_file = $pdf->nmidGetWorkerDir() . '/' . $tree_id . '_' . $leaf_id . '_availabilityTreeSumGraph.png';
			$command    = $phpBinary . " CereusReporting_AvailabilityTreeSumCLI.php $leaf_id $tree_id $startTime $endTime > " . $image_file;
			$title      = 'Tree Availability Summary Report';
			$lgid       = 0;
			//$tier = -1;
			CereusReporting_logger( 'Adding AvailabilityTreeSum ' . $title . ' -> ' . $command, 'debug', 'system' );
			$content = $pdf->nmidGetWorkerFileContent() . 'availability_tree_sum' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			$pdf->nmidSetWorkerFileContent( $content );
			// Download file and store to tmp dir:

			$secure_key      = sha1( $leaf_id . $tree_id . $startTime . $endTime . SECURE_URL_KEY );
			$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/CereusReporting_AvailabilityTreeSumCLI.php?key=' . $secure_key . '&leafId=' . $leaf_id . '&treeId=' . $tree_id . '&start=' . $startTime . '&end=' . $endTime;
			curl_download( $graph_image_url, $image_file );
		}
	}



	function printAvailabilityTable( $pdf, $reportId, $host_id, $startTime, $endTime, $tier )
	{
		// Get DB Instance
		$db = DBCxn::get();

        if ( readConfigOption( 'nmid_avail_addTable' ) ) {
            $modeTimeFrame              = array();
            $modeTimeFrame[ 'raw' ]     = readConfigOption( 'nmid_avail_PollMaxRawData' );
            $modeTimeFrame[ 'hourly' ]  = readConfigOption( 'nmid_avail_HourlyMaxRawData' );
            $modeTimeFrame[ 'daily' ]   = readConfigOption( 'nmid_avail_DailyMaxRawData' );
            $modeTimeFrame[ 'weekly' ]  = readConfigOption( 'nmid_avail_WeeklyMaxRawData' );
            $modeTimeFrame[ 'monthly' ] = readConfigOption( 'nmid_avail_MonthlyMaxRawData' );
            $modeTimeFrame[ 'yearly' ]  = readConfigOption( 'nmid_avail_YearlyMaxRawData' );
            $contentText                = "";
            $globalSLA                  = readConfigOption( 'nmid_avail_globalSla' );
            $slaValue                   = $globalSLA;
            $slaString                  = 'Global';
            $host_sla                   = db_fetch_cell( "select nmid_host_sla from host where id=$host_id" );
            if ( $host_sla > 0 ) {
                $slaValue  = $host_sla;
                $slaString = 'Host';
            }

            list ( $availability, $totalPolls, $failedPolls ) = get_device_availability( $host_id, $startTime, $endTime );

            if ( $pdf->nmidGetPdfType() > 0 ) {
                $contentText .= "<table autosize=\"0\" repeat_header=\"1\" width=100%><thead>";
                $contentText .= "	<tr>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Total Polls</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Failed Polls</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Availability</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>" . $slaString . " SLA</th>";
                $contentText .= "	</tr></thead>";
                $contentText .= "	<tbody><tr>";
                $contentText .= " 	<td align=center>" . number_format( $totalPolls ) . "</td>";
                $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $failedPolls ) . "</td>";
                $fontColor = '#007700';
                if ( $availability < $slaValue ) {
                    $fontColor = '#aa0000';
                }
                $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center><font color=" . $fontColor . "><b>" . number_format( $availability, 2 ) . "%</b></font></td>";
                $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $slaValue, 2 ) . "%</td>";
                $contentText .= "	</tr>";
                $contentText .= "</tbody></table>";
            }
            else {
                $contentText .= "Total Polls: " . number_format( $totalPolls ) . " | " .
                    "Failed Polls: " . number_format( $failedPolls ) . " | " .
                    "Availability: " . number_format( $availability, 2 ) . "% | " .
                    "Global SLA: " . number_format( $slaValue, 2 ) . "%";
            }
            $command    = '';
            $image_file = '';
            $lgid       = '';
            CereusReporting_logger( 'Adding AvailabilityChartTable ' . '', 'debug', 'system' );
            $content = $pdf->nmidGetWorkerFileContent() . 'text' . '@' . $command . '@' . $contentText . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
            $pdf->nmidSetWorkerFileContent( $content );
        }
	}

	function printAvailabilityCombinedTable( $pdf, $tree_id, $leaf_id, $startTime, $endTime, $tier, $slaTime_id )
	{
		// Get DB Instance
		$db = DBCxn::get();

        if ( readConfigOption( 'nmid_avail_addTable' ) ) {
            $modeTimeFrame              = array();
            $modeTimeFrame[ 'raw' ]     = readConfigOption( 'nmid_avail_PollMaxRawData' );
            $modeTimeFrame[ 'hourly' ]  = readConfigOption( 'nmid_avail_HourlyMaxRawData' );
            $modeTimeFrame[ 'daily' ]   = readConfigOption( 'nmid_avail_DailyMaxRawData' );
            $modeTimeFrame[ 'weekly' ]  = readConfigOption( 'nmid_avail_WeeklyMaxRawData' );
            $modeTimeFrame[ 'monthly' ] = readConfigOption( 'nmid_avail_MonthlyMaxRawData' );
            $modeTimeFrame[ 'yearly' ]  = readConfigOption( 'nmid_avail_YearlyMaxRawData' );
            $orderKey                   = getDBValue( 'order_key', 'select order_key from graph_tree_items where id=' . $leaf_id . ';' );
            $orderKey                   = preg_replace( "/0{3,3}/", "", $orderKey );
            $sql                        = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id=:graph_tree_id AND order_key like :order_key";
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':graph_tree_id', $tree_id );
            $stmt->bindValue( ':order_key', $orderKey.'%' );
            $stmt->execute();

            $contentText                = "";
            if ( $pdf->nmidGetPdfType() > 0 ) {
                $contentText .= "<table autosize=\"0\" repeat_header=\"1\" width=100%><thead>";
                $contentText .= "	<tr>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Device</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Total Polls</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Failed Polls</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Availability</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>SLA</th>";
                $contentText .= "	</tr></thead><tbody>";
            }
            while ( $hostRow = $stmt->fetch() ) {
                if ( $hostRow[ 'host_id' ] > 0 ) {
                    $host_id   = $hostRow[ 'host_id' ];
                    $globalSLA = readConfigOption( 'nmid_avail_globalSla' );
                    $slaValue  = $globalSLA;
                    $host_sla  = db_fetch_cell( "select nmid_host_sla from host where id=$host_id" );
                    if ( $host_sla > 0 ) {
                        $slaValue = $host_sla;
                    }

                    list ( $availability, $totalPolls, $failedPolls ) = get_device_availability( $host_id, $startTime, $endTime );

                    $hostDescription = getDBValue( 'description', 'select description from host where id=' . $host_id . ';' );
                    $hostIp          = getDBValue( 'hostname', 'select hostname from host where id=' . $host_id . ';' );

                    if ( $pdf->nmidGetPdfType() > 0 ) {
                        $contentText .= "	<tr>";
                        $contentText .= " 	<td align=left width=20%>$hostDescription</td>";
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $totalPolls ) . "</td>";
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $failedPolls ) . "</td>";
                        $fontColor = '#007700';
                        if ( $availability < $slaValue ) {
                            $fontColor = '#aa0000';
                        }
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center><font color=" . $fontColor . "><b>" . number_format( $availability, 2 ) . "%</b></font></td>";
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $slaValue, 2 ) . "%</td>";
                        $contentText .= "	</tr>";
                    }
                    else {
                        $contentText .= "Total Polls: " . number_format( $totalPolls ) . " | " .
                            "Failed Polls: " . number_format( $failedPolls ) . " | " .
                            "Availability: " . number_format( $availability, 2 ) . "% | " .
                            "Global SLA: " . number_format( $slaValue, 2 ) . "%";
                    }
                }
            }
            $stmt->closeCursor();
            if ( strlen( $contentText ) > 0 ) {
                if ( $pdf->nmidGetPdfType() > 0 ) {
                    $contentText .= "</tbody></table>";
                }
                $command    = '';
                $image_file = '';
                $lgid       = '';
                CereusReporting_logger( 'Adding AvailabilityCombinedTable ' . '', 'debug', 'system' );
                $content = $pdf->nmidGetWorkerFileContent() . 'text' . '@' . $command . '@' . $contentText . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
                $pdf->nmidSetWorkerFileContent( $content );
            }
        }
	}

	/*
	 * printDetailedAvailabilityCombinedTable
	 * Prints a detailed tabled containing the failed polls of a device
	 *
	 *
	 */
	function printDetailedAvailabilityCombinedTable( $pdf, $reportId, $tree_id, $leaf_id, $startTime, $endTime, $tier, $slaTime_id )
	{
		// Get DB Instance
		$db = DBCxn::get();

        $table_rows    = 0;
        $orderKey      = getPreparedDBValue('SELECT order_key FROM graph_tree_items WHERE id= ?', array($leaf_id) );
        $orderKey      = preg_replace( "/0{3,3}/", "", $orderKey ).'%';
        $contentText   = "";
        $hasReportData = FALSE;
        if ( $pdf->nmidGetPdfType() > 0 ) {
            $contentText .= "<h4>Failed Polls Table</h4>";
            $contentText .= "<table autosize=\"1\" repeat_header=\"1\" width=100%><thead>";
            $contentText .= "	<tr>";
            $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Date</th>";
            $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Device</th>";
            $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Failed Polls</th>";
            $contentText .= "	</tr></thead><tbody>";
        }

        $where_clause = CereusReporting_buildTimeStampQuery( $startTime, $endTime );

        $sql    = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id=:tree_id AND order_key like :order_Key;";
        $stmt = $db->prepare($sql);
        $stmt->setFetchMode( PDO::FETCH_ASSOC );
        $stmt->bindParam(':tree_id', $tree_id, PDO::PARAM_INT);
        $stmt->bindParam(':order_key', $orderKey , PDO::PARAM_STR);
        $stmt->execute();
        while ( $row = $stmt->fetch() ) {
            if ( $hostRow[ 'host_id' ] > 0 ) {
                $host_id = $hostRow[ 'host_id' ];

                $sql = "
                    SELECT
                        deviceId,
                        typeId,
                        timeStamp,
                        total_polls,
                        failed_polls
                    FROM
                        plugin_nmidCreatePDF_Availability_Table_" . $host_id . "
                    WHERE
                        $where_clause
                    AND
                        deviceId = " . $host_id . "
                    ORDER BY timeStamp";

                $rows = db_fetch_assoc( $sql );

                $hostDescription = getPreparedDBValue( 'select description from host where id= ?', array($host_id) );
                $hostIp          = getPreparedDBValue( 'select hostname from host where id= ?', array($host_id) );
                $skipData        = TRUE;
                $slaTimeFrame    = readConfigOption( 'nmid_avail_globalSlaTimeFrame' );
                if ( $slaTime_id > 0 ) {
                    $slaTimeFrame = $slaTime_id;
                }
                //`echo "$host_id - $hostDescription - $hostIp" >> /tmp/data.txt`;
                $s_timeframes_sql = "
                    SELECT
                      `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
                      `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
                      `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`
                    FROM
                      `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
                    WHERE
                      `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=:slaTimeFrame
                ";
                $stmt = $db->prepare( $s_timeframes_sql);
                $stmt->bindValue( ':slaTimeFrame', $slaTimeFrame );
                $stmt->execute();
                //$tfRows = db_fetch_assoc( $s_timeframes_sql );
                foreach ( $rows as $row ) {
                    if ( $row[ 'deviceId' ] == $host_id ) {
                        $totalPolls        = $row[ 'total_polls' ];
                        $failedPolls       = $row[ 'failed_polls' ];
                        $dayString         = date( "D", $row[ 'timeStamp' ] );
                        $noSLA_TotalPolls  = 0;
                        $noSLA_FailedPolls = 0;
                        $endTimeSpan       = readConfigOption( 'poller_interval' );
                        if ( $row[ 'typeId' ] == 'p' ) {
                            $endTimeSpan = readConfigOption( 'poller_interval' );
                        }
                        elseif ( $row[ 'typeId' ] == 'y' ) {
                            $endTimeSpan = 3600 * 24 * 356;
                        }
                        elseif ( $row[ 'typeId' ] == 'm' ) {
                            $endTimeSpan = 3600 * 24 * 30;
                        }
                        elseif ( $row[ 'typeId' ] == 'w' ) {
                            $endTimeSpan = 3600 * 24 * 7;
                        }
                        elseif ( $row[ 'typeId' ] == 'd' ) {
                            $endTimeSpan = 3600 * 24;
                        }
                        elseif ( $row[ 'typeId' ] == 'h' ) {
                            $endTimeSpan = 3600;
                        }

                        while ( $tfRow = $stmt->fetch() ) {
                            if ( preg_match( "/$dayString/i", $tfRow[ 'defaultDays' ] ) ) {
                                $a_defaultStartTimeItemsList = preg_split( "/,/", $tfRow[ 'defaultStartTime' ] );
                                $a_defaultEndTimeItemsList   = preg_split( "/,/", $tfRow[ 'defaultEndTime' ] );
                                for ( $listCount = 0; $listCount < sizeof( $a_defaultStartTimeItemsList );
                                      $listCount++ ) {
                                    $a_defaultStartTimeItems = preg_split( "/:/", $a_defaultStartTimeItemsList[ $listCount ] );
                                    $s_defaultStartTime      = mktime( $a_defaultStartTimeItems[ 0 ], $a_defaultStartTimeItems[ 1 ], 0, date( "m", $row[ 'timeStamp' ] ), date( "j", $row[ 'timeStamp' ] ), date( "Y", $row[ 'timeStamp' ] ) );
                                    $a_defaultEndTimeItems   = preg_split( "/:/", $a_defaultEndTimeItemsList[ $listCount ] );
                                    $s_defaultEndTime        = mktime( $a_defaultEndTimeItems[ 0 ], $a_defaultEndTimeItems[ 1 ], 0, date( "m", $row[ 'timeStamp' ] ), date( "j", $row[ 'timeStamp' ] ), date( "Y", $row[ 'timeStamp' ] ) );
                                    if ( ( $row[ 'timeStamp' ] >= $s_defaultStartTime ) AND ( $row[ 'timeStamp' ] <= $s_defaultEndTime ) ) {
                                        $skipData = FALSE;
                                    }
                                }
                            }
                        }

                        if ( $skipData == TRUE ) {
                            $noSLA_TotalPolls  = $noSLA_TotalPolls + $row[ 'total_polls' ];
                            $noSLA_FailedPolls = $noSLA_FailedPolls + $row[ 'failed_polls' ];
                        }

                        $totalPolls  = $totalPolls - $noSLA_TotalPolls;
                        $failedPolls = $failedPolls - $noSLA_FailedPolls;

                        if ( $totalPolls < 0 ) {
                            $totalPolls = 0;
                        }
                        if ( $failedPolls < 0 ) {
                            $failedPolls = 0;
                        }

                        if ( $failedPolls > 0 ) {
                            $hasReportData = TRUE;
                            $dateFormat = readConfigOption( 'nmid_pdf_dateformat' );
                            if ( $pdf->nmidGetPdfType() > 0 ) {
                                $contentText .= "	<tr>";
                                $contentText .= "   <td align=left>" . date( $dateFormat, $row[ 'timeStamp' ] ) . " - " . date( $dateFormat, $row[ 'timeStamp' ] + $endTimeSpan ) . "</td>";
                                $contentText .= " 	<td style='border-left: 1px solid #000000;' align=left width=20%>$hostDescription</td>";
                                $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $failedPolls ) . "</td>";
                                $contentText .= "	</tr>";
                            }
                            else {
                                $contentText .= "Date: " . date( $dateFormat, $row[ 'timeStamp' ] ) . " - " . date( $dateFormat, $row[ 'timeStamp' ] + $endTimeSpan ) . " | " .
                                    "Host: " . $hostDescription . " | " .
                                    "Failed Polls: " . number_format( $failedPolls, 2 ) . "%";
                            }
                        }
                    }
                }
                $stmt->closeCursor();
            }
        }
        $stmt->closeCursor();
        if ( strlen( $contentText ) > 0 ) {
            if ( $hasReportData == FALSE ) {
                $contentText .= '<tr><td colspan=3 align=center>No Failed polls during this time period.</td></tr>';
            }
            if ( $pdf->nmidGetPdfType() > 0 ) {
                $contentText .= "</tbody></table>";
            }
            $command    = '';
            $image_file = '';
            $lgid       = '';
            $content    = $pdf->nmidGetWorkerFileContent() . 'text' . '@' . $command . '@' . $contentText . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
            $pdf->nmidSetWorkerFileContent( $content );
        }
	}

	function printDetailedAvailabilityCombinedTreeTable( $pdf, $reportId, $tree_id, $startTime, $endTime, $tier, $slaTime_id )
	{
		// Get DB Instance
		$db = DBCxn::get();

        $table_rows    = 0;
        $hasReportData = FALSE;

        $contentText = "";
        if ( $pdf->nmidGetPdfType() > 0 ) {
            $contentText .= "<h4>Failed Polls Table</h4>";
            $contentText .= "[nrnrnr]<table autosize=\"1\" repeat_header=\"1\" width=100%><thead>";
            $contentText .= "	<tr>";
            $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Date</th>";
            $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Device</th>";
            $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Failed Polls</th>";
            $contentText .= "	</tr></thead><tbody>";
        }

        $where_clause = "timeStamp BETWEEN " . $startTime . " AND " . $endTime;

        $sql    = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id=:tree_id";
        $stmt = $db->prepare( $sql);
        $stmt->bindValue( ':tree_id', $tree_id );
        $stmt->execute();
        while ( $hostRow = $stmt->fetch() ) {
            if ( $hostRow[ 'host_id' ] > 0 ) {
                $host_id = $hostRow[ 'host_id' ];

                $hostDescription = getDBValue( 'description', 'select description from host where id=' . $host_id . ';' );
                $hostIp          = getDBValue( 'hostname', 'select hostname from host where id=' . $host_id . ';' );
                $skipData        = TRUE;
                $slaTimeFrame    = readConfigOption( 'nmid_avail_globalSlaTimeFrame' );
                if ( $slaTime_id > 0 ) {
                    $slaTimeFrame = $slaTime_id;
                }
                $s_timeframes_sql = "
                       SELECT
                         `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
                         `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
                         `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`
                       FROM
                         `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
                       WHERE
                         `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=" . $slaTimeFrame . "
                   ";
                $tfRows           = db_fetch_assoc( $s_timeframes_sql );
                $rows             = db_fetch_assoc( "
                    SELECT
                        deviceId,
                        timeStamp,
                        total_polls,
                        failed_polls
                    FROM
                        plugin_nmidCreatePDF_Availability_Table_" . $host_id . "
                    WHERE
                        $where_clause
                    ORDER BY timeStamp" );
                foreach ( $rows as $row ) {
                    if ( $row[ 'deviceId' ] == $host_id ) {
                        $totalPolls        = $row[ 'total_polls' ];
                        $failedPolls       = $row[ 'failed_polls' ];
                        $dayString         = date( "D", $row[ 'timeStamp' ] );
                        $noSLA_TotalPolls  = 0;
                        $noSLA_FailedPolls = 0;
                        foreach ( $tfRows as $tfRow ) {
                            if ( preg_match( "/$dayString/i", $tfRow[ 'defaultDays' ] ) ) {
                                $a_defaultStartTimeItemsList = preg_split( "/,/", $tfRow[ 'defaultStartTime' ] );
                                $a_defaultEndTimeItemsList   = preg_split( "/,/", $tfRow[ 'defaultEndTime' ] );
                                for ( $listCount = 0; $listCount < sizeof( $a_defaultStartTimeItemsList );
                                      $listCount++ ) {
                                    $a_defaultStartTimeItems = preg_split( "/:/", $a_defaultStartTimeItemsList[ $listCount ] );
                                    $s_defaultStartTime      = mktime( $a_defaultStartTimeItems[ 0 ], $a_defaultStartTimeItems[ 1 ], 0, date( "m", $row[ 'timeStamp' ] ), date( "d", $row[ 'timeStamp' ] ), date( "Y", $row[ 'timeStamp' ] ) );
                                    $a_defaultEndTimeItems   = preg_split( "/:/", $a_defaultEndTimeItemsList[ $listCount ] );
                                    $s_defaultEndTime        = mktime( $a_defaultEndTimeItems[ 0 ], $a_defaultEndTimeItems[ 1 ], 0, date( "m", $row[ 'timeStamp' ] ), date( "d", $row[ 'timeStamp' ] ), date( "Y", $row[ 'timeStamp' ] ) );
                                    if ( $row[ 'timeStamp' ] > $s_defaultStartTime - 1 ) {
                                        $skipData = FALSE;
                                    }
                                    if ( $row[ 'timeStamp' ] + 1 > $s_defaultEndTime ) {
                                        $skipData = TRUE;
                                    }
                                }
                            }
                        }
                        if ( $skipData == TRUE ) {
                            $noSLA_TotalPolls  = $noSLA_TotalPolls + $row[ 'total_polls' ];
                            $noSLA_FailedPolls = $noSLA_FailedPolls + $row[ 'failed_polls' ];
                        }

                        $totalPolls  = $totalPolls - $noSLA_TotalPolls;
                        $failedPolls = $failedPolls - $noSLA_FailedPolls;

                        if ( $totalPolls < 0 ) {
                            $totalPolls = 0;
                        }
                        if ( $failedPolls < 0 ) {
                            $failedPolls = 0;
                        }

                        if ( $failedPolls > 0 ) {
                            $hasReportData = TRUE;
                            if ( $pdf->nmidGetPdfType() > 0 ) {
                                $dateFormat = readConfigOption( 'nmid_pdf_dateformat' );
                                $contentText .= "	<tr>";
                                $contentText .= "   <td align=left>" . date( $dateFormat, $row[ 'timeStamp' ] ) . "</td>";
                                $contentText .= " 	<td style='border-left: 1px solid #000000;' align=left width=20%>$hostDescription</td>";
                                $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $failedPolls ) . "</td>";
                                $contentText .= "	</tr>";
                            }
                            else {
                                $contentText .= "Date: " . date( $dateFormat, $row[ 'timeStamp' ] ) . " | " .
                                    "Host: " . $hostDescription . " | " .
                                    "Failed Polls: " . number_format( $failedPolls, 2 ) . "%";
                            }
                        }
                    }
                }
            }
        }
        $stmt->closeCursor();
        if ( strlen( $contentText ) > 0 ) {
            if ( $hasReportData == FALSE ) {
                $contentText .= '<tr><td colspan=3 align=center>No Failed polls during this time period.</td></tr>';
            }
            if ( $pdf->nmidGetPdfType() > 0 ) {
                $contentText .= "</tbody></table>";
            }
            $command    = '';
            $image_file = '';
            $lgid       = '';
            $content    = $pdf->nmidGetWorkerFileContent() . 'text' . '@' . $command . '@' . $contentText . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
            $pdf->nmidSetWorkerFileContent( $content );
        }
	}

	function printAvailabilityCombinedTreeTable( $pdf, $reportId, $tree_id, $startTime, $endTime, $tier, $slaTime_id )
	{

		// Get DB Instance
		$db = DBCxn::get();

        if ( readConfigOption( 'nmid_avail_addTable' ) ) {
            $contentText = "";
            if ( $pdf->nmidGetPdfType() > 0 ) {
                $contentText .= "<table autosize=\"0\" repeat_header=\"1\" width=100%><thead>";
                $contentText .= "	<tr>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Device</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Total Polls</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Failed Polls</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>Availability</th>";
                $contentText .= " 	<th style='border-bottom: 2px solid #000000;'>SLA</th>";
                $contentText .= "	</tr></thead><tbody>";
            }
            $sql    = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id=:tree_id";
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':tree_id', $tree_id );
            $stmt->execute();
            while ( $hostRow = $stmt->fetch() ) {
                if ( $hostRow[ 'host_id' ] > 0 ) {
                    $host_id   = $hostRow[ 'host_id' ];
                    $globalSLA = readConfigOption( 'nmid_avail_globalSla' );
                    $slaValue  = $globalSLA;
                    $host_sla  = db_fetch_cell( "select nmid_host_sla from host where id=$host_id" );
                    if ( $host_sla > 0 ) {
                        $slaValue = $host_sla;
                    }
                    $hostDescription = getDBValue( 'description', 'select description from host where id=' . $host_id . ';' );
                    $hostIp          = getDBValue( 'hostname', 'select hostname from host where id=' . $host_id . ';' );

                    list ( $availability, $totalPolls, $failedPolls ) = get_device_availability( $host_id, $startTime, $endTime );

                    if ( $pdf->nmidGetPdfType() > 0 ) {
                        $contentText .= "	<tr>";
                        $contentText .= " 	<td align=left>$hostDescription</td>";
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $totalPolls ) . "</td>";
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $failedPolls ) . "</td>";
                        $fontColor = '#007700';
                        if ( $availability < $slaValue ) {
                            $fontColor = '#aa0000';
                        }
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center><font color=" . $fontColor . "><b>" . number_format( $availability, 2 ) . "%</b></font></td>";
                        $contentText .= " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $slaValue, 2 ) . "%</td>";
                        $contentText .= "	</tr>";
                    }
                    else {
                        $contentText .= "Total Polls: " . number_format( $totalPolls ) . " | " .
                            "Failed Polls: " . number_format( $failedPolls ) . " | " .
                            "Availability: " . number_format( $availability, 2 ) . "% | " .
                            "Global SLA: " . number_format( $slaValue, 2 ) . "%";
                    }
                }
            }
            $stmt->closeCursor();
            if ( strlen( $contentText ) > 0 ) {
                if ( $pdf->nmidGetPdfType() > 0 ) {
                    $contentText .= "</tbody></table>";
                }
                $command    = '';
                $image_file = '';
                $lgid       = '';
                $content    = $pdf->nmidGetWorkerFileContent() . 'text' . '@' . $command . '@' . $contentText . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
                $pdf->nmidSetWorkerFileContent( $content );
            }
        }
	}


// Availability Report Generation - Multi Report
	function printAvailabilityGraph( $pdf, $reportId, $host_id, $startTime, $endTime, $tier )
	{
		// Get DB Instance
		$db = DBCxn::get();

		global $phpBinary, $config;

		if ( readConfigOption( 'nmid_avail_addGraph' ) ) {
			$image_file      = $pdf->nmidGetWorkerDir() . '/' . $host_id . '-' . $reportId . '_availability.png';
			$command         = $phpBinary . " CereusReporting_AvailabilityChartCLI.php $host_id $startTime $endTime > " . $image_file;
			$hostDescription = getDBValue( 'description', 'select description from host where id=' . $host_id . ';' );
			$title           = $hostDescription . ' - Availability Report';
			$lgid            = 0;
			//$tier = -1;
			CereusReporting_logger( 'Adding AvailabilityChart ' . $title . ' -> ' . $command, 'debug', 'system' );
			$content = $pdf->nmidGetWorkerFileContent() . 'availability' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			$pdf->nmidSetWorkerFileContent( $content );

			// Download file and store to tmp dir:
			$secure_key      = sha1( $host_id . $startTime . $endTime . SECURE_URL_KEY );
			$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/CereusReporting_AvailabilityChartCLI.php?key=' . $secure_key . '&deviceId=' . $host_id . '&start=' . $startTime . '&end=' . $endTime;
			CereusReporting_logger( 'Adding availability_combined ' . '[' . $graph_image_url . ']', 'debug', 'availability' );
			curl_download( $graph_image_url, $image_file );
		}
		printAvailabilityTable( $pdf, $reportId, $host_id, $startTime, $endTime, $tier );
	}
