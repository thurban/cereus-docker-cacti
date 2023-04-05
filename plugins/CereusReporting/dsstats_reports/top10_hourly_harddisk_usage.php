<?php
	/*******************************************************************************

	File:         $Id: top10_hourly_harddisk_usage.php,v afc11c4d72ad 2016/07/14 09:30:20 thurban $
	Modified_On:  $Date: 2016/07/14 09:30:20 $
	Modified_By:  $Author: thurban $
	Language:     Perl
	Encoding:     UTF-8
	Status:       -
	License:      Commercial
	Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	$dir = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting.dsstats_reports@", "", $dir );

	chdir( $dir );
	require_once( "../include/phpchartdir.php" );
	require_once( "../CereusReporting_ChartDirector.php" );
	require_once( '../functions.php' ); // Support functions
	chdir( $mainDir );
	//include("./include/auth.php");
	include( "./include/global.php" );
	include_once( "./lib/rrd.php" );
	include_once( './include/config.php' );
	chdir( $dir );

	/* Create Connection to the DB */
	$link = mysql_connect( "$database_hostname:$database_port", $database_username, $database_password );

	mysql_select_db( $database_default );

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
            data_source_stats_hourly a
        INNER JOIN
            data_source_stats_hourly b
        ON
            a.local_data_id=b.local_data_id
        WHERE
            a.rrd_name = 'hdd_used'
        AND
            b.rrd_name = 'hdd_free'
        ORDER BY
            ( ( 100* b.average ) / ( a.average + b.average) ) desc
        LIMIT 10;
        ";
	$result = mysql_query( $sql );
	while ( $row = mysql_fetch_assoc( $result ) ) {
		$labelst[ 'id_' . $row[ 'local_data_id' ] ] = getDBValue( 'name_cache', "select name_cache from data_template_data where local_data_id=" . $row[ 'local_data_id' ] . ";" );
		$hdd_used                                   = round( $row[ 'a_average' ] / ( 1024 * 1024 ) );
		$hdd_free                                   = round( $row[ 'b_average' ] / ( 1024 * 1024 ) );
		$data0[ 'id_' . $row[ 'local_data_id' ] ]   = $hdd_used;
		$data1[ 'id_' . $row[ 'local_data_id' ] ]   = $hdd_free;
		if ( ( $hdd_used + $hdd_free ) > 0 ) {
			$data2[ 'id_' . $row[ 'local_data_id' ] ] = round( ( 100 * $hdd_free ) / ( $hdd_used + $hdd_free ) );
		}
	}
	arsort( $data2, SORT_NUMERIC );

	foreach ( $data2 as $key => $value ) {
		$data3[ ] = $data0[ $key ];
		$data4[ ] = $data1[ $key ];
		//$label_tmp = preg_replace("/.*- Traffic /","dummy",$labelst[ $key ] );
		$labels[ ] = $labelst[ $key ];
	}

	# Create a XYChart object of size 500 x 320 pixels
	$c = new XYChart( 800, 300 );
	$c->setBackground( $c->linearGradientColor( 0, 0, 0, $c->getHeight() / 2, 0xe8f0f8, 0xaaccff ), 0x88aaee );
	$c->setRoundedFrame();
	$c->setDropShadow();

	# Set the plotarea at (100, 40) and of size 280 x 240 pixels

	$c->setPlotArea( 200, 40, 480, 240 );
	$c->swapXY( TRUE );
	# Add a legend box at (400, 100)
	$legendBox = $c->addLegend( 700, 10 );
	$legendBox->setBackground( Transparent, Transparent );


	# Add a title to the chart using 14 points Times Bold Itatic font

	$c->addTitle( "Top 10 Harddisk Usage", "timesbi.ttf", 14 );

	# Set the labels on the x axis
	$c->xAxis->setLabels( $labels );
	$c->yAxis->setLabelFormat( "{value} MBytes" );

	# Add a stacked bar layer and set the layer 3D depth to 8 pixels
	$layer = $c->addBarLayer2( Stack, 8 );

	# Add the three data sets to the bar layer
	$layer->addDataSet( $data3, 0xff8080, "Used" );
	$layer->addDataSet( $data4, 0x80ff80, "Free" );

	# Enable bar label for the whole bar
	$layer->setAggregateLabelStyle();
	$layer->setAggregateLabelFormat( "{value} MBytes" );

	# Enable bar label for each segment of the stacked bar
	$layer->setDataLabelStyle();
	$layer->setDataLabelFormat( "{value} MBytes" );

	$layer->setBorderColor( Transparent, softLighting( Top ) );

	// Close the database connection
	mysql_close( $link );

	# Output the chart
	header( "Content-type: image/png" );
	print( $c->makeChart2( PNG ) );
?> 
