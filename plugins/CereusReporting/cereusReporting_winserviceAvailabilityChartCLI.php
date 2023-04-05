<?php
	/*******************************************************************************

	File:         $Id: cereusReporting_winserviceAvailabilityChartCLI.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	Modified_On:  $Date: 2020/12/10 07:06:31 $
	Modified_By:  $Author: thurban $
	Language:     Perl
	Encoding:     UTF-8
	Status:       -
	License:      Commercial
	Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/


	$startTime = -1;
	$endTime = -1;
	$chartMode = 'past';
	$dataMode = 'p';
	$hoursStep = -1;
	$leafid = -1;
	$tree_id = 1;
	$dataMode = 'p';
	$spaceLeft = 250;

	$parms = $_SERVER[ 'argv' ];
	array_shift( $parms );
	$leafid = $parms[ 0 ];
	$tree_id = $parms[ 1 ];
	$slaTime_id = $parms[ 2 ];
	$startTime = $parms[ 3 ];
	$endTime = $parms[ 4 ];
	$chartTitle = $parms[ 5 ];

	$dir = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

	chdir( $dir );
	require_once( "./include/phpchartdir.php" );
	require_once( "CereusReporting_ChartDirector.php" );

	require_once( './functions.php' ); // Support functions
	chdir( $mainDir );
	//include("./include/auth.php");
	include_once( "./include/global.php" );
	include_once( './include/config.php' );
	chdir( $dir );

	// Get DB Instance
	$db            = DBCxn::get();

	$globalSLA = readConfigOption( 'nmid_avail_globalSla' );
	$hostSLA = 0;
	$startTag = readConfigOption( 'nmid_avail_startTag' );
	$endTag = readConfigOption( 'nmid_avail_endTag' );
	$chartWidth = readConfigOption( 'nmid_avail_chartWidth' );
	$chartHeight = readConfigOption( 'nmid_avail_chartHeight' );
	$slaTimeFrame = readConfigOption( 'nmid_avail_globalSlaTimeFrame' );
	if ( $slaTime_id > 0 ) {
		$slaTimeFrame = $slaTime_id;
	}
	$nmid_avail_offSLATransparent = readConfigOption( 'nmid_avail_offSLATransparent' );
	$modeTimeFrame = array();
	$modeTimeFrame[ 'raw' ] = readConfigOption( 'nmid_avail_PollMaxRawData' );
	$modeTimeFrame[ 'hourly' ] = readConfigOption( 'nmid_avail_HourlyMaxRawData' );
	$modeTimeFrame[ 'daily' ] = readConfigOption( 'nmid_avail_DailyMaxRawData' );
	$modeTimeFrame[ 'weekly' ] = readConfigOption( 'nmid_avail_WeeklyMaxRawData' );
	$modeTimeFrame[ 'monthly' ] = readConfigOption( 'nmid_avail_MonthlyMaxRawData' );
	$modeTimeFrame[ 'yearly' ] = readConfigOption( 'nmid_avail_YearlyMaxRawData' );
	$serverCount = 0;
	//$addDataTable = readConfigOption('nmid_avail_addTable');


	$endTimeSpan = readConfigOption( 'poller_interval' );

	if ( readConfigOption( 'nmid_avail_useRRDstyle' ) ) {
		if ( ( $endTime - $startTime ) > $modeTimeFrame[ 'raw' ] ) {
			$dataMode    = 'h';
			$endTimeSpan = 3600;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'hourly' ] * 3600 ) ) {
			$dataMode    = 'd';
			$endTimeSpan = 3600 * 24;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'daily' ] * 24 * 3600 * 7 ) ) {
			$dataMode    = 'w';
			$endTimeSpan = 3600 * 24 * 7;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'weekly' ] * 24 * 3600 * 30 ) ) {
			$dataMode    = 'm';
			$endTimeSpan = 3600 * 24 * 30;
		}
		elseif ( ( $endTime - $startTime ) > ( $modeTimeFrame[ 'monthly' ] * 24 * 3600 * 356 ) ) {
			$dataMode    = 'y';
			$endTimeSpan = 3600 * 24 * 356;
		}
	}

	$time = time() - 3600 * $hoursStep;
	/* Create Connection to the DB */

	$labels = array();
	$taskNo = array();
	$startDate = array();
	$endDate = array();
	$startDateInSLA = array();
	$endDateInSLA = array();
	$startDateOutSLA = array();
	$endDateOutSLA = array();
	$inTaskNo = array();
	$outTaskNo = array();

	$startDateException = array();
	$endDateException = array();
	$taskException = array();
	$startDateNoException = array();
	$endDateNoException = array();
	$taskNoException = array();

	$deviceToTask = array();

	$colors = array();

	if ( $leafid > 0 ) {
		addLeafToChart( $leafid, $tree_id );
	}
	else {
		addTreeToChart( $tree_id );
	}


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

	$inSLAColor = $c->patternColor( array( 0xccffffff, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff,
		                                0xccff0000, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff,
		                                0xccff0000, 0xccffffff, 0xccffffff, 0xccffffff ), 4 );
	$outSLAColor = $c->patternColor( array( 0xccffffff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xccffffff, 0xccffffff,
		                                 0xcc0000ff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xccffffff, 0xccffffff,
		                                 0xcc0000ff, 0xffffff, 0xccffffff, 0xccffffff ), 4 );
	$inSLATask = $c->patternColor( array( 0xffffff, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000,
		                               0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff,
		                               0xffffff ), 4 );
	$outSLATask = $c->patternColor( array( 0xffffff, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0x00ff00,
		                                0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff,
		                                0xffffff ), 4 );


	# Add a title to the chart using 15 points Times Bold Itatic font, with white
	# (ffffff) text on a dark red (800000) background
	$textBoxObj = $c->addTitle( "Win Service - " . $chartTitle, readConfigOption( 'nmid_avail_font' ), 15 );

	# Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative
	# white/grey background. Enable both horizontal and vertical grids by setting their
	# colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2
	# pixels in width
	$plotAreaBgColor = $c->linearGradientColor( 0, 0, 0, $c->getHeight() - 40, 0xaaccff,
	                                            0xf9fcff );
	$c->setPlotArea( $spaceLeft, 60, $c->getWidth() - $spaceLeft - 40, $c->getHeight() - 160, $plotAreaBgColor,
	                 -1, -1, 0xffffff );
	#$c->setPlotArea(65, 25, $chartWidth - 105, $chartHeight -70, 0xeeeeee, 0xffffff, 0xc0c0c0, 0xc0c0c0, 0xc0c0c0);
	#$plotAreaObj = $c->setPlotArea(100, 55, 460, 50, 0xffffff, 0xeeeeee, LineColor,
	#    0xc0c0c0, 0xc0c0c0);
	#$plotAreaObj->setGridWidth(2, 1, 1, 1);

	# swap the x and y axes to create a horziontal box-whisker chart
	$c->swapXY();

	# Set the y-axis scale to be date scale from Aug 16, 2004 to Nov 22, 2004, with ticks
	# every 7 days (1 week)
	$viewPortStartDate = chartTime2( $startTime );
	$viewPortEndDate = chartTime2( $endTime );
	$c->xAxis->setDateScale( $viewPortStartDate, $viewPortEndDate );
	//$c->yAxis->setDateScale(chartTime(2010, 01, 01), chartTime(2010, 1, 31), 86400 * 1);

	# If all ticks are yearly aligned, then we use "yyyy" as the label format.
	$c->xAxis->setFormatCondition( "align", 360 * 86400 );
	$c->xAxis->setLabelFormat( "{value|yyyy}" );

	# If all ticks are monthly aligned, then we use "mmm yyyy" in bold font as the first label of a
	# year, and "mmm" for other labels.
	$c->xAxis->setFormatCondition( "align", 30 * 86400 );
	$c->xAxis->setMultiFormat( StartOfYearFilter(), "<*font=bold*>{value|mmm yyyy}", AllPassFilter(),
	                           "{value|mmm}" );

	# If all ticks are daily algined, then we use "mmm dd<*br*>yyyy" in bold font as the first label
	# of a year, and "mmm dd" in bold font as the first label of a month, and "dd" for other labels.
	$c->xAxis->setFormatCondition( "align", 86400 );
	$c->xAxis->setMultiFormat( StartOfYearFilter(),
	                           "<*block,halign=left*><*font=bold*>{value|mmm dd<*br*>yyyy}", StartOfMonthFilter(),
	                           "<*font=bold*>{value|mmm dd}" );
	$c->xAxis->setMultiFormat2( AllPassFilter(), "{value|dd}" );

	# For all other cases (sub-daily ticks), use "hh:nn<*br*>mmm dd" for the first label of a day,
	# and "hh:nn" for other labels.
	$c->xAxis->setFormatCondition( "else" );
	$c->xAxis->setMultiFormat( StartOfDayFilter(), "<*font=bold*>{value|hh:nn<*br*>mmm dd}",
	                           AllPassFilter(), "{value|hh:nn}" );

	# Set the y-axis to shown on the top (right + swapXY = top)
	$c->setYAxisOnRight();

	# Set the labels on the x axis
	$c->xAxis->setLabels( $labels );

	# Reverse the x-axis scale so that it points downwards.
	$c->xAxis->setReverse();

	# Set the horizontal ticks and grid lines to be between the bars
	//$c->xAxis->setTickOffset(0.5);

	# Add some symbols to the chart to represent milestones. The symbols are added using
	# scatter layers. We need to specify the task index, date, name, symbol shape, size
	# and color.

	// SLA Relevenat Dates
	//$inSLALayer = $c->addBoxLayer($startDateInSLA, $endDateInSLA, $inSLAColor, "SLA relevant" );
	//$inSLALayer->setDataWidth(8);
	//$inSLALayer->setXData($inTaskNo);
	//$inSLALayer->setBorderColor(SameAsMainColor);
	//
	//// OutOfSLA Relevenat Dates
	//$outSLALayer = $c->addBoxLayer($startDateOutSLA, $endDateOutSLA, $outSLAColor, "SLA not relevant");
	//$outSLALayer->setDataWidth(8);
	//$outSLALayer->setXData($outTaskNo);
	//$outSLALayer->setBorderColor(SameAsMainColor);

	if ( $leafid > 0 ) {
		addLeafExceptionsToChart( $leafid, $tree_id, $c );
	}
	else {
		addTreeExceptionsToChart( $tree_id, $c );
	}


	# Add a multi-color box-whisker layer to represent the gantt bars
	$layer = $c->addBoxWhiskerLayer2( $startDate, $endDate, NULL, NULL, NULL, $colors );
	$layer->setXData( $taskNo );
	$layer->setBorderColor( SameAsMainColor );
	$layer->setDataWidth( 20 );


	# Add a legend box at (140, 265) - bottom of the plot area. Use 8 pts Arial Bold as
	# the font with auto-grid layout. Set the width to the same width as the plot area.
	# Set the backgorund to grey (dddddd).
	#$legendBox = $c->addLegend2(100, 265, AutoGrid, "arialbd.ttf", 8);
	#$legendBox->setWidth(461);
	#$legendBox->setBackground(0xdddddd);

	# The keys for the scatter layers (milestone symbols) will automatically be added to
	# the legend box. We just need to add keys to show the meanings of the bar colors.
	#$legendBox->addKey("Server 1", 0x00cc00);

	if ( $outageExists ) {
		$b = $c->addLegend2( 180, $c->getHeight() - 90, AutoGrid, readConfigOption( 'nmid_avail_font' ), 8 );
	}

	#$b->setAlignment(TopRight);
	#$b->setBackground(0x80808080, -1, 2);

	# Output the chart
	//header("Content-type: image/png");
	print( $c->makeChart2( PNG ) );

	function addTreeToChart( $tree_id )
	{
		global $db;
		$sql       = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id='" . $tree_id . "';";
		$result    = mysql_query( $sql );
		$hostAdded = array();
		while ( $row = mysql_fetch_assoc( $result ) ) {
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
		global $db;
		$orderKey = getDBValue( 'order_key', 'select order_key from graph_tree_items where id=' . $leafid . ';' );
		$hostId   = getDBValue( 'host_id', 'select host_id from graph_tree_items where id=' . $leafid . ';' );
		$orderKey = preg_replace( "/0{3,3}/", "", $orderKey );
		$sql      = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id='" . $tree_id . "' AND order_key like '" . $orderKey . "%';";
		if ( $hostId > 0 ) {
			$sql = "select host_id,local_graph_id,rra_id from graph_tree_items where id='" . $leafid . "';";
		}
		$result = mysql_query( $sql );
		while ( $row = mysql_fetch_assoc( $result ) ) {
			if ( $row[ 'host_id' ] > 0 ) {
				addServerToChart( $row[ 'host_id' ] );
			}
		}
	}

	function addTreeExceptionsToChart( $tree_id, $chart )
	{
		global $db;
		$sql       = "select id from graph_tree_items where graph_tree_id='" . $tree_id . "';";
		$result    = mysql_query( $sql );
		$hostAdded = array();

		while ( $row = mysql_fetch_assoc( $result ) ) {
			if ( $row[ 'host_id' ] > 0 ) {
				if ( array_key_exists( $row[ 'host_id' ], $hostAdded ) == FALSE ) {
					$hostAdded[ $row[ 'host_id' ] ] = TRUE;
					checkAvailabilityExceptions( $row[ 'host_id' ], $chart );
				}
			}
		}
	}

	function addLeafExceptionsToChart( $leafid, $tree_id, $chart )
	{
		global $db;
		$orderKey = getDBValue( 'order_key', 'select order_key from graph_tree_items where id=' . $leafid . ';' );
		$orderKey = preg_replace( "/0{3,3}/", "", $orderKey );
		$hostId   = getDBValue( 'host_id', 'select host_id from graph_tree_items where id=' . $leafid . ';' );
		$sql      = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id='" . $tree_id . "' AND order_key like '" . $orderKey . "%';";
		if ( $hostId > 0 ) {
			$sql = "select host_id,local_graph_id,rra_id from graph_tree_items where id='" . $leafid . "';";
		}
		$result = mysql_query( $sql );
		while ( $row = mysql_fetch_assoc( $result ) ) {
			if ( $row[ 'host_id' ] > 0 ) {
				checkAvailabilityExceptions( $row[ 'host_id' ], $chart );
			}
		}
	}

	function addWinServiceToChart( $deviceId = 0, $ldid = 0, $title = '' )
	{
		global $dataMode, $labels, $taskNo, $startDate, $endDate, $colors, $serverCount, $startTime, $endTime, $globalSLA,
		       $startDateInSLA, $startDateOutSLA, $endDateInSLA, $endDateOutSLA, $outSLAColorArray, $inSLAColorArray,
		       $outTaskNo, $inTaskNo, $endTimeSpan, $deviceToTask, $slaTime_id, $modeTimeFrame, $outageExists, $db;

		$where_clause = "
        (
            ( timeStamp > " . $startTime . " AND typeId = 'p'  AND timeStamp < " . $endTime . " AND ( ( " . $endTime . " - timestamp ) < " . $modeTimeFrame[ 'raw' ] . " ))
        OR
            ( timeStamp > " . $startTime . " AND typeId = 'h'  AND timeStamp < " . $endTime . " AND ( ( " . $endTime . " - timestamp ) < ( " . $modeTimeFrame[ 'hourly' ] . " * 3600 ) ))
        OR
            ( timeStamp > " . $startTime . " AND typeId = 'd'  AND timeStamp < " . $endTime . " AND ( ( " . $endTime . " - timestamp ) < ( " . $modeTimeFrame[ 'daily' ] . " * 24 * 7 * 3600 ) ))
        OR
            ( timeStamp > " . $startTime . " AND typeId = 'w'  AND timeStamp < " . $endTime . " AND ( ( " . $endTime . " - timestamp ) < ( " . $modeTimeFrame[ 'weekly' ] . " * 24 * 30 * 3600 ) ))
        OR
            ( timeStamp > " . $startTime . " AND typeId = 'm'  AND timeStamp < " . $endTime . " AND ( ( " . $endTime . " - timestamp ) < ( " . $modeTimeFrame[ 'monthly' ] . " * 24 * 365 * 3600 ) ))
        OR
            ( timeStamp > " . $startTime . " AND typeId = 'y'  AND timeStamp < " . $endTime . " )
        )
    ";

		$sql = "
        SELECT
            SUM(total_polls) as counter
        FROM
            plugin_nmidCreatePDF_Availability_Table
        WHERE
            $where_clause
        AND
            deviceId = " . $deviceId;

		$dataCount = getDBValue( 'counter', $sql );

		$sql = "
        SELECT
            MIN(timeStamp) as startReal
        FROM
            plugin_nmidCreatePDF_Availability_Table
        WHERE
            deviceId = " . $deviceId . "
        AND total_polls > 0";

		$startReal = getDBValue( 'startReal', $sql );
		if ( $startReal < $startTime ) {
			$startReal = $startTime;
		}

		// Initial Availability is a transparent light green
		$taskNo[ ]                 = $serverCount;
		$deviceToTask[ $deviceId ] = $serverCount;
		$startDate[ ]              = chartTime2( $startTime );
		$endDate[ ]                = chartTime2( $endTime );
		$colors[ ]                 = 0xcc00cc00;

		// Initial Availability is green
		$taskNo[ ]    = $serverCount;
		$startDate[ ] = chartTime2( $startReal );
		$endDate[ ]   = chartTime2( $endTime );
		if ( $dataCount > 0 ) {
			$colors[ ] = 0xcc00cc00;
		}
		else {
			$colors[ ] = 0xcc00cc00;
		}

		$sql = "
        SELECT
            timeStamp,
            typeId,
            total_polls,
            failed_polls
        FROM
            plugin_nmidCreatePDF_Availability_Table
        WHERE
            $where_clause
        AND
            deviceId = " . $deviceId . "
        ORDER BY `timeStamp`";

		$skipData = TRUE;

		// Get the default SLA Timeframe for this host (e.g. 24x7 or 5x8 )
		$slaTimeFrame = CereusReporting_getSlaTimeFrame( $deviceId );
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
		$tfResult         = mysql_query( $s_timeframes_sql );

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
		$tfItemResult     = mysql_query( $s_timeframes_sql );

		// Initialize some variables
		$rowCount          = 0;
		$totalData         = 0;
		$noSLA_TotalPolls  = 0;
		$noSLA_FailedPolls = 0;
		$totalPolls        = 0;
		$failedPolls       = 0;


		$result = mysql_query( $sql );
		if ( $result ) {
			while ( $row = mysql_fetch_assoc( $result ) ) {
				$dayString = date( "D", $row[ 'timeStamp' ] );
				$skipData  = TRUE;

				if ( $row[ 'typeId' ] == 'h' ) {
					$endTimeSpan = 3600;
				}
				elseif ( $row[ 'typeId' ] == 'd' ) {
					$endTimeSpan = 3600 * 24;
				}
				elseif ( $row[ 'typeId' ] == 'w' ) {
					$endTimeSpan = 3600 * 24 * 7;
				}
				elseif ( $row[ 'typeId' ] == 'm' ) {
					$endTimeSpan = 3600 * 24 * 30;
				}
				elseif ( $row[ 'typeId' ] == 'y' ) {
					$endTimeSpan = 3600 * 24 * 356;
				}


				$skipData = TRUE;
				mysql_data_seek( $tfResult, 0 );
				while ( $tfRow = mysql_fetch_assoc( $tfResult ) ) {
					if ( preg_match( "/$dayString/", $tfRow[ 'defaultDays' ] ) ) {
						$a_defaultStartTimeItemsList = preg_split( "/,/", $tfRow[ 'defaultStartTime' ] );
						$a_defaultEndTimeItemsList   = preg_split( "/,/", $tfRow[ 'defaultEndTime' ] );
						for ( $listCount = 0; $listCount < sizeof( $a_defaultStartTimeItemsList ); $listCount++ ) {
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

				mysql_data_seek( $tfItemResult, 0 );
				$prevSkipVar = $skipData;
				while ( $tfRow = mysql_fetch_assoc( $tfItemResult ) ) {
					if ( $tfRow[ 'slaEnabled' ] == 'on' ) {
						// SLA Relevant Data needs to be included
						if ( $row[ 'timeStamp' ] > $tfRow[ 'startTimeStamp' ] - 1 ) {
							if ( $row[ 'timeStamp' ] < ( $tfRow[ 'endTimeStamp' ] + 1 ) ) {
								$skipData          = FALSE;
								$startDateInSLA[ ] = chartTime2( $tfRow[ 'startTimeStamp' ] );
								if ( $tfRow[ 'timeStamp' ] > $endTime ) {
									$endDateOutSLA[ ] = chartTime2( $endTime );
								}
								else {
									$endDateOutSLA[ ] = chartTime2( $tfRow[ 'endTimeStamp' ] );
								}
								$inSLAColorArray[ ] = 0x00cc00;
								$inTaskNo[ ]        = $serverCount;
							}
						}
					}
					else {
						// NON-SLA Relevant Data needs to be skipped
						if ( $row[ 'timeStamp' ] > $tfRow[ 'startTimeStamp' ] - 1 ) {
							if ( $row[ 'timeStamp' ] < ( $tfRow[ 'endTimeStamp' ] + 1 ) ) {
								$skipData           = TRUE;
								$startDateOutSLA[ ] = chartTime2( $tfRow[ 'startTimeStamp' ] );
								if ( $tfRow[ 'timeStamp' ] > $endTime ) {
									$endDateOutSLA[ ] = chartTime2( $endTime );
								}
								else {
									$endDateOutSLA[ ] = chartTime2( $tfRow[ 'endTimeStamp' ] );
								}
								$outSLAColorArray[ ] = 0xbb0000;
								$outTaskNo[ ]        = $serverCount;
								$outageExists        = TRUE;
							}
						}
					}
				}

				if ( $skipData == FALSE ) {
					$totalPolls  = $totalPolls + $row[ 'total_polls' ];
					$failedPolls = $failedPolls + $row[ 'failed_polls' ];
					if ( $row[ 'total_polls' ] > 0 ) {
						$rowCount++;
						$data = ( 100 * ( $row[ 'total_polls' ] - $row[ 'failed_polls' ] ) ) / $row[ 'total_polls' ];
						// $data = 100 - ( ( 100 * $row['failed_polls'] ) / $row['total_polls'] ) ;
						$totalData = $totalData + $data;
						if ( $data < 100 ) {
							$taskNo[ ]    = $serverCount;
							$startDate[ ] = chartTime2( $row[ 'timeStamp' ] );
							if ( $row[ 'timeStamp' ] + $endTimeSpan > $endTime ) {
								$endDate[ ] = chartTime2( $endTime );
							}
							else {
								$endDate[ ] = chartTime2( $row[ 'timeStamp' ] + $endTimeSpan );
							}
							$colors[ ] = 0xcc0000;
						}
						else {
							$taskNo[ ]    = $serverCount;
							$startDate[ ] = chartTime2( $row[ 'timeStamp' ] );
							if ( $row[ 'timeStamp' ] + $endTimeSpan > $endTime ) {
								$endDate[ ] = chartTime2( $endTime );
							}
							else {
								$endDate[ ] = chartTime2( $row[ 'timeStamp' ] + $endTimeSpan );
							}
							$colors[ ] = 0x00cc00;
						}
					}
				}
				else {
					$noSLA_TotalPolls  = $noSLA_TotalPolls + $row[ 'total_polls' ];
					$noSLA_FailedPolls = $noSLA_FailedPolls + $row[ 'failed_polls' ];
				}
			} // end while $row
		} // end if $result
		mysql_free_result( $result );
		mysql_free_result( $tfResult );
		mysql_free_result( $tfItemResult );

		// Add service outtages:
		$serviceSql = "
        SELECT
            failed_polls,
            timeStamp
        FROM
            plugin_nmidCreatePDF_AvailabilityFailedPolls_Table
        WHERE
            timeStamp > :startTime
        AND
            timeStamp < :endTime
        AND
            ldid = :ldid";

		$local_stmt = $db->prepare( $serviceSql );
		$local_stmt->bindValue( ':ldid', $ldid );
		$local_stmt->bindValue( ':startTime', $startTime );
		$local_stmt->bindValue( ':endTime', $endTime );
		$local_stmt->execute();
		while ( $a_hostServiceState = $local_stmt->fetch( ) ) {
			$taskNo[ ]    = $serverCount;
			$startDate[ ] = chartTime2( $a_hostServiceState[ 'timeStamp' ] );
			$endDate[ ]   = chartTime2( $a_hostServiceState[ 'timeStamp' ] + readConfigOption( 'poller_interval' ) );
			$colors[ ]    = 0xcc0000;
		}
		$local_stmt->closeCursor();

		$failedServicePolls = CereusReporting_getFailedServicePolls( $deviceId, $ldid, $startTime, $endTime ) + $failedPolls;
		//$hostDescription = getDBValue('description','select description from host where id='.$deviceId.';');
		$//hostIp = getDBValue('hostname','select hostname from host where id='.$deviceId.';');
		$hostSLA  = getDBValue( 'nmid_host_sla', 'select nmid_host_sla from host where id=' . $deviceId . ';' );
		$slaValue = $globalSLA;
		if ( $hostSLA > 0 ) {
			$slaValue = $hostSLA;
		}

		$totalPolls = $totalPolls - $noSLA_TotalPolls;

		if ( $totalPolls < 0 ) {
			$totalPolls = 0;
		}
		if ( $failedServicePolls < 0 ) {
			$failedServicePolls = 0;
		}

		$availability = 100;
		if ( $totalPolls > 0 ) {
			$availability = ( 100 * ( $totalPolls - $failedServicePolls ) ) / $totalPolls;
		}

		$percentage = 0;
		if ( $rowCount > 0 ) {
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
		}
		$labels[ ] = $title . ' ( <*color=' . $fontColor . '*>' . $percentage . '%<*color=000000*>)';

		$serverCount++;
	}


	function addServerToChart( $deviceId )
	{
		global $dataMode, $labels, $taskNo, $startDate, $endDate, $colors, $serverCount, $startTime, $endTime, $globalSLA,
		       $startDateInSLA, $startDateOutSLA, $endDateInSLA, $endDateOutSLA, $outSLAColorArray, $inSLAColorArray,
		       $outTaskNo, $inTaskNo, $endTimeSpan, $deviceToTask, $slaTime_id, $modeTimeFrame, $outageExists;

		// Check for Windows Services

		if ( read_config_option( "extended_paths" ) == "on" ) {
			$sql = "
			SELECT
			`data_template_data`.local_data_id,
			`data_template_data`.name_cache
			FROM
			`data_template_data`,
			`data_local`,
			`data_template_rrd`
			WHERE
			`data_local`.id = `data_template_data`.local_data_id
			AND
			`data_template_rrd`.Local_data_id = `data_template_data`.local_data_id
			AND
			`data_local`.host_id = " . $deviceId . "
			AND
			   `data_template_rrd`.data_source_name LIKE '%service_state%'
		";
		}
		else {
			// Check for Windows Services
			$sql = "
	        SELECT
	            `data_template_data`.local_data_id,
	            `data_template_data`.name_cache
	        FROM
	            `data_template_data`,
	            `data_local`
	        WHERE
	            `data_local`.id = `data_template_data`.local_data_id
	        AND
	            `data_local`.host_id = " . $deviceId . "
	        AND
	           `data_template_data`.data_source_path LIKE '%service_state%'
	    ";
		}

		$result = mysql_query( $sql );
		while ( $a_hostServiceState = mysql_fetch_assoc( $result ) ) {
			addWinServiceToChart( $deviceId, $a_hostServiceState[ 'local_data_id' ], $a_hostServiceState[ 'name_cache' ] );
		}
		mysql_free_result( $result );
	}

	function checkAvailabilityExceptions( $deviceId, $chart )
	{
		global $inSLATask, $outSLATask, $deviceToTask, $startTime, $endTime, $outageExists;
		$hostDescription = getDBValue( 'description', 'select description from host where id=' . $deviceId . ';' );
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
            `plugin_nmidCreatePDF_Availability_Change_Table`.`deviceId` = " . $deviceId . "
        ORDER BY `startTimeStamp`";

		$result = mysql_query( $sql );
		while ( $row = mysql_fetch_assoc( $result ) ) {
			$color = 0x33ffcc66;
			if ( $row[ 'endTimeStamp' ] > $startTime ) {
				if ( $row[ 'decreaseAvailability' ] == 0 ) {
					$startDateNoException = array();
					$endDateNoException   = array();
					$taskNoException      = array();

					if ( $row[ 'startTimeStamp' ] < $startTime ) {
						$startDateNoException[ ] = chartTime2( $startTime );
					}
					else {
						$startDateNoException[ ] = chartTime2( $row[ 'startTimeStamp' ] );
					}
					if ( $row[ 'endTimeStamp' ] > $endTime ) {
						$endDateNoException[ ] = chartTime2( $endTime );
					}
					else {
						$endDateNoException[ ] = chartTime2( $row[ 'endTimeStamp' ] );
					}
					$taskNoException[ ] = $deviceToTask[ $deviceId ];
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
						$startDateNoException[ ] = chartTime2( $startTime );
					}
					else {
						$startDateNoException[ ] = chartTime2( $row[ 'startTimeStamp' ] );
					}
					if ( $row[ 'endTimeStamp' ] > $endTime ) {
						$endDateNoException[ ] = chartTime2( $endTime );
					}
					else {
						$endDateNoException[ ] = chartTime2( $row[ 'endTimeStamp' ] );
					}
					$taskNoException[ ] = $deviceToTask[ $deviceId ];
					// SLA Relevenat Dates
					$inSLALayer = $chart->addBoxLayer( $startDateNoException, $endDateNoException, $inSLATask, $row[ 'shortDescription' ] . ' [' . $hostDescription . ']' );
					$inSLALayer->setDataWidth( 8 );
					$inSLALayer->setXData( $taskNoException );
					$inSLALayer->setBorderColor( SameAsMainColor );
				}
				$outageExists = TRUE;
			}
		}
	}

?>

