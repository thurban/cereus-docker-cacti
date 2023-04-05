<?php
/*******************************************************************************

 File:         $Id: CereusReporting_orderDSStatsGraphs.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 Modified_On:  $Date: 2020/12/10 07:06:31 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/
	include_once( 'functions.php' );
$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
chdir($mainDir);
include_once("./include/auth.php");
$_SESSION['custom']=false;


/* set default action */
if (!isset($_REQUEST["dsstats_id"])) { $_REQUEST["dsstats_id"] = ""; }
if (!isset($_REQUEST["report_id"])) { $_REQUEST["report_id"] = ""; }
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

input_validate_input_number( $_REQUEST["dsstats_id"] );
input_validate_input_number( $_REQUEST["report_id"] );

switch ($_REQUEST["action"]) {
    case 'item_movedown':
		form_move($_REQUEST["dsstats_id"],$_REQUEST["report_id"], +1);
		break;
	case 'item_moveup':
		form_move($_REQUEST["dsstats_id"],$_REQUEST["report_id"], -1);
		break;
	default:
		break;
}

header('Location: '.$config['url_path'].'plugins/CereusReporting/CereusReporting_addReport.php?action=update&ReportId='.$_REQUEST["report_id"] );


// function to add a local_graph_id to a report
function form_move( $dsstats_id, $report_id, $graph_move ) {
	$orig_order_number = db_fetch_cell("select `order` from `plugin_nmidCreatePDF_DSStatsReports` where `Id`=" . $dsstats_id . " AND `ReportId`=".$report_id);
	$max_order_number = db_fetch_cell("select MAX(`order`) from `plugin_nmidCreatePDF_DSStatsReports` where `ReportId`=".$report_id);
	if ( ($orig_order_number + $graph_move > 0) && ( $orig_order_number + $graph_move  <= $max_order_number ) ) {
		$order_number = $orig_order_number + $graph_move;
		$previous_element = db_fetch_cell("select `Id` from `plugin_nmidCreatePDF_DSStatsReports` where `order`=" . $order_number . " AND `ReportId`=".$report_id);

		db_execute("UPDATE `plugin_nmidCreatePDF_DSStatsReports` SET `order`='".$orig_order_number."' WHERE `id`=" . $previous_element);
		db_execute("UPDATE `plugin_nmidCreatePDF_DSStatsReports` SET `order`='".$order_number."' WHERE `id`=" . $dsstats_id);
	}
}

?>
