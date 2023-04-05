<?php
/*******************************************************************************

 File:         $Id: CereusReporting_doArchiveReport.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 Modified_On:  $Date: 2020/12/10 07:06:31 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/

	require_once('functions.php');  // Support functions
	include_once( './include/functions_compat.php' );

	$mainDir = preg_replace("@plugins.CereusReporting@","",__DIR__);
	chdir($mainDir);
	//include("./include/auth.php");
	include_once("./include/global.php");
	include_once('./include/config.php');
	chdir(__DIR__);
	$_SESSION['custom']=false;

	/* Create Connection to the DB */
	$db = DBCxn::get();

	// Check for valid input
	if ( isNumber( $_REQUEST["ArchiveId"] == FALSE ) ) {
		header("Location: CereusReporting_Archive.php");
	}

	/* set default action */
	if (!isset($_REQUEST["ArchiveId"])) { $_REQUEST["ArchiveId"] = ""; }
	if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }


	switch ($_REQUEST["action"]) {
		case 'view':
			form_do($_REQUEST["ArchiveId"], 'view');
			break;
		case 'download':
			form_do($_REQUEST["ArchiveId"], 'download');
			break;

		default:
			form_do($_REQUEST["ArchiveId"], 'view');
			break;
	}

	function form_do( $archiveId, $action ) {
		global $colors, $hash_type_names, $db;

		$s_sqlQuery = "
			SELECT
			  `plugin_nmidCreatePDF_Archives`.`ArchiveId`,
			  `plugin_nmidCreatePDF_Archives`.`Name`,
			  `plugin_nmidCreatePDF_Archives`.`Description`,
			  `plugin_nmidCreatePDF_Archives`.`startDate`,
			  `plugin_nmidCreatePDF_Archives`.`endDate`,
			  `plugin_nmidCreatePDF_Archives`.`archiveDate`,
			  `plugin_nmidCreatePDF_Archives`.`filePath`
			FROM
			  `plugin_nmidCreatePDF_Archives`
			WHERE
			  `ArchiveId` = '$archiveId';
		";

		$stmt = $db->prepare( $s_sqlQuery);
		$stmt->execute();
		$row = $stmt->fetch();

		$startTimeFileString = date("dMy-Gi", $row['startDate']);
		$endTimeFileString = date("dMy-Gi", $row['endDate']);
		$filename = $row['Name'] . '_' .
			$startTimeFileString . '_' .
			$endTimeFileString . '.pdf';
		$downloadfile = $row['filePath'];
		if ( preg_match("/.gz$/",$downloadfile) ) {
			$filename = $filename . '.gz';
		} elseif ( preg_match("/.zip$/",$downloadfile) ) {
			$filename = $filename . '.zip';
		}

		$filesize = filesize($downloadfile);

		if ( $action == 'download' ) {
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"$filename\"");
			header("Content-Length: $filesize");
			readfile($downloadfile);
			exit();
		}
		else {
			// not implemented
			header("Location: CereusReporting_Archive.php");
		}
	}

?>
