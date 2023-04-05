<?php
/*******************************************************************************

 File:         $Id: CereusReporting_Backup.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
 Modified_On:  $Date: 2017/11/01 15:05:58 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );

$mainDir = preg_replace("@plugins.CereusReporting@","",__DIR__);

chdir( $mainDir );
include_once("./include/auth.php");
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
	case '1':
		form_backup();
		break;
	case '2':
		form_restore();
		break;
	default:
		cr_top_header();
		form_display();
		cr_bottom_footer();
		break;
}

function form_restore() {
    global $colors, $hash_type_names;
    /* loop through each of the selected items and add them to the backup file */
	foreach ( $_POST as $var => $val) {
		if (preg_match("/^chk_(.+)$/", $var, $matches)) {
			$filename = __DIR__.'/backup/'.$matches[1].'.xml';
			if(file_exists( $filename )) {
			error_reporting(0);
			$xml = simplexml_load_file( $filename );
				if($xml) {
					// Add the report
					$reportId = _addReport($xml->header->name,
										   $xml->header->Description,
										   $xml->header->reportType,
								   		   $xml->header->includeSubDirs,
							      		   $xml->header->type,
							      		   $xml->header->pageSize,
							      		   $xml->header->pageOrientation,
							      		   $xml->header->pageGraphFOrmat,
							      		   $xml->header->logo,
							      		   $xml->header->coverPage,
							      		   $xml->header->outputType
							      		   );
					
					if ( $xml->header->reportType == "3" ) {
						// Restore the report items for the mutli graph report 
						foreach($xml->items as $item) {
							_addReportItem($reportId,
										   $item->type,
										   $item->data,
										   $item->order
										   );
						}
					}
					else {
						// Restore the report data for the normal graph report 
						foreach($xml->data as $dataItem) {
							_addReportData($reportId,
										   $dataItem->local_graph_id,
										   $dataItem->Description,
										   $dataItem->order
										   );
						}
					}

					// Restore the Report schedules			
					foreach( $xml->schedule as $schedule ) {
						_addSchedule($schedule->Name,
									 $schedule->Date,
									 $schedule->isRecurring,
									 $schedule->frequency,
									 $schedule->Status,
									 $reportId,
									 $schedule->Recipients,
									 $schedule->Description,
									 $schedule->archiveReport
									);
					}
				}
			}
			else {
				print "File [$filename] does not exist.<br>";
			}
			error_reporting(1);
		}
	}
    header("Location: CereusReporting_Backup.php");
}

function _addReportData($i_reportId, $i_localGraphId, $s_description, $i_order ) {
	//print "INSERT INTO `plugin_nmidCreatePDF_GraphReports` (`ReportId`, `local_graph_id`,`Description`,`order`) VALUES ('$i_reportId', '$i_localGraphId',$s_description,'$i_order')<br>";
	db_execute("INSERT INTO `plugin_nmidCreatePDF_GraphReports` (`ReportId`, `local_graph_id`,`Description`,`order`,`group`) VALUES ($i_reportId, $i_localGraphId,'$s_description',$i_order,0)");
}

function _addReportItem($i_reportId, $s_type, $s_data, $i_order ) {
	//print "INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ('$i_reportId', '$s_type','$s_data','$i_order')<br>";
	db_execute("INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports` (`ReportId`, `type`,`data`,`order`) VALUES ($i_reportId, '$s_type','$s_data',$i_order)");
}

function _addReport($s_reportName,$s_reportDescription,$i_reportReportType,$i_reportIncludeSubDirs,
					$i_reportType,$s_reportPageSize,$s_reportPageOrientation,$s_reportPageGraphFormat,
					$s_reportCoverLogo,$s_reportCoverPage,$i_reportOutputType) {
	db_execute("
	INSERT INTO `plugin_nmidCreatePDF_Reports`
		(
		 `Name`, `Description`,`reportType`, `leafId`, `includeSubDirs`,
		 `type`,`pageSize`,`pageOrientation`, `pageGraphFormat`, `Logo`,
		 `CoverPage`,`outputType`
		)
	VALUES
		(
		 '$s_reportName', '$s_reportDescription','$i_reportReportType',
		 '-1','$i_reportIncludeSubDirs', '$i_reportType', '$s_reportPageSize',
		 '$s_reportPageOrientation', '$s_reportPageGraphFormat','$s_reportCoverLogo',
		 '$s_reportCoverPage','$i_reportOutputType'
		)
	");

	$reportId = db_fetch_cell("
		SELECT
		  `ReportId`
		FROM
		  `plugin_nmidCreatePDF_Reports` 
		WHERE
			Name='$s_reportName'
		AND reportType='$i_reportReportType'
		AND includeSubDirs='$i_reportIncludeSubDirs'
		AND Description='$s_reportDescription'
		AND pageSize='$s_reportPageSize'
		AND Logo='$s_reportCoverLogo'
		AND CoverPage='$s_reportCoverPage'
		AND pageOrientation='$s_reportPageOrientation'
		AND pageGraphFormat='$s_reportPageGraphFormat'
		AND type='$i_reportType'
		AND outputType='$i_reportOutputType'
	   ");

	return $reportId;
}

function _addSchedule($s_scheduleName,$s_scheduleDate,$i_scheduleIsRecurring,
			$s_scheduleFrequency, $i_scheduleScheduleStatus, $s_scheduleReportId,
			$s_scheduleRecipients, $s_scheduleDescription,
			$i_archiveReport ) {
	
	db_execute("
		INSERT INTO `plugin_nmidCreatePDF_Reports_scheduler`
			(`Name`, `Date`, `isRecurring`,`frequency`, `Status`, `ReportID`,`Recipients`,`Description`,`archiveReport`)
		VALUES
			('$s_scheduleName', '$s_scheduleDate','$i_scheduleIsRecurring', '$s_scheduleFrequency',
			'$i_scheduleScheduleStatus', '$s_scheduleReportId', '$s_scheduleRecipients',
			'$s_scheduleDescription','$i_archiveReport')
		");	
}

function form_backup() {
    global $colors, $hash_type_names;
    
    /* loop through each of the selected items and add them to the backup file */
	foreach ( $_POST as $var => $val) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */
            //db_execute("DELETE FROM `plugin_nmidCreatePDF_Reports` where `ReportId`='" . $matches[1] . "'");
			
			$somecontent = '';
			$filename = 'unknown';
			
			$a_reports = db_fetch_assoc("
				SELECT
				  `plugin_nmidCreatePDF_Reports`.`ReportId`,
				  `plugin_nmidCreatePDF_Reports`.`Name`,
				  `plugin_nmidCreatePDF_Reports`.`Logo`,
				  `plugin_nmidCreatePDF_Reports`.`CoverPage`,
				  `plugin_nmidCreatePDF_Reports`.`includeSubDirs`,
				  `plugin_nmidCreatePDF_Reports`.`leafId`,
				  `plugin_nmidCreatePDF_Reports`.`reportType`,
				  `plugin_nmidCreatePDF_Reports`.`type`,
				  `plugin_nmidCreatePDF_Reports`.`pageSize`,
				  `plugin_nmidCreatePDF_Reports`.`pageOrientation`,
				  `plugin_nmidCreatePDF_Reports`.`pageGraphFormat`,
				  `plugin_nmidCreatePDF_Reports`.`outputType`,
				  `plugin_nmidCreatePDF_Reports`.`Description`
				FROM
				  `plugin_nmidCreatePDF_Reports` 
				WHERE
				  `plugin_nmidCreatePDF_Reports`.`ReportId` = ".$matches[1]."
			   ");
			
			if ( sizeof( $a_reports ) > 0 )
			{
				foreach ($a_reports as $s_report)
		        {
					$somecontent .= '<?xml version=\'1.0\'?>
				<report>
					<header>
						<name>'.makeXMLClean( $s_report['Name'] ).'</name>
						<logo>'.makeXMLClean( $s_report['Logo'] ).'</logo>
						<coverPage>'.makeXMLClean( $s_report['CoverPage'] ).'</coverPage>
						<includeSubDirs>'.makeXMLClean( $s_report['includeSubDirs'] ).'</includeSubDirs>
						<leafId>'.makeXMLClean( $s_report['leafId'] ).'</leafId>
						<reportType>'.makeXMLClean( $s_report['reportType'] ).'</reportType>
						<type>'.makeXMLClean( $s_report['type'] ).'</type>
						<pageSize>'.makeXMLClean( $s_report['pageSize'] ).'</pageSize>
						<pageOrientation>'.makeXMLClean( $s_report['pageOrientation'] ).'</pageOrientation>
						<pageGraphFormat>'.makeXMLClean( $s_report['pageGraphFormat'] ).'</pageGraphFormat>
						<outputType>'.makeXMLClean( $s_report['outputType'] ).'</outputType>
						<Description>'.makeXMLClean( $s_report['Description'] ).'</Description>
					</header>';
					$filename = makeSafe( $s_report['Name'] );
					$filename = str_replace(" ","_",$filename);
					$filename = __DIR__.'/backup/'.$filename.'.xml';
				}
			}
			
			
			$a_graphItems = db_fetch_assoc("
				SELECT
				  `plugin_nmidCreatePDF_GraphReports`.`ReportId`,
				  `plugin_nmidCreatePDF_GraphReports`.`local_graph_id`,
				  `plugin_nmidCreatePDF_GraphReports`.`order`,
				  `plugin_nmidCreatePDF_GraphReports`.`group`,
				  `plugin_nmidCreatePDF_GraphReports`.`Description`
				FROM
				  `plugin_nmidCreatePDF_GraphReports`
				WHERE
				  `plugin_nmidCreatePDF_GraphReports`.`ReportId` = ".$matches[1]."
			");


			if ( count( $a_graphItems ) > 0 ) {
				foreach ($a_graphItems as $s_graphItem)
				{
					$somecontent .= '<data>
						<local_graph_id>'.makeXMLClean( $s_graphItem['local_graph_id'] ).'</local_graph_id>
						<order>'.makeXMLClean( $s_graphItem['order'] ).'</order>
						<group>'.makeXMLClean( $s_graphItem['group'] ).'</group>
						<Description>'.makeXMLClean( $s_graphItem['Description'] ).'</Description>
					</data>'."\n";
				}
			}
			
			$a_multiGraphItems = db_fetch_assoc("
				SELECT
				  `plugin_nmidCreatePDF_MultiGraphReports`.`ReportId`,
				  `plugin_nmidCreatePDF_MultiGraphReports`.`order`,
				  `plugin_nmidCreatePDF_MultiGraphReports`.`data`,
				  `plugin_nmidCreatePDF_MultiGraphReports`.`type`
				FROM
				  `plugin_nmidCreatePDF_MultiGraphReports`
				WHERE
				  `plugin_nmidCreatePDF_MultiGraphReports`.`ReportId` = ".$matches[1]."
			");

			if ( count( $a_multiGraphItems ) > 0 ) {
				foreach ($a_multiGraphItems as $s_multiGraphItem)
				{
					$somecontent .= '<items>
						<order>'.makeXMLClean( $s_multiGraphItem['order'] ).'</order>
						<data>'.makeXMLClean( $s_multiGraphItem['data'] ).'</data>
						<type>'.makeXMLClean( $s_multiGraphItem['type'] ).'</type>
					</items>'."\n";
				}
			}
			
			
			$a_scheduleItems = db_fetch_assoc("
			SELECT
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Description`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Recipients`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Name`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Date`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`isRecurring`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`archiveReport`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`frequency`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Status`
			FROM
			  `plugin_nmidCreatePDF_Reports_scheduler`
			WHERE `ReportId` = ".$matches[1]."
			");

			if ( count( $a_scheduleItems ) > 0 ) {
				foreach ($a_scheduleItems as $a_scheduleItem)
				{
					$somecontent .= '<schedule>
						<Description>'.makeXMLClean( $a_scheduleItem['Description'] ).'</Description>
						<Recipients>'.makeXMLClean( $a_scheduleItem['Recipients'] ).'</Recipients>
						<Name>'.makeXMLClean( $a_scheduleItem['Name'] ).'</Name>
						<Date>'.makeXMLClean( $a_scheduleItem['Date'] ).'</Date>
						<isRecurring>'.makeXMLClean( $a_scheduleItem['isRecurring'] ).'</isRecurring>
						<archiveReport>'.makeXMLClean( $a_scheduleItem['archiveReport'] ).'</archiveReport>
						<frequency>'.makeXMLClean( $a_scheduleItem['frequency'] ).'</frequency>
						<Status>'.makeXMLClean( $a_scheduleItem['Status'] ).'</Status>
					</schedule>'."\n";
				}
			}

			$somecontent .= "\n</report>";

			
			// if (is_writable($filename)) {
				if (!$handle = fopen($filename, "w")) {
					//print "Kann die Datei $filename nicht �ffnen";
					//exit;
			    }
		   
			    // Schreibe $somecontent in die ge�ffnete Datei.
			    if (!fwrite($handle, $somecontent)) {
				    //print "Kann in die Datei $filename nicht schreiben";
				   //exit;
			    }

			    fclose($handle);
			// }
			// else {
			// 	print "$filename is not writeable";
			// }
			
        }
	}
    header("Location: CereusReporting_Backup.php");
}

function form_edit() {
    
}

function form_display() {
    global $colors, $hash_type_names;
	$limit = 100;
    print "<font size=+1>CereusReporting - Report Backup</font><br>\n";
    print "<hr>\n";
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

    print "<form name=chk method=POST action=CereusReporting_Backup.php>\n";

    html_start_box("<strong>Report Backup</strong>", "100%", $colors["header"], "3", "center", "");

    form_hidden_box("save_component_import","1","");

    if ( sizeof( $a_reports ) > 0 ) 
    {
        $menu_text = array(
            "ID" => array("ReportId", "ASC"),
            "Name" => array("Name", "ASC"),
            "Description" => array("Description", "ASC"),
            "reportType" => array("Report Type", "ASC"),
            "type" => array("Schedule Type", "ASC")
        );
    
        html_header_sort_checkbox($menu_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);
    
        $i = 0;
		
		$a_reportType = array();
		$a_reportType[0] = 'Normal Report';
		$a_reportType[1] = 'Graph Report';
		$a_reportType[2] = 'DSStats Report';
		$a_reportType[3] = 'Multi Report';
    
        foreach ($a_reports as $s_report)
        {
            form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $s_report['ReportId']); $i++;
            form_selectable_cell($s_report['ReportId'], $s_report["ReportId"]);
            form_selectable_cell("<a href='CereusReporting_addReport.php?action=update&ReportType=".$s_report['reportType']."&ReportId=".$s_report["ReportId"]."'><b>".$s_report['Name']."</b></a>",$s_report['ReportId'],250);
			$description = $s_report['Description'];
			$description = preg_replace("/<br>/","",$description);
			if ( strlen ( $description ) > $limit ) {
				$description = substr($description, 0, strrpos(substr($description, 0, $limit), ' ')) . '...';
			}
            form_selectable_cell($description, $s_report["ReportId"]);
            form_selectable_cell($a_reportType[ $s_report['reportType'] ], $s_report["ReportId"]);
            form_selectable_cell($s_report['type'], $s_report["ReportId"]);
            form_checkbox_cell('selected_items', $s_report["ReportId"]);
            form_end_row();
        }
        html_end_box(false);

		$task_actions = array(
		1 => "Backup"
		);
		draw_actions_dropdown($task_actions);
	}		
    else
    {
		print "<tr><td><em>No Reports exist</em></td></tr>";
        html_end_box(false);
    }
		
    print "</form>";
		
    print "<br/><br/><br/><form name=chk method=POST action=CereusReporting_Backup.php>\n";

    html_start_box("<strong>Report Restore</strong>", "100%", $colors["header"], "3", "center", "");

    form_hidden_box("save_component_import","1","");

	$fileCount = 0;
	if (is_dir( __DIR__.'/backup/' )) {
		if ($dh = opendir( __DIR__.'/backup/' )) {
			while (($file = readdir($dh)) !== false) {
				if (!(is_dir( $file ) )) {
					if (preg_match('/.xml$/',$file) ){
						$fileCount++;//echo "filename: $file : filetype: " . filetype(__DIR__ . $file) . "\n";
					}
				}
			}
			closedir($dh);
		}
	}
    if ( $fileCount > 0 ) 
    {
        $menu_text = array(
            "File" => array("File", "ASC"),
            "Name" => array("Name", "ASC"),
            "Description" => array("Description", "ASC"),
            "reportType" => array("Report Type", "ASC"),
            "type" => array("Schedule Type", "ASC")
        );
    
        html_header_sort_checkbox($menu_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);
    
        $i = 0;
		
		
		$a_reportType = array();
		$a_reportType[0] = 'Normal Report';
		$a_reportType[1] = 'Graph Report';
		$a_reportType[2] = 'DSStats Report';
		$a_reportType[3] = 'Multi Report';

		if (is_dir( __DIR__.'/backup/' )) {
			if ($dh = opendir( __DIR__.'/backup/' )) {
				while (($file = readdir($dh)) !== false) {
					if (!(is_dir( $file ) )) {
						if(file_exists( __DIR__.'/backup/'.$file )) {
							if (preg_match('/.xml$/',$file) ){
								$xml = simplexml_load_file( __DIR__.'/backup/'.$file );
								if($xml) {
									$xml->header->name;
									form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $file); $i++;
									form_selectable_cell( basename($file), str_replace('.xml','',basename($file)) );
									form_selectable_cell( $xml->header->name, str_replace('.xml','',basename($file)),250 );
									//form_selectable_cell("<a href='CereusReporting_addReport.php?action=update&ReportType=".$s_report['reportType']."&ReportId=".$s_report["ReportId"]."'><b>".$s_report['Name']."</b></a>",str_replace('.xml','',basename($file)) ,250);
									$description = $xml->header->Description;
									$description = preg_replace("/<br>/","",$description);
									if ( strlen ( $xml->header->Description ) > $limit ) {
										$description = substr($xml->header->Description, 0, strrpos(substr($xml->header->Description, 0, $limit), ' ')) . '...';
									}
									form_selectable_cell($description, str_replace('.xml','',basename($file)) );
									if (  $xml->header->reportType == "0" ) {
										$type = 0;
									}
									elseif (  $xml->header->reportType == "1" ) {
										$type = 1;
									}
									elseif (  $xml->header->reportType == "2" ) {
										$type = 2;
									}
									elseif (  $xml->header->reportType == "3" ) {
										$type = 3;
									}
									form_selectable_cell($a_reportType[ $type ], str_replace('.xml','',basename($file)) );
									form_selectable_cell($xml->header->type,str_replace('.xml','',basename($file)) );
									form_checkbox_cell('selected_items', str_replace('.xml','',basename($file)) );
									form_end_row();
								}
							}
						}
					}
				}
				closedir($dh);
			}
		}    

        html_end_box(false);

		$task_actions = array(
		2 => "Restore"
		);
		draw_actions_dropdown($task_actions);
		
				
    }
    else
    {
		print "<tr><td><em>No Reports exist</em></td></tr>";
        html_end_box(false);
    }
    
    print "</form>";
}    


?>
