<?php
	/*******************************************************************************
 * Copyright (c) 2017. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2017.
 *
 * File:         $Id: create-pdf-report.php,v 732f49817120 2017/03/21 11:36:25 thurban $
 * Filename:     create-pdf-report.php
 * LastModified: 21.03.17 07:41
 * Modified_On:  $Date: 2017/03/21 11:36:25 $
 * Modified_By:  $Author: thurban $
 *
 ******************************************************************************/;

	$mainUrl = $_SERVER[ 'PHP_SELF' ];
	$mainUrl = preg_replace( "@plugins/CereusReporting/".__FILE__."@", "", $mainUrl );
	define( 'MPDF_URI', $mainUrl . '/plugins/CereusReporting/ReportEngines/mpdf/' );

	include_once( 'functions.php' ); // Support functions
	include_once( 'reportEngine.php' ); // Report Engine

	chdir( $mainDir );
	include_once( "./include/global.php" );
	include_once( "./lib/rrd.php" );
	//include_once( './include/config.php' );
	chdir( $dir );

	$startTime = $_REQUEST[ 'starttime' ];
	$endTime = $_REQUEST[ 'endtime' ];
	$cgiUserId = -1;
	$tree_id = 2;
	$leafid = 8;
	$lgi = array();
	$cgiAddSubLeafs = FALSE;
	$nmid_send_report_email = FALSE;

	/* loop through each of the selected tasks and delete them*/
    foreach ( $_REQUEST as $var => $val) {
		if ( $var == 'lgi_fix' ) {
			$lgiString = preg_replace( "@^;@", "", $val );
			$lgiString = preg_replace( "@lgi_@", "", $lgiString );
			$lgi       = preg_split( "/;/", $lgiString );
		}
		else if ( $var == 'leaf_id' ) {
			$leafid = $val;
		}
		else if ( $var == 'starttime' ) {
			$startTime = $val;
		}
		else if ( $var == 'endtime' ) {
			$endTime = $val;
		}
		else if ( $var == 'tree_id' ) {
			$tree_id = $val;
		}
		else if ( $var == 'nmid_pdfAddSubLeafs' ) {
			if ( $val == 1 ) {
				$cgiAddSubLeafs = TRUE;
			}
			else {
				$cgiAddSubLeafs = FALSE;
			}
		}
		else if ( $var == 'nmid_send_report_email') {
			$nmid_send_report_email = TRUE;
		}
		else if ( $var == 'user_target_email') {
			$user_target_email = $val;
		}
		else if ( $var == 'user_id' ) {
			$cgiUserId = $val;
		}
	}

	// Get reporting date for the footer
	$dateFormat = readConfigOption( "nmid_pdf_dateformat" );
	$startTimeString = date( "$dateFormat T Y", $startTime );
	$endTimeString = date( "$dateFormat T Y", $endTime );
	$reportDate = date( "$dateFormat T Y", time() );

	CereusReporting_logger( "Creating database connection", 'debug', 'report' );
	/* Create Connection to the DB */
	// Get DB Instance
	$db   = DBCxn::get();

	/* Retrieve Database values */
	$orderKey = '';
	if ( $leafid > 0 ) {
		$orderKey = getPreparedDBValue( 'SELECT MIN(order_key) AS order_key FROM graph_tree_items WHERE id=?;', array( $leafid ) );
	}
	else {
		$orderKey = getPreparedDBValue( 'SELECT MIN(order_key) AS order_key FROM graph_tree_items WHERE local_graph_id=0 AND rra_id=0 AND host_id=0 AND graph_tree_id=?;', array( $tree_id ) );
	}
	$isHost = getPreparedDBValue( 'SELECT host_id FROM graph_tree_items WHERE id=?;', array( $leafid ) );
	$isSmokepingEnabled = readPluginStatus( 'nmidSmokeping' ) || FALSE;
	$isBoostEnabled = readPluginStatus( 'boost' ) || FALSE;
	$phpBinary = readConfigOption( 'path_php_binary' );
	$orderKey = preg_replace( "/(0{3,3})+$/", "", $orderKey );
	$font = readConfigOption( 'nmid_pdffontname' );
	$pdfType = readConfigOption( 'nmid_pdf_type' );
	$debugModeOn = readConfigOption( 'nmid_pdf_debug' );
	$coverPageIsUsed = FALSE;

	if ( $isBoostEnabled ) {
		$isBoostCacheEnabled = readConfigOption( 'boost_png_cache_enable' );
		if ( $isBoostCacheEnabled == 'on' ) {
			$boost_png_cache_directory = readConfigOption( 'boost_png_cache_directory' );
		}
	}
	$reportTitle = getPreparedDBValue( 'SELECT title FROM graph_tree_items WHERE id=?;', array( $leafid ) );

	$subTitle = readConfigOption( 'nmid_pdfsubtitle' );
	$reportDefaultTitle = readConfigOption( 'nmid_pdftitle' );
	$logoImage = readConfigOption( 'nmid_pdflogo' );
	$pageSize = readConfigOption( 'nmid_pdfpagesize', $cgiUserId, 'graph' );
	$orientation = readConfigOption( 'nmid_pdfpageorientation', $cgiUserId, 'graph' );
	$graphPerPage = readConfigOption( 'nmid_pdfgraphPerPage', $cgiUserId, 'graph' );
	$headerFontSize = readConfigOption( 'nmid_pdffontsize', $cgiUserId, 'graph' );
	$footerText = readConfigOption( 'nmid_pdffooter' );
	$useHostname = readConfigOption( 'nmid_pdfUserHostname' );
    $displayReportTitle = readConfigOption( 'nmid_pdf_ondemand_show_header' );
	$setLinks = readConfigOption( 'nmid_pdfSetLinks' );
	$printHeader = readConfigOption( 'nmid_pdfPrintHeaderFooter' );
	$printFooter = readConfigOption( 'nmid_pdfPrintHeaderFooter' );
	$headerTemplate = readConfigOption( 'nmid_cr_design_header_template' );
	$footerTemplate = readConfigOption( 'nmid_cr_design_footer_template' );
	$reportAuthor = getPreparedDBValue( 'SELECT full_name FROM user_auth WHERE id=?;', array( $cgiUserId ) );

	$headerText = replaceTextFields( $headerTemplate, array(
		                                                'REPORTTITLE' => $reportTitle,
		                                                'START'       => $startTimeString,
		                                                'END'         => $endTimeString,
		                                                'AUTHOR'      => $reportAuthor,
		                                                'REPORTDATE'  => $reportDate
	                                                )
	);
	$footerText = replaceTextFields( $footerTemplate, array(
		                                                'REPORTTITLE' => $reportTitle,
		                                                'START'       => $startTimeString,
		                                                'END'         => $endTimeString,
		                                                'AUTHOR'      => $reportAuthor,
		                                                'REPORTDATE'  => $reportDate
	                                                )
	);
	if ( ( $graphPerPage > -1 ) == FALSE ) {
		$graphPerPage = readConfigOption( 'nmid_pdfgraphPerPage' );
	}
	if ( ( $orientation ) == FALSE ) {
		$orientation = readConfigOption( 'nmid_pdfpageorientation' );
	}
	if ( ( $pageSize > 0 ) == FALSE ) {
		$pageSize = readConfigOption( 'nmid_pdfpagesize' );
	}
	if ( ( $headerFontSize > -1 ) == FALSE ) {
		$headerFontSize = readConfigOption( 'nmid_pdffontsize' );
	}
	if ( $reportTitle ) {
		$title = $reportTitle;
	}
	else {
		$title = $reportDefaultTitle;
	}

	if ( strlen( $logoImage ) < 2 ) {
		$logoImage = 'images/default_logo.png';
	}

	# create the report engine
	// Check whether this is a host specific report
	if ( ( $isHost ) && ( $useHostname == 'on' ) ) {
		$hostDescription = getPreparedDBValue( 'SELECT description FROM host WHERE id=?;', array( $isHost ) );
		$hostIp          = getPreparedDBValue( 'SELECT hostname FROM host WHERE id=?;', array( $isHost ) );
		$title           = "Report for host " . $hostDescription . "(" . $hostIp . ")";
		$cgiAddSubLeafs  = TRUE;
	}

	if ($displayReportTitle != "on") {
	    $title = '';
	}

	CereusReporting_logger( "Initializing Report Engine", 'debug', 'report' );
	$report = nmid_report_initialize( $pdfType, $pageSize, $subTitle, EDITION, $title, $font, $tree_id, "tree" );
	$report->nmidSetPrintFooter( $printHeader );
	CereusReporting_logger( 'Print Header : [' . $printHeader . ']', "debug", "PDFCreation" );
	$report->nmidSetPrintHeader( $printFooter );
	CereusReporting_logger( 'Print Footer: [' . $printFooter . ']', "debug", "PDFCreation" );
	$report->setNmidHeaderTxt( $headerText );
	$report->nmidSetHeaderTitle( $title );

	CereusReporting_logger( "Initializing Report Header", 'debug', 'report' );
	nmid_report_initialize_header_data( $report, $subTitle, $footerText, $reportDate );


	if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'TEMPLATING' ) ) || ( isSMBServer() ) ) {

		// special functions for the Professional and Corporate Editions
		$defaultLogo = $logoImage;
		$coverPage   = getPreparedDBValue( 'SELECT CereusReporting_cover_page FROM graph_tree WHERE id=?;', array( $tree_id ) );
		$logoImage   = getPreparedDBValue( 'SELECT CereusReporting_cover_logo FROM graph_tree WHERE id=?;', array( $tree_id ) );

		// If we have a tree specific logo, set it here
		if ( file_exists( $logoImage ) ) {
			CereusReporting_logger( "Adding Company Report Logo", 'debug', 'report' );
			$report->nmidSetLogoImage( $logoImage );
		}
		else {
			CereusReporting_logger( "Report Logo does not exist. Using default logo", 'warning', 'report' );
			$report->nmidSetLogoImage( $defaultLogo );
		}

		CereusReporting_logger( "Importing Report Template", 'debug', 'report' );
		// Check for pageSize and orientation specific CoverPages
		$plainCoverPageFile = $coverPage;
		$plainCoverPageFile = preg_replace( "/\.pdf/", "", $plainCoverPageFile );
		if ( file_exists( $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf' ) ) {
			if ( $pdfType == MPDF_ENGINE ) {
				$coverPage = $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf';
				$report->SetImportUse();
				$report->SetDocTemplate( $coverPage, 1 );
			}
			elseif ( $pdfType == TCPDF_ENGINE ) {
				$coverPage = $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf';
				$report->setSourceFile( $coverPage );
				$templateTplId = $report->ImportPage( 1 );
				$report->UseTemplate( $templateTplId );
			}
		}
		else {
			CereusReporting_logger( "Report PDF Template does not exist", 'warning', 'report' );
		}
	}
	else {
		// set the default logo for all pages for the EXPRESS edition
		$report->nmidSetLogoImage( $logoImage );
	}

	CereusReporting_logger( "Initializing Report Footer", 'debug', 'report' );
	nmid_report_initializes_headerfooter( $report );


	if ( sizeof( $lgi ) > 0 ) {
		if ( strlen( $lgi[ 0 ] ) > 0 ) {
			doLgiPrint( $report, $lgi, $leafid, $startTime, $endTime );
		}
		else {
			doLeafGraphs( $report, $leafid, $startTime, $endTime );
		}
	}
	else {
		doLeafGraphs( $report, $leafid, $startTime, $endTime );
	}

	CereusReporting_logger( "Finalizing Report.", 'debug', 'report' );
	nmid_report_finalize( $report );


	$title = preg_replace( '/\s+/', '_', $title );
	$report_filename = 'CactiReport.pdf';
	if ( strlen( $title ) > 0 ) {
		$report_filename = $title . '.pdf';
	}


	if ( $nmid_send_report_email ) {
		$reportFileName = sys_get_temp_dir() . '/' . date( "Ymd-Hi", time() ) .'_'. $title . '_' . date( "Ymd-Hi", $startTime ) . '-' . date( "Ymd-Hi", $endTime ) . '.pdf';

		CereusReporting_logger( 'Preparing the PDF file for emailing [' . $reportFileName . '].', "debug", "PDFCreation" );
		$report->Output( $reportFileName, "F" );
		CereusReporting_logger( 'Sending PDF file to user [' . $user_target_email . '].', "debug", "PDFCreation" );

		$dateFormat = readConfigOption( 'nmid_pdf_dateformat' );
		$subject    = 'CereusReporting - On-Deman Report from ' . date( $dateFormat, time() );
		// TODO: Make email content customizable
		$body       = "<br>\n<br>\n<hr>Please look at the attached file for the report.<br>\n";
		$mailError  = CereusReporting_send_pdfReport_mail( $user_target_email, '', '', $subject, $body, $reportFileName, '', '' );
		if ( $mailError ) {
			CereusReporting_logger( 'NMID CereusReporting - Errors: ' . $mailError, "error", "nmid_mail" );
		}
		else {
			CereusReporting_logger( 'NMID CereusReporting - Report [' . $title . '] has been sent out', "notice", "nmid_mail" );
		}
		unlink( $reportFileName );
		header( "Location: ".$_SERVER['HTTP_REFERER'] );
	} else {
		$report->Output( $report_filename, "D" );
	}

	echo json_encode(array('status' => true));

	// TODO: Cleanup Report files ( create function )
	// END