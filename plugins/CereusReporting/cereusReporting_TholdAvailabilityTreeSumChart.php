<?php
	/*******************************************************************************

	 File:         $Id: cereusReporting_TholdAvailabilityTreeSumChart.php,v bf53c8f13e3c 2017/01/09 10:01:05 thurban $
	 Modified_On:  $Date: 2017/01/09 10:01:05 $
	 Modified_By:  $Author: thurban $ 
	 Language:     Perl
	 Encoding:     UTF-8
	 Status:       -
	 License:      Commercial
	 Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 
	*******************************************************************************/
	

    $dir = dirname(__FILE__);
    $mainDir = preg_replace("@plugins.CereusReporting@","",$dir);

    chdir($dir);
    require_once("./include/phpchartdir.php");
    require_once("CereusReporting_ChartDirector.php");
	include_once('./modules/availability/polling_functions.php');
    
    require_once('./functions.php');  // Support functions
    chdir($mainDir);
    //include("./include/auth.php");
    include_once("./include/global.php");
    include_once("./lib/rrd.php");
    include_once('./include/config.php');
    chdir($dir);

    $globalSLA = readConfigOption('nmid_avail_globalSla');
    $hostSLA = 0;
    $startTag = readConfigOption('nmid_avail_startTag');
    $endTag = readConfigOption('nmid_avail_endTag');
    $chartWidth = readConfigOption('nmid_avail_chartWidth');
    $chartHeight = readConfigOption('nmid_avail_chartHeight');
    $slaTimeFrame = readConfigOption('nmid_avail_globalSlaTimeFrame');
    $nmid_avail_offSLATransparent = readConfigOption('nmid_avail_offSLATransparent');
    $modeTimeFrame = array();
    $serverCount = 0;
	$globalAvailability = 0;
    //$addDataTable = readConfigOption('nmid_avail_addTable');

    $startTime = -1;
    $endTime = -1;
    $chartMode = 'past';
    $dataMode = 'p';
    $hoursStep = -1;
    $leafid = -1;
    $tree_id = 1;
    $spaceLeft = 60;
    $outageExists = FALSE;
    /* loop through each of the selected tasks and delete them*/
    foreach ( $_REQUEST as $var => $val) {
        if ($var == 'tholdId' ) {
            $tholdId = $val;
        }
        elseif ($var == 'startTime' ) {
            $startTime = $val;
        }
        elseif ($var == 'endTime' ) {
            $endTime = $val;
        }
    }
    //$deviceId = getTholdHost($tholdId);
    $endTimeSpan =  getPollerInterval($tholdId);

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
	
	if ( $leafid > 0 )  {
        addLeafToChart( $leafid, $tree_id );
        $chartLabel = getDBValue( 'title','select title from graph_tree_items where id='.$leafid.';' );
    }
    else {
        addTreeToChart( $tree_id );
        $chartLabel = getDBValue( 'name','select name from graph_tree where id='.$tree_id.';' );
    }
    if ( $serverCount > 0 ) {
        $globalAvailability = $globalAvailability / $serverCount;
    }
    $chartLabel = $chartLabel . ' ( '.sprintf("%0.2f", $globalAvailability ).' )';


    $chartHeight = 30;
    $chartWidth = 800;
    
	// addLeafToChart( $leafid, $tree_id );
	$title = db_fetch_cell("SELECT name FROM thold_data where id = ".$tholdId);
	addTholdToChart($tholdId, '');
    $chartHeight = (100);
    
    # Create a XYChart object of size 620 x 325 pixels. Set background color to light red
    # (0xffcccc), with 1 pixel 3D border effect.
    # Create a XYChart object of size 400 x 270 pixels
    if ( $chartHeight < 10 ) { $chartHeight = 270; }
    if ( $chartWidth < 10 ) { $chartWidth = 800; }
    
    $c = new XYChart($chartWidth, $chartHeight );
    
    $inSLAColor = $c->patternColor(array(0xccffffff, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff, 0xccff0000, 0xccffffff, 0xccffffff, 0xccffffff), 4);
    $outSLAColor = $c->patternColor(array(0xccffffff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xccffffff, 0xccffffff, 0xcc0000ff, 0xffffff, 0xccffffff, 0xccffffff), 4);
    $inSLATask = $c->patternColor(array(0xffffff, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xffffff), 4);
    $outSLATask = $c->patternColor(array(0xffffff, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0x00ff00, 0xffffff, 0xffffff, 0xffffff), 4);
    

    # Add a title to the chart using 15 points Times Bold Itatic font, with white
    # (ffffff) text on a dark red (800000) background
    //$textBoxObj = $c->addTitle($title, readConfigOption('nmid_avail_font'), 12);
    $textBoxObj = $c->addTitle($title, "arial.ttf", 10);
	//$textBoxObj->setBackground(0xcccccc, 0);

    # Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative
    # white/grey background. Enable both horizontal and vertical grids by setting their
    # colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2
    # pixels in width
    $plotAreaBgColor = $c->linearGradientColor(0, 0, 0, $c->getHeight() - 40, 0xaaccff,
        0xf9fcff);
    $c->setPlotArea($spaceLeft, 40, $c->getWidth() - $spaceLeft - 40, $c->getHeight() - 60, $plotAreaBgColor,
        -1, -1, 0xffffff);
    
    # swap the x and y axes to create a horziontal box-whisker chart
    $c->swapXY();
    
    # Set the y-axis scale to be date scale from Aug 16, 2004 to Nov 22, 2004, with ticks
    # every 7 days (1 week)
    $viewPortStartDate = chartTime2( $startTime );
    $viewPortEndDate = chartTime2( $endTime );
    $c->xAxis->setDateScale($viewPortStartDate, $viewPortEndDate);
   
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
    
    # Set the y-axis to shown on the top (right + swapXY = top)
    $c->setYAxisOnRight();
    
    # Set the labels on the x axis
    $c->xAxis->setLabels($labels);
    
    # Reverse the x-axis scale so that it points downwards.
    $c->xAxis->setReverse();
    
    //addTreeExceptionsToChart( $tree_id, $c);
    
 
    # Add a multi-color box-whisker layer to represent the gantt bars
    $layer = $c->addBoxWhiskerLayer2($startDate, $endDate, null, null, null, $colors);
    $layer->setXData($taskNo);
    $layer->setBorderColor(SameAsMainColor);
    $layer->setDataWidth(20);


    # Add a legend box at (140, 265) - bottom of the plot area. Use 8 pts Arial Bold as
    # the font with auto-grid layout. Set the width to the same width as the plot area.
    # Set the backgorund to grey (dddddd).
    #$legendBox = $c->addLegend2(100, 265, AutoGrid, "arialbd.ttf", 8);
    #$legendBox->setWidth(461);
    #$legendBox->setBackground(0xdddddd);
    
    # The keys for the scatter layers (milestone symbols) will automatically be added to
    # the legend box. We just need to add keys to show the meanings of the bar colors.
    #$legendBox->addKey("Server 1", 0x00cc00);
    
    //if ( $outageExists ) {
    //    $b = $c->addLegend2(180, $c->getHeight() - 90, AutoGrid, readConfigOption('nmid_avail_font'), 8);
    //}
    
    # Output the chart
    header("Content-type: image/png");
    print($c->makeChart2(PNG));
    
function addTholdToChart( $tholdId = 0, $title = '') {
//	global $startTime, $endTime, $labels, $taskNo, $startDate, $endDate, $colors;

    global $dataMode, $labels, $taskNo, $startDate, $endDate, $colors, $serverCount, $startTime, $endTime, $globalSLA,
    $startDateInSLA, $startDateOutSLA, $endDateInSLA, $endDateOutSLA, $outSLAColorArray, $inSLAColorArray,
    $outTaskNo, $inTaskNo, $endTimeSpan, $deviceToTask, $slaTime_id, $modeTimeFrame, $outageExists;

    // Initial Availability is a transparent light green 
    $taskNo[] = $serverCount;
    $startDate[] = chartTime2( $startTime );
    $endDate[] = chartTime2( $endTime );
    $colors[] = 0x00cc00;
       
	$startTimeTholdBreach = 0;
	$endTimeTholdBreach = 0;
	$isInBreach = FALSE;
	
	$a_breaches = db_fetch_assoc("
		SELECT
			time,
			status
		FROM
			plugin_thold_log
		WHERE
			`threshold_id` = ".$tholdId."
		AND
			`status`>3
		AND
			time <= ".$endTime."
		AND
			time >= ".$startTime."
		ORDER BY
			`time`");
	
	// Calculate total failed polls time
	foreach ($a_breaches as $s_breach) {
		if ($s_breach['status'] == THOLD_STATUS_ALERT ) {
			$isInBreach = TRUE;
			$startTimeTholdBreach = $s_breach['time'];
		}
		elseif ($s_breach['status'] == THOLD_STATUS_NORMAL ) {
			if ( $isInBreach ) {
				$isInBreach = FALSE;
				$endTimeTholdBreach = $s_breach['time'];

				$taskNo[] = $serverCount;
				$startDate[] = chartTime2( $startTimeTholdBreach );
				$endDate[] = chartTime2( $endTimeTholdBreach );
				$colors[] = 0xcc0000;
				
			} 
		}
	}

    $availability = getTholdAvailability($tholdId,$startTime,$endTime);

    $percentage = 0;
	$percentage = sprintf("%0.2f", $availability );
    if ( $percentage == 100 ) {
        if ( $availability < 100 ) {
            $percentage = '99.99';
        }
    }
  
    $fontColor = 'cc0000';
    //if ( $percentage >= $slaValue ) {
    //    $fontColor = '00cc00';
    //}
    //$labels[] = '<*block,halign=right*>'. $title. ' <*br*><*block,halign=right*>( <*color='.$fontColor.'*>'.$percentage.'%<*color=000000*>)<*/*><*/*>';
	$labels[] = '<*block,halign=right*>( <*color='.$fontColor.'*>'.$percentage.'%<*color=000000*>)<*/*>';
}


?>