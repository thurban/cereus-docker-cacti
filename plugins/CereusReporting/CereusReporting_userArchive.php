<?php
    /*******************************************************************************
     *
     * File:         $Id: CereusReporting_userArchive.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
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
    $dir     = dirname( __FILE__ );
    $mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
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

    switch ( $_REQUEST[ "drp_action" ] ) {
        case '1':
            // Mark Read
            form_read();
            break;
        case '2':
            // Mark UnRead
            form_unRead();
            break;
        case '3':
            // Hide Report
            form_hide();
            break;
        case '4':
            // Show All Reports
            form_showAll();
            break;
        default:
	        cr_top_graph_header();
            form_display();
            cr_bottom_footer();
            break;
    }

    function form_read()
    {
        // Mark selected report(s) as read
        foreach ( $_POST as $var => $val) {
            if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
                /* ================= input validation ================= */
                input_validate_input_number( $matches[ 1 ] );
                /* ==================================================== */
                db_execute( "UPDATE `plugin_nmidCreatePDF_UserReportList` SET Status=1  where `ArchiveId`='" . $matches[ 1 ] . "'" );

            }
        }
        header( "Location: CereusReporting_userArchive.php" );
    }

    function form_unRead()
    {
        // Mark selected report(s) as unread
        foreach ( $_POST as $var => $val) {
            if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
                /* ================= input validation ================= */
                input_validate_input_number( $matches[ 1 ] );
                /* ==================================================== */
                db_execute( "UPDATE `plugin_nmidCreatePDF_UserReportList` SET Status=2  where `ArchiveId`='" . $matches[ 1 ] . "'" );

            }
        }
        header( "Location: CereusReporting_userArchive.php" );
    }

    function form_hide()
    {
        // Hide selected report(s)
        foreach ( $_POST as $var => $val) {
            if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
                /* ================= input validation ================= */
                input_validate_input_number( $matches[ 1 ] );
                /* ==================================================== */
                db_execute( "UPDATE `plugin_nmidCreatePDF_UserReportList` SET Status=3  where `ArchiveId`='" . $matches[ 1 ] . "'" );

            }
        }
        header( "Location: CereusReporting_userArchive.php" );
    }

    function form_showAll()
    {
        // Show all reports
        db_execute( "UPDATE `plugin_nmidCreatePDF_UserReportList` SET Status=1 where Status=3 AND `UserId`='" . $_SESSION[ "sess_user_id" ] . "'" );
        header( "Location: CereusReporting_userArchive.php" );
    }


    function form_display()
    {
        global $colors, $hash_type_names;
        print "<font size=+1>CereusReporting - User Reports</font><br>\n";
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
                || ( $_REQUEST[ "sort_column" ] == 'isUnread' )
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
        SELECT DISTINCT
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
  		  	 INNER JOIN
  		  	 `plugin_nmidCreatePDF_UserGroupList`
  		  	 ON
  		  	 `plugin_nmidCreatePDF_UserGroupList`.UserGroupId = `plugin_nmidCreatePDF_UserGroups`.GroupId
  		  	 WHERE 
		  	 `plugin_nmidCreatePDF_UserGroupList`.UserId = " . $_SESSION[ "sess_user_id" ]
        );

        foreach ( $a_archives as $s_archive ) {
            $id = getDBValue( 'ArchiveId', 'select ArchiveId from plugin_nmidCreatePDF_UserReportList where ArchiveId=' . $s_archive[ "ArchiveId" ] . ' AND UserId=' . $_SESSION[ "sess_user_id" ] . ";" );
            if ( $id > 0 ) {
                // ok, entry exists
            }
            else {
                db_execute( "INSERT INTO `plugin_nmidCreatePDF_UserReportList` ( `ArchiveId`, `Status`, `UserId` ) VALUES ( " . $s_archive[ "ArchiveId" ] . " ,2, " . $_SESSION[ "sess_user_id" ] . ") " );
            }
        }

        $a_archives = db_fetch_assoc( "
			SELECT DISTINCT
          `plugin_nmidCreatePDF_Archives`.`ArchiveId`,
          `plugin_nmidCreatePDF_Archives`.`Name`,
			 `plugin_nmidCreatePDF_UserGroups`.Name as GroupName,
          `plugin_nmidCreatePDF_Archives`.`startDate`,
          `plugin_nmidCreatePDF_Archives`.`endDate`,
          `plugin_nmidCreatePDF_Archives`.`archiveDate`,
          `plugin_nmidCreatePDF_UserReportList`.Status
			 FROM
          `plugin_nmidCreatePDF_Archives`,`plugin_nmidCreatePDF_UserGroupReports`,`plugin_nmidCreatePDF_UserGroups`,
			 `plugin_nmidCreatePDF_UserGroupList`,`plugin_nmidCreatePDF_UserReportList`
  		  	 WHERE 
			 `plugin_nmidCreatePDF_Archives`.`ArchiveId` = `plugin_nmidCreatePDF_UserGroupReports`.`ArchiveId`
  		  	 AND
  		  	 `plugin_nmidCreatePDF_UserGroups`.GroupId = `plugin_nmidCreatePDF_UserGroupReports`.UserGroupId
  		  	 AND
  		  	 `plugin_nmidCreatePDF_UserGroupList`.UserGroupId = `plugin_nmidCreatePDF_UserGroups`.GroupId
  		  	 AND 
  		  	 `plugin_nmidCreatePDF_UserGroupReports`.UserGroupId = `plugin_nmidCreatePDF_UserGroupList`.UserGroupId
		  	 AND 
		  	 `plugin_nmidCreatePDF_UserGroupList`.UserId = `plugin_nmidCreatePDF_UserReportList`.UserId
		  	 AND 
		  	 `plugin_nmidCreatePDF_Archives`.`ArchiveId` = `plugin_nmidCreatePDF_UserReportList`.ArchiveId
		  	 AND 
		  	 `plugin_nmidCreatePDF_UserReportList`.Status < 3
		  	 AND
		  	 `plugin_nmidCreatePDF_UserGroupList`.UserId =  " . $_SESSION[ "sess_user_id" ] . "
			 ORDER BY `plugin_nmidCreatePDF_UserReportList`.Status DESC, `plugin_nmidCreatePDF_Archives`.`archiveDate` DESC"
        );


        html_start_box( "<strong>Report Archive</strong>", "100%", $colors[ "header" ], "3", "center", "" );
        print "<form name=chk method=POST action=CereusReporting_userArchive.php>\n";

        if ( sizeof( $a_archives ) > 0 ) {
            $menu_text = array(
                "Name"        => array( "Name", "ASC" ),
                "GroupName"   => array( "Group Name", "ASC" ),
                "startDate"   => array( "Start Date", "ASC" ),
                "endDate"     => array( "End Date", "ASC" ),
                "archiveDate" => array( "Archived", "ASC" ),
                "Status"      => array( "Status", "ASC" )
            );

            html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

            $i                  = 0;
            $dateFormat         = readConfigOption( "nmid_pdf_dateformat" );
            $statusArchive      = array();
            $statusArchive[ 1 ] = 'Read';
            $statusArchive[ 2 ] = 'Unread';
            $statusArchive[ 3 ] = 'Hide';
            $statusArchive[ 4 ] = 'ShowAll';
            foreach ( $a_archives as $s_archive ) {
                $boldStart = '';
                $boldEnd   = '';
                if ( $s_archive[ "Status" ] == 2 ) {
                    $boldStart = '<b>';
                    $boldEnd   = '</b>';
                }
                form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_archive[ 'ArchiveId' ] );
                $i++;
                form_selectable_cell( $boldStart . "<a href='CereusReporting_doArchiveReport.php?action=download&ArchiveId=" . $s_archive[ "ArchiveId" ] . "'>Download " . $s_archive[ 'Name' ] . "</a>" . $boldEnd, $s_archive[ 'ArchiveId' ], 250 );
                form_selectable_cell( $boldStart . $s_archive[ 'GroupName' ] . $boldEnd, $s_archive[ "ArchiveId" ] );
                form_selectable_cell( $boldStart . date( "$dateFormat", $s_archive[ "startDate" ] ) . $boldEnd, $s_archive[ "ArchiveId" ] );
                form_selectable_cell( $boldStart . date( "$dateFormat", $s_archive[ "endDate" ] ) . $boldEnd, $s_archive[ "ArchiveId" ] );
                form_selectable_cell( $boldStart . date( "$dateFormat", $s_archive[ "archiveDate" ] ) . $boldEnd, $s_archive[ "ArchiveId" ] );
                form_selectable_cell( $boldStart . $statusArchive[ $s_archive[ "Status" ] ] . $boldEnd, $s_archive[ "ArchiveId" ] );
                form_checkbox_cell( 'selected_items', $s_archive[ "ArchiveId" ] );
                form_end_row();
            }
            html_end_box( FALSE );

            $task_actions = array(
                1 => "Mark Read",
                2 => "Mark Unread",
                3 => "Hide Report",
                4 => "Show All Reports"
            );
            draw_actions_dropdown( $task_actions );
        }
        else {
            print "<tr><td><em>No archived reports for your userid exist. <a href='CereusReporting_userArchive.php?drp_action=4'>Show all reports</a></em></td></tr>";
            html_end_box( FALSE );
        }

        print "</form>";
    }


?>
