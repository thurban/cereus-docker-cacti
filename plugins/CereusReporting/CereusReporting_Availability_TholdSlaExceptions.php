<?php
    /*******************************************************************************
     *
     * File:         $Id: CereusReporting_Availability_TholdSlaExceptions.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
     * Modified_On:  $Date: 2020/12/10 07:06:31 $
     * Modified_By:  $Author: thurban $
     * Language:     Perl
    * Encoding:     UTF-8
    * Status:       -
    * License:      Commercial
    * Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
     *******************************************************************************/
    include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
    $dir     = dirname( __FILE__ );
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
                db_execute( "DELETE FROM `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions` where `Id`='" . $matches[ 1 ] . "'" );
            }
        }
        header( "Location: CereusReporting_Availability_TholdSlaExceptions.php" );
    }

    function form_edit()
    {

    }

    function form_display()
    {
        global $colors, $hash_type_names;
        print "<font size=+1>CereusReporting - Availability - Thold SLA Exceptions Data</font><br>\n";
        print "<hr>\n";

        $username     = db_fetch_cell( "select username from user_auth where id=" . $_SESSION[ "sess_user_id" ] );
        $where_clause = "";

        if ( isset( $_REQUEST[ "sort_column" ] ) ) {
            if (
                ( $_REQUEST[ "sort_column" ] == 'Id' )
                || ( $_REQUEST[ "sort_column" ] == 'startTimeStamp' )
                || ( $_REQUEST[ "sort_column" ] == 'endTimeStamp' )
                || ( $_REQUEST[ "sort_column" ] == 'comment' )
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
          `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`Id`,
          `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`thold_data_id`,
          `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`is_sla_relevant`,
          `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`startTimeStamp`,
          `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`endTimeStamp`,
          `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`comment`
        FROM
          `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`
		$where_clause
    " );

        print "<form name=chk method=POST action=CereusReporting_Availability_TholdSlaExceptions.php>\n";

        html_start_box( "<strong>Availaiblity Thold SLA Exceptions</strong>", "100%", $colors[ "header" ], "3", "center", "CereusReporting_Availability_addTholdSlaExceptions.php?action=add" );

        form_hidden_box( "save_component_import", "1", "" );

        if ( sizeof( $a_archives ) > 0 ) {
            $menu_text = array(
                "Id"              => array( "Id", "ASC" ),
                "comment"         => array( "Comment", "ASC" ),
                "is_sla_relevant" => array( "Is SLA Relevant", "ASC" ),
                "startTimeStamp"  => array( "Start Time", "ASC" ),
                "endTimeStamp"    => array( "End Time", "ASC" )
            );

            html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

            $i          = 0;
            $dateFormat = readConfigOption( "nmid_pdf_dateformat" );
            foreach ( $a_archives as $s_archive ) {
                form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_archive[ 'Id' ] );
                $i++;
                form_selectable_cell( $s_archive[ 'Id' ], $s_archive[ "Id" ] );
                form_selectable_cell( "<a href='CereusReporting_Availability_addTholdSlaExceptions.php?action=update&dataId=" . $s_archive[ "Id" ] . "'>" . $s_archive[ 'comment' ] . "</b></a>", $s_archive[ 'Id' ], 250 );
                form_selectable_cell( $s_archive[ 'is_sla_relevant' ], $s_archive[ "Id" ] );
                form_selectable_cell( date( "$dateFormat", $s_archive[ "startTimeStamp" ] ), $s_archive[ "Id" ] );
                form_selectable_cell( date( "$dateFormat", $s_archive[ "endTimeStamp" ] ), $s_archive[ "Id" ] );
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
            print "<tr><td><em>No Thold SLA Exceptions records exist</em></td></tr>";
            html_end_box( FALSE );
        }

        print "</form>";
    }


?>
