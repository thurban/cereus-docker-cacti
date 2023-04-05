<?php
/*******************************************************************************

 File:         $Id: CereusReporting_createTemplateReport.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 Modified_On:  $Date: 2020/12/10 07:06:31 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/

$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
chdir($mainDir);
include_once("./include/auth.php");
include_once("./lib/data_query.php");
$_SESSION['custom']=false;

input_validate_input_number( $_REQUEST["ReportId"] );



/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }
if (!isset($_REQUEST["drp_action"])) { $_REQUEST["drp_action"] = ""; }
/* loop through each of the selected tasks and delete them*/

foreach ( $_POST as $var => $val) {
    if (preg_match("/^lgi_([0-9]+)$/", $var, $matches)) {
        $lgi[] = $matches[1];
    }
    else if ($var == 'leaf_id' ) {
        $startTime = $val;
    }
    else if ($var == 'endtime' ) {
        $endTime = $val;
    }
    else if ($var == 'tree_id' ) {
		
        $tree_id = $val;
    }
    else if ($var == 'nmid_pdfAddSubLeafs' ) {
        if ($val == 1) {
            $cgiAddSubLeafs = TRUE;
        } else {
            $cgiAddSubLeafs = FALSE;
        }
    }
    else if ( $var == 'nmid_pdfgraphPerPage' ) {
        $cgiGraphFormat = $val;
    }
    else if ( $var == 'nmid_pdfpageorientation' ) {
        $cgiPageOrientation = $val;
    }
    else if ( $var == 'nmid_pdfpagesize' ) {
        $cgiPageSize = $val;
    }
    else if ( $var == 'nmid_pdffontsize' ) {
        $cgiFontSize = $val;
    }
}



switch ($_REQUEST["drp_action"]) {
	case '2':
		form_graph_delete( $_REQUEST["ReportId"] );
		break;
	default:
		break;
}

switch ($_REQUEST["action"]) {
    case 'save':
		form_save($_REQUEST["ReportId"]);
		break;
	default:
		
		include_once("./include/top_header.php");

		form_display( $_REQUEST["ReportId"] );
		include_once("./include/bottom_footer.php");
		break;
}

function form_graph_delete($report_id) {
    global $colors, $hash_type_names;
    
    /* loop through each of the selected tasks and delete them*/
    foreach ( $_POST as $var => $val) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */
			$current_order = db_fetch_cell("select MAX(`order`) from plugin_nmidCreatePDF_GraphReports where `Id`='".$matches[1]."'");
			db_execute("UPDATE `plugin_nmidCreatePDF_GraphReports` SET `order`=`order`-1 where `order` > '$current_order' AND `ReportId` = '$report_id'");
            db_execute("DELETE FROM `plugin_nmidCreatePDF_GraphReports` where `Id`='" . $matches[1] . "'");
        }
	}
}

function form_save( $reportId ) {
    global $colors, $hash_type_names;

    $db = DBCxn::get();

    if ( (isset ($_POST['Name'])) && (isset ($_POST['save_component_import']) ) ) {
		$i_reportIncludeSubDirs = 0;
		$i_reportIsGraphReport = 0;
        if (isset ($_POST['Name'])) { $s_reportName = $_POST['Name']; }
        if (isset ($_POST['CoverPage'])) { $s_reportCoverPage = $_POST['CoverPage']; }
        if (isset ($_POST['CoverLogo'])) { $s_reportCoverLogo = $_POST['CoverLogo']; }
		if (isset ($_POST['pageSize'])) { $s_reportPageSize = htmlentities(strip_tags($_POST['pageSize'])); }
		if (isset ($_POST['pageOrientation'])) { $s_reportPageOrientation = htmlentities(strip_tags($_POST['pageOrientation'])); }
		if (isset ($_POST['pageGraphFormat'])) { $s_reportPageGraphFormat = htmlentities(strip_tags($_POST['pageGraphFormat'])); }    
        if (isset ($_POST['isGraphReport'])) {
			$i_reportIsGraphReport= htmlentities(strip_tags($_POST['isGraphReport']));
			if ( $i_reportIsGraphReport == "on" )
			{
				$i_reportIsGraphReport = '1';
			}
			else {
				$i_reportIsGraphReport = '0';
			}
		}
        if (isset ($_POST['includeSubDirs'])) {
			$i_reportIncludeSubDirs = strip_tags($_POST['includeSubDirs']);
			if ( $i_reportIncludeSubDirs == "on" )
			{
				$i_reportIncludeSubDirs = '1';
			}
			else {
				$i_reportIncludeSubDirs = '0';
			}
		}
        if (isset ($_POST['Description'])) { $s_reportDescription = $_POST['Description']; }
        if (isset ($_POST['type'])) { $i_reportType = strip_tags($_POST['type']); }
		
        $sql = "
			INSERT INTO `plugin_nmidCreatePDF_Reports`
				(`Name`, `Description`,`isGraphReport`, `leafId`, `includeSubDirs`,`type`,`pageSize`,`pageOrientation`,`pageGraphFormat`,
				`Logo`, `CoverPage`)
			VALUES
				(:s_reportName, :s_reportDescription,:i_reportIsGraphReport, '-1',:i_reportIncludeSubDirs, :i_reportType,
				:s_reportPageSize,:s_reportPageOrientation,:s_reportPageGraphFormat,:s_reportCoverLogo,:s_reportCoverPage)
			";

        $stmt = $db->prepare( $sql);
        $stmt->bindValue( ':s_reportName', $s_reportName );
        $stmt->bindValue( ':s_reportDescription', $s_reportDescription );
        $stmt->bindValue( ':i_reportIsGraphReport', $i_reportIsGraphReport );
        $stmt->bindValue( ':i_reportIncludeSubDirs', $i_reportIncludeSubDirs );
        $stmt->bindValue( ':i_reportType', $i_reportType );
        $stmt->bindValue( ':s_reportPageSize', $s_reportPageSize );
        $stmt->bindValue( ':s_reportPageOrientation', $s_reportPageOrientation );
        $stmt->bindValue( ':s_reportPageGraphFormat', $s_reportPageGraphFormat );
        $stmt->bindValue( ':s_reportCoverLogo', $s_reportCoverLogo );
        $stmt->bindValue( ':s_reportCoverPage', $s_reportCoverPage );
        $stmt->execute();

		// Now retrieve the ReportId for the Graph Processing
		$reportId = db_fetch_cell("select `ReportId` from `plugin_nmidCreatePDF_Reports` where Name=".$s_reportName);

		// Check if the graphs id variable transmitted is  an array and cycle
		// trough the array appropriately
		if ( is_array( $lgi ) )  {
			//echo "Is Array<br>";
			foreach ( $lgi as $lgID ) {
				//echo "Adding $lgID to report $reportId<br>";
				addGraphToReport( $lgID, $reportId );
			}
		}
		else
		{
			// if it is not an array, we check for a vali local_graph_id (lgi)
			// and add the graph to the report.
			if ( $lgi > 0 ) {
				addGraphToReport( $lgi, $reportId );
			}
		}
		
    }
    header("Location: CereusReporting_Reports.php");

}

function form_display( $reportId ) {
    global $colors, $hash_type_names, $config;
    
    $defaultName = "";
    $defaultDescription = "";
    $defaultUser = "";
    $defaultEmail = "";
	$s_defaultName = '';
	$s_defaultDescription = '';
	$i_defaultIncludeSubDirs = 0;
	$i_defaultLeafId = 'not defined';
	$i_defaultIsGraphReport = 0;
	$i_defaultType = 1;
	$s_defaultTypeDescription = 'On Demand';
	$i_defaultGraphFormat = '0';
	$i_defaultPageOrientation = 'P';
	$i_defaultPageSize = 'A4';
	
    if ( $reportId > 0 )
    {
        $a_reports = db_fetch_assoc("
			SELECT
			  `plugin_nmidCreatePDF_Reports_Types`.`TypeId` as type,
			  `plugin_nmidCreatePDF_Reports_Types`.`Description` as typeDescription,
			  `plugin_nmidCreatePDF_Reports`.`Description` as Description,
			  `plugin_nmidCreatePDF_Reports`.`isGraphReport`,
			  `plugin_nmidCreatePDF_Reports`.`leafId`,
			  `plugin_nmidCreatePDF_Reports`.`isGraphReport`,
			  `plugin_nmidCreatePDF_Reports`.`Logo`,
			  `plugin_nmidCreatePDF_Reports`.`CoverPage`, 
			  `plugin_nmidCreatePDF_Reports`.`Name`,
			  `plugin_nmidCreatePDF_Reports`.`pageSize`,
			  `plugin_nmidCreatePDF_Reports`.`pageOrientation`,
			  `plugin_nmidCreatePDF_Reports`.`pageGraphFormat`
			FROM
			  `plugin_nmidCreatePDF_Reports_Types` INNER JOIN
			  `plugin_nmidCreatePDF_Reports` ON `plugin_nmidCreatePDF_Reports`.`type` =
			  `plugin_nmidCreatePDF_Reports_Types`.`TypeId`		
			WHERE ReportId='$reportId'
		");
        foreach ($a_reports as $s_report)
        {
            $s_defaultName = $s_report['Name'];
            $s_defaultDescription = $s_report['Description'];
            $s_defaultCoverLogo = $s_report['Logo'];
            $s_defaultCoverPage = $s_report['CoverPage'];
			$i_defaultIncludeSubDirs = $s_report['includeSubDirs'];	// 1 = true
			$i_defaultLeafId = $s_report['leafId'];
			$i_defaultIsGraphReport = $s_report['isGraphReport'];
			$i_defaultType = $s_report['type'];
			$s_defaultTypeDescription = $s_report['typeDescription'];
			$i_defaultGraphFormat = $s_report['pageGraphFormat'];
			$i_defaultPageOrientation = $s_report['pageOrientation'];
			$i_defaultPageSize = $s_report['pageSize'];
		}
    }
    
    print "<font size=+1>CereusReporting - Add Report Template</font><br>\n";
    print "<hr>\n";

	?>
	<form method="post" action="CereusReporting_addReport.php" enctype="multipart/form-data">
	<?php

	html_start_box("<strong>Report</strong> [new]", "100%", $colors["header"], "3", "center", "");

	form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Report Name</font><br>
            The name of the report.
		</td>
		<td>
			<?php form_text_box("Name","",$s_defaultName,255); ?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Report Description</font><br>
			The detailed describtion of this report. This will be also be displayed in the report.
		</td>
		<td>
			<?php form_text_area("Description",$s_defaultDescription,5,50,""); ?>
		</td>
	</tr>
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Report Cover Page</font><br>
            The CoverPage of this report. Uses default if left empty.
		</td>
		<td>
			<?php form_text_box("CoverPage","",$s_defaultCoverPage,255); ?>
		</td>
	</tr>	

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Report Cover Logo</font><br>
            The CoverLogo of this report. Uses default if left empty.
		</td>
		<td>
			<?php form_text_box("CoverLogo","",$s_defaultCoverLogo,255); ?>
		</td>
	</tr>
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Report is Graph Report</font><br>
			A graph report contains individual graphs that can be selected individually.<br>
			They do not need to belong to a specific host or tree.
		</td>
		<td>
			<?php
				if ( $i_defaultIsGraphReport == 1 ) {
					$i_defaultIsGraphReport = 'on';
				}
				form_checkbox("isGraphReport",$i_defaultIsGraphReport,"This is a graph report","");
			?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Report includes sub leafs</font><br>
			The report can include sub leafs. <br><i>This is only valid for non graph reports.</i>
		</td>
		<td>
			<?php form_checkbox("includeSubDirs",$i_defaultIncludeSubDirs,"Report includes sub leafs",""); ?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Report LeafId</font><br>
			The Leaf for this report. <br><i>This is only valid for non graph reports.</i>
		</td>
		<td>
			<?php echo $i_defaultLeafId; ?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Default Report Timespan</font><br>
			The default report timespane of this report.
		</td>
		<td>
   			<?php
            $a_timeSpans = db_fetch_assoc("
				SELECT
				  `plugin_nmidCreatePDF_Reports_Types`.`Description` AS `name`,
				  `plugin_nmidCreatePDF_Reports_Types`.`TypeId` AS `id`
				FROM
				  `plugin_nmidCreatePDF_Reports_Types`;
			");
            form_dropdown("type",$a_timeSpans, "name", "id", $i_defaultType, "" ,$i_defaultType ,"","");
			?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Report Page Size</font><br>
			The default report timespane of this report.
		</td>
		<td>
   			<?php
            $a_PageSize[0]['name'] = 'A3';
			$a_PageSize[0]['id'] = 'A3';
            $a_PageSize[1]['name'] = 'A4';
			$a_PageSize[1]['id'] = 'A4';
            $a_PageSize[2]['name'] = 'A5';
			$a_PageSize[2]['id'] = 'A5';
            $a_PageSize[3]['name'] = 'Letter';
			$a_PageSize[3]['id'] = 'Letter';
            $a_PageSize[4]['name'] = 'Legal';
			$a_PageSize[4]['id'] = 'Legal';
            form_dropdown("pageSize",$a_PageSize, "name", "id", $i_defaultPageSize, "" ,$i_defaultPageSize ,"","");
			?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Report Page Orientation</font><br>
			The default report timespane of this report.
		</td>
		<td>
   			<?php
            $a_PageOrientation[0]['name'] = 'Portrait';
			$a_PageOrientation[0]['id'] = 'P';
            $a_PageOrientation[1]['name'] = 'Landscape';
			$a_PageOrientation[1]['id'] = 'L';
            form_dropdown("pageOrientation",$a_PageOrientation, "name", "id", $i_defaultPageOrientation, "" ,$i_defaultPageOrientation ,"","");
			?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Report Graph Format</font><br>
			The default report timespane of this report.
		</td>
		<td>
   			<?php
            $a_GraphFormat[0]['name'] = 'Default';
			$a_GraphFormat[0]['id'] = '0';
            $a_GraphFormat[1]['name'] = '2 Graphs, 2 Columns';
			$a_GraphFormat[1]['id'] = '2x2';
			form_dropdown("pageGraphFormat",$a_GraphFormat, "name", "id", $i_defaultGraphFormat, "" ,$i_defaultGraphFormat ,"","");
			?>
		</td>
	</tr>
	<?php

    if ( $reportId > 0)
    {
    	form_hidden_box("update_component_import","1","");
        form_hidden_box("ReportId",$reportId,"");
    }
    else
    {
    	form_hidden_box("save_component_import","1","");
    }
	html_end_box();
    form_save_button("CereusReporting_Reports.php", "save");

// Grap Report Data

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
		  `graph_templates_graph`.`title_cache` AS `title_cache`, `host`.`description`,
		  `plugin_nmidCreatePDF_GraphReports`.`local_graph_id`,
		  `plugin_nmidCreatePDF_GraphReports`.`Id`
		FROM
		  `graph_local` LEFT JOIN
		  `graph_templates` ON (`graph_local`.`graph_template_id` =
			  `graph_templates`.`id`) INNER JOIN
		  `plugin_nmidCreatePDF_GraphReports` ON
			`plugin_nmidCreatePDF_GraphReports`.`local_graph_id` = `graph_local`.`id`
		  INNER JOIN
		  `host` ON `graph_local`.`host_id` = `host`.`id`, `graph_templates_graph`
		WHERE
		  `graph_local`.`id` = `graph_templates_graph`.`local_graph_id` AND
		  (`plugin_nmidCreatePDF_GraphReports`.`ReportId` = $reportId )
		ORDER BY `order`;
	");

	html_start_box("<strong>Local Graphs for this report</strong>", "100%", $colors["header"], "3", "center", "");

	if ( sizeof( $a_reports ) > 0 ) 
	{
		$menu_text = array(
			"Id" => array("ID", "ASC"),
			"title_cache" => array("Graph Name", "ASC"),
			"description" => array("Host Description", "ASC"),
			"local_graph_id" => array("Local Graph Id", "ASC"),
			"order_key" => array("Order", "ASC")
		);
	
		html_header_sort_checkbox($menu_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);
		form_hidden_box("delete_graphs","1","");
		form_hidden_box("ReportId",$reportId,"");
		$i = 0;
	
		foreach ($a_reports as $s_report)
		{
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $s_report['Id']); $i++;
			form_selectable_cell($s_report['Id'], $s_report["Id"]);
			form_selectable_cell($s_report['title_cache'], $s_report["Id"]);
			form_selectable_cell($s_report['description'], $s_report["Id"]);
			form_selectable_cell($s_report['local_graph_id'], $s_report["Id"]);
			print "<td>
				<a href='CereusReporting_orderGraphs.php?action=item_movedown&report_id=".$reportId."&graph_id=" . $s_report['Id'] . "'><img src='".$config['url_path']."images/move_down.gif' border='0' alt='Move Down'></a><a href='CereusReporting_orderGraphs.php?action=item_moveup&report_id=".$reportId."&graph_id=" . $s_report['Id'] . "'><img src='".$config['url_path']."images/move_up.gif' border='0' alt='Move Up'></a>
				</td>\n";
			
			form_checkbox_cell('selected_items', $s_report["Id"]);
			
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
		print "<tr><td><em>No Graphs exist</em></td></tr>";
		html_end_box(false);
	}
	
	print "</form>";
}

// function to add a local_graph_id to a report
function addGraphToReport( $lgID, $reportId ) {
	$current_order_max = db_fetch_cell("select MAX(`order`) from plugin_nmidCreatePDF_GraphReports where ReportId=".$reportId);
	if (!(isset($current_order_max))) {
		$current_order_max = 0;
	}
	$current_order_max = $current_order_max + 1;
	db_execute("INSERT INTO `plugin_nmidCreatePDF_GraphReports` (`ReportId`, `local_graph_id`,`Description`,`order`) VALUES ('$reportId', '$lgID','','$current_order_max')");
}

include_once("./include/bottom_footer.php");

?>
