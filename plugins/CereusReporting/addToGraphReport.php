<?php
	/*******************************************************************************
	 *
	 * File:         $Id: addToGraphReport.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
	 * Modified_On:  $Date: 2017/07/18 06:44:34 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	include_once( 'functions.php' );
	if ( function_exists('top_header')) {
		include_once( __DIR__.'/functions_cacti_1.0.0.php' );
	} else {
		include_once( __DIR__.'/functions_cacti_0.8.php' );
	}
	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );
	include_once( "./include/auth.php" );
	include_once( "./include/global.php" );

	global $config;

	$leafid  = 0;
	$tree_id = 0;

	// Sanitize strings
	// $dsstats_name   = filter_var( $_REQUEST[ "dsstats_name" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$reportId       = filter_var( $_REQUEST[ "report_id" ], FILTER_SANITIZE_NUMBER_INT );
	$leafid         = filter_var( $_REQUEST[ "leaf_id" ], FILTER_SANITIZE_NUMBER_INT );
	$tree_id        = filter_var( $_REQUEST[ "tree_id" ], FILTER_SANITIZE_NUMBER_INT );
	if (isset($_REQUEST[ "nmid_pdfAddSubLeafs" ])) {
		$cgiAddSubLeafs = TRUE;
	} else {
		$cgiAddSubLeafs = TRUE;
	}

	$lgiString = preg_replace( "@^;@", "", $_REQUEST[ "lgi_fix" ] );
	$lgiString = preg_replace( "@lgi_@", "", $lgiString );
	if ( preg_match( "/;/", $lgiString ) > 0 ) {
		$lgi = filter_var_array( explode( ";", $lgiString ), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
	}
	else {
		$lgi = filter_var( $lgiString, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
	}

	$isNotLgiPrint = TRUE;
	// Check if the graphs id variable transmitted is  an array and cycle
	// trough the array appropriately
	if ( is_array( $lgi ) ) {
		//echo "Is Array<br>";
		foreach ( $lgi as $lgID ) {
			//echo "Adding $lgID to report $reportId<br>";
			if ( strlen( $lgID ) > 0 ) {
				$isNotLgiPrint = FALSE;
				addGraphToReport( $lgID, $reportId );
			}
		}
	}
	elseif ( $lgi > 0 ) {
		// if it is not an array, we check for a valid local_graph_id (lgi)
		// and add the graph to the report.
		addGraphToReport( $lgi, $reportId );
	}
	elseif ( preg_match( "@^av_([0-9]+)$@", $lgi ) > 0 ) {
		addGraphToReport( $lgi, $reportId );
	}
	elseif ( preg_match( "@^avc_([0-9]+)_([0-9]+)$@", $lgi ) > 0 ) {
		addGraphToReport( $lgi, $reportId );
	}
	elseif ( preg_match( "@^avwsc_([0-9]+)_([0-9]+)$@", $lgi ) > 0 ) {
		addGraphToReport( $lgi, $reportId );
	}
	elseif ( preg_match( "@^sp_([0-9]+)$@", $lgi ) > 0 ) {
		addGraphToReport( $lgi, $reportId );
	}
	else if ( $leafid > 0 ) {
		addLeafToReport( $leafid, $reportId );
	}
	else if ( $tree_id > 0 ) {
		addTreeToReport( $tree_id, $reportId );
	}

	// function to add a local_graph_id to a report
	function addGraphToReport( $lgID, $reportId )
	{
		$current_order_max  = 0;
		$isSmokepingEnabled = readPluginStatus( 'nmidSmokeping' ) || FALSE;
		$reportType         = db_fetch_cell( "SELECT reportType FROM plugin_nmidCreatePDF_Reports WHERE ReportId=" . $reportId );
		if ( $reportType == '3' ) { // Multi Graph
			$current_order_max = db_fetch_cell( "SELECT MAX(`order`) FROM plugin_nmidCreatePDF_MultiGraphReports WHERE ReportId=" . $reportId );
		}
		else { // Graph Report
			$current_order_max = db_fetch_cell( "SELECT MAX(`order`) FROM plugin_nmidCreatePDF_GraphReports WHERE ReportId=" . $reportId );
		}
		if ( !( isset( $current_order_max ) ) ) {
			$current_order_max = 0;
		}
		$current_order_max = $current_order_max + 1;
		if ( $reportType == '3' ) { // Multi Graph
			if ( preg_match( "/sp_([0-9]+)/", $lgID, $matches ) ) {
				$host_id = $matches[ 1 ];
				db_execute( "INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ('$reportId', 'smokeping',$host_id,'$current_order_max')" );
			}
			elseif ( preg_match( "/av_([0-9]+)/", $lgID, $matches ) ) {
				$host_id = $matches[ 1 ];
				db_execute( "INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ('$reportId', 'availability',$host_id,'$current_order_max')" );
			}
			elseif ( preg_match( "/avc_([0-9]+)_([0-9]+)/", $lgID, $matches ) ) {
				$tree_id = $matches[ 1 ];
				$leaf_id = $matches[ 2 ];
				if ( isNumber( $leaf_id ) == FALSE ) {
					$leaf_id = -1;
				}
				db_execute( "INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ('$reportId', 'availability_combined','$tree_id;$leaf_id','$current_order_max')" );
			}
			elseif ( preg_match( "/avwsc_([0-9]+)_([0-9]+)/", $lgID, $matches ) ) {
				$tree_id = $matches[ 1 ];
				$leaf_id = $matches[ 2 ];
				if ( isNumber( $leaf_id ) == FALSE ) {
					$leaf_id = -1;
				}
				db_execute( "INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ('$reportId', 'availability_winservice','$tree_id;$leaf_id','$current_order_max')" );
			}
			elseif ( preg_match( "/wm_([0-9]+)/", $lgID, $matches ) ) {
				$weathermap_id = $matches[ 1 ];
				db_execute( "INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ('$reportId', 'weathermap',$weathermap_id,'$current_order_max')" );
			}
			else {
				db_execute( "INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ('$reportId', 'graph',$lgID,'$current_order_max')" );
			}
		}
		else { // Graph Report
			if ( preg_match( "^sp_([0-9]+)$", $lgID, $matches ) ) {
				$host_id = $matches[ 1 ];
				// ad to report ...
			}
			else {
				db_execute( "INSERT INTO `plugin_nmidCreatePDF_GraphReports` (`ReportId`, `local_graph_id`,`Description`,`order`) VALUES ('$reportId', '$lgID','','$current_order_max')" );
			}
		}
	}

	function addTreeToReport( $tree_id, $reportId )
	{
		$sql    = "SELECT id FROM graph_tree_items WHERE graph_tree_id=? AND order_key LIKE '___000%';";
		$result = cr_db_fetch_assoc_prepared($sql,array($tree_id));
		foreach ( $result as $row ) {
			addLeafToReport( $row[ 'id' ], $reportId );
		}
	}

	function addLeafToReport( $leafid, $reportId )
	{
		global $orderKey, $tree_id, $cgiAddSubLeafs;
		CereusReporting_addLeafToReport( $leafid, $reportId );
	}

	$mainUrl = $_SERVER[ 'PHP_SELF' ];
	$mainUrl = preg_replace( "/plugins\/CereusReporting\/addToGraphReport.php/", "", $mainUrl );
	header( 'Location: ' . $mainUrl . 'graph_view.php?action=tree' );
?>
