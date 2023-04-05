<?php
    /*******************************************************************************
     * Copyright (c) 2019. - All Rights Reserved
     * Unauthorized copying of this file, via any medium is strictly prohibited
     * Proprietary and confidential
     * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2019.
     *
     * File:         $Id: CereusReporting_AvailabilityChart.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
     * Filename:     CereusReporting_AvailabilityChart.php
     * LastModified: 09/01/2017, 10:15
     * Modified_On:  $Date: 2020/12/10 07:06:31 $
     * Modified_By:  $Author: thurban $
     *
     ******************************************************************************/

    $dir = dirname(__FILE__);
    $mainDir = preg_replace("@plugins.CereusReporting@","",$dir);

    chdir($dir);
    require_once("./include/phpchartdir.php");
    require_once("CereusReporting_ChartDirector.php");
    
    require_once('./functions.php');  // Support functions
    chdir($mainDir);
    //include("./include/auth.php");
    include_once("./include/global.php");
    include_once('./include/config.php');
    chdir($dir);

    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "alternate" ] = '';
    $colors[ "light" ] = '';
    $colors[ "header" ] = '';

    $globalSLA = readConfigOption('nmid_avail_globalSla');
    $hostSLA = 0;
    $startTag = readConfigOption('nmid_avail_startTag');
    $endTag = readConfigOption('nmid_avail_endTag');
    $chartWidth = readConfigOption('nmid_avail_chartWidth');
    $chartHeight = readConfigOption('nmid_avail_chartHeight');
    $slaTimeFrame = readConfigOption('nmid_avail_globalSlaTimeFrame');
    $nmid_avail_offSLATransparent = readConfigOption('nmid_avail_offSLATransparent');
    //$addDataTable = readConfigOption('nmid_avail_addTable');

    $startTime = -1;
    $endTime = -1;
    $chartMode = 'past';
    $dataMode = 'p';
    $hoursStep = -1;
    /* loop through each of the selected tasks and delete them*/
    foreach ( $_REQUEST as $var => $val) {
        if ($var == 'deviceId' ) {
            $deviceId = $val;
        }
        elseif ($var == 'hours' ) {
            $hoursStep = $val;
        }
        elseif ($var == 'startTime' ) {
            $startTime = $val - 30;
        }
        elseif ($var == 'endTime' ) {
            $endTime = $val + 30;
        }
        elseif ($var == 'mode' ) {
            $chartMode = $val;
        }
        elseif ($var == 'data' ) {
            $dataMode = $val;
        }
    }
    
    $time = time() - 3600 * $hoursStep;
    /* Create Connection to the DB */
    $db = DBCxn::get();


	$where_clause = CereusReporting_buildTimeStampQuery($startTime,$endTime);

    $sql = "
        SELECT
            MIN(timeStamp) as startReal
        FROM
            plugin_nmidCreatePDF_Availability_Table_" . $deviceId . "
        WHERE
            deviceId = ".$deviceId."
        AND total_polls > 0";
        
    $startReal = getDBValue('startReal',$sql);
    if ( $startReal < $startTime  ) {
        $startReal = $startTime;
    }

    # The data for the bar chart
    $sql = "
        SELECT
            timeStamp,
            total_polls,
            failed_polls
        FROM
            plugin_nmidCreatePDF_Availability_Table_" . $deviceId . "
        WHERE
            timeStamp > ".$time."
        AND
            typeId = '".$dataMode."'
        AND
            deviceId = ".$deviceId." 
        ORDER BY `timeStamp`";
    if ( $chartMode == 'time') {
        $sql = "
            SELECT
                timeStamp,
                total_polls,
                failed_polls
            FROM
                plugin_nmidCreatePDF_Availability_Table_" . $deviceId . "
            WHERE
                $where_clause
            AND
                deviceId = ".$deviceId."   
            ORDER BY `timeStamp`";
    }

    $stmt = $db->prepare( $sql);
    $stmt->execute();

    $labels = array();
    $viewPortEndDate  = chartTime2( time() );
    $data = array();
    //header("Content-type: text/html");
    $skipData = TRUE;

    $nmid_host_sla_timeframe =getDBValue( 'nmid_host_sla_timeframe','select nmid_host_sla_timeframe from host where id='.$deviceId.';' );
    if ( $nmid_host_sla_timeframe > 0 ) {
        $slaTimeFrame = $nmid_host_sla_timeframe;
    }
    if ( !( $slaTimeFrame > 0 ) ) {
        $slaTimeFrame = 1;
    }
    while ( $row = $stmt->fetch() ) {
        $dayString = date("D", $row['timeStamp']);
        $skipData = TRUE;
        // Check for SLA TimeFrame
        $s_timeframes_sql = "
            SELECT
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`
            FROM
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
            WHERE
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=".$slaTimeFrame."
        ";
        $stmt_slaTime = $db->prepare( $s_timeframes_sql);
        $stmt_slaTime->execute();
        while ($tfRow = $stmt_slaTime->fetch() ) {
            if ( preg_match("/$dayString/",$tfRow['defaultDays'] ) ) {
                $a_defaultStartTimeItems = preg_split("/:/",$tfRow['defaultStartTime']);
                $s_defaultStartTime = mktime($a_defaultStartTimeItems[0], $a_defaultStartTimeItems[1], 0, date("m",$row['timeStamp']), date("d",$row['timeStamp']), date("Y",$row['timeStamp']) );
                $a_defaultEndTimeItems = preg_split("/:/",$tfRow['defaultEndTime']);
                $s_defaultEndTime = mktime($a_defaultEndTimeItems[0], $a_defaultEndTimeItems[1], 0, date("m",$row['timeStamp']), date("d",$row['timeStamp']), date("Y",$row['timeStamp']) );
                if ( $row['timeStamp'] > $s_defaultStartTime - 1 ) {
                    if ( $row['timeStamp'] < $s_defaultEndTime + 1 ) {
                        $skipData = FALSE;
                    }
                }
                if ( $row['timeStamp'] + 1 > $s_defaultEndTime ) {
                    if ( $row['timeStamp'] < $s_defaultEndTime + 1 ) {
                        $skipData = TRUE;
                    }
                }
            } else {
                $skipData = TRUE;
            }
        }
        $stmt_slaTime->closeCursor();

        // Check for SLA TimeFrame Items
        $s_timeframes_sql = "
            SELECT
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`startTimeStamp`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`endTimeStamp`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`slaEnabled`
            FROM
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`
            WHERE
              `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`slaTimeFrameId`=".$slaTimeFrame."
        ";
        $stmt_slaTime = $db->prepare( $s_timeframes_sql);
        $stmt_slaTime->execute();
        $prevSkipVar = $skipData;
        while ($tfRow = $stmt_slaTime->fetch( ) ) {
            if ( $tfRow['slaEnabled'] == 'on' ) {
                // SLA Relevant Data needs to be included
                if ( $row['timeStamp'] > $tfRow['startTimeStamp'] - 1 ) {
                    if ( $row['timeStamp'] < ( $tfRow['endTimeStamp'] + 1 ) )  {
                        $skipData = FALSE;
                    }
                } 
            }
            else {
                // NON-SLA Relevant Data needs to be skipped
                if ( $row['timeStamp'] > $tfRow['startTimeStamp'] - 1 ) {
                    if ( $row['timeStamp'] < ( $tfRow['endTimeStamp'] + 1 ) )  {
                        $skipData = TRUE;
                    }
                } 
            }
        }
        $stmt_slaTime->closeCursor();

        if ( $skipData == FALSE ) {
            if ( $row['total_polls'] > 0 ) {
                $data[] = 100 - ( 100 * $row['failed_polls'] ) / $row['total_polls'];
            }
            else {
                $data[] = NoValue;
            }
            $labels[] = chartTime2($row['timeStamp']);
            $viewPortEndDate = chartTime2($row['timeStamp']);
        }
        else {
            $data[] = NoValue;
            $labels[] = chartTime2($row['timeStamp']);
            $viewPortEndDate = chartTime2($row['timeStamp']);
        }
        
    }
    $stmt->closeCursor();

    $viewPortStartDate = chartTime2( time() );
    if (count( $labels ) > 0) {
        $viewPortStartDate = $labels[0];
    }

    # Create a XYChart object of size 400 x 270 pixels
    if ( $chartHeight < 10 ) { $chartHeight = 270; }
    if ( $chartWidth < 10 ) { $chartWidth = 800; }
    $c = new XYChart($chartWidth, $chartHeight);
    
    $c->setBackground(0xf0f0f0,0xD0D0D0,1);
    
    # Set the plotarea at (80, 25) and of size 300 x 200 pixels. Use alternate color
    # background (0xeeeeee) and (0xffffff). Set border and grid colors to grey
    # (0xc0c0c0).
    $c->setPlotArea(65, 25, $chartWidth - 105, $chartHeight -70, 0xeeeeee, 0xffffff, 0xc0c0c0, 0xc0c0c0, 0xc0c0c0);
    
    $hostDescription = getDBValue('description','select description from host where id='.$deviceId.';');
    $hostIp = getDBValue('hostname','select hostname from host where id='.$deviceId.';');
    $hostSLA = getDBValue('nmid_host_sla','select nmid_host_sla from host where id='.$deviceId.';');
    
    # Add a title to the chart using 14 pts Times Bold Italic font
    $c->addTitle("Availability Report - ".$hostDescription.' [ '.$hostIp.' ]', readConfigOption('nmid_avail_font'), 14);
    
    # Add a title to the y axis
    $c->yAxis->setTitle("Availability (%)");
    
    # Set the y axis width to 2 pixels
    $c->yAxis->setWidth(2);
    
    # Set the labels on the x axis.
    $c->xAxis->setLabels2($labels,"{value|mm/dd/yy hh:nn}");
    
    # Display 1 out of 3 labels on the x-axis. Show minor ticks for remaining labels.
    # Set x-axis date scale to the view port date range. ChartDirector auto-scaling will
    # automatically determine the ticks on the axis.
    $c->xAxis->setDateScale( chartTime2($startTime), chartTime2($endTime) );

    #
    # In the current demo, the x-axis range can be from a few years to a few days. We can let
    # ChartDirector auto-determine the date/time format. However, for more beautiful formatting, we
    # set up several label formats to be applied at different conditions.
    #

    # If all ticks are yearly aligned, then we use "yyyy" as the label format.
    $c->xAxis->setFormatCondition("align", 360 * 86400);
    $c->xAxis->setLabelFormat("{value|yyyy}");

    # If all ticks are monthly aligned, then we use "mmm yyyy" in bold font as the first label of a
    # year, and "mmm" for other labels.
    $c->xAxis->setFormatCondition("align", 30 * 86400);
    $c->xAxis->setMultiFormat(StartOfYearFilter(), "<*font=bold*>{value|mmm yyyy}", AllPassFilter(),
        "{value|mmm}");

    # If all ticks are daily algined, then we use "mmm dd<*br*>yyyy" in bold font as the first label
    # of a year, and "mmm dd" in bold font as the first label of a month, and "dd" for other labels.
    $c->xAxis->setFormatCondition("align", 86400);
    $c->xAxis->setMultiFormat(StartOfYearFilter(),
        "<*block,halign=left*><*font=bold*>{value|mmm dd<*br*>yyyy}", StartOfMonthFilter(),
        "<*font=bold*>{value|mmm dd}");
    $c->xAxis->setMultiFormat2(AllPassFilter(), "{value|dd}");

    # For all other cases (sub-daily ticks), use "hh:nn<*br*>mmm dd" for the first label of a day,
    # and "hh:nn" for other labels.
    $c->xAxis->setFormatCondition("else");
    $c->xAxis->setMultiFormat(StartOfDayFilter(), "<*font=bold*>{value|hh:nn<*br*>mmm dd}",
        AllPassFilter(), "{value|hh:nn}");

    
    # Set the x axis width to 2 pixels
    $c->xAxis->setWidth(2);
    
    $slaString = 'Global';
    $slaValue = $globalSLA;
    if ( $hostSLA > 0 ) {
        $slaValue = $hostSLA;
        $slaString = 'Host';
    }
    
    # Add a horizontal red (0x800080) mark line at y = 80
    $yMark = $c->yAxis->addMark( $slaValue , 0xff0000, $slaString . " SLA Threshold");
    
    
    # Set the mark line width to 2 pixels
    $yMark->setLineWidth(2);
    
    # Put the mark label at the top center of the mark line
    $yMark->setAlignment(TopCenter);

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
            `plugin_nmidCreatePDF_Availability_Change_Table`.`deviceId` = ".$deviceId."
        ORDER BY `startTimeStamp`";

    $stmt = $db->prepare( $sql);
    $stmt->execute();

    while ( $row = $stmt->fetch( ) ) {
        $color = 0x33ffcc66;
        if ( $row['decreaseAvailability'] == 0 ) {
            $color = 0xdd33ff33;
        }

        # Add an orange (0xffcc66) zone from x = 18 to x = 20
        $c->xAxis->addZone(chartTime2( $row['startTimeStamp'] ), chartTime2( $row['endTimeStamp'] ), $color);    
    
        # Add a vertical brown (0x995500) mark line at x = 18
        $xMark1 = $c->xAxis->addMark(chartTime2( $row['startTimeStamp'] ), 0x995500, $row['shortDescription']." ".$startTag);
    
        # Set the mark line width to 2 pixels
        $xMark1->setLineWidth(2);
    
        # Put the mark label at the left of the mark line
        $xMark1->setAlignment(Left);
    
        # Rotate the mark label by 90 degrees so it draws vertically
        $xMark1->setFontAngle(90);
    
        # Add a vertical brown (0x995500) mark line at x = 20
        $xMark2 = $c->xAxis->addMark(chartTime2( $row['endTimeStamp'] ), 0x995500, $row['shortDescription']." ".$endTag);
    
        # Set the mark line width to 2 pixels
        $xMark2->setLineWidth(2);
    
        # Put the mark label at the right of the mark line
        $xMark2->setAlignment(Right);
    
        # Rotate the mark label by 90 degrees so it draws vertically
        $xMark2->setFontAngle(90);
    }
    $stmt->closeCursor();

      # Add a line layer for the lines, using a line width of 2 pixels
    $lineLayerObj = $c->addAreaLayer($data, 0x00cc00);       
    
    # Now we add the 3 data series to a line layer, using the color red (ff0000), green (00cc00) and
    # blue (0000ff)
    $lineLayerObj->setXData($labels);
    $lineLayerObj->addDataSet($data, $c->yZoneColor($globalSLA, 0x44ff0000, 0x4400ff00), "Availability");
    if ( $nmid_avail_offSLATransparent ) {
        $lineLayerObj->setGapColor( 0xff000000 );
    } else {
        $lineLayerObj->setGapColor( 0xee222222 );
    }
   
    
    $c->yAxis->setLinearScale(0, 110, 20, 10);
    
    # Output the chart
    header("Content-type: image/png");
    print($c->makeChart2(PNG));
?>