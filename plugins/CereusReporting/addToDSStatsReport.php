<?php
	/*******************************************************************************
	 *
	 * File:         $Id: addToDSStatsReport.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );
	include_once( "./include/auth.php" );

	input_validate_input_number( $_REQUEST[ "ReportId" ] );

	/* set default action */
	if ( !isset( $_REQUEST[ "ReportId" ] ) ) {
		$_REQUEST[ "ReportId" ] = "";
	}
	if ( !isset( $_REQUEST[ "dsstats_name" ] ) ) {
		$_REQUEST[ "dsstats_name" ] = "";
	}

	// Sanitize strings
	$dsstats_name = filter_var( $_REQUEST[ "dsstats_name" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$reportId     = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );

	// Add DSStats Graph to Report
	addGraphToReport( $dsstats_name, $reportId );

	// function to add a local_graph_id to a report
	function addGraphToReport( $dsstats_name, $reportId )
	{
		$current_order_max = db_fetch_cell( "SELECT MAX(`order`) FROM plugin_nmidCreatePDF_DSStatsReports WHERE ReportId=" . $reportId );
		if ( !( isset( $current_order_max ) ) ) {
			$current_order_max = 0;
		}
		$current_order_max = $current_order_max + 1;
		db_execute( "INSERT INTO `plugin_nmidCreatePDF_DSStatsReports` (`ReportId`, `DSStatsGraph`,`Description`,`order`) VALUES ('$reportId', '$dsstats_name','','$current_order_max')" );
	}

	header( 'Location: CereusReporting_addDSStatsReport.php?ReportId=' . $reportId );

?>
