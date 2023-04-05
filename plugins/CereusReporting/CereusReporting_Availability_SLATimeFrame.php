<?php
/*******************************************************************************

 File:         $Id: CereusReporting_Availability_SLATimeFrame.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 Modified_On:  $Date: 2020/12/10 07:06:31 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
chdir($mainDir);
include_once("./include/auth.php");
include_once("./lib/data_query.php");
$_SESSION['custom']=false;

    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "alternate" ] = '';
    $colors[ "light" ] = '';
    $colors[ "header" ] = '';

/* set default action */
if (!isset($_REQUEST["drp_action"])) { $_REQUEST["drp_action"] = ""; }
if (!isset($_REQUEST["sort_column"])) { $_REQUEST["sort_column"] = ""; }
if (!isset($_REQUEST["sort_direction"])) { $_REQUEST["sort_direction"] = ""; }

switch ($_REQUEST["drp_action"]) {
	case '2':
		form_delete();
		break;
	default:
		cr_top_header();
		form_display();
		cr_bottom_footer();
		break;
}


function form_delete() {
    global $colors, $hash_type_names;

    $db = DBCxn::get();

    /* loop through each of the selected tasks and delete them*/
    foreach ( $_POST as $var => $val) {
		if (preg_match("/^chk_([0-9]+)(.*)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			$defaultDays = $matches[2];
			/* ==================================================== */
            $sql = "DELETE
					    FROM
							`plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
						WHERE
							`Id`=:id
						AND
							defaultDays=:defaultDays";

            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':id', $matches[1] );
            $stmt->bindValue( ':defaultDays', $defaultDays );
            $stmt->execute();
        }
	}
    header("Location: CereusReporting_Availability_SLATimeFrame.php");
}

function form_edit() {
    
}

function form_display() {
    global $colors, $hash_type_names;
    print "<font size=+1>CereusReporting - Availability - SLA TimeFrame Data</font><br>\n";
    print "<hr>\n";
	

    $username = db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]);
    $where_clause = "";
        
    if ( isset($_REQUEST["sort_column"]))
    {
        if (
            ( $_REQUEST["sort_column"] == 'Id' )
            || ( $_REQUEST["sort_column"] == 'deviceId' )
            || ( $_REQUEST["sort_column"] == 'startTimeStamp' )
            || ( $_REQUEST["sort_column"] == 'endTimeStamp' )
           )
        {
            if (
                ( $_REQUEST["sort_direction"] == 'ASC' )
                || ( $_REQUEST["sort_direction"] == 'DESC' )
            )
            {
                $where_clause  .= ' ORDER BY ' .
                    $_REQUEST["sort_column"] .
                    ' ' .$_REQUEST["sort_direction"];
            }
        }
    }
    $a_archives = db_fetch_assoc("
        SELECT
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`longDescription`
        FROM
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
		$where_clause
    ");

    print "<form name=chk method=POST action=CereusReporting_Availability_SLATimeFrame.php>\n";

    html_start_box("<strong>Availaiblity SLA TimeFrame</strong>", "100%", $colors["header"], "3", "center", "CereusReporting_Availability_addSLATimeFrame.php?action=add");

    form_hidden_box("save_component_import","1","");

    if ( sizeof( $a_archives ) > 0 ) 
    {
        $menu_text = array(
            "Id" => array("Id", "ASC"),
            "shortDescription" => array("Short Description", "ASC"),
            "defaultDays" => array("Default Days", "ASC"),
            "defaultStartTime" => array("Start Time", "ASC"),
            "defaultEndTime" => array("End Time", "ASC")
        );
    
        html_header_sort_checkbox($menu_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);
    
        $i = 0;
    
        foreach ($a_archives as $s_archive)
        {
            form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $s_archive['Id'].$s_archive['defaultDays']); $i++;
            form_selectable_cell($s_archive['Id'], $s_archive["Id"].$s_archive['defaultDays']);
            form_selectable_cell("<a href='CereusReporting_Availability_addSLATimeFrame.php?action=update&defaultDays=".$s_archive['defaultDays']."&dataId=".$s_archive["Id"]."'>".$s_archive['shortDescription']."</b></a>",$s_archive['Id'].$s_archive['defaultDays'],250);
            form_selectable_cell($s_archive['defaultDays'], $s_archive["Id"].$s_archive['defaultDays']);
            form_selectable_cell( $s_archive["defaultStartTime"], $s_archive["Id"].$s_archive['defaultDays']);
            form_selectable_cell( $s_archive["defaultEndTime"], $s_archive["Id"].$s_archive['defaultDays']);
            form_checkbox_cell('selected_items', $s_archive["Id"].$s_archive['defaultDays']);
            form_end_row();
        }
        html_end_box(false);

	$task_actions = array(
		1 => "Please select an action",
	    2 => "Delete"
	);
	draw_actions_dropdown($task_actions);
    }
    else
    {
	print "<tr><td><em>No sla timeframe records exist</em></td></tr>";
        html_end_box(false);
    }
    
    print "</form>";
}    


?>
