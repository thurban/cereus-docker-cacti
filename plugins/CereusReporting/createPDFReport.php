<?php
	/*******************************************************************************
	 * Copyright (c) 2017. - All Rights Reserved
	 * Unauthorized copying of this file, via any medium is strictly prohibited
	 * Proprietary and confidential
	 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2017.
	 *
	 * File:         $Id: createPDFReport.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Filename:     create-pdf-report.php
	 * LastModified: 21.03.17 07:41
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 *
	 ******************************************************************************/

    ini_set( 'max_execution_time', 0 );
    set_time_limit( 0 );

	list( $micro, $seconds ) = explode( " ", microtime() );
    $start = $seconds + $micro;

	$mainDir = preg_replace( "@plugins.CereusReporting@", "", __DIR__ );

    // Curl is installed so proceed
    //$ch = curl_init();
    $curl_nodes = array();
    $curl_destination = array();

	chdir( $mainDir );
	include_once( "./include/global.php" );
	include_once( './include/config.php' );
	chdir( __DIR__ );

    include_once( __DIR__.'/functions.php' ); // Support functions
    $filterMode = readConfigOption( "nmid_pdf_debug" );
    if ($filterMode > 4 ) {
        error_reporting(1);
    } else {
        error_reporting(0);
    }

    include_once( __DIR__.'/reportEngine.php' ); // Report Engine

	$startTime = filter_input( INPUT_POST, 'starttime', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$endTime = filter_input( INPUT_POST, 'endtime', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$cgiUserId = -1;
	$tree_id = 2;
	$leafid = 8;
    $report_coverpage = '';
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
        else if ( $var == 'report_coverpage' ) {
            $report_coverpage = __DIR__ .'/templates/coverpages/'.$val;
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


	session_write_close();


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

	// Get report template for this report
	$report_template_id = getPreparedDBValue( 'select CereusReporting_cover_page from graph_tree where id=?;', array( $tree_id ) );
	if ( $report_template_id > 0 ) {
		// great
	} else {
		$report_template_id = -1;
	}
	$pageSize = getPreparedDBValue( 'SELECT page_size FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
	$orientation = getPreparedDBValue( 'SELECT page_orientation FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
	$reportTitleTemplate = getPreparedDBValue( 'SELECT report_title FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
	$reportSubTitleTemplate = getPreparedDBValue( 'SELECT report_subtitle FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
	$headerTemplate = getPreparedDBValue( 'SELECT header_template FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
	$footerTemplate = getPreparedDBValue( 'SELECT footer_template FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
	$reportDefaultTitle = getPreparedDBValue( 'SELECT report_title FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
	$subTitle = getPreparedDBValue( 'SELECT report_subtitle FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
    $addPageNumbers = readConfigOption("nmid_pdf_ondemand_show_page_numbers");

	$reportTitle = getPreparedDBValue( 'SELECT title FROM graph_tree_items WHERE id=?;', array( $leafid ) );
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

	$logoImage = readConfigOption( 'nmid_pdflogo' );
	$useHostname = readConfigOption( 'nmid_pdfUserHostname' );
	$setLinks = readConfigOption( 'nmid_pdfSetLinks' );
	$printHeader = readConfigOption( 'nmid_pdfPrintHeaderFooter' );
	$printFooter = readConfigOption( 'nmid_pdfPrintHeaderFooter' );
	$graphPerPage = readConfigOption( 'nmid_pdfgraphPerPage' );
	$headerFontSize = readConfigOption( 'nmid_pdffontsize' );
	$reportAuthor = getPreparedDBValue( 'SELECT full_name FROM user_auth WHERE id=?;', array( $cgiUserId ) );
	# create the report engine


	// Check whether this is a host specific report
	$hostDescription = '';
	$hostIp = '';
	if ( ( $isHost ) && ( $useHostname == 'on' ) ) {
		$hostDescription = getPreparedDBValue( 'SELECT description FROM host WHERE id=?;', array( $isHost ) );
		$hostIp          = getPreparedDBValue( 'SELECT hostname FROM host WHERE id=?;', array( $isHost ) );
		$title           = "Report for host " . $hostDescription . "(" . $hostIp . ")";
		$cgiAddSubLeafs  = TRUE;
	}



    $headerText = replaceTextFields( $headerTemplate, array(
		                                                'REPORTTITLE' => $reportTitle,
		                                                'REPORTSUBTITLE' => $subTitle,
		                                                'START'       => $startTimeString,
		                                                'END'         => $endTimeString,
		                                                'AUTHOR'      => $reportAuthor,
		                                                'DEVICENAME'      => $hostDescription . "(" . $hostIp . ")",
		                                                'DEVICEIP'      =>$hostIp,
		                                                'DEVICEDESCRIPTION'      => $hostDescription,
		                                                'REPORTDATE'  => $reportDate
	                                                )
	);
	$footerText = replaceTextFields( $footerTemplate, array(
		                                                'REPORTTITLE' => $reportTitle,
		                                                'REPORTSUBTITLE' => $subTitle,
		                                                'START'       => $startTimeString,
		                                                'END'         => $endTimeString,
		                                                'AUTHOR'      => $reportAuthor,
		                                                'DEVICENAME'      => $hostDescription . "(" . $hostIp . ")",
		                                                'DEVICEIP'      =>$hostIp,
		                                                'DEVICEDESCRIPTION'      => $hostDescription,
		                                                'REPORTDATE'  => $reportDate
	                                                )
	);

    $title = "";

    if ( readConfigOption("nmid_pdf_ondemand_show_header") == "on" ) {
        if ( $reportTitle ) {
            $title = $reportTitle;
        }
        else {
            $title = $reportDefaultTitle;
        }
	}

	CereusReporting_logger( "Initializing Report Engine", 'debug', 'report' );
	$report = nmid_report_initialize( $pdfType, $pageSize, $subTitle, EDITION, $title, $font, $tree_id, "tree", $footerText, $headerText  );
	if ( $addPageNumbers == "on") {
	    $report->nmidSet_PrintPageNumbers(true);
     } else {
        $report->nmidSet_PrintPageNumbers(false);
    }
	$report->nmidSetPrintFooter( $printFooter );
	CereusReporting_logger( 'Print Footer: [' . $printFooter . ']', "debug", "PDFCreation" );
	$report->nmidSetPrintHeader( $printHeader );
	CereusReporting_logger( 'Print Header : [' . $printHeader . ']', "debug", "PDFCreation" );
	$report->nmidSetHeaderText( $headerText );
	$report->nmidSetFooterText( $footerText );
	$report->nmidSetHeaderTitle( $title );
	$report->nmidSetHeaderSubTitle( $subTitle );

	CereusReporting_logger( "Initializing Report Header", 'debug', 'report' );
	nmid_report_initialize_header_data( $report, $subTitle, $footerText, $headerText, $reportDate );


	if ( ( EDITION == "CORPORATE" ) ||  ( isSMBServer() ) ) {
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

		if ( $pdfType == MPDF_ENGINE ) {
            // Check for pageSize and orientation specific CoverPages
            if ( file_exists( $report_coverpage ) && ( is_dir( $report_coverpage ) == FALSE ) ) {
                $coverPageIsUsed = true;
                $pagecount = $report->SetSourceFile( $report_coverpage );
                // Import the last page of the source PDF file
                $prependTplId = $report->ImportPage();
                $report->AddPage();
                $report->UseTemplate( $prependTplId );
            }
            else {
                // CereusReporting_logger( 'PDF Append Page: ['.$appendFile.']', "debug", "PDFCreation" );
            }
		} elseif ( $pdfType == TCPDF_ENGINE ) {
            // Check for pageSize and orientation specific CoverPages
            $report->nmidDisableTemplate();
            if ( file_exists( $report_coverpage ) && ( is_dir( $report_coverpage ) == FALSE ) ) {
                $coverPageIsUsed = true;
                $pageCount = $report->setSourceFile( $report_coverpage );
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    // Import the last page of the source PDF file
                    $prependTplId = $report->ImportPage( $pageNo );
                    $report->UseTemplate( $prependTplId );
                }
                $report->AddPage();
            }

			// Check for pageSize and orientation specific CoverPages
			$report->setPrintHeader( true );
			if ( $printHeader ) {
				//
			} else {
				$report->nmidSetHeaderText(' ');
			}
			if ( $printFooter ) {
				$report->setPrintFooter( true );
			} else {
				$report->setPrintFooter( FALSE );
			}
		}
	}
	else {
		// set the default logo for all pages for the EXPRESS edition
		$report->nmidSetLogoImage( $logoImage );
	}

	CereusReporting_logger( "Initializing Report Footer", 'debug', 'report' );
	nmid_report_initializes_headerfooter( $report, $graphPerPage, "ondemand" );

    $control_text = '';
    if ( $coverPageIsUsed ) {
        if ( $pdfType == MPDF_ENGINE ) {
            $control_text = '<pagebreak />';
        } elseif ( $pdfType == TCPDF_ENGINE ) {
            $params       = $report->serializeTCPDFtagParameters( array( 'true' ) );
            $control_text = '<tcpdf method="setPrintHeader" params="' . $params . '" />';
            $control_text .= '<tcpdf method="AddPage" />';
            $control_text .= '<tcpdf method="setPrintFooter" params="' . $params . '" />';
            $report->nmidEnableTemplate();
        }
        printControlText( $report, 0, $control_text, 0, 'pagebreak' );
    } else {
        if ( $pdfType == TCPDF_ENGINE ) {
            $params       = $report->serializeTCPDFtagParameters( array( 'true' ) );
            $control_text = '<tcpdf method="setPrintHeader" params="' . $params . '" />';
            $control_text .= '<tcpdf method="setPrintFooter" params="' . $params . '" />';
            $report->nmidEnableTemplate();
        } elseif ( $pdfType == MPDF_ENGINE ) {
            $control_text .= '';
            $report->nmidEnableTemplate();
        }
        printControlText( $report, 0, $control_text, 0, 'pagebreak' );
    }

	if ( sizeof( $lgi ) > 0 ) {
		if ( strlen( $lgi[ 0 ] ) > 0 ) {
			@doLgiPrint( $report, $lgi, $leafid, $startTime, $endTime );
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

	setCookie("downloadStarted", 1, time() + 20, '/', "", false, false);

	if ( $nmid_send_report_email ) {
		$reportFileName = sys_get_temp_dir() . '/' . date( "Ymd-Hi", time() ) .'_'. $title . '_' . date( "Ymd-Hi", $startTime ) . '-' . date( "Ymd-Hi", $endTime ) . '.pdf';

		CereusReporting_logger( 'Preparing the PDF file for emailing [' . $reportFileName . '].', "debug", "PDFCreation" );
		@$report->Output( $reportFileName, "F" );
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
		@$report->Output( $report_filename, "D" );
	}
    //   curl_close($ch);
	session_start();

	// END
