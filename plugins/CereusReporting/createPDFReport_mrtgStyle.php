<?php
    /*******************************************************************************

    File:         $Id: createPDFReport_mrtgStyle.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
    Modified_On:  $Date: 2020/12/10 07:06:31 $
    Modified_By:  $Author: thurban $
    Language:     Perl
    Encoding:     UTF-8
    Status:       -
    License:      Commercial
    Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
     *******************************************************************************/

    $dir     = dirname( __FILE__ );
    $mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

    require_once( 'functions.php' ); // Support functions
    require_once( 'reportEngine.php' ); // Report Engine

    // Curl is installed so proceed
    $ch = curl_init();

    chdir( $mainDir );
    include_once( "./include/global.php" );
    include_once( "./lib/rrd.php" );
    include_once( './include/config.php' );
    chdir( $dir );

//$startTime = $_REQUEST['starttime'];
//$endTime = $_REQUEST['endtime'];
    $startTime = -1;
    $endTime   = -1;
    $lgid      = -1;
    $cgiUserId = -1;


    /* loop through each of the selected tasks and delete them*/
foreach ( $_POST as $var => $val) {
        if ( $var == 'lgid' ) {
            $lgid = $val;
        }
        else if ( $var == 'user_id' ) {
            $cgiUserId = $val;
        }
    }
    $reportDate = '';

    /* Create Connection to the DB */
	// Get DB Instance
	$db   = DBCxn::get();

    /* Retrieve Database values */
    $isBoostEnabled = readPluginStatus( 'boost' ) || FALSE;
    $phpBinary      = readConfigOption( 'path_php_binary' );
    $font           = readConfigOption( 'nmid_pdffontname' );
    $pdfType        = readConfigOption( 'nmid_pdf_type' );
    $isHost         = getDBValue( 'host_id', 'select host_id from graph_local where id=' . $lgid . ';' );

    if ( $isBoostEnabled ) {
        $isBoostCacheEnabled = readConfigOption( 'boost_png_cache_enable' );
        if ( $isBoostCacheEnabled == 'on' ) {
            $boost_png_cache_directory = readConfigOption( 'boost_png_cache_directory' );
        }
    }
    $reportTitle        = "Graph View Report";
    $subTitle           = readConfigOption( 'nmid_pdfsubtitle' );
    $reportDefaultTitle = readConfigOption( 'nmid_pdftitle' );
    $logoImage          = readConfigOption( 'nmid_pdflogo' );
    $pageSize           = readConfigOption( 'nmid_pdfpagesize', $cgiUserId, 'graph' );
    $orientation        = readConfigOption( 'nmid_pdfpageorientation', $cgiUserId, 'graph' );
    $graphPerPage       = readConfigOption( 'nmid_pdfgraphPerPage', $cgiUserId, 'graph' );
    $headerFontSize     = readConfigOption( 'nmid_pdffontsize', $cgiUserId, 'graph' );
    $footerText         = readConfigOption( 'nmid_pdffooter' );
    $useHostname        = readConfigOption( 'nmid_pdfUserHostname' );
    $setLinks           = readConfigOption( 'nmid_pdfSetLinks' );

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

    $filename = 'Default Filename';

    if ( $isHost ) {
        $hostDescription = getDBValue( 'description', 'select description from host where id=' . $isHost . ';' );
        $hostIp          = getDBValue( 'hostname', 'select hostname from host where id=' . $isHost . ';' );
        $filename        = "Report for host " . $hostDescription . "-" . $hostIp . "-";
    }
    else {
        $filename = 'Graph View Report';
    }

    header( "Cache-Control: public" );
    header( "Content-Description: File Transfer" );
    header( "Cache-Control: max-age=5" );
    header( "Content-Type: application/x-pdf" );
    header( "Content-Disposition: attachment; filename=\"" . $filename . "\.pdf\"" );

    if ( strlen( $logoImage ) < 2 ) {
        $logoImage = 'images/default_logo.png';
    }

# create the report engine
    $report = nmid_report_initialize( $pdfType, $pageSize, $subTitle, EDITION, $title, $font, "", "mrtg" );

// Check whether this is a host specific report
    if ( ( $isHost ) && ( $useHostname == 'on' ) ) {
        $hostDescription = getDBValue( 'description', 'select description from host where id=' . $isHost . ';' );
        $hostIp          = getDBValue( 'hostname', 'select hostname from host where id=' . $isHost . ';' );
        $report->nmidSetHeaderTitle( "Report for host " . $hostDescription . "(" . $hostIp . ")" );
    }
    else {
       $report->nmidSetHeaderTitle( $title );

    }
    nmid_report_initialize_header_data( $report, $subTitle, $footerText, $reportDate );


        if ( $pdfType == 1 ) {
            // special functions for the Professional and Corporate Editions
            //$defaultLogo = $logoImage;
            //$coverPage = getDBValue('CereusReporting_cover_page','select CereusReporting_cover_page from graph_tree where id='.$tree_id.';');
            //$logoImage = getDBValue('CereusReporting_cover_logo','select CereusReporting_cover_logo from graph_tree where id='.$tree_id.';');
            //
            //// If we have a tree specific logo, set it here
            //if ( file_exists ( $logoImage ) ) {
            //    $report->nmidSetLogoImage( $logoImage );
            //} else {
            //    $report->nmidSetLogoImage( $defaultLogo );
            //}
            //
            //// Check for pageSize and orientation specific CoverPages
            //$plainCoverPageFile = $coverPage;
            //$plainCoverPageFile = preg_replace("/\.pdf/", "", $plainCoverPageFile);
            //if ( file_exists ( $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf') ) {
            //    $coverPage = $plainCoverPageFile . '-' . $orientation . '-' . $pageSize . '.pdf';
            //    $report->SetDocTemplate($coverPage,1);
            //}
            $report->nmidSetLogoImage( $logoImage );
        }
        else {
            $report->nmidSetLogoImage( $logoImage );
        }


    nmid_report_initializes_headerfooter( $report, $graphPerPage, 'defined' );


    if ( isset ( $lgid ) ) {
        doRRAPrint( $report, $lgid );
    }

    nmid_report_finalize( $report );

    $filename = preg_replace( '/\s+/', '_', $filename );

// Send PDF Report to Browser
    @$report->Output( $filename . '.pdf', "D" );

    curl_close($ch);

// END

?>
