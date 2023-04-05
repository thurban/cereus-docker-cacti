<?php
/*******************************************************************************

 File:         $Id: top10_monthly_traffic_stats_pie.php,v afc11c4d72ad 2016/07/14 09:30:20 thurban $
 Modified_On:  $Date: 2016/07/14 09:30:20 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/

    $dir = dirname(__FILE__);
	$mainDir = preg_replace("@plugins.CereusReporting.dsstats_reports@","",$dir);


    chdir($dir);
	require_once( "../include/phpchartdir.php" );
    require_once("../CereusReporting_ChartDirector.php");
    require_once('../functions.php');  // Support functions
    chdir($mainDir);
    //include("./include/auth.php");
    include("./include/global.php");
    include_once("./lib/rrd.php");
    include_once('./include/config.php');
    chdir($dir);

    /* Create Connection to the DB */
    $link = mysql_connect("$database_hostname:$database_port", $database_username, $database_password);
    
    mysql_select_db($database_default);
    $data0 = array();
    $data1 = array();
    $data2 = array();
    $data3 = array();
    $data4 = array();
    $labels = array();

    # The data for the bar chart
    $sql = "
        SELECT
            a.rrd_name,
            a.local_data_id as local_data_id,
            a.average as a_average,
            b.average as b_average,
            b.rrd_name,
            (a.average + b.average) as sum
        FROM
            data_source_stats_monthly a
        INNER JOIN
            data_source_stats_monthly b
        ON
            a.local_data_id=b.local_data_id
        WHERE
            a.rrd_name = 'traffic_in'
        AND
            b.rrd_name = 'traffic_out'
        ORDER BY
            (a.average + b.average) desc
        LIMIT 10;
        ";
    $result = mysql_query( $sql );
    while ( $row = mysql_fetch_assoc( $result ) ) {
        $labelst[ 'id_'. $row['local_data_id'] ] = getDBValue('name_cache',"select name_cache from data_template_data where local_data_id=".$row['local_data_id'].";");
        $hdd_used = round( $row['a_average'] );
        $hdd_free = round( $row['b_average'] );
        $data0[ 'id_'. $row['local_data_id'] ] = $hdd_used;
        $data1[ 'id_'. $row['local_data_id']  ] = $hdd_free;
        if ( ( $hdd_used + $hdd_free ) > 0 ) {
                $data2[ 'id_'. $row['local_data_id'] ] = $hdd_used + $hdd_free ;
        }
    }
    asort( $data2, SORT_NUMERIC );

    $data = array();
    foreach ($data2 as $key => $value) {
        $data3[] = $data0[ $key ];
        $data4[] = $data1[ $key ];
        $data[] = $data0[ $key ] + $data1[ $key ];
        //$label_tmp = preg_replace("/.*- Traffic /","dummy",$labelst[ $key ] );
        $labels[] = $labelst[ $key ];
    }


    # Create a XYChart object of size 500 x 320 pixels
    $c = new PieChart(800, 400);
    $c->setBackground($c->linearGradientColor(0, 0, 0, 100, 0x99ccff, 0xffffff), 0x888888 );
    $c->setRoundedFrame();
    $c->setDropShadow();


   
    # The colors to use for the sectors
    $colors = array(0x66aaee, 0xeebb22, 0xbbbbbb, 0x8844ff, 0xdd2222, 0x009900 );

    #  Add a title using 18 pts Times New Roman Bold Italic font. Add 16 pixels top margin
    # to the title.
    
    $textBoxObj = $c->addTitle("Top 10 - Average monthly traffic", "timesbi.ttf", 18);
    $textBoxObj->setMargin2(0, 0, 16, 0);
    
    # Set the center of the pie at (160, 165) and the radius to 110 pixels
    $c->setPieSize(200, 160, 145);
    
    # Draw the pie in 3D with a pie thickness of 25 pixels
    $c->set3D(25); 

    # Set the pie data and the pie labels
    $c->setData($data, $labels); 


    # Set the sector colors
    $c->setColors2(DataColor, $colors);
    
    # Use local gradient shading for the sectors
    $c->setSectorStyle(LocalGradientShading);
    
    # Use the side label layout method, with the labels positioned 16 pixels from the pie
    # bounding box
    $c->setLabelLayout(SideLayout, 16);
    
    # Show only the sector number as the sector label
    $c->setLabelFormat("{={sector}+1}");

    # Set the sector label style to Arial Bold 10pt, with a dark grey (444444) border
    $textBoxObj = $c->setLabelStyle("arialbd.ttf", 10);
    $textBoxObj->setBackground(Transparent, 0x444444);
    
    # Add a legend box, with the center of the left side anchored at (330, 175), and
    # using 10 pts Arial Bold Italic font
    $b = $c->addLegend(400, 175, true, "arialbi.ttf", 10);
    $b->setAlignment(Left);
    
    # Set the legend box border to dark grey (444444), and with rounded conerns
    $b->setBackground(Transparent, 0x444444);
    $b->setRoundedCorners();
    
    # Set the legend box margin to 16 pixels, and the extra line spacing between the
    # legend entries as 5 pixels
    $b->setMargin(16);
    $b->setKeySpacing(0, 5);
    
    # Set the legend box icon to have no border (border color same as fill color)
    $b->setKeyBorder(SameAsMainColor);
    
    # Set the legend text to show the sector number, followed by a 120 pixels wide block
    # showing the sector label, and a 40 pixels wide block showing the percentage
    $b->setText( "<*block,valign=top*>{={sector}+1}.<*advanceTo=22*><*block,width=250*>{label}". "<*/*><*block,width=40,halign=right*>{percent}<*/*>%"); 

    // Close the database connection
    mysql_close($link);

    # Output the chart
    header("Content-type: image/png");
    print($c->makeChart2(PNG));
?>