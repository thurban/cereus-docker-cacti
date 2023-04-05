<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_Availability_DowntimeSchedule.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	* Encoding:     UTF-8
	* Status:       -
	* License:      Commercial
	* Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
	$dir = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );
	include_once( "./include/auth.php" );
	include_once( "./lib/data_query.php" );
	$_SESSION[ 'custom' ] = FALSE;

	$colors = array();
	$colors[ "form_alternate1" ] = '';
	$colors[ "form_alternate2" ] = '';
	$colors[ "alternate" ] = '';
	$colors[ "light" ] = '';
	$colors[ "header" ] = '';

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

	switch ( $_REQUEST[ "drp_action" ] ) {
		case '2':
			form_delete();
			break;
		default:
			cr_top_header();
			form_display();
			cr_bottom_footer();
			break;
	}


	function form_delete()
	{
		global $colors, $hash_type_names;

		/* loop through each of the selected tasks and delete them*/
		foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_Availability_Change_Table` where `Id`='" . $matches[ 1 ] . "'" );
			}
		}
		header( "Location: CereusReporting_Availability_DowntimeSchedule.php" );
	}

	function form_edit()
	{

	}

	function form_display()
	{
		global $colors, $hash_type_names;
		print "<font size=+1>CereusReporting - Availability Data</font><br>\n";
		print "<hr>\n";



		$username     = db_fetch_cell( "select username from user_auth where id=" . $_SESSION[ "sess_user_id" ] );
		$where_clause = "";

		if ( isset( $_REQUEST[ "sort_column" ] ) ) {
			if (
				( $_REQUEST[ "sort_column" ] == 'Id' )
				|| ( $_REQUEST[ "sort_column" ] == 'deviceId' )
				|| ( $_REQUEST[ "sort_column" ] == 'startTimeStamp' )
				|| ( $_REQUEST[ "sort_column" ] == 'endTimeStamp' )
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
		$a_archives = db_fetch_assoc( "
        SELECT
          `plugin_nmidCreatePDF_Availability_Change_Table`.`Id`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`deviceId`,
          CONCAT(host.description,' [',host.hostname,'] ') as name,
          `plugin_nmidCreatePDF_Availability_Change_Type`.`decreaseAvailability`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`startTimeStamp`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`endTimeStamp`,
          `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription`
        FROM
          `plugin_nmidCreatePDF_Availability_Change_Table` INNER JOIN
          `plugin_nmidCreatePDF_Availability_Change_Type` ON `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId`
            = `plugin_nmidCreatePDF_Availability_Change_Type`.`Id`
            INNER JOIN `host` ON `plugin_nmidCreatePDF_Availability_Change_Table`.`deviceId` = host.id
		$where_clause
    " );


		print "<form name=chk method=POST action=CereusReporting_Availability_DowntimeSchedule.php>\n";

		html_start_box( "<strong>Availaiblity Downtime Schedule</strong>", "100%", $colors[ "header" ], "3", "center", "CereusReporting_Availability_addDowntimeSchedule.php?action=add" );

		form_hidden_box( "save_component_import", "1", "" );

		if ( sizeof( $a_archives ) > 0 ) {
			$menu_text = array(
				"shortDescription"     => array( "Short Description", "ASC" ),
				"deviceId"             => array( "Device", "ASC" ),
				"startTimeStamp"       => array( "Start Date", "ASC" ),
				"endTimeStamp"         => array( "End Date", "ASC" ),
				"decreaseAvailability" => array( "Type", "ASC" )
			);

			html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

			$i          = 0;
			$dateFormat = readConfigOption( "nmid_pdf_dateformat" );
			foreach ( $a_archives as $s_archive ) {
				form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_archive[ 'Id' ] );
				$i++;
				form_selectable_cell( "<a href='CereusReporting_Availability_addDowntimeSchedule.php?action=update&dataId=" . $s_archive[ "Id" ] . "'>" . $s_archive[ 'shortDescription' ] . "</b></a>", $s_archive[ 'Id' ], 250 );
				form_selectable_cell( $s_archive[ 'name' ], $s_archive[ "Id" ] );
				form_selectable_cell( date( "$dateFormat", $s_archive[ "startTimeStamp" ] ), $s_archive[ "Id" ] );
				form_selectable_cell( date( "$dateFormat", $s_archive[ "endTimeStamp" ] ), $s_archive[ "Id" ] );
				form_selectable_cell( $s_archive[ 'decreaseAvailability' ], $s_archive[ "Id" ] );
				form_checkbox_cell( 'selected_items', $s_archive[ "Id" ] );
				form_end_row();
			}
			html_end_box( FALSE );

			$task_actions = array(
				1 => "Please select an action",
				2 => "Delete"
			);
			draw_actions_dropdown( $task_actions );
		}
		else {
			print "<tr><td><em>No availability records exist</em></td></tr>";
			html_end_box( FALSE );
		}

		print "</form>";
	}


?>
