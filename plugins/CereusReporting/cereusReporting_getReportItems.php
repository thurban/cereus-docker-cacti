<?php
/*******************************************************************************

 File:         $Id: cereusReporting_getReportItems.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
 Modified_On:  $Date: 2017/07/18 06:44:34 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/
include_once('functions.php');
	include_once( './include/functions_compat.php' );

$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
chdir($mainDir);
include_once("./include/auth.php");

input_validate_input_number( $_REQUEST["ReportId"] );


/* set default action */
if ( !isset($_REQUEST["ReportId"]) ) { $_REQUEST["ReportId"] = ""; }
$reportId = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );

// Add DSStats Graph to Report
printReportItems( $reportId );

// function to add a local_graph_id to a report
function addGraphToReport( $dsstats_name, $reportId ) {
	$current_order_max = db_fetch_cell("select MAX(`order`) from plugin_nmidCreatePDF_DSStatsReports where ReportId=".$reportId);
	if (!(isset($current_order_max))) {
		$current_order_max = 0;
	}
	$current_order_max = $current_order_max + 1;
	db_execute("INSERT INTO `plugin_nmidCreatePDF_DSStatsReports` (`ReportId`, `DSStatsGraph`,`Description`,`order`) VALUES ('$reportId', '$dsstats_name','','$current_order_max')");
}


?>
