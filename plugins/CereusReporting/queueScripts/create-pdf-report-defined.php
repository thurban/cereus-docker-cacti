<?php
	/*******************************************************************************
 * Copyright (c) 2017. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2017.
 *
 * File:         $Id: create-pdf-report-defined.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 * Filename:     create-pdf-report-defined.php
 * LastModified: 21.03.17 07:25
 * Modified_On:  $Date: 2020/12/10 07:06:31 $
 * Modified_By:  $Author: thurban $
 *
 ******************************************************************************/

	if ( ini_get('max_execution_time') < 10 ) {
		set_time_limit ( 0 );
	}

	$dir = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

	$mainUrl = $_SERVER[ 'PHP_SELF' ];
	$mainUrl = preg_replace( "@plugins/CereusReporting/".__FILE__."@", "", $mainUrl );
	define( 'MPDF_URI', $mainUrl . '/plugins/CereusReporting/ReportEngines/mpdf/' );

	include_once( 'functions.php' ); // Support functions
	include_once( 'reportEngine.php' ); // Report Engine

	chdir( $mainDir );
	include_once( "./include/global.php" );
	include_once( "./lib/rrd.php" );
	include_once( './include/config.php' );
	chdir( $dir );

	$startTime = time() - 3600;
	$endTime = time();
	$cgiGraphFormat = -1;
	$cgiPageOrientation = FALSE;
	$cgiPageSize = FALSE;
	$cgiFontSize = -1;
	$archiveReport = FALSE;
	$archiveUserGroup = '';
	$mode = 'generate';


	/* loop through each of the selected tasks and delete them*/
    foreach ( $_REQUEST as $var => $val) {
		if ( $var == 'ReportId' ) {
			$reportId      = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );
		}
		else if ( $var == 'mode' ) {
			$mode = filter_var( $_REQUEST[ "mode" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else if ( $var == 'starttime' ) {
			$startTime      = filter_var( $_REQUEST[ "starttime" ], FILTER_SANITIZE_NUMBER_INT );
		}
		else if ( $var == 'endtime' ) {
			$endTime      = filter_var( $_REQUEST[ "endtime" ], FILTER_SANITIZE_NUMBER_INT );
		}
		else if ( $var == 'date1' ) {
			$startTime = filter_var( $_REQUEST[ "date1" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else if ( $var == 'date2' ) {
			$endTime = filter_var( $_REQUEST[ "date2" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else if ( $var == 'nmid_pdfgraphPerPage' ) {
			$cgiGraphFormat      = filter_var( $_REQUEST[ "nmid_pdfgraphPerPage" ], FILTER_SANITIZE_NUMBER_INT );
		}
		else if ( $var == 'nmid_pdfpageorientation' ) {
			$cgiPageOrientation =  filter_var( $_REQUEST[ "nmid_pdfpageorientation" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else if ( $var == 'nmid_pdfpagesize' ) {
			$cgiPageSize = filter_var( $_REQUEST[ "nmid_pdfpagesize" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else if ( $var == 'nmid_pdffontsize' ) {
			$cgiFontSize      = filter_var( $_REQUEST[ "nmid_pdffontsize" ], FILTER_SANITIZE_NUMBER_INT );
		}
		else if ( $var == 'archiveReport' ) {
			$archiveReport = TRUE;
		}
		else if ( $var == 'archiveUserGroup' ) {
			$archiveUserGroup = filter_var( $_REQUEST[ "archiveUserGroup" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
	}


	$startTime = strtotime( $startTime );
	$endTime = strtotime( $endTime );
	$dateFormat = readConfigOption( "nmid_pdf_dateformat" );
	$startTimeString = date( "$dateFormat T Y", $startTime );
	$endTimeString = date( "$dateFormat T Y", $endTime );
	// Get reporting date for the footer
	$reportDate = date( "$dateFormat T Y", time() );

	/* Create Connection to the DB */
	// Get DB Instance
	$db   = DBCxn::get();

	/* Retrieve Database values */
	$outputType = getPreparedDBValue( 'SELECT outputType FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$isSmokepingEnabled = readPluginStatus( 'nmidSmokeping' ) || FALSE;
	$isBoostEnabled = readPluginStatus( 'boost' ) || FALSE;
	$phpBinary = readConfigOption( 'path_php_binary' );
	$archiveDir = readConfigOption( 'nmid_archiveDir' );
	$font = readConfigOption( 'nmid_pdffontname' );
	$pdfType = readConfigOption( 'nmid_pdf_type' );
	$debugModeOn = readConfigOption( 'nmid_pdf_debug' );
	$coverPageIsUsed = false;

	if ( $outputType == 1 ) { // HTML Report
		$pdfType = -1; // use HTML Engine
	}

	if ( $isBoostEnabled ) {
		$isBoostCacheEnabled = readConfigOption( 'boost_png_cache_enable' );
		if ( $isBoostCacheEnabled == 'on' ) {
			$boost_png_cache_directory = readConfigOption( 'boost_png_cache_directory' );
		}
	}
	$reportTitle = getPreparedDBValue( 'SELECT Name FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	CereusReporting_logger( 'Creating Report [' . $reportTitle . ']', "notice", "PDFCreation" );
	$logoImage = readConfigOption( 'nmid_pdflogo' );
	$pageSize = getPreparedDBValue( 'SELECT pageSize FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$orientation = getPreparedDBValue( 'SELECT pageOrientation FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$graphPerPage = getPreparedDBValue( 'SELECT pageGraphFormat FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$showGraphHeader = getPreparedDBValue( 'SELECT showGraphHeader FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );

	$printHeader = getPreparedDBValue( 'SELECT printHeader FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$printFooter = getPreparedDBValue( 'SELECT printFooter FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$skipHFCoverPage = getPreparedDBValue( 'SELECT skipHFCoverPage FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );

	$printDetailedFailedPollsTable = getPreparedDBValue( 'SELECT printDetailedFailedPollsTable FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$printDetailedPollsTable = getPreparedDBValue( 'SELECT printDetailedPollsTable FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$prependFile = dirname( __FILE__ ) . '/templates/coverpages/' . getPreparedDBValue( 'SELECT PrependPDFFile FROM plugin_nmidCreatePDF_Reports WHERE  ReportId=?;', array( $reportId ) );
	$appendFile = getPreparedDBValue( 'SELECT AppendPDFFile FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );

	//$footerText = readConfigOption( 'nmid_pdffooter' );
	$useHostname = readConfigOption( 'nmid_pdfUserHostname' );
    $displayReportTitle = readConfigOption( 'nmid_pdf_ondemand_show_header' );
	$setLinks = readConfigOption( 'nmid_pdfSetLinks' );
	$headerFontSize = readConfigOption( 'nmid_pdffontsize' );
	$useUnicode = readConfigOption( 'nmid_pdfUseUnicode' );

	$reportTitleTemplate = readConfigOption( 'nmid_pdftitle' );
	$reportSubTitleTemplate = readConfigOption( 'nmid_pdfsubtitle' );
	$customReportTitle = getPreparedDBValue( 'SELECT customReportTitle FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$customSubReportTitle = getPreparedDBValue( 'SELECT customSubReportTitle FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );

	if ( strlen( $customReportTitle ) == 0 ) {
		$customReportTitle = $reportTitleTemplate;
	}
	if ( strlen( $customSubReportTitle ) == 0 ) {
		$customSubReportTitle = $reportSubTitleTemplate;
	}

	$headerTemplate = readConfigOption( 'nmid_cr_design_header_template' );
	$footerTemplate = readConfigOption( 'nmid_cr_design_footer_template' );
	$customHeaderTemplate = getPreparedDBValue( 'SELECT customHeader FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$customFooterTemplate = getPreparedDBValue( 'SELECT customFooter FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	$reportAuthor = getPreparedDBValue( 'SELECT author FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );
	if ( strlen( $customHeaderTemplate ) > 0 ) {
		$headerTemplate = $customHeaderTemplate;
	}
	if ( strlen( $customFooterTemplate ) > 0 ) {
		$footerTemplate = $customFooterTemplate;
	}
	$reportFilename = readConfigOption( 'nmid_cr_design_report_file_template' );

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
	$reportFilename = replaceTextFields( $reportFilename, array(
		                                                    'REPORTTITLE' => $reportTitle,
		                                                    'START'       => $startTimeString,
		                                                    'END'         => $endTimeString,
		                                                    'AUTHOR'      => $reportAuthor,
		                                                    'REPORTDATE'  => $reportDate
	                                                    )
	);

	CereusReporting_logger( 'PDF Cover Page: [' . $prependFile . ']', "debug", "PDFCreation" );
	CereusReporting_logger( 'PDF Append Page: [' . $appendFile . ']', "debug", "PDFCreation" );

	if ( $cgiGraphFormat > -1 ) {
		$graphPerPage = $cgiGraphFormat;
	}
	if ( $cgiPageOrientation ) {
		$orientation = $cgiPageOrientation;
	}
	if ( $cgiPageSize ) {
		$pageSize = $cgiPageSize;
	}
	if ( $cgiFontSize > -1 ) {
		$headerFontSize = $cgiFontSize;
	}
	if ( $reportTitle ) {
		$title = $reportTitle;
	}
	else {
		$title = 'Unknown';
	}

	if ( $outputType == 1 ) { // HTML Report
		header( "Cache-Control: public" );
		header( "Content-Description: File Transfer" );
		header( "Cache-Control: max-age=5" );
		header( "Content-Type: application/zip" );
		header( 'Content-Transfer-Encoding: binary' );
		header( "Content-Disposition: attachment; filename=\"" . $title . "\.zip\"" );
	}

	if ( strlen( $logoImage ) < 2 ) {
		$logoImage = 'images/default_logo.png';
	}

	# create the report engine
	$report = nmid_report_initialize( $pdfType, $pageSize, $customSubReportTitle, EDITION, $customReportTitle, $font, $reportId, "Report" );
	$report->nmidSetPrintFooter( $printHeader );
	CereusReporting_logger( 'Print Header : ['.$printHeader.']', "debug", "PDFCreation" );
	$report->nmidSetPrintHeader( $printFooter);
	CereusReporting_logger( 'Print Footer: ['.$printFooter.']', "debug", "PDFCreation" );
	$report->nmidSetSkipCoverPage($skipHFCoverPage);
	CereusReporting_logger( 'Skipping Cover Page: ['.$skipHFCoverPage.']', "debug", "PDFCreation" );
	$report->nmidSetHeaderTitle( $customReportTitle );
	$report->setNmidHeaderTxt( $headerText );
	nmid_report_initialize_header_data( $report, $customSubReportTitle, $footerText, $reportDate );
	$appendTplId = FALSE;
	$prependTplId = FALSE;

	if ( $printDetailedFailedPollsTable ) {
		$report->nmidSetShowDetailedFailedTable(true);
	}
	if ( $printDetailedPollsTable ) {
		$report->nmidSetShowDetailedTable(true);
	}

	if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'TEMPLATING' ) ) || ( isSMBServer() ) ) {
		// special functions for the Professional and Corporate Editions
		$defaultLogo = $logoImage;
		$coverPage   = getDBValue( 'CoverPage', 'select CoverPage from plugin_nmidCreatePDF_Reports where ReportId=' . $reportId . ';' );
		$logoImage   = getDBValue( 'Logo', 'select Logo from plugin_nmidCreatePDF_Reports where ReportId=' . $reportId . ';' );

		// If we have a tree specific logo, set it here
		if ( file_exists( $logoImage ) ) {
			CereusReporting_logger( "Adding Company Report Logo", 'debug', 'report' );
			$report->nmidSetLogoImage( $logoImage );
		}
		else {
			CereusReporting_logger( "Report Logo does not exist. Using default logo", 'warning', 'report' );
			$report->nmidSetLogoImage( $defaultLogo );
		}
		if ( $pdfType == MPDF_ENGINE ) {
			// Check for pageSize and orientation specific CoverPages
			$plainCoverPageFile = $coverPage;
			$plainCoverPageFile = preg_replace( "/\.pdf/", "", $plainCoverPageFile );
			if ( file_exists( $prependFile ) && ( is_dir( $prependFile ) == FALSE ) ) {
				$coverPageIsUsed = true;
				$report->SetImportUse();
				$pagecount = $report->SetSourceFile( $prependFile );
				// Import the last page of the source PDF file
				$prependTplId = $report->ImportPage();
				$report->UseTemplate( $prependTplId );
				$report->AddPage();
			}
			else {
				// CereusReporting_logger( 'PDF Append Page: ['.$appendFile.']', "debug", "PDFCreation" );
			}

			if ( file_exists( $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf' ) ) {
				$coverPage = $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf';
				$report->SetImportUse();
				$report->SetDocTemplate( $coverPage, 1 );
				$report->RestartDocTemplate();
			}
			else {
				// CereusReporting_logger( 'PDF Report Template Page: ['.$coverPage.']', "debug", "PDFCreation" );
			}

			if ( file_exists( $appendFile ) && ( is_dir( $appendFile ) == FALSE ) ) {
				$report->SetImportUse();
				$appendTplId = true;
			}
		} elseif ( $pdfType == TCPDF_ENGINE ) {
			// Check for pageSize and orientation specific CoverPages
			$plainCoverPageFile = $coverPage;
			$plainCoverPageFile = preg_replace( "/\.pdf/", "", $plainCoverPageFile );
			if ( file_exists( $prependFile ) && ( is_dir( $prependFile ) == FALSE ) ) {
				$coverPageIsUsed = true;
				//$report->SetImportUse();
				$pageCount = $report->setSourceFile( $prependFile );
				for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
					// Import the last page of the source PDF file
					$prependTplId = $report->ImportPage( 1 );
					// get the size of the imported page
					$size = $report->getTemplateSize($prependTplId);

					$report->UseTemplate( $prependTplId );

					// create a page (landscape or portrait depending on the imported page size)
					/*
					if ($size['w'] > $size['h']) {
						$report->AddPage('L', array($size['w'], $size['h']));
					} else {
						$report->AddPage('P', array($size['w'], $size['h']));
					}
					*/
					//$report->AddPage();

				}

			}
			else {
				// CereusReporting_logger( 'PDF Append Page: ['.$appendFile.']', "debug", "PDFCreation" );
			}

			if ( file_exists( $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf' ) ) {
				$coverPage = $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf';
				$report->setSourceFile( $coverPage );
				$templateTplId = $report->ImportPage( 1 );
				$report->UseTemplate( $templateTplId );
			}
			else {
				// CereusReporting_logger( 'PDF Append Page: ['.$appendFile.']', "debug", "PDFCreation" );
			}

			if ( file_exists( $appendFile ) && ( is_dir( $appendFile ) == FALSE ) ) {
				//$report->SetImportUse();
				$appendTplId = true;
			}
		}
		else {
			$report->nmidSetLogoImage( $logoImage );
		}
	}
	else {
		// set the default logo for all pages for the EXPRESS edition
		$report->nmidSetLogoImage( $logoImage );
	}

	if ( $outputType == 1 ) {
		$report->nmidSetPdfType( -1 );
	}
	nmid_report_initializes_headerfooter( $report );
	if ( $outputType == 1 ) {
		$report->nmidSetPdfType( 1 );
	}

	// Add report description to report:
	$reportDescription = getPreparedDBValue( 'SELECT Description FROM plugin_nmidCreatePDF_Reports WHERE id=?;', array( $reportId ) );

	$reportDescription = replaceTextFields( $reportDescription, array(
		                                                          'REPORTTITLE' => $reportTitle,
		                                                          'START'       => $startTimeString,
		                                                          'END'         => $endTimeString,
		                                                          'AUTHOR'      => $reportAuthor,
		                                                          'REPORTDATE'  => $reportDate
	                                                          )
	);

	nmid_report_add_reportDescription( $report, $reportDescription, $headerFontSize );

	// is this a graph report ?
	$reportType = getPreparedDBValue( 'SELECT reportType FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?;', array( $reportId ) );

	if ( $reportType == 1 ) { // Graph Report
		CereusReporting_logger( 'Creating Graph Report with id [' . $reportId . ']', "debug", "PDFCreation" );
// retrieve the graphs from the database
		$sql    = "
    SELECT
      `plugin_nmidCreatePDF_GraphReports`.`local_graph_id`,
      `plugin_nmidCreatePDF_GraphReports`.`ReportId`
    FROM
      `plugin_nmidCreatePDF_GraphReports`
    WHERE `plugin_nmidCreatePDF_GraphReports`.`ReportId` = ?
    ORDER BY `order`";
		$stmt = $db->prepare($sql);
		$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$stmt->execute( array($reportId) );
		while ( $row = $stmt->fetch() ) {
			$lgi[ ] = $row[ 'local_graph_id' ];
		}
		$stmt->closeCursor();
		// prepare the images
		$wf_dir = sys_get_temp_dir(). '/' . time() . '-' . $reportId . '-' . $startTime . '-' . $endTime;
		$report->nmidSetWorkerFile( $wf_dir . '/workerfile' );
		$report->nmidSetWorkerDir( $wf_dir );
		mkdir( $wf_dir );

		$tier = 1;
		if ( $report->nmidGetPdfType() == 1 ) {
			$text = $report->nmidGetHeaderTitle();
			if ( ( preg_match( "/<bookmark/", $text ) ) || ( preg_match( "/<div/", $text ) ) ) {
				if ( preg_match( "/<bookmark content=\"(.*)\"/", $text, $title_match ) ) {
					$text = $title_match[ 1 ];
				}
				else {
					// SKIP
					$text = "Report";
				}

			}
			$report->Bookmark( $text, 0 );
		}

		if ( isset ( $lgi ) ) {
			if ( is_array( $lgi ) ) {
				foreach ( $lgi as $lgid ) {
					doGraphPrint( $report, $lgid, $startTime, $endTime, $tier, $reportId );
				}
				//doLgiPrint( $report, $lgi, $leafid, $startTime, $endTime );
			}
		}

		$fh = fopen( $report->nmidGetWorkerFile(), "w+" );
		fwrite( $fh, $report->nmidGetWorkerFileContent() );
		fclose( $fh );

		$fh = fopen( $report->nmidGetWorkerFile(), "r" );
		while ( $line = fgets( $fh ) ) {
			$a_data       = preg_split( "/@/", $line );
			$s_type       = $a_data[ 0 ];
			$s_cmd        = $a_data[ 1 ];
			$s_title      = $a_data[ 2 ];
			$s_tier       = $a_data[ 3 ];
			$s_image_file = $a_data[ 4 ];
			$s_lgid       = $a_data[ 5 ];
			$s_lgid       = preg_replace( "/\n/", "", $s_lgid );
			if ( $s_type == 'graph' ) {
				if ( file_exists( $s_image_file ) ) {
					addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
				}
			}
		}
		if ( $report->nmidGetCurCol() == 2 ) {
			$report->WriteHTML( '<td></td></tr></table>', FALSE, FALSE );
		}
		fclose( $fh );
	}
	elseif ( $reportType == 2 ) { // DSStats Report
		if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'DSSTATS' ) ) || ( isSMBServer() ) ) {
			CereusReporting_logger( 'Creating DSSTATS Report with id [' . $reportId . ']', "debug", "DSSTATS" );
			doDsstatsGraphs( $report, $reportId );
		}
	}
	elseif ( $reportType == 3 ) { // Multi Report
		CereusReporting_logger( 'Creating Multi Report with id [' . $reportId . ']', "debug", "PDFCreation" );

		$tier = 1;
		if ( $report->nmidGetPdfType() == 1 ) {
			$text = $report->nmidGetHeaderTitle();
			if ( ( preg_match( "/<bookmark/", $text ) ) || ( preg_match( "/<div/", $text ) ) ) {
				if ( preg_match( "/<bookmark content=\"(.*)\"/", $text, $title_match ) ) {
					$text = $title_match[ 1 ];
				}
				else {
					// SKIP
					$text = "Report";
				}

			}
			$report->Bookmark( $text, 0 );
		}
		$sql    = "
    SELECT
      `plugin_nmidCreatePDF_MultiGraphReports`.`type`,
      `plugin_nmidCreatePDF_MultiGraphReports`.`data`
    FROM
      `plugin_nmidCreatePDF_MultiGraphReports`
    WHERE `plugin_nmidCreatePDF_MultiGraphReports`.`ReportId` = ?
    ORDER BY `order`";

		// prepare the images
		$wf_dir = sys_get_temp_dir() . '/' . time() . '-' . $reportId . '-' . $startTime . '-' . $endTime;
		$report->nmidSetWorkerFile( $wf_dir . '/workerfile' );
		$report->nmidSetWorkerDir( $wf_dir );
		mkdir( $wf_dir );

		if ( $coverPageIsUsed ) {
			$control_text = '';
			if ( $pdfType == MPDF_ENGINE ) {
				$control_text = '<pagebreak />';
			}
			elseif ( $pdfType == TCPDF_ENGINE ) {
				$control_text .= '<tcpdf method="AddPage" />';
			}
			printControlText( $report, $reportId, $control_text, $tier, 'pagebreak' );
		}
		if ( $printHeader ) {
			$control_text = '';
			if ( $pdfType == MPDF_ENGINE ) {
				$control_text = '<sethtmlpageheader name="myheader" value="1" show-this-page="1" />';
			}
			elseif ( $pdfType == TCPDF_ENGINE ) {
				$params       = $report->serializeTCPDFtagParameters( array( 'true' ) );
				$control_text = '<tcpdf method="setPrintHeader" params="' . $params . '" />';
			}
			printControlText( $report, $reportId, $control_text, $tier, 'enable_header' );
		}
		if ( $printFooter ) {
			$control_text = '';
			if ( $pdfType == MPDF_ENGINE ) {
				$control_text = '<sethtmlpageheader name="myfooter" value="1" show-this-page="1" />';
			}
			elseif ( $pdfType == TCPDF_ENGINE ) {
				$params       = $report->serializeTCPDFtagParameters( array( 'true' ) );
				$control_text = '<tcpdf method="setPrintFooter" params="' . $params . '" />';
			}
			printControlText( $report, $reportId, $control_text, $tier, 'enable_footer' );
		}

		$stmt = $db->prepare($sql);
		$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$stmt->execute( array($reportId) );
		while ( $row = $stmt->fetch() ) {
			if ( $row[ 'type' ] == 'graph' ) {
				doGraphPrint( $report, $row[ 'data' ], $startTime, $endTime, $tier, $reportId );
			}
			elseif ( $row[ 'type' ] == 'dsstats' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'DSSTATS' ) ) || ( isSMBServer() ) ) {
					$dsstats_string      = preg_split( "/:/", $row[ 'data' ], 2 );
					$dsstats_graph       = $dsstats_string[ 0 ];
					$dsstats_description = $dsstats_string[ 1 ];
					printDsstatsGraph( $report, $reportId, $dsstats_graph, $dsstats_description, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'availability' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printAvailabilityGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'availability_combined' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printAvailabilityCombinedGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'availability_thold_tree_sum' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printAvailabilityTholdSumGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'availability_tree_sum' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printAvailabilityTreeSumGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'tree_item' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printTreeItemGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'regexp' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printRegExpItemGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'availability_winservice' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printAvailabilityWinServiceCombinedGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'availability_thold' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					printAvailabilityTholdGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'weathermap' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'WEATHERMAP' ) ) || ( isSMBServer() ) ) {
					printWeathermapGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
				}
			}
			elseif ( $row[ 'type' ] == 'sqlstatement' ) {
				printSQLStatementTable( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
			}
			elseif ( $row[ 'type' ] == 'smokeping' ) {
				printSmokepingGraph( $report, $reportId, $row[ 'data' ], $startTime, $endTime, $tier );
			}
			elseif ( $row[ 'type' ] == 'text' ) {
				$row[ 'data' ] = replaceTextFields( $row[ 'data' ], array(
					'REPORTTITLE' => $reportTitle,
					'START'       => $startTimeString,
					'END'         => $endTimeString,
					'AUTHOR'      => $reportAuthor,
					'REPORTDATE'  => $reportDate
				) );
				printText( $report, $reportId, $row[ 'data' ], $tier );
			}
			elseif ( $row[ 'type' ] == 'title' ) {
				printTitle( $report, $reportId, $row[ 'data' ], $tier );
				$tier = 3;
			}
			elseif ( $row[ 'type' ] == 'chapter' ) {
				printChapter( $report, $reportId, $row[ 'data' ], $tier );
				$tier = 2;
			}
			elseif ( $row[ 'type' ] == 'pdf_file' ) {
				printPDFFile( $report, $reportId, $row[ 'data' ], $tier );
			}
			elseif ( $row[ 'type' ] == 'pagebreak' ) {
				if ( $pdfType == MPDF_ENGINE ) {
					$control_text = '<pagebreak />';
				}
				elseif ( $pdfType == TCPDF_ENGINE ) {
					$control_text .= '<tcpdf method="AddPage" />';
				}

				printControlText( $report, $reportId, $control_text, $tier, 'pagebreak' );
			}
			elseif ( $row[ 'type' ] == 'enable_header' ) {
				$control_text = '';
				if ( $pdfType == MPDF_ENGINE ) {
					$control_text = '<sethtmlpageheader name="myheader" value="1" show-this-page="1" />';
				}
				elseif ( $pdfType == TCPDF_ENGINE ) {
					$params       = $report->serializeTCPDFtagParameters( array( 'true' ) );
					$control_text = '<tcpdf method="setPrintHeader" params="' . $params . '" />';
				}
				printControlText( $report, $reportId, $control_text, $tier, 'enable_header' );
			}
			elseif ( $row[ 'type' ] == 'enable_footer' ) {
				$control_text = '';
				if ( $pdfType == MPDF_ENGINE ) {
					$control_text = '<sethtmlpageheader name="myfooter" value="1" show-this-page="1" />';
				}
				elseif ( $pdfType == TCPDF_ENGINE ) {
					$params       = $report->serializeTCPDFtagParameters( array( 'true' ) );
					$control_text = '<tcpdf method="setPrintFooter" params="' . $params . '" />';
				}
				printControlText( $report, $reportId, $control_text, $tier, 'enable_footer' );
			}
			elseif ( $row[ 'type' ] == 'disable_header' ) {
				$control_text = '';
				if ( $pdfType == MPDF_ENGINE ) {
					$control_text = '<sethtmlpageheader value="-1" />';
				}
				elseif ( $pdfType == TCPDF_ENGINE ) {
					$params       = $report->serializeTCPDFtagParameters( array( 'false' ) );
					$control_text = '<tcpdf method="setPrintHeader" params="' . $params . '" />';
				}
				printControlText( $report, $reportId, $control_text, $tier, 'disable_header' );
			}
			elseif ( $row[ 'type' ] == 'disable_footer' ) {
				$control_text = '';
				if ( $pdfType == MPDF_ENGINE ) {
					$control_text = '<sethtmlpageheader value="-1" />';
				}
				elseif ( $pdfType == TCPDF_ENGINE ) {
					$params       = $report->serializeTCPDFtagParameters( array( 'false' ) );
					$control_text = '<tcpdf method="setPrintFooter" params="' . $params . '" />';
				}
				printControlText( $report, $reportId, $control_text, $tier, 'disable_footer' );
			}
			elseif ( $row[ 'type' ] == 'reportit_report' ) {
				printReportItReport( $report, $reportId, $row[ 'data' ], $tier );
			}
			elseif ( $row[ 'type' ] == 'bookmark' ) {
				$control_text = '';
				if ( $pdfType == MPDF_ENGINE ) {
					$control_text = '<bookmark content="' . $row[ 'data' ] . '" level="' . $tier . '"/>';
				}
				elseif ( $pdfType == TCPDF_ENGINE ) {
					$params       = $report->serializeTCPDFtagParameters( array( $row[ 'data' ], $tier - 1, -1, '', '',
						                                                   array( 0, 0, 0 ) ) );
					$control_text = '<tcpdf method="Bookmark" params="' . $params . '" />';
				}
				printControlText( $report, $reportId, $control_text, $tier, 'bookmark' );
			}
		}
		$stmt->closeCursor();


		$fh = fopen( $report->nmidGetWorkerFile(), "w+" );
		fwrite( $fh, $report->nmidGetWorkerFileContent() );
		fclose( $fh );

		if ( $mode == 'preview' ) {
			CereusReporting_logger( 'Starting Preview Mode', "debug", "PDFCreation" );
		}

		$fh = fopen( $report->nmidGetWorkerFile(), "r" );
		while ( $line = fgets( $fh ) ) {
			$a_data       = preg_split( "/@/", $line );
			$s_type       = $a_data[ 0 ];
			$s_cmd        = $a_data[ 1 ];
			$s_title      = $a_data[ 2 ];
			$s_tier       = $a_data[ 3 ];
			$s_image_file = $a_data[ 4 ];
			if ( $mode == 'preview' ) {
				$s_image_file = 'images/preview_graph.png';
			}
			$s_lgid = $a_data[ 5 ];
			$s_lgid = preg_replace( "/\n/", "", $s_lgid );
			if ( $s_type == 'graph' ) {
				if ( file_exists( $s_image_file ) ) {
					addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
				}
			}
			elseif ( $s_type == 'smokeping' ) {
				if ( file_exists( $s_image_file ) ) {
					$file      = $s_image_file;
					$f         = fopen( $file, 'r' );
					$imageFile = fread( $f, filesize( $file ) );
					fclose( $f );
					if ( file_exists( $imageFile ) ) {
						addImage( $report, $s_title, $imageFile, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'dsstats' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'DSSTATS' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'availability' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'availability_combined' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'availability_winservice' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'availability_thold' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'availability_thold_tree_sum' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'availability_tree_sum' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'AVAILABILITY' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'weathermap' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'WEATHERMAP' ) ) || ( isSMBServer() ) ) {
					if ( file_exists( $s_image_file ) ) {
						addImage( $report, $s_title, $s_image_file, $s_lgid, $s_tier );
					}
				}
			}
			elseif ( $s_type == 'sqlstatement' ) {
				printSQLDataToReport( $report, $s_title );
			}
			elseif ( $s_type == 'text' ) {
				printTextToReport( $report, $s_title );
			}
			elseif ( $s_type == 'title' ) {
				printTitleToReport( $report, $s_title );
			}
			elseif ( $s_type == 'chapter' ) {
				printChapterToReport( $report, $s_title, $s_tier );
			}
			elseif ( $s_type == 'pagebreak' ) {
				printControlTextToReport( $report, $s_title );
			}
			elseif ( $s_type == 'enable_header' ) {
				printControlTextToReport( $report, $s_title );
			}
			elseif ( $s_type == 'enable_footer' ) {
				printControlTextToReport( $report, $s_title );
			}
			elseif ( $s_type == 'disable_header' ) {
				printControlTextToReport( $report, $s_title );
			}
			elseif ( $s_type == 'disable_footer' ) {
				printControlTextToReport( $report, $s_title );
			}
			elseif ( $s_type == 'reportit_report' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'WEATHERMAP' ) ) || ( isSMBServer() ) ) {
					printReportItReport( $report, $reportId, $s_title, $s_tier );
				}
			}
			elseif ( $s_type == 'bookmark' ) {
				printControlText( $report, $reportId, '<bookmark content="' . $s_title . '" level="' . $s_tier . '"/>', $s_tier, 'bookmark' );
			}
			elseif ( $s_type == 'pdf_file' ) {
				if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'WEATHERMAP' ) ) || ( isSMBServer() ) ) {
					$pdf_file = $s_title;
					addPDFFileToReport( $report, $reportId, $pdf_file, $s_tier );
				}
			}
		}
		if ( $report->nmidGetCurCol() == 2 ) {
			if ( $report->nmidGetPdfType() == TCPDF_ENGINE ) {
				$report->writeHTML( '<td></td></tr></table></div>', TRUE, FALSE, TRUE, FALSE, '' );
			}
			else {
				$report->WriteHTML( '<td></td></tr></table></div>', FALSE, FALSE );
			}
		}
		fclose( $fh );
	}

	if ( $appendTplId ) {
		$report->SetDocTemplate();
		$report->SetHTMLHeader();
		//$report->AddPage();
		$report->SetHTMLFooter();
		$pagecount = $report->SetSourceFile( $appendFile );
		// Import all pages of the source PDF file
		for ( $page = 1; $page <= $pagecount; $page++ ) {
			$report->AddPage();
			$appendTplId = $report->ImportPage($page);
			$report->UseTemplate( $appendTplId );
		}
	}

	CereusReporting_logger( 'Finalizing the  report.', "debug", "PDFCreation" );
	nmid_report_finalize( $report );


	$title = preg_replace( '/\s+/', '_', $title );

	if ( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'ARCHIVING' ) ) || ( isSMBServer() ) ) {
		if ( $archiveReport ) {
			$startTimeFileString = date( "dMy-Gi", $startTime );
			$endTimeFileString   = date( "dMy-Gi", $endTime );
			$archiveTitle        = $archiveDir . '/' .
				$title . '_' .
				$startTimeFileString . '_' .
				$endTimeFileString;
			if ( $outputType == '1' ) {
				$archiveTitle = $archiveTitle;
			}
			else {
				$archiveTitle = $archiveTitle . '.pdf';
			}
			// Archive the Report to the archive_dir
			$report->Output( $archiveTitle, "F" );
			$archiveFilePath = $archiveTitle;

			// insert the filepath to the archive table
			$currentTime = time();
			$sql         = "
        INSERT INTO
          `plugin_nmidCreatePDF_Archives`
        ( `Name`, `Description`, `startDate`, `endDate`, `archiveDate`, `filePath`, `ReportId` )
        VALUES
        (?, '', ?, ?, ? , ?, ? )
        ";
			$stmt = $db->prepare($sql);
			$stmt->execute( array($title,$startTime,$endTime,$currentTime,$archiveFilePath,$reportId) );
			$stmt->closeCursor();

			$archiveId = getDBValue( 'ArchiveId', "
                   select
                     ArchiveId
                   from
                     plugin_nmidCreatePDF_Archives
                   where
                     startDate='" . $startTime . "'
                    AND
                     endDate='" . $endTime . "'
                    AND
                     archiveDate='" . $currentTime . "'
                    AND
                     ReportId=" . $reportId . ";" );
			if ( $archiveUserGroup > 0 ) {
				$sql    = "
            INSERT INTO
              `plugin_nmidCreatePDF_UserGroupReports`
            ( `ArchiveId`, `UserGroupId` )
            VALUES
            (?, ? )
            ";
				$stmt = $db->prepare($sql);
				$stmt->execute( array($archiveId,$archiveUserGroup) );
				$stmt->closeCursor();			}
		}
	}

	//$title = preg_replace( '/\s+/', '_', $title );
		$report->Output( $reportFilename . '.pdf', "D" );

// TODO: Migrate to separate function:
	if ( ( $reportType == 3 ) || ( $reportType == 1 ) ) { // Multi(3) Graph(1) report Report
		if ( $debugModeOn < 5 ) {
			//Remove images
			$fh = fopen( $report->nmidGetWorkerFile(), "r" );
			while ( $line = fgets( $fh ) ) {
				$a_data       = preg_split( "/@/", $line );
				$s_image_file = $a_data[ 4 ];
				$s_type       = $a_data[ 0 ];
				if ( $s_type == 'graph' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}
				elseif ( $s_type == 'smokeping' ) {
					if ( file_exists( $s_image_file ) ) {
						$file = $s_image_file;
						if ( filesize( $file ) > 0 ) {
							$f         = fopen( $file, 'r' );
							$imageFile = fread( $f, filesize( $file ) );
							fclose( $f );
							if ( file_exists( $imageFile ) ) {
								unlink( $imageFile );
							}
							if ( file_exists( $s_image_file ) ) {
								unlink( $s_image_file );
							}
						}
					}
				}
				elseif ( $s_type == 'dsstats' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}
				elseif ( $s_type == 'availability' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}
				elseif ( $s_type == 'availability_combined' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}
				elseif ( $s_type == 'availability_winservice' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}
				elseif ( $s_type == 'availability_thold' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}
				elseif ( $s_type == 'availability_thold_tree_sum' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}
				elseif ( $s_type == 'availability_tree_sum' ) {
					if ( file_exists( $s_image_file ) ) {
						unlink( $s_image_file );
					}
				}

			}
			fclose( $fh );


			// Remove workerfile
			if ( file_exists( $report->nmidGetWorkerFile() ) ) {
				unlink( $report->nmidGetWorkerFile() );
			}

			if ( $outputType == 1 ) {
				unlink( $report->nmidGetWorkerDir() . '/' . $title . '.pdf' . '.zip' );
			}

			// Remove worker dir if empty
			if ( is_emtpy_dir( $report->nmidGetWorkerDir() ) ) {
    			rmdir( $report->nmidGetWorkerDir() );
			}
		}
	}
	// END
