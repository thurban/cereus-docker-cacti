<?php
    /*******************************************************************************
     *
     * File:         $Id: CereusReporting_ArchiveUserGroups_Add.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
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
    if ( !isset( $_REQUEST[ "GroupId" ] ) ) {
        $_REQUEST[ "GroupId" ] = "";
    }
    if ( !isset( $_REQUEST[ "action" ] ) ) {
        $_REQUEST[ "action" ] = "";
    }
    if ( !isset( $_REQUEST[ "drp_action" ] ) ) {
        $_REQUEST[ "drp_action" ] = "";
    }
    if ( !isset( $_REQUEST[ "delete_users" ] ) ) {
        $_REQUEST[ "delete_users" ] = "";
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
    $_REQUEST[ "delete_users" ]   = filter_var( $_REQUEST[ "delete_users" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
    $_REQUEST[ "action" ]         = filter_var( $_REQUEST[ "action" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
    $_REQUEST[ "GroupId" ]        = filter_var( $_REQUEST[ "GroupId" ], FILTER_SANITIZE_NUMBER_INT );

    input_validate_input_number( $_REQUEST[ "GroupId" ] );


    switch ( $_REQUEST[ "drp_action" ] ) {
        case '2':
            form_user_delete( $_REQUEST[ "GroupId" ], $_REQUEST[ "delete_users" ] );
            break;
        default:
            break;
    }

    switch ( $_REQUEST[ "action" ] ) {
        case 'save':
            form_save( $_REQUEST[ "GroupId" ] );
            break;
        default:
            cr_top_header();
            form_display( $_REQUEST[ "GroupId" ] );
            cr_bottom_footer();
            break;
    }


    function form_user_delete( $groupId, $action_type )
    {
        /* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
            if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
                /* ================= input validation ================= */
                input_validate_input_number( $matches[ 1 ] );
                /* ==================================================== */

                db_execute( "DELETE FROM `plugin_nmidCreatePDF_UserGroupList` where `UserId`='" . $matches[ 1 ] . "'" );
            }
        }
    }

    function form_save( $groupId )
    {
        // Get DB Instance
        $db          = DBCxn::get();

        if ( !isset( $_REQUEST[ "Name" ] ) ) {
            $_REQUEST[ "Name" ] = "";
        }
        if ( !isset( $_REQUEST[ "Description" ] ) ) {
            $_REQUEST[ "Description" ] = "";
        }
        $s_groupName =  $_REQUEST[ 'Name' ] ;
        $s_groupDescription = $_REQUEST[ 'Description' ];

        if ( ( isset ( $_REQUEST[ 'Name' ] ) ) && ( isset ( $_REQUEST[ 'save_component_import' ] ) ) ) {
            $insert_sql ="
			INSERT INTO `plugin_nmidCreatePDF_UserGroups`
				( `Name`, `Description` )
			VALUES
				( :groupName, :groupDescription )
			";
            $stmt = $db->prepare( $insert_sql);
            $stmt->bindValue( ':groupName', $s_groupName );
            $stmt->bindValue( ':groupDescription', $s_groupDescription );
            $stmt->execute();
        }
        if ( ( isset ( $_REQUEST[ 'Name' ] ) ) && ( isset ( $_REQUEST[ 'update_component_import' ] ) ) ) {
            $update_sql = "
			UPDATE `plugin_nmidCreatePDF_UserGroups`
			Set
				Name=:groupName,
				Description=:groupDescription
			WHERE
				GroupId=:groupId
			";
            $stmt = $db->prepare( $update_sql);
            $stmt->bindValue( ':groupName', $s_groupName );
            $stmt->bindValue( ':groupDescription', $s_groupDescription );
            $stmt->bindValue( ':groupId', $groupId );
			$stmt->execute();
        }
        header( "Location: CereusReporting_ArchiveUserGroups.php" );

    }

    function form_display( $groupId )
    {
        global $colors;

        $s_defaultName        = "";
        $s_defaultDescription = "";

        if ( $groupId > 0 ) {
            $a_groups = db_fetch_assoc( "
			SELECT
			  `plugin_nmidCreatePDF_UserGroups`.`Description` as Description,
			  `plugin_nmidCreatePDF_UserGroups`.`Name`
			FROM
			  `plugin_nmidCreatePDF_UserGroups`
			WHERE GroupId='$groupId'
		" );
            foreach ( $a_groups as $s_group ) {
                $s_defaultName        = $s_group[ 'Name' ];
                $s_defaultDescription = $s_group[ 'Description' ];
            }
        }
        print "<font size=+1>CereusReporting - Add Archive User Group</font><br>\n";
        print "<hr>\n";

        ?>
	<form name="GroupData" method="post" action="CereusReporting_ArchiveUserGroups_Add.php" enctype="multipart/form-data">
        <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
        <?php } ?>

	<?php

        if ( $groupId > 0 ) {
            html_start_box( "<strong>Archive User Group</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
        }
        else {
            html_start_box( "<strong>Archive User Group</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
        }

        form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">User Group Name</font><br>
            The name of the archive user group.
        </td>
        <td>
            <?php form_text_box( "Name", "", $s_defaultName, 255 ); ?>
        </td>
        </tr>

        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">User Group Description</font><br>
            The detailed description of this archive user group.
        </td>
        <td>
            <?php form_text_area( "Description", $s_defaultDescription, 5, 50, "" ); ?>
        </td>
        </tr>

        <?php

        if ( $groupId > 0 ) {
            form_hidden_box( "update_component_import", "1", "" );
            form_hidden_box( "GroupId", $groupId, "" );
        }
        else {
            form_hidden_box( "save_component_import", "1", "" );
        }
        html_end_box();
        form_save_button( "CereusReporting_ArchiveUserGroups.php", "save", 'GroupId' );

        // Graph Report Data
        if ( $groupId > 0 ) {
            $where_clause = '';


            print "<form name=chk method=POST action=CereusReporting_ArchiveUserGroups_Add.php>\n";
            form_hidden_box( "GroupId", $groupId, "" );

            if ( isset( $_REQUEST[ "sort_column" ] ) ) {
                if (
                    ( $_REQUEST[ "sort_column" ] == 'username' )
                    || ( $_REQUEST[ "sort_column" ] == 'full_name' )
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
            $a_users = db_fetch_assoc( "
			SELECT
			  `user_auth`.`id`,
			  `user_auth`.`username`,
			  `user_auth`.`full_name`
			FROM
			  `user_auth` INNER JOIN
			  `plugin_nmidCreatePDF_UserGroupList` ON (`user_auth`.`id` =
				  `plugin_nmidCreatePDF_UserGroupList`.`UserId`)
			WHERE
			  `plugin_nmidCreatePDF_UserGroupList`.`UserGroupId` =  $groupId " .
                                           $where_clause
            );

            html_start_box( "<strong>Users added to this group</strong>", "100%", $colors[ "header" ], "3", "center", "CereusReporting_ArchiveUserGroups_AddUser.php?action=add&GroupId=" . $groupId );

            if ( sizeof( $a_users ) > 0 ) {
                $menu_text = array(
                    "Username",
                    "Full Name"
                );

                html_header_checkbox( $menu_text, FALSE );
                form_hidden_box( "delete_users", "1", "" );
                form_hidden_box( "GroupId", $groupId, "" );
                $i = 0;

                foreach ( $a_users as $s_user ) {
                    form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_user[ 'id' ] );
                    $i++;
                    form_selectable_cell( $s_user[ 'username' ], $s_user[ "id" ] );
                    form_selectable_cell( $s_user[ 'full_name' ], $s_user[ "id" ] );
                    form_checkbox_cell( 'selected_items', $s_user[ "id" ] );

                    form_end_row();
                }
                html_end_box( FALSE );

                $task_actions = array(
                    1 => "Please select an action",
                    2 => "Delete"
                );
                cr_draw_actions_dropdown( $task_actions, 0 );
            }
            else {
                print "<tr><td><em>No Users exist</em></td></tr>";
                html_end_box( FALSE );
            }
            print "</form>";
        }
    }


    cr_bottom_footer();

?>
