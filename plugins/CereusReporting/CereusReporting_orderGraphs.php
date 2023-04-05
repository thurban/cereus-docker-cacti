<?php
/*******************************************************************************
 *
 * File:         $Id: CereusReporting_orderGraphs.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 * Modified_On:  $Date: 2020/12/10 07:06:31 $
 * Modified_By:  $Author: thurban $
 * Language:     Perl
 * Encoding:     UTF-8
 * Status:       -
 * License:      Commercial
 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 *******************************************************************************/
	include_once( 'functions.php' );
$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
chdir($mainDir);
include_once("./include/auth.php");
$_SESSION['custom']=false;

input_validate_input_number( $_REQUEST["graph_id"] );
input_validate_input_number( $_REQUEST["report_id"] );

/* set default action */
if (!isset($_REQUEST["graph_id"])) { $_REQUEST["graph_id"] = ""; }
if (!isset($_REQUEST["report_id"])) { $_REQUEST["report_id"] = ""; }
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) {
    case 'item_movedown':
		form_move($_REQUEST["graph_id"],$_REQUEST["report_id"], +1);
		break;
	case 'item_moveup':
		form_move($_REQUEST["graph_id"],$_REQUEST["report_id"], -1);
		break;
	default:
		break;
}

header('Location: '.$config['url_path'].'plugins/CereusReporting/CereusReporting_addReport.php?action=update&ReportId='.$_REQUEST["report_id"] );


// function to add a local_graph_id to a report
function form_move( $graph_id, $report_id, $graph_move ) {

	$db = DBCxn::get();
	CereusReporting_logger( "Reordering report items", 'debug', 'gui' );

	// $orig_order_number = db_fetch_cell("select `order` from `plugin_nmidCreatePDF_GraphReports` where `Id`=" . $graph_id . " AND `ReportId`=".$report_id);
	$stmt = $db->prepare('select `order` from `plugin_nmidCreatePDF_GraphReports` where `Id` = :graphId AND `ReportId` = :reportId');
	$stmt->bindValue(':graphId',$graph_id);
	$stmt->bindValue(':reportId',$report_id);
	$stmt->execute();
	$orig_order_number = $stmt->fetchColumn();

	// $max_order_number = db_fetch_cell("select MAX(`order`) from `plugin_nmidCreatePDF_GraphReports` where `ReportId`=".$report_id);
	$stmt = $db->prepare('select MAX(`order`) from `plugin_nmidCreatePDF_GraphReports` where `ReportId` = :reportId');
	$stmt->bindValue(':reportId',$report_id);
	$stmt->execute();
	$max_order_number = $stmt->fetchColumn();


	if ( ($orig_order_number + $graph_move > 0) && ( $orig_order_number + $graph_move  <= $max_order_number ) ) {
		$order_number = $orig_order_number + $graph_move;

		// $previous_element = db_fetch_cell("select `Id` from `plugin_nmidCreatePDF_GraphReports` where `order`=" . $order_number . " AND `ReportId`=".$report_id);
		$stmt = $db->prepare('select `Id` from `plugin_nmidCreatePDF_GraphReports` where `order`= :orderNumber AND `ReportId` = :reportId');
		$stmt->bindValue(':orderNumber',$order_number);
		$stmt->bindValue(':reportId',$report_id);
		$stmt->execute();
		$previous_element = $stmt->fetchColumn();

		// db_execute("UPDATE `plugin_nmidCreatePDF_GraphReports` SET `order`='".$orig_order_number."' WHERE `id`=" . $previous_element);
		$q = $db->createUpdateQuery();
		$q->update( 'plugin_nmidCreatePDF_GraphReports' )
			->set( '`order`', $orig_order_number)
			->where( $q->expr->eq('id',$previous_element));
		$stmt = $q->prepare();
		$stmt->execute();

		// db_execute("UPDATE `plugin_nmidCreatePDF_GraphReports` SET `order`='".$order_number."' WHERE `id`=" . $graph_id);
		$q = $db->createUpdateQuery();
		$q->update( 'plugin_nmidCreatePDF_GraphReports' )
			->set( '`order`', $order_number)
			->where( $q->expr->eq('id',$graph_id));
		$stmt = $q->prepare();
		$stmt->execute();
	}
}

?>
