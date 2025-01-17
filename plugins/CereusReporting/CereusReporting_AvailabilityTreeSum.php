<?php
    /*******************************************************************************
     *
     * File:         $Id: CereusReporting_AvailabilityTreeSum.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
     * Modified_On:  $Date: 2020/12/10 07:06:31 $
     * Modified_By:  $Author: thurban $
     * Language:     Perl
    * Encoding:     UTF-8
    * Status:       -
    * License:      Commercial
    * Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
     *******************************************************************************/


    $dir     = dirname( __FILE__ );
    $mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

    chdir( $dir );
    require_once( "./include/phpchartdir.php" );
    require_once( "CereusReporting_ChartDirector.php" );

    require_once( './functions.php' ); // Support functions
    chdir( $mainDir );
    //include("./include/auth.php");
    include_once( "./include/global.php" );
    include_once( "./lib/rrd.php" );
    include_once( './include/config.php' );
    chdir( $dir );

    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "alternate" ] = '';
    $colors[ "light" ] = '';
    $colors[ "header" ] = '';

    $globalSLA                    = readConfigOption( 'nmid_avail_globalSla' );
    $hostSLA                      = 0;
    $startTag                     = readConfigOption( 'nmid_avail_startTag' );
    $endTag                       = readConfigOption( 'nmid_avail_endTag' );
    $chartWidth                   = readConfigOption( 'nmid_avail_chartWidth' );
    $chartHeight                  = readConfigOption( 'nmid_avail_chartHeight' );
    $slaTimeFrame                 = readConfigOption( 'nmid_avail_globalSlaTimeFrame' );
    $nmid_avail_offSLATransparent = readConfigOption( 'nmid_avail_offSLATransparent' );
    $modeTimeFrame                = array();
    $modeTimeFrame[ 'raw' ]       = readConfigOption( 'nmid_avail_PollMaxRawData' );
    $modeTimeFrame[ 'hourly' ]    = readConfigOption( 'nmid_avail_HourlyMaxRawData' );
    $modeTimeFrame[ 'daily' ]     = readConfigOption( 'nmid_avail_DailyMaxRawData' );
    $modeTimeFrame[ 'weekly' ]    = readConfigOption( 'nmid_avail_WeeklyMaxRawData' );
    $modeTimeFrame[ 'monthly' ]   = readConfigOption( 'nmid_avail_MonthlyMaxRawData' );
    $modeTimeFrame[ 'yearly' ]    = readConfigOption( 'nmid_avail_YearlyMaxRawData' );
    $serverCount                  = 0;
    $globalAvailability           = 0;

    //$addDataTable = readConfigOption('nmid_avail_addTable');

    $startTime    = -1;
    $endTime      = -1;
    $chartMode    = 'past';
    $hoursStep    = -1;
    $leafid       = -1;
    $tree_id      = 1;
    $outageExists = FALSE;
    $isSorted     = FALSE;
    $sortedChar   = '';
    /* loop through each of the selected tasks and delete them*/
    foreach ( $_REQUEST as $var => $val) {
        if ( $var == 'deviceId' ) {
            $deviceId = $val;
        }
        elseif ( $var == 'hours' ) {
            $hoursStep = $val;
        }
        elseif ( $var == 'startTime' ) {
            $startTime = $val;
        }
        elseif ( $var == 'endTime' ) {
            $endTime = $val;
        }
        elseif ( $var == 'mode' ) {
            $chartMode = $val;
        }
        elseif ( $var == 'data' ) {
            $dataMode = $val;
        }
        elseif ( $var == 'leaf_id' ) {
            if ( preg_match( "/^([0-9]+)$/", $val, $matches ) ) {
                $leafid = $matches[ 1 ];
            }
        }
        elseif ( $var == 'tree_id' ) {
            if ( preg_match( "/^([0-9]+)$/", $val, $matches ) ) {
                $tree_id = $matches[ 1 ];
            }
        }
        elseif ( $var == 'sorted' ) {
            $isSorted = TRUE;
        }
        elseif ( $var == 'sortedChar' ) {
            if ( preg_match( "/^([0-9]+)$/", $val, $matches ) ) {
                $sortedChar = $matches[ 1 ];
            }
        }
    }

    // aggregation is not implemented yet
    $endTimeSpan = readConfigOption( 'poller_interval' );
    if ( read_config_option( 'nmid_avail_useRRDstyle' ) ) {
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
    /* Create Connection to the DB */
    $link = mysql_connect( "$database_hostname:$database_port", $database_username, $database_password );
    mysql_select_db( $database_default );

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
        addLeafToChart( $leafid, $tree_id );
        $chartLabel = getDBValue( 'title', 'select title from graph_tree_items where id=' . $leafid . ';' );
    }
    else {
        addTreeToChart( $tree_id );
        $chartLabel = getDBValue( 'name', 'select name from graph_tree where id=' . $tree_id . ';' );
    }
    if ( $serverCount > 0 ) {
        $globalAvailability = $globalAvailability / $serverCount;
    }
    $chartLabel = $chartLabel . ' ( ' . sprintf( "%0.2f", $globalAvailability ) . ' )';


    $chartHeight = 30;
    $chartWidth  = 800;

    # Create a XYChart object of size 800 x 30 pixels. Set background color to light red
    # (0xffcccc), with 1 pixel 3D border effect.
    # Create a XYChart object of size 400 x 270 pixels
    $c = new XYChart( $chartWidth, $chartHeight );

    # Set the plotarea at (180, 1) and of size 800 x 25 pixels. 
    $c->setPlotArea( 180, 1, $c->getWidth(), 25, Transparent,
                     Transparent, Transparent, Transparent );

    # swap the x and y axes to create a horziontal box-whisker chart
    $c->swapXY();

    # The data for the bar chart
    $data0 = array( $globalAvailability );
    $data1 = array( ( 100 - $globalAvailability ) );

    # The labels for the bar chart
    $labels = array( $chartLabel );

    # Add a stacked bar layer and set the layer 3D depth to 8 pixels
    $layer = $c->addBarLayer2( Stack );

    # Add the three data sets to the bar layer
    $layer->addDataSet( $data0, 0x00cc00, "Ok" );
    $layer->addDataSet( $data1, 0xcc0000, "Bad" );
    $layer->setBorderColor( Transparent, softLighting( Top ) );

    # Set the labels on the x axis.
    $c->xAxis->setLabels( $labels );

    # Set the x axis to Transparent, with labels in dark red (0x663300)
    $c->xAxis->setColors( Transparent, 0x663300 );

    # Set the y axis and labels to Transparent
    $c->yAxis->setColors( Transparent, Transparent );

    # Output the chart
    header( "Content-type: image/png" );
    print( $c->makeChart2( PNG ) );

    function addTreeToChart( $tree_id )
    {
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
        global $isSorted, $sortedFrom, $sortedTo;
        $orderKey = getDBValue( 'order_key', 'select order_key from graph_tree_items where id=' . $leafid . ';' );
        $hostId   = getDBValue( 'host_id', 'select host_id from graph_tree_items where id=' . $leafid . ';' );
        $orderKey = preg_replace( "/0{3,3}/", "", $orderKey );
        $sql      = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id='" . $tree_id . "' AND order_key like '" . $orderKey . "%';";
        if ( $isSorted ) {
            $sql = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id='" . $tree_id . "' AND order_key like '" . $orderKey . "%' LIMIT " . $sortedFrom . "," . $sortedTo . ";";
        }
        if ( $hostId > 0 ) {
            $sql = "select host_id,local_graph_id,rra_id from graph_tree_items where id='" . $leafid . "';";
            if ( $isSorted ) {
                $sql = "select host_id,local_graph_id,rra_id from graph_tree_items where id='" . $leafid . "' AND order_key like '" . $orderKey . "%' LIMIT " . $sortedFrom . "," . $sortedTo . ";";
            }
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
        global $isSorted, $sortedFrom, $sortedTo;
        $sql = "select id from graph_tree_items where graph_tree_id='" . $tree_id . "';";
        if ( $isSorted ) {
            $sql = "select id from graph_tree_items where graph_tree_id='" . $tree_id . "' LIMIT " . $sortedFrom . "," . $sortedTo . ";";
        }
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

    function addServerToChart( $deviceId )
    {
        global $dataMode, $labels, $taskNo, $startDate, $endDate, $colors, $serverCount, $startTime, $endTime, $globalSLA,
               $startDateInSLA, $startDateOutSLA, $endDateInSLA, $endDateOutSLA, $outSLAColorArray, $inSLAColorArray,
               $outTaskNo, $inTaskNo, $endTimeSpan, $deviceToTask, $slaTime_id, $modeTimeFrame, $outageExists, $globalAvailability;

        $where_clause = CereusReporting_buildTimeStampQuery( $startTime, $endTime );


        $sql = "
        SELECT
            SUM(total_polls) as counter
        FROM
            plugin_nmidCreatePDF_Availability_Table_".$deviceId."
        WHERE
            $where_clause
        AND
            deviceId = " . $deviceId;


        $dataCount = getDBValue( 'counter', $sql );

        $sql = "
        SELECT
            MIN(timeStamp) as startReal
        FROM
            plugin_nmidCreatePDF_Availability_Table_".$deviceId."
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
        SELECT DISTINCT
            timeStamp,
            typeId,
            total_polls,
            failed_polls
        FROM
            plugin_nmidCreatePDF_Availability_Table_".$deviceId."
        WHERE
            $where_clause
        AND
            deviceId = " . $deviceId . "
        ORDER BY `timeStamp`";

        //header("Content-type: text/html");
        $skipData = TRUE;

        $slaTimeFrame = CereusReporting_getSlaTimeFrame( $deviceId );

        $rowCount          = 0;
        $totalData         = 0;
        $noSLA_TotalPolls  = 0;
        $noSLA_FailedPolls = 0;
        $totalPolls        = 0;
        $failedPolls       = 0;
        $endTimeSpan       = 0;

        $result = mysql_query( $sql );
        if ( $result ) {
            while ( $row = mysql_fetch_assoc( $result ) ) {
                $dayString = date( "D", $row[ 'timeStamp' ] );
                $skipData  = TRUE;

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

                while ( $tfRow = mysql_fetch_assoc( $tfResult ) ) {
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
                mysql_free_result( $tfResult );
                if ( $skipData == TRUE ) {
                    $noSLA_TotalPolls  = $noSLA_TotalPolls + $row[ 'total_polls' ];
                    $noSLA_FailedPolls = $noSLA_FailedPolls + $row[ 'failed_polls' ];
                }

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
                $tfResult         = mysql_query( $s_timeframes_sql );
                $prevSkipVar      = $skipData;
                while ( $tfRow = mysql_fetch_assoc( $tfResult ) ) {
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
                        $totalData   = $totalData + $data;
                        $dateFormat  = readConfigOption( "nmid_pdf_dateformat" );
                        $myTime      = date( "$dateFormat T Y", $endTime );
                        $myStampTime = date( "$dateFormat T Y", $row[ 'timeStamp' ] );
                        if ( $data < 100 ) {
                            $taskNo[ ]    = $serverCount;
                            $startDate[ ] = chartTime2( $row[ 'timeStamp' ] );
                            if ( $row[ 'timeStamp' ] + $endTimeSpan >= $endTime ) {
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
                            if ( $row[ 'timeStamp' ] + $endTimeSpan >= $endTime ) {
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

        $hostDescription = getDBValue( 'description', 'select description from host where id=' . $deviceId . ';' );
        $hostIp          = getDBValue( 'hostname', 'select hostname from host where id=' . $deviceId . ';' );
        $hostSLA         = getDBValue( 'nmid_host_sla', 'select nmid_host_sla from host where id=' . $deviceId . ';' );
        $slaValue        = $globalSLA;
        if ( $hostSLA > 0 ) {
            $slaValue = $hostSLA;
        }

        list ( $availability, $totalPolls, $failedPolls ) = get_device_availability( $deviceId, $startTime, $endTime );

        $percentage = 0;
        if ( $rowCount > 0 ) {
            $percentage = sprintf( "%0.2f", $availability );
        }
        if ( $percentage == 100 ) {
            if ( $availability < 100 ) {
                $percentage = '99.99';
            }
        }
        $globalAvailability = $globalAvailability + $percentage;

        $fontColor = 'cc0000';
        if ( $percentage >= $slaValue ) {
            $fontColor = '00cc00';
        }
        if ( $dataCount > 0 ) {
            $labels[ ] = $hostDescription . ' ( <*color=' . $fontColor . '*>' . $percentage . '%<*color=000000*>)';
        }
        else {
            $labels[ ] = $hostDescription . ' ( n/a )';
        }

        $serverCount++;
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

