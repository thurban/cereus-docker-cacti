<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_addDSStatsReport.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
	 * Modified_On:  $Date: 2017/11/01 15:05:58 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", __DIR__ );

	chdir( $mainDir );
	include_once( "./include/auth.php" );
	$_SESSION[ 'custom' ] = FALSE;

	/* set default action */
	if ( !isset( $_REQUEST[ "drp_action" ] ) ) {
		$_REQUEST[ "drp_action" ] = "";
	}
	if ( !isset( $_REQUEST[ "sort_column" ] ) ) {
		$_REQUEST[ "sort_column" ] = "";
	}
	if ( !isset( $_REQUEST[ "sort_direction" ] ) ) {
		$_REQUEST[ "sort_direction" ] = "";
	}
	if ( !isset( $_REQUEST[ "ReportId" ] ) ) {
		$_REQUEST[ "ReportId" ] = "";
	}

	// Sanitize strings
	$_REQUEST[ "drp_action" ]     = filter_var( $_REQUEST[ "drp_action" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_column" ]    = filter_var( $_REQUEST[ "sort_column" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_direction" ] = filter_var( $_REQUEST[ "sort_direction" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$reportId                     = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );

	input_validate_input_number( $reportId );

	cr_top_header();
	form_display( $reportId );
	cr_bottom_footer();

	function form_display( $reportId )
	{
		global $colors, $hash_type_names;
		print "<font size=+1>CereusReporting - DSStats Graphs</font><br>\n";
		print "<hr>\n";

		if ( !( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'DSSTATS' ) ) || ( isSMBServer() ) ) ) {
			// Multi Repots are only supported for PROFESSIONAL and CORPORATE editions
			print "<p>Multi Reports is not supported for this Edition. Your edition is :<b>" . EDITION . "</b><br>\n";
			return;
		}
		$where_clause = '';
		if ( isset( $_REQUEST[ "sort_column" ] ) ) {
			if (
				( $_REQUEST[ "sort_column" ] == 'Name' )
				|| ( $_REQUEST[ "sort_column" ] == 'Example Image' )
			) {
				if (
					( $_REQUEST[ "sort_direction" ] == 'ASC' )
					|| ( $_REQUEST[ "sort_direction" ] == 'DESC' )
				) {
					$where_clause .= ' ORDER BY ' .
						$_REQUEST[ "sort_column" ] .
						' ' . $_REQUEST[ "sort_direction" ];
				}
			}
		}
		$a_dsstatsGraphs = array();
		$s_dsstatsDir    = __DIR__ . '/dsstats_reports/';
		if ( is_dir( $s_dsstatsDir ) ) {
			if ( $dirhandle = opendir( $s_dsstatsDir ) ) {
				while ( ( $file = readdir( $dirhandle ) ) !== FALSE ) {
					if ( is_file( $s_dsstatsDir . $file ) ) {
						$a_dsstatsGraphs[] = $file;
					}
				}
				closedir( $dirhandle );
			}
		}

		html_start_box( "<strong>DSSTats Graphs</strong>", "100%", $colors[ "header" ], "3", "center", "" );

		form_hidden_box( "ReportId", $reportId, "" );
		form_hidden_box( "ReportType", "2", "" );

		if ( sizeof( $a_dsstatsGraphs ) > 0 ) {
			$menu_text = array(
				"Example" => array( "Example Image", "ASC" ),
				"Name"    => array( "Name", "ASC" ),
				"Action"  => array( "Action", "ASC" )
			);

			html_header_sort( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

			$i = 0;

			foreach ( $a_dsstatsGraphs as $s_dsstatsGraph ) {
				form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $i );
				$i++;
				form_selectable_cell( "<img src='dsstats_reports/" . $s_dsstatsGraph . "' width=500>", $i, 500 );
				form_selectable_cell( $s_dsstatsGraph, $i );
				if ( existsDsstatsGraph( $reportId, $s_dsstatsGraph ) ) {
					form_selectable_cell( "Graph already added", $i, 250 );
				}
				else {
					form_selectable_cell( "<a href='addToDSStatsReport.php?action=update&dsstats_name=" . $s_dsstatsGraph . "&ReportId=" . $reportId . "'><b>Add to Report</b></a>", $i, 250 );
				}
				form_end_row();
			}
			html_end_box( FALSE );
		}
		else {
			print "<tr><td><em>No Reports exist</em></td></tr>";
			html_end_box( FALSE );
		}

		print "
		<table align='center' width='100%' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>
			<tr>
				<td bgcolor='#f5f5f5' align='right'>
					<a href='CereusReporting_addReport.php?ReportId=" . $reportId . "&ReportType=2'>Go back to report</a>
				</td>
	
			</tr>
		</table>
	";

	}

	function existsDsstatsGraph( $reportId, $s_dsstatsGraph )
	{
		$a_reports = db_fetch_assoc( "
	SELECT
	  `plugin_nmidCreatePDF_DSStatsReports`.`Id`,
	  `plugin_nmidCreatePDF_DSStatsReports`.`DSStatsGraph`,
	  `plugin_nmidCreatePDF_DSStatsReports`.`Description`
	FROM
	  `plugin_nmidCreatePDF_DSStatsReports`
	WHERE
	  `plugin_nmidCreatePDF_DSStatsReports`.`ReportId` = $reportId 
	ORDER BY `order`;
	" );

		foreach ( $a_reports as $s_report ) {
			if ( $s_report[ 'DSStatsGraph' ] == $s_dsstatsGraph ) {
				return TRUE;
			}
		}
		return FALSE;
	}

?>
