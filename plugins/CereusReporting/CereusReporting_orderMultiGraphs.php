<?php
/*******************************************************************************

 File:         $Id: CereusReporting_orderMultiGraphs.php,v 49ca56948f57 2016/06/10 05:04:50 thurban $
 Modified_On:  $Date: 2016/06/10 05:04:50 $
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
if (!isset($_REQUEST["multi_id"])) { $_REQUEST["multi_id"] = ""; }
if (!isset($_REQUEST["report_id"])) { $_REQUEST["report_id"] = ""; }
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

input_validate_input_number( $_REQUEST["multi_id"] );
input_validate_input_number( $_REQUEST["report_id"] );

switch ($_REQUEST["action"]) {
    case 'item_movedown':
		form_move($_REQUEST["multi_id"],$_REQUEST["report_id"], +1);
		break;
	case 'item_moveup':
		form_move($_REQUEST["multi_id"],$_REQUEST["report_id"], -1);
		break;
	default:
		break;
}

header('Location: '.$config['url_path'].'plugins/CereusReporting/CereusReporting_addReport.php?action=update&ReportType=3&ReportId='.$_REQUEST["report_id"] );


// function to add a local_graph_id to a report
function form_move( $multi_id, $report_id, $graph_move ) {
	$orig_order_number = db_fetch_cell("select `order` from `plugin_nmidCreatePDF_MultiGraphReports` where `Id`=" . $multi_id . " AND `ReportId`=".$report_id);
	$max_order_number = db_fetch_cell("select MAX(`order`) from `plugin_nmidCreatePDF_MultiGraphReports` where `ReportId`=".$report_id);
	if ( ($orig_order_number + $graph_move > 0) && ( $orig_order_number + $graph_move  <= $max_order_number ) ) {
		$order_number = $orig_order_number + $graph_move;
		$previous_element = db_fetch_cell("select `Id` from `plugin_nmidCreatePDF_MultiGraphReports` where `order`=" . $order_number . " AND `ReportId`=".$report_id);

		db_execute("UPDATE `plugin_nmidCreatePDF_MultiGraphReports` SET `order`='".$orig_order_number."' WHERE `id`=" . $previous_element);
		db_execute("UPDATE `plugin_nmidCreatePDF_MultiGraphReports` SET `order`='".$order_number."' WHERE `id`=" . $multi_id);
	}
}

?>
