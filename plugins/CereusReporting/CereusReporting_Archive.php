<?php
    /*******************************************************************************
     *
     * File:         $Id: CereusReporting_Archive.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
     * Modified_On:  $Date: 2020/12/10 07:06:31 $
     * Modified_By:  $Author: thurban $
     * License:      Commercial
    * Copyright:    Copyright 2009/2012 by Urban-Software.de / Thomas Urban
     *******************************************************************************/
    include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );


    $mainDir = preg_replace( "@plugins.CereusReporting@", "", __DIR__ );
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
                $archiveFile = db_fetch_cell( "select filePath from plugin_nmidCreatePDF_Archives where ArchiveId=" . $matches[ 1 ] );
                db_execute( "DELETE FROM `plugin_nmidCreatePDF_Archives` where `ArchiveId`='" . $matches[ 1 ] . "'" );
                unlink( $archiveFile );
            }
        }
        header( "Location: CereusReporting_Archive.php" );
    }

    function form_edit()
    {

    }

    function form_display()
    {
        global $colors, $hash_type_names;
        print "<font size=+1>CereusReporting - Archive</font><br>\n";
        print "<hr>\n";

        if ( !( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'ARCHIVING' ) ) || ( isSMBServer() ) ) ) {
            // Scheduling is not supported for EXPRESS or PROFESSIONAL edition
            print "<p>Archiving is not supported for this Edition. Your edition is :<b>" . EDITION . "</b><br>\n";
            return;
        }

        $username = db_fetch_cell( "select username from user_auth where id=" . $_SESSION[ "sess_user_id" ] );

        $where_clause = '';
        if ( isset( $_REQUEST[ "sort_column" ] ) ) {
            if (
                ( $_REQUEST[ "sort_column" ] == 'ArchiveId' )
                || ( $_REQUEST[ "sort_column" ] == 'Name' )
                || ( $_REQUEST[ "sort_column" ] == 'GroupName' )
                || ( $_REQUEST[ "sort_column" ] == 'startDate' )
                || ( $_REQUEST[ "sort_column" ] == 'endDate' )
                || ( $_REQUEST[ "sort_column" ] == 'archiveDate' )
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
          `plugin_nmidCreatePDF_Archives`.`ArchiveId`,
          `plugin_nmidCreatePDF_Archives`.`Name`,
		  `plugin_nmidCreatePDF_UserGroups`.Name as GroupName,
          `plugin_nmidCreatePDF_Archives`.`startDate`,
          `plugin_nmidCreatePDF_Archives`.`endDate`,
          `plugin_nmidCreatePDF_Archives`.`archiveDate`
        FROM
          `plugin_nmidCreatePDF_Archives` 
          INNER JOIN 
			 `plugin_nmidCreatePDF_UserGroupReports`
			 ON
			 `plugin_nmidCreatePDF_Archives`.`ArchiveId` = `plugin_nmidCreatePDF_UserGroupReports`.`ArchiveId`
		  	 INNER JOIN
  		  	 `plugin_nmidCreatePDF_UserGroups`
  		  	 ON
  		  	 `plugin_nmidCreatePDF_UserGroups`.GroupId = `plugin_nmidCreatePDF_UserGroupReports`.UserGroupId
		" . $where_clause
        );

        print "<form name=chk method=POST action=CereusReporting_Archive.php>\n";

        html_start_box( "<strong>Report Archive</strong>", "100%", $colors[ "header" ], "3", "center", "CereusReporting_addReport.php?action=add" );

        form_hidden_box( "save_component_import", "1", "" );

        if ( sizeof( $a_archives ) > 0 ) {
            $menu_text = array(
                "Name"        => array( "Name", "ASC" ),
                "GroupName"   => array( "Group Name", "ASC" ),
                "startDate"   => array( "Start Date", "ASC" ),
                "endDate"     => array( "End Date", "ASC" ),
                "archiveDate" => array( "Archived", "ASC" )
            );

            html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

            $i          = 0;
            $dateFormat = readConfigOption( "nmid_pdf_dateformat" );
            foreach ( $a_archives as $s_archive ) {
                form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_archive[ 'ArchiveId' ] );
                $i++;
                form_selectable_cell( "<a href='CereusReporting_doArchiveReport.php?action=download&ArchiveId=" . $s_archive[ "ArchiveId" ] . "'>Download <b>" . $s_archive[ 'Name' ] . "</b></a>", $s_archive[ 'ArchiveId' ], 250 );
                form_selectable_cell( $s_archive[ 'GroupName' ], $s_archive[ "ArchiveId" ] );
                form_selectable_cell( date( "$dateFormat", $s_archive[ "startDate" ] ), $s_archive[ "ArchiveId" ] );
                form_selectable_cell( date( "$dateFormat", $s_archive[ "endDate" ] ), $s_archive[ "ArchiveId" ] );
                form_selectable_cell( date( "$dateFormat", $s_archive[ "archiveDate" ] ), $s_archive[ "ArchiveId" ] );
                form_checkbox_cell( 'selected_items', $s_archive[ "ArchiveId" ] );
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
            print "<tr><td><em>No archived reports exist</em></td></tr>";
            html_end_box( FALSE );
        }

        print "</form>";
    }


?>
