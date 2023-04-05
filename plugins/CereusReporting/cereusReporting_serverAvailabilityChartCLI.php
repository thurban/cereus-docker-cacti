<?php
	/*******************************************************************************
	 *
	 * File:         $Id: cereusReporting_serverAvailabilityChartCLI.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
	 * Modified_On:  $Date: 2018/11/11 17:22:55 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/


	chdir( __DIR__ );
	require_once( __DIR__.'/include/phpchartdir.php' );
	require_once( __DIR__.'/CereusReporting_ChartDirector.php' );
	include_once( __DIR__.'/include/functions_compat.php' );
	require_once( __DIR__.'/functions.php' ); // Support functions
	include_once( '../../include/global.php' );
	include_once( '../../include/config.php' );

	if ( function_exists('top_header')) {
		include_once( __DIR__.'/include/functions_cacti_1.0.0.php' );
	} else {
		include_once( __DIR__.'/include/functions_cacti_0.8.php' );
	}

	$startTime  = -1;
	$endTime    = -1;
	$slaTime_id = 0;
	$chartMode  = 'past';
	$hoursStep  = -1;
	$leafid     = -1;
	$tree_id    = 1;
	$isSorted   = FALSE;
	$sortedChar = '1';
	$chartTitle = 'Availability Report';
	$sortedFrom = 0;
	$sortedTo   = '';
	$sortType   = 1;

	if ( !isset( $_SERVER[ "argv" ][ 0 ] ) || isset( $_SERVER[ 'REQUEST_METHOD' ] ) || isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
		// Web Browser
		CereusReporting_logger( 'We are in WebBrowser mode', 'debug', 'availability_server_cli' );
		$leafid     = filter_input( INPUT_GET, 'leafId', FILTER_SANITIZE_NUMBER_INT );
		$tree_id    = filter_input( INPUT_GET, 'treeId', FILTER_SANITIZE_NUMBER_INT );
		$slaTime_id = filter_input( INPUT_GET, 'slaTimeId', FILTER_SANITIZE_NUMBER_INT );
		$isSorted   = filter_input( INPUT_GET, 'isSorted', FILTER_SANITIZE_NUMBER_INT );
		if ( $isSorted ) {
			$sortedFrom = filter_input( INPUT_GET, 'sortedFrom', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
			$sortedTo   = filter_input( INPUT_GET, 'sortedTo', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		$startTime  = filter_input( INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT );
		$endTime    = filter_input( INPUT_GET, 'end', FILTER_SANITIZE_NUMBER_INT );
		$secure_key = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		header( 'Content-Type: image/png' );

		// echo "Key: [$secure_key]<br>";
		if ( $secure_key == sha1( $leafid . $tree_id . $slaTime_id . $startTime . $endTime . SECURE_URL_KEY ) ) {
			// Great. proceed.
			CereusReporting_logger( 'Security passed. Proceeding with chart creation', 'debug', 'availability_server_cli' );
		}
		else {
			CereusReporting_logger( 'Security failed. Stopping chart creation', 'debug', 'availability_server_cli' );
			die( "<br><strong>You are not allowed to call this script via the web-browser.</strong>" );
		}
	}
	else {
		CereusReporting_logger( 'We are in CLI mode', 'debug', 'availability_server_cli' );
		$parms = $_SERVER[ 'argv' ];
		array_shift( $parms );
		$leafid     = $parms[ 0 ];
		$tree_id    = $parms[ 1 ];
		$slaTime_id = $parms[ 2 ];
		$startTime  = $parms[ 3 ];
		$endTime    = $parms[ 4 ];
		$isSorted   = 0;
		$sortedFrom = 0;
		if ( isset( $parms[ 5 ] ) ) {
			$isSorted = $parms[ 5 ];
		}
		if ( isset( $parms[ 6 ] ) ) {
			$sortedFrom = $parms[ 6 ];
		}


		if ( isset( $parms[ 7 ] ) ) {
			$sortedTo = $parms[ 7 ];
		}
	}
	if ( $isSorted == 1 ) {
		CereusReporting_logger( 'Device Count based sorting enabled', 'debug', 'availability_server_cli' );
		//$isSorted   = TRUE;
		$chartTitle = $chartTitle . ' ( ' . $sortedFrom . ' to ' . ( $sortedFrom + $sortedTo - 1 ) . ' )';
		if ( preg_match( "/^([0-9]+)$/", $sortedChar, $matches ) ) {
			$sortedChar = $matches[ 1 ];
		}
	}
	elseif ( $isSorted == 2 ) {
		CereusReporting_logger( 'Device Name based sorting enabled', 'debug', 'availability_server_cli' );
		//$isSorted   = TRUE;
		$sortType   = 2;
		$sortedChar = $sortedFrom;
		$chartTitle = $chartTitle . ' ( ' . strtoupper( $sortedChar ) . ' )';
		if ( preg_match( "/^(.)$/", $sortedChar, $matches ) ) {
			$sortedChar = $matches[ 1 ];
		}
	}


	CereusReporting_logger( 'Retrieving default variables', 'debug', 'availability_server_cli' );

	$globalSLA    = readConfigOption( 'nmid_avail_globalSla' );
	$hostSLA      = 0;
	$startTag     = readConfigOption( 'nmid_avail_startTag' );
	$endTag       = readConfigOption( 'nmid_avail_endTag' );
	$chartWidth   = readConfigOption( 'nmid_avail_chartWidth' );
	$chartHeight  = readConfigOption( 'nmid_avail_chartHeight' );
	$slaTimeFrame = readConfigOption( 'nmid_avail_globalSlaTimeFrame' );
	if ( $slaTime_id > 0 ) {
		$slaTimeFrame = $slaTime_id;
	}
	$nmid_avail_offSLATransparent = readConfigOption( 'nmid_avail_offSLATransparent' );
	$modeTimeFrame                = array();
	$modeTimeFrame[ 'raw' ]       = readConfigOption( 'nmid_avail_PollMaxRawData' );
	$modeTimeFrame[ 'hourly' ]    = readConfigOption( 'nmid_avail_HourlyMaxRawData' );
	$modeTimeFrame[ 'daily' ]     = readConfigOption( 'nmid_avail_DailyMaxRawData' );
	$modeTimeFrame[ 'weekly' ]    = readConfigOption( 'nmid_avail_WeeklyMaxRawData' );
	$modeTimeFrame[ 'monthly' ]   = readConfigOption( 'nmid_avail_MonthlyMaxRawData' );
	$modeTimeFrame[ 'yearly' ]    = readConfigOption( 'nmid_avail_YearlyMaxRawData' );
	$serverCount                  = 0;
	//$addDataTable = readConfigOption('nmid_avail_addTable');


	// aggregation is not implemented yet
	$endTimeSpan    = readConfigOption( 'poller_interval' );
	$nameColumnSize = readConfigOption( 'nmid_cr_design_availchart_name_size' );

	if ( readConfigOption( 'nmid_avail_useRRDstyle' ) ) {
		if ( ( $endTime - $startTime ) > $modeTimeFrame[ 'raw' ] ) {
			$endTimeSpan = 3600;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'hourly' ] * 3600 ) ) {
			$endTimeSpan = 3600 * 24;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'daily' ] * 24 * 3600 * 7 ) ) {
			$endTimeSpan = 3600 * 24 * 7;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'weekly' ] * 24 * 3600 * 30 ) ) {
			$endTimeSpan = 3600 * 24 * 30;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'monthly' ] * 24 * 3600 * 356 ) ) {
			$endTimeSpan = 3600 * 24 * 356;
		}
	}

	$time = time() - 3600 * $hoursStep;

	CereusReporting_logger( 'Connecting to database', 'debug', 'availability_server_cli' );
	// Get DB Instance
	$db   = DBCxn::get();

	CereusReporting_logger( 'Initializing variables', 'debug', 'availability_server_cli' );
	$labels          = array();
	$taskNo          = array();
	$startDate       = array();
	$endDate         = array();
	$startDateInSLA  = array();
	$endDateInSLA    = array();
	$startDateOutSLA = array();
	$endDateOutSLA   = array();
	$inTaskNo        = array();
	$outTaskNo       = array();

	$startDateException   = array();
	$endDateException     = array();
	$taskException        = array();
	$startDateNoException = array();
	$endDateNoException   = array();
	$taskNoException      = array();

	$deviceToTask = array();

	$colors = array();

	if ( $leafid > 0 ) {
		CereusReporting_logger( 'Entering Leaf mode Chart Generation', 'debug', 'availability_server_cli' );
		addLeafToChart( $leafid, $tree_id );
	}
	else {
		CereusReporting_logger( 'Entering Tree mode Chart Generation', 'debug', 'availability_server_cli' );
		addTreeToChart( $tree_id );
	}

	CereusReporting_logger( 'Preparing actual Chart', 'debug', 'availability_server_cli' );

	$chartHeight = ( 60 + 105 + 30 * $serverCount );

	# Create a XYChart object of size 620 x 325 pixels. Set background color to light red
	# (0xffcccc), with 1 pixel 3D border effect.
	# Create a XYChart object of size 400 x 270 pixels
	if ( $chartHeight < 10 ) {
		$chartHeight = 270;
	}
	if ( $chartWidth < 10 ) {
		$chartWidth = 800;
	}


	$c = new XYChart( $chartWidth, $chartHeight );

	$inSLAColor  = $c->patternColor( array( 0xccffffff, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff,
		                                 0xccff0000, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff,
		                                 0xccff0000, 0xccffffff, 0xccffffff, 0xccffffff ), 4 );
	$outSLAColor = $c->patternColor( array( 0xccffffff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xccffffff, 0xccffffff,
		                                 0xcc0000ff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xccffffff, 0xccffffff,
		                                 0xcc0000ff, 0xffffff, 0xccffffff, 0xccffffff ), 4 );
	$inSLATask   = $c->patternColor( array( 0xffffff, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000,
		                                 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff,
		                                 0xffffff ), 4 );
	$outSLATask  = $c->patternColor( array( 0xffffff, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0x00ff00,
		                                 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff,
		                                 0xffffff ), 4 );


	# Add a title to the chart using 15 points Times Bold Itatic font, with white
	# (ffffff) text on a dark red (800000) background
	$textBoxObj = $c->addTitle( $chartTitle, readConfigOption( 'nmid_avail_font' ), 15 );

	# Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative
	# white/grey background. Enable both horizontal and vertical grids by setting their
	# colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2
	# pixels in width
	$plotAreaBgColor = $c->linearGradientColor( 0, 0, 0, $c->getHeight() - 40, 0xaaccff,
	                                            0xf9fcff );
	$c->setPlotArea( $nameColumnSize, 60, $c->getWidth() - ( $nameColumnSize + 40 ), $c->getHeight() - 160, $plotAreaBgColor,
	                 -1, -1, 0xffffff );

	# swap the x and y axes to create a horziontal box-whisker chart
	$c->swapXY();

	# Set the x and y axis stems to transparent and the label font to 12pt Arial
	$c->xAxis->setColors(Transparent);
	$c->yAxis->setColors(Transparent);
	$c->xAxis->setLabelStyle(readConfigOption( 'nmid_avail_font' ), 8);
	$c->yAxis->setLabelStyle(readConfigOption( 'nmid_avail_font' ), 8);


	$markLayer = $c->addBoxWhiskerLayer(null, null, null, null, $markData, -1, 0xff0000);
	$markLayer->setLineWidth(2);
	$markLayer->setDataGap(0.1);
	//$markLayer->setDataLabelStyle(readConfigOption( 'nmid_avail_font' ), 8);



	# Set the y-axis to shown on the top (right + swapXY = top)
	$c->setYAxisOnRight();

	# Set the labels on the x axis
	$c->xAxis->setLabels( $labels );

	# Reverse the x-axis scale so that it points downwards.
	$c->xAxis->setReverse();

	//y-axis range 0 - 100 with a label every 20 units
	$c->yAxis->setLinearScale(0, 100, 20);

	//lower limit is automatically determined, and upper limit should use be close to myUpperLimit
	//$c.yAxis().setLinearScale(Chart.NoValue, myUpperLimit);

	if ( $leafid > 0 ) {
		CereusReporting_logger( 'Retrieving Leaf Exceptions', 'debug', 'availability_server_cli' );
		addLeafExceptionsToChart( $leafid, $tree_id, $c );
	}
	else {
		CereusReporting_logger( 'Retrieving Tree Exceptions', 'debug', 'availability_server_cli' );
		addTreeExceptionsToChart( $tree_id, $c );
	}


	# Add a multi-color box-whisker layer to represent the gantt bars
    // $layer = $c->addBoxWhiskerLayer2( $startDate, $endDate, NULL, NULL, NULL, $colors );
    // $layer->setXData( $taskNo );
    // $layer->setBorderColor( SameAsMainColor );
    // $layer->setDataWidth( 20 );

	# Add a blue (0x6699bb) bar chart layer using the given data
	$layer = $c->addBarLayer3($taskNo, $colors);
    $layer->setDataLabelStyle(readConfigOption( 'nmid_avail_font' ), 8);

# Use bar gradient lighting with the light intensity from 0.8 to 1.3
	$layer->setBorderColor(Transparent, barLighting(0.8, 1.3));

# Set rounded corners for bars
	$layer->setRoundedCorners();

# Display labela on top of bars using 12pt Arial font
	//$layer->setAggregateLabelStyle("Arial", 12);

    // if ( $outageExists ) {
		$legendBox = $c->addLegend( 180, $c->getHeight() - 80, AutoGrid, readConfigOption( 'nmid_avail_font' ), 8 );
    //}

	$legendBox->setAlignment(TopRight);
	//$legendBox->setBackground(0x80808080, -1, 2);
	//$legendBox = $c->addLegend($c->getWidth() / 2, $c->getHeight() - 15, false, "arial.ttf", 8);
	//$legendBox->setAlignment(BottomCenter);

	# Set the legend box background and border to pale blue (e8f0f8) and bluish grey (445566)
	$legendBox->setBackground(0xe8f0f8, 0x445566);

	# Use rounded corners of 5 pixel radius for the legend box
	$legendBox->setRoundedCorners(5);

	# Use line style legend key
	$legendBox->setLineStyleKey();

	# Add the legend key for the mark line
	$legendBox->addKey("Target SLA", 0xff0000, 2);

	# Output the chart
	//header( "Content-type: image/png" );
	CereusReporting_logger( 'Create Final Chart with ['.count($taskNo).'] tasks and ['.count($labels).'] labels to display', 'debug', 'availability_server_cli' );
	print( $c->makeChart2( PNG ) );

	function addTreeToChart( $tree_id )
	{
	    // Get all items for this tree
	    $a_item_array  = cr_get_hosts( $tree_id );
		$hostAdded = array();

	    foreach ( $a_item_array as $row ) {
			if ( $row[ 'host_id' ] > 0 ) {
				if ( array_key_exists( $row[ 'host_id' ], $hostAdded ) == FALSE ) {
					$hostAdded[ $row[ 'host_id' ] ] = TRUE;
					addServerToChart( $row[ 'host_id' ] );
				}
			}
		}
	}

	function addLeafToChart( $leafid, $tree_id )
	{
        global $isSorted, $sortedFrom, $sortedTo, $db;
	    CereusReporting_logger( 'Adding leaf with id : ['.$leafid.']', "debug", "availability_server" );
	    $devices = cr_get_leaf_items($tree_id, $leafid, $isSorted, $sortedFrom, $sortedTo);
	    foreach ( $devices as $row ) {
			if ( $row[ 'host_id' ] > 0 ) {
	            CereusReporting_logger( 'Adding host with id : ['.$row[ 'host_id' ].']', "debug", "availability_server" );
				addServerToChart( $row[ 'host_id' ] );
			}
		}
	}

	function addTreeExceptionsToChart( $tree_id, $chart )
	{
        global $isSorted, $sortedFrom, $sortedTo, $db;

		$sql = "SELECT host_id FROM graph_tree_items WHERE graph_tree_id=?;";
	    $params = array($tree_id);
		if ( $isSorted ) {
	        $sql = "SELECT host_id FROM graph_tree_items WHERE graph_tree_id=? LIMIT ?,?;";
	        $params = array($tree_id,$sortedFrom,$sortedTo);
			}
		$hostAdded = array();
		$stmt = $db->prepare($sql);
	    $stmt->setFetchMode( PDO::FETCH_ASSOC );
	    $stmt->execute($params);
	    while ( $row = $stmt->fetch() ) {
			if ( $row[ 'host_id' ] > 0 ) {
				if ( array_key_exists( $row[ 'host_id' ], $hostAdded ) == FALSE ) {
					$hostAdded[ $row[ 'host_id' ] ] = TRUE;
					checkAvailabilityExceptions( $row[ 'host_id' ], $chart );
				}
			}
		}
		$stmt->closeCursor();
	}

	function addLeafExceptionsToChart( $leafid, $tree_id, $chart )
	{
    	global $db;

		$orderKey = getPreparedDBValue( 'SELECT order_key FROM graph_tree_items WHERE id=?;',array($leafid) );
		$orderKey = preg_replace( "/0{3,3}/", "", $orderKey );
        $hostId   = getPreparedDBValue( 'SELECT host_id FROM graph_tree_items WHERE id=?;',array($leafid) );
        $sql      = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE graph_tree_id=? AND order_key like ?;";
	    $params = array($tree_id,$orderKey.'%');

		if ( $hostId > 0 ) {
            $sql = "select host_id,local_graph_id,rra_id from graph_tree_items where id=?;";
	        $params = array($leafid);

		}
		$stmt = $db->prepare($sql);
	    $stmt->setFetchMode( PDO::FETCH_ASSOC );
	    $stmt->execute($params);
	    while ( $row = $stmt->fetch() ) {
			if ( $row[ 'host_id' ] > 0 ) {
				checkAvailabilityExceptions( $row[ 'host_id' ], $chart );
			}
		}
		$stmt->closeCursor();
	}

	function addServerToChart( $deviceId )
	{
        global $dataMode, $labels, $taskNo, $startDate, $endDate, $colors, $serverCount, $startTime, $endTime, $globalSLA,
		       $startDateInSLA, $startDateOutSLA, $endDateInSLA, $endDateOutSLA, $outSLAColorArray, $inSLAColorArray,
               $outTaskNo, $inTaskNo, $endTimeSpan, $deviceToTask, $slaTime_id, $modeTimeFrame, $outageExists,
               $markData, $db;

	    /* take time and log performance data */
	    list( $micro, $seconds ) = explode( " ", microtime() );
	    $start = $seconds + $micro;

        $total_polls =  CereusReporting_getTotalPollsFromDevice( $deviceId, $startTime, $endTime );
	    $failed_polls = CereusReporting_getFailedPollsFromDevice( $deviceId, $startTime, $endTime );
	    $endTimeSpan = readConfigOption( 'poller_interval' );
	    CereusReporting_logger( 'Device Polls: ['.$total_polls.']', "debug", "availability_server" );
	    CereusReporting_logger( 'Device Failed Polls: ['.$failed_polls.']', "debug", "availability_server" );

		$slaTimeFrame = CereusReporting_getSlaTimeFrame( $deviceId );

		$rowCount          = 0;
		$totalData         = 0;
		$noSLA_TotalPolls  = 0;
		$noSLA_FailedPolls = 0;
		$totalPolls        = 0;
		$failedPolls       = 0;
		$endTimeSpan       = 0;
		$currentStartTime  = $startTime;


	    $where_clause = CereusReporting_buildTimeStampQuery( $startTime -1, $endTime+1 );
	    $device_failed_polls_sql = "
            SELECT
                timeStamp,
                failed_polls
            FROM
                plugin_nmidCreatePDF_AvailabilityFailedPolls_Table
            WHERE
               $where_clause
               AND
                deviceId = " . $deviceId . " ORDER BY timeStamp;"
	    ;

	    $sth = $db->prepare( $device_failed_polls_sql );
        $device_failed_polls_sql = preg_replace('/\n/',' ',$device_failed_polls_sql);
	    CereusReporting_logger( 'Retrieving data: ['.$device_failed_polls_sql.']', "debug", "availability_server" );
	    $sth->execute();

	    // Check for SLA TimeFrame Items
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

	    $sth_sla = $db->prepare( $s_timeframes_sql );
	    $sth_sla->execute();
	    $tfRows_items = $sth_sla->fetchAll(PDO::FETCH_ASSOC);
	    $sth_sla->closeCursor();

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
	    $sth_sla = $db->prepare( $s_timeframes_sql );
	    $sth_sla->execute();
	    $tfRows = $sth_sla->fetchAll(PDO::FETCH_ASSOC);
	    $sth_sla->closeCursor();

	    while ( ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) !== false ) {
            $dayString   = date( "D", $currentStartTime );
            $skipData    = TRUE;
		    $currentStartTime = $row['timeStamp'];

		    // Check for SLA TimeFrame
		    foreach( $tfRows as $tfRow ) {
			    if ( preg_match( "/$dayString/i", $tfRow[ 'defaultDays' ] ) ) {
						$a_defaultStartTimeItemsList = preg_split( "/,/", $tfRow[ 'defaultStartTime' ] );
						$a_defaultEndTimeItemsList   = preg_split( "/,/", $tfRow[ 'defaultEndTime' ] );
						for ( $listCount = 0; $listCount < sizeof( $a_defaultStartTimeItemsList ); $listCount++ ) {
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
			    $total_polls = $total_polls - 1;
			    $failed_polls = $failed_polls - $row['failed_polls'];
				}

				$prevSkipVar      = $skipData;
            foreach ( $tfRows_items as $tfRow ) {
					if ( $tfRow[ 'slaEnabled' ] == 'on' ) {
						// SLA Relevant Data needs to be included
					}
					else {
						// NON-SLA Relevant Data needs to be skipped
                    if ( ( $currentStartTime > $tfRow[ 'startTimeStamp' ] - 1 ) && ( $currentStartTime < ( $tfRow[ 'endTimeStamp' ] + 1 ) ) ) {
                        $failed_polls = $failed_polls - $row['failed_polls'];
	                    $total_polls = $total_polls - 1;
								}
								}
				}
			} // end while $row
	    $sth->closeCursor();

        $hostDescription = getPreparedDBValue( 'SELECT description FROM host WHERE id=?;', array($deviceId ) );
        $hostIp          = getPreparedDBValue( 'SELECT hostname FROM host WHERE id=?;', array($deviceId ) );
        $hostSLA         = getPreparedDBValue( 'SELECT nmid_host_sla FROM host WHERE id=?;', array($deviceId ) );
        if ( $hostSLA < 1 ) {
	        $markData[] = readConfigOption('nmid_avail_globalSla');
        } else {
	        $markData[] = $hostSLA;
        }

		$slaValue        = $globalSLA;
		if ( $hostSLA > 0 ) {
			$slaValue = $hostSLA;
		}

		$percentage = 0;
        if ( $total_polls > 0 ) {
	        $availability = ( 100 * ( $total_polls - $failed_polls ) ) / $total_polls;
			$percentage = sprintf( "%0.2f", $availability );
		}
		if ( $percentage == 100 ) {
			if ( $availability < 100 ) {
				$percentage = '99.99';
			}
		}

		$fontColor = 'cc0000';
		if ( $percentage >= $slaValue ) {
			$fontColor = '00cc00';
	        $colors[ ]    = 0x99bb55;  // Green
        } else {
	        $colors[ ]    = 0xbb5555;  // Red
		}
        if ( $total_polls > 0 ) {
            $labels[ ] = $hostDescription . ' ( <*color=' . $fontColor . '*>' . $percentage . '%<*color=000000*>)';
		}
		else {
			$labels[] = $hostDescription . ' ( n/a )';
		}

		$serverCount++;
	    $taskNo[ ]    = $percentage;
	    /* take time and log performance data */
	    list( $micro, $seconds ) = preg_split( "/\s/", microtime() );
	    $end = $seconds + $micro;

	    $cacti_stats = sprintf( "Time:%01.4f ", round( $end - $start, 4 ) );
	    CereusReporting_logger( 'Server Data preparation STATS: ['.$cacti_stats.']', "debug", "availability_server" );

	}

	function checkAvailabilityExceptions( $deviceId, $chart )
	{
        global $inSLATask, $outSLATask, $deviceToTask, $startTime, $endTime, $outageExists, $db;

	    $hostDescription = getPreparedDBValue( 'SELECT description FROM host WHERE id=?;', array($deviceId ) );
		# The data for the bar chart
		$sql = "
        SELECT
          `plugin_nmidCreatePDF_Availability_Change_Table`.`Id`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`deviceId`,
          `plugin_nmidCreatePDF_Availability_Change_Type`.`decreaseAvailability`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`startTimeStamp`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`endTimeStamp`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription`
        FROM
          `plugin_nmidCreatePDF_Availability_Change_Table` INNER JOIN
          `plugin_nmidCreatePDF_Availability_Change_Type` ON `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId`
            = `plugin_nmidCreatePDF_Availability_Change_Type`.`Id`
        WHERE
            `plugin_nmidCreatePDF_Availability_Change_Table`.`deviceId` = ?
        ORDER BY `startTimeStamp`";
        $params= array($deviceId);

	    $stmt = $db->prepare($sql);
	    $stmt->setFetchMode( PDO::FETCH_ASSOC );
	    $stmt->execute($params);
	    while ( $row = $stmt->fetch() ) {
			$color = 0x33ffcc66;
			if ( $row[ 'endTimeStamp' ] > $startTime ) {
				if ( $row[ 'decreaseAvailability' ] == 0 ) {
					$startDateNoException = array();
					$endDateNoException   = array();
					$taskNoException      = array();

					if ( $row[ 'startTimeStamp' ] < $startTime ) {
						$startDateNoException[] = chartTime2( $startTime );
					}
					else {
						$startDateNoException[] = chartTime2( $row[ 'startTimeStamp' ] );
					}
					if ( $row[ 'endTimeStamp' ] > $endTime ) {
						$endDateNoException[] = chartTime2( $endTime );
					}
					else {
						$endDateNoException[] = chartTime2( $row[ 'endTimeStamp' ] );
					}
					$taskNoException[] = $deviceToTask[ $deviceId ];
					// SLA Relevenat Dates
					$inSLALayer = $chart->addBoxLayer( $startDateNoException, $endDateNoException, $outSLATask, $row[ 'shortDescription' ] . ' [' . $hostDescription . ']' );
					$inSLALayer->setDataWidth( 8 );
					$inSLALayer->setXData( $taskNoException );
					$inSLALayer->setBorderColor( SameAsMainColor );
				}
				else {
					$startDateNoException = array();
					$endDateNoException   = array();
					$taskNoException      = array();

					if ( $row[ 'startTimeStamp' ] < $startTime ) {
						$startDateNoException[] = chartTime2( $startTime );
					}
					else {
						$startDateNoException[] = chartTime2( $row[ 'startTimeStamp' ] );
					}
					if ( $row[ 'endTimeStamp' ] > $endTime ) {
						$endDateNoException[] = chartTime2( $endTime );
					}
					else {
						$endDateNoException[] = chartTime2( $row[ 'endTimeStamp' ] );
					}
					$taskNoException[] = $deviceToTask[ $deviceId ];
					// SLA Relevenat Dates
					$inSLALayer = $chart->addBoxLayer( $startDateNoException, $endDateNoException, $inSLATask, $row[ 'shortDescription' ] . ' [' . $hostDescription . ']' );
					$inSLALayer->setDataWidth( 8 );
					$inSLALayer->setXData( $taskNoException );
					$inSLALayer->setBorderColor( SameAsMainColor );
				}
				$outageExists = TRUE;
			}
		}
		$stmt->closeCursor();
	}