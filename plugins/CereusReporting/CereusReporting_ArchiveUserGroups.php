<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_ArchiveUserGroups.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
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
	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );
	//include_once($mainDir.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'auth.php');
	//include_once($mainDir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tree.php');
	//include_once($mainDir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'data_query.php');
	include( "./include/auth.php" );
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

	// Sanitize strings
	$_REQUEST[ "drp_action" ]     = filter_var( $_REQUEST[ "drp_action" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_column" ]    = filter_var( $_REQUEST[ "sort_column" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_direction" ] = filter_var( $_REQUEST[ "sort_direction" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );

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
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_UserGroups` WHERE `GroupId`='" . $matches[ 1 ] . "'" );

			}
		}
		header( "Location: CereusReporting_ArchiveUserGroups.php" );
	}

	function form_edit()
	{

	}

	function form_display()
	{
		global $colors, $hash_type_names;
		print "<font size=+1>CereusReporting - Archive User Groups</font><br>\n";
		print "<hr>\n";
		$username = db_fetch_cell( "SELECT username FROM user_auth WHERE id=" . $_SESSION[ "sess_user_id" ] );

		$where_clause = '';
		if ( isset( $_REQUEST[ "sort_column" ] ) ) {
			if (
				( $_REQUEST[ "sort_column" ] == 'GroupId' )
				|| ( $_REQUEST[ "sort_column" ] == 'Name' )
				|| ( $_REQUEST[ "sort_column" ] == 'Description' )
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
		$a_groups = db_fetch_assoc( "
        SELECT
          `plugin_nmidCreatePDF_UserGroups`.`GroupId`,
          `plugin_nmidCreatePDF_UserGroups`.`Name`,
          `plugin_nmidCreatePDF_UserGroups`.`Description`
        FROM
          `plugin_nmidCreatePDF_UserGroups`;
    " );

		print "<form name=chk method=POST action=CereusReporting_ArchiveUserGroups.php>\n";

		html_start_box( "<strong>Archive User Groups</strong>", "100%", $colors[ "header" ], "3", "center", "CereusReporting_ArchiveUserGroups_Add.php?action=add" );

		form_hidden_box( "save_component_import", "1", "" );

		if ( sizeof( $a_groups ) > 0 ) {
			$menu_text = array(
				"Name"        => array( "Name", "ASC" ),
				"Description" => array( "Description", "ASC" )
			);

			html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

			$i     = 0;
			$limit = 100;

			foreach ( $a_groups as $s_group ) {
				form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_group[ 'GroupId' ] );
				$i++;
				form_selectable_cell( "<a href='CereusReporting_ArchiveUserGroups_Add.php?action=update&GroupId=" . $s_group[ "GroupId" ] . "'><b>" . $s_group[ 'Name' ] . "</b></a>", $s_group[ 'GroupId' ], 250 );
				$description = $s_group[ 'Description' ];
				$description = preg_replace( "/<br>/", "", $description );
				if ( strlen( $description ) > $limit ) {
					$description = substr( $description, 0, strrpos( substr( $description, 0, $limit ), ' ' ) ) . '...';
				}
				form_selectable_cell( $description, $s_group[ "GroupId" ] );
				form_checkbox_cell( 'selected_items', $s_group[ "GroupId" ] );
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
			print "<tr><td><em>No Archive User Groups exist</em></td></tr>";
			html_end_box( FALSE );
		}

		print "</form>";
	}


?>
