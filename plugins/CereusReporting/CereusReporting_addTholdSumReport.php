<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_addTholdSumReport.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
	 * Modified_On:  $Date: 2017/07/18 06:44:34 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

	chdir( $mainDir );
	include_once( "./include/auth.php" );
	include_once( "./lib/tree.php" );
	include_once( "./lib/data_query.php" );
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
	$_REQUEST[ "ReportId" ]       = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );

	input_validate_input_number( $_REQUEST[ "ReportId" ] );

	cr_top_header();
	form_display( $_REQUEST[ "ReportId" ] );
	cr_bottom_footer();

	function form_display( $reportId )
	{
		global $colors, $hash_type_names, $dir, $config;
		print "<font size=+1>CereusReporting - Thold Data</font><br>\n";
		print "<hr>\n";

		if ( isset( $_REQUEST[ "sort_column" ] ) ) {
			if (
			( $_REQUEST[ "sort_column" ] == 'Name' )
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

		$a_tholdData = db_fetch_assoc( "
        SELECT
			graph_tree.name AS name,
			graph_tree_items.title AS title,
			graph_tree_items.id AS id
		FROM
			graph_tree_items
		INNER JOIN
			graph_tree
		ON
			graph_tree.id = graph_tree_items.graph_tree_id
		WHERE
			rra_id=0
		AND
			host_id=0
		" . $where_clause
		);

		html_start_box( "<strong>Thold Tree Selection</strong>", "100%", $colors[ "header" ], "3", "center", "" );

		form_hidden_box( "TreeId", $reportId, "" );
		form_hidden_box( "save_component_import", "1", "" );
		form_hidden_box( "itemType", "tholdsumdata", "" );

		if ( sizeof( $a_tholdData ) > 0 ) {
			$menu_text = array(
				"Name"   => array( "Parent", "ASC" ),
				"Title"  => array( "Name", "ASC" ),
				"Action" => array( "Action", "ASC" )
			);

			html_header_sort( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

			$i = 0;

			foreach ( $a_tholdData as $s_tholdData ) {

				form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_tholdData[ 'id' ] );
				$i++;
				//form_selectable_cell("<img src='". $config['url_path'] . "plugins/weathermap/weathermap-cacti-plugin.php?action=viewimage&id=".$s_tholdData['filehash']."&time=".time()."' width=500>",$s_tholdData['id'],500);
				form_selectable_cell( $s_tholdData[ 'name' ], $s_tholdData[ 'id' ] );
				form_selectable_cell( $s_tholdData[ 'title' ], $s_tholdData[ 'id' ] );
				form_selectable_cell( "<a href='CereusReporting_addMultiReport.php?action=save&save_component_import=1&itemType=13&Data=" . $s_tholdData[ 'id' ] . "&ReportId=" . $reportId . "'><b>Add to Report</b></a>", $s_tholdData[ 'id' ], 250 );
				form_end_row();
			}
			html_end_box( FALSE );
		}
		else {
			print "<tr><td><em>No Tree items of type 'Header' exist.</em></td></tr>";
			html_end_box( FALSE );
		}

		print "
		<table align='center' width='100%' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>
			<tr>
				<td bgcolor='#f5f5f5' align='right'>
					<a href='CereusReporting_addReport.php?ReportId=" . $reportId . "'>Go back to report</a>
				</td>
	
			</tr>
		</table>
	";

	}

?>
