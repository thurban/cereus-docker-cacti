<?php
/*******************************************************************************
 *
 * File:         $Id: CereusReporting_GenerateReports.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
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
if (!isset($_REQUEST["reportId"])) { $_REQUEST["reportId"] = ""; }
if (!isset($_REQUEST["sort_column"])) { $_REQUEST["sort_column"] = ""; }
if (!isset($_REQUEST["sort_direction"])) { $_REQUEST["sort_direction"] = ""; }

switch ($_REQUEST["drp_action"]) {
    case '1':
		form_generate( $_REQUEST["reportId"] );
		break;
	default:
		cr_top_graph_header();
		form_display();
		cr_bottom_footer();
		break;
}

function form_generate() {
    
}


function form_display() {
    global $colors, $hash_type_names;
    print "<font size=+1>CereusReporting - Report Generation</font><br>\n";
    print "<hr>\n";
    $username = db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]);
    
        
    if ( isset($_REQUEST["sort_column"]))
    {
        if (
            ( $_REQUEST["sort_column"] == 'ReportID' )
            || ( $_REQUEST["sort_column"] == 'Name' )
            || ( $_REQUEST["sort_column"] == 'Description' )
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
    $a_reports = db_fetch_assoc("
        SELECT
          `plugin_nmidCreatePDF_Reports`.`ReportId`,
          `plugin_nmidCreatePDF_Reports`.`Name`,
          `plugin_nmidCreatePDF_Reports`.`reportType`,
          `plugin_nmidCreatePDF_Reports`.`Description` as Description,
          `plugin_nmidCreatePDF_Reports_Types`.`Description` as type
        FROM
          `plugin_nmidCreatePDF_Reports` INNER JOIN
          `plugin_nmidCreatePDF_Reports_Types` ON `plugin_nmidCreatePDF_Reports`.`type`
            = `plugin_nmidCreatePDF_Reports_Types`.`TypeId`;
    ");

    if (api_user_realm_auth('CereusReporting_addReport.php')) {
        html_start_box("<strong>Reports</strong>", "100%", $colors["header"], "3", "center", "CereusReporting_addReport.php?action=add");
    }
    else {
        html_start_box("<strong>Reports</strong>", "100%", $colors["header"], "3", "center", "");
    }

    if ( sizeof( $a_reports ) > 0 ) 
    {
        $menu_text = array(
            //"ID" => array("ReportId", "ASC"),
            "Name" => array("Name", "ASC"),
            "Description" => array("Description", "ASC"),
            "reportType" => array("Report Type", "ASC"),
            "type" => array("Report Type", "ASC"),
            "action" => array("Quick Action", "ASC")
        );
    
        html_header_sort($menu_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);
    
        $i = 0;
		$limit = 100;
        foreach ($a_reports as $s_report)
        {
		    $showReport = TRUE;

			if ( $showReport ) {			
				form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $s_report['ReportId']); $i++;
				//form_selectable_cell($s_report['ReportId'], $s_report["ReportId"]);
				if (api_user_realm_auth('CereusReporting_addReport.php')) {
					form_selectable_cell("<a href='CereusReporting_addReport.php?action=update&ReportId=".$s_report["ReportId"]."'><img src='images/Report.png'/><b>".$s_report['Name']."</b></a>",$s_report['ReportId'],250);
				}
				else {
					form_selectable_cell($s_report['Name'],$s_report['ReportId'],250);
				}
				$description = $s_report['Description'];
				$description = preg_replace("/<br>/","",$description);
				if ( strlen ( $description ) > $limit ) {
					$description = substr($description, 0, strrpos(substr($description, 0, $limit), ' ')) . '...';
				}			
				form_selectable_cell($description, $s_report["ReportId"]);
	
				$a_reportType = array();
				$a_reportType[0] = 'Normal Report';
				$a_reportType[1] = 'Graph Report';
				$a_reportType[2] = 'DSStats Report';
				$a_reportType[3] = 'Multi Report';

				form_selectable_cell($a_reportType[ $s_report["reportType"] ], $s_report["ReportId"]);
				form_selectable_cell($s_report['type'], $s_report["ReportId"]);
				form_selectable_cell("<a href='CereusReporting_GenerateReport_now.php?reportId=".$s_report["ReportId"]."'>Generate Report</a>", $s_report["ReportId"]);
				form_end_row();
			}
        }
        html_end_box(false);

    }
    else
    {
		print "<tr><td><em>No Reports exist</em></td></tr>";
        html_end_box(false);
    }
    
    print "</form>";
}    


?>
