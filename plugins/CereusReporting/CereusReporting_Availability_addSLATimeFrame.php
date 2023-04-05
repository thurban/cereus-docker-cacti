<?php
/*******************************************************************************
 *
 * File:         $Id: CereusReporting_Availability_addSLATimeFrame.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
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
if (!isset($_REQUEST["dataId"])) { $_REQUEST["dataId"] = ""; }
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }
if (!isset($_REQUEST["defaultDays"])) { $_REQUEST["defaultDays"] = ""; }

switch ($_REQUEST["action"]) {
    case 'save':
		form_save($_REQUEST["dataId"]);
		break;
	default:
    
		include_once("./include/top_header.php");

		form_display( $_REQUEST["dataId"], $_REQUEST["defaultDays"] );
		include_once("./include/bottom_footer.php");
		break;
}

function form_save( $dataId ) {
    global $colors, $hash_type_names;

    $db = DBCxn::get();
	if (isset ($_REQUEST['defaultStartTime'])) { $s_defaultStartTime = $_REQUEST['defaultStartTime']; }
	if (isset ($_REQUEST['defaultEndTime'])) { $s_defaultEndTime = htmlentities(strip_tags($_REQUEST['defaultEndTime'])); }
	if (isset ($_REQUEST['shortDescription'])) { $s_dataShortDescription= $_REQUEST['shortDescription']; }
	if (isset ($_REQUEST['longDescription'])) { $s_dataLongDescription = $_REQUEST['longDescription']; }

	$s_defaultDays = "";
	if (isset ($_REQUEST['day_mon'])) { $s_defaultDays = $s_defaultDays . 'Mon,'; }
	if (isset ($_REQUEST['day_tue'])) { $s_defaultDays = $s_defaultDays . 'Tue,'; }
	if (isset ($_REQUEST['day_wed'])) { $s_defaultDays = $s_defaultDays . 'Wed,'; }
	if (isset ($_REQUEST['day_thu'])) { $s_defaultDays = $s_defaultDays . 'Thu,'; }
	if (isset ($_REQUEST['day_fri'])) { $s_defaultDays = $s_defaultDays . 'Fri,'; }
	if (isset ($_REQUEST['day_sat'])) { $s_defaultDays = $s_defaultDays . 'Sat,'; }
	if (isset ($_REQUEST['day_sun'])) { $s_defaultDays = $s_defaultDays . 'Sun,'; }

	$s_defaultDays = preg_replace("/,$/","",$s_defaultDays);

	if (isset ($_REQUEST['slaId'])) { $s_defaultSlaId = $_REQUEST['slaId']; }

	if ( (isset ($_REQUEST['shortDescription'])) && (isset ($_REQUEST['save_component_import']) ) ) {
		if ( $s_defaultSlaId > 0 ) {
			$sql = "
				INSERT INTO `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
					(`id`,`defaultDays`, `defaultStartTime`, `defaultEndTime`,`shortDescription`, `longDescription`)
				VALUES
					(:s_defaultSlaId, :s_defaultDays, :s_defaultStartTime,:s_defaultEndTime, :s_dataShortDescription,
					:s_dataLongDescription)
				";
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':s_defaultSlaId', $s_defaultSlaId, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultDays', $s_defaultDays, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultStartTime', $s_defaultStartTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultEndTime', $s_defaultEndTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataShortDescription', $s_dataShortDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataLongDescription', $s_dataLongDescription, PDO::PARAM_STR );
            $stmt->execute();
		}
		else {
			$sql = "
				INSERT INTO `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
					(`defaultDays`, `defaultStartTime`, `defaultEndTime`,`shortDescription`, `longDescription`)
				VALUES
					(:s_defaultDays, :s_defaultStartTime,:s_defaultEndTime, :s_dataShortDescription,
					:s_dataLongDescription)
				";
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':s_defaultDays', $s_defaultDays, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultStartTime', $s_defaultStartTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultEndTime', $s_defaultEndTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataShortDescription', $s_dataShortDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataLongDescription', $s_dataLongDescription, PDO::PARAM_STR );
            $stmt->execute();
		}

    }
    else if ( (isset ($_REQUEST['shortDescription'])) && (isset ($_REQUEST['update_component_import']) ) ) {
		if ( $s_defaultSlaId > 0 ) {
			$sql = "
			UPDATE `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
			Set
				Id=:s_defaultSlaId,
				defaultDays=:s_defaultDays,
				defaultStartTime=:s_defaultStartTime,
				defaultEndTime=:s_defaultEndTime,
				shortDescription=:s_dataShortDescription,
				longDescription=:s_dataLongDescription
			WHERE
				Id=:dataId
			AND
				defaultDays=:s_oldDefaultDays
			";
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':s_defaultSlaId', $s_defaultSlaId, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultDays', $s_defaultDays, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultStartTime', $s_defaultStartTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultEndTime', $s_defaultEndTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataShortDescription', $s_dataShortDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataLongDescription', $s_dataLongDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':dataId', $dataId, PDO::PARAM_STR );
            $stmt->bindValue( ':s_oldDefaultDays', $s_oldDefaultDays, PDO::PARAM_STR );
            $stmt->execute();
		}
		else {
			$sql = "
			UPDATE `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
			Set
				defaultDays=:s_defaultDays,
				defaultStartTime=:s_defaultStartTime,
				defaultEndTime=:s_defaultEndTime,
				shortDescription=:s_dataShortDescription,
				longDescription=:s_dataLongDescription
			WHERE
				Id=:dataId
			AND
				defaultDays=:s_oldDefaultDays
			";
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':s_defaultDays', $s_defaultDays, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultStartTime', $s_defaultStartTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultEndTime', $s_defaultEndTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataShortDescription', $s_dataShortDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataLongDescription', $s_dataLongDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':dataId', $dataId, PDO::PARAM_STR );
            $stmt->bindValue( ':s_oldDefaultDays', $s_oldDefaultDays, PDO::PARAM_STR );
            $stmt->execute();
		}		
    }
    header("Location: CereusReporting_Availability_SLATimeFrame.php");
}

function form_display( $dataId, $defaultDays ) {
    global $colors, $hash_type_names, $config;
    
	$s_defaultDays = 'Mon,Tue,Wed,Thu,Fri';
	$s_defaultStartTime = '08:00';
	$s_defaultEndTime = '17:00'; 
	$s_defaultShortDescription = '';
	$s_defaultLongDescription = '';
	$s_oldDefaultDays =  '';	
    if ( $dataId > 0 )
    {
        $a_reports = db_fetch_assoc("
			SELECT
			 `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`longDescription`
		        FROM
			 `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
			WHERE
				Id='$dataId'
			AND
				defaultDays='$defaultDays'
		");
        foreach ($a_reports as $s_report)
        {
            $s_defaultDays = $s_report['defaultDays'];
			$s_oldDefaultDays = $s_defaultDays;
            $s_defaultStartTime = $s_report['defaultStartTime'];
			$s_defaultEndTime = $s_report['defaultEndTime'];	// 1 = true
		    $s_defaultShortDescription = $s_report['shortDescription'];
			$s_defaultLongDescription = $s_report['longDescription'];
		}
    }
    
    print "<font size=+1>CereusReporting - Add SLA TimeFrame Data</font><br>\n";
    print "<hr>\n";


	?>
	<form method="post" action="CereusReporting_Availability_addSLATimeFrame.php" enctype="multipart/form-data">
	<?php

	if ( $dataId > 0 ) {
		html_start_box("<strong>Availability SLA TimeFrame</strong> [update]", "100%", $colors["header"], "3", "center", "");
	}
	else {
		html_start_box("<strong>Availability SLA TimeFrame</strong> [new]", "100%", $colors["header"], "3", "center", "");		
	}

	form_hidden_box("oldDefaultDays",$s_oldDefaultDays,"");
	
	form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Associated SLA TimeFrame</font><br>
			The SLA TimeFrame this item belongs to.
		</td>
		<td>
			
   			<?php
			    $a_SLATimeFrame = db_fetch_assoc("
				 SELECT
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id` as id,
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`id` as name
				FROM
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
				ORDER BY
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`;
			");
			$a_SLATimeFrame[-1]['id'] = 0;
			$a_SLATimeFrame[-1]['name'] = '<new>';
			form_dropdown("slaId",$a_SLATimeFrame, "name", "id", $dataId, "" ,$dataId ,"","");
			?>
		</td>
	</tr>
	
	<?php  form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
		    <font class="textEditTitle">Default Days</font><br>
		    The days for which SLA should be counted for.
		</td>
		<td>
   			<?php
			$day_mon = 'off';
			$day_tue = 'off';
			$day_wed = 'off';
			$day_thu = 'off';
			$day_fri = 'off';
			$day_sat = "";
			$day_sun = "";
			if ( preg_match("/Mon/",$s_defaultDays ) ) {
			    $day_mon = 'on';
			}
			if ( preg_match("/Tue/",$s_defaultDays ) ) {
			    $day_tue = 'on';
			}
			if ( preg_match("/Wed/",$s_defaultDays ) ) {
			    $day_wed = 'on';
			}
			if ( preg_match("/Thu/",$s_defaultDays ) ) {
			    $day_thu = 'on';
			}
			if ( preg_match("/Fri/",$s_defaultDays ) ) {
			    $day_fri = 'on';
			}
			if ( preg_match("/Sat/",$s_defaultDays ) ) {
			    $day_sat = 'on';
			}
			if ( preg_match("/Sun/",$s_defaultDays ) ) {
			    $day_sun = 'on';
			}
			form_checkbox("day_mon",$day_mon,"Monday","");
			//print "Start Time";
			//form_text_box("defaultMonStartTime","",$s_defaultMonStartTime,255);
			//print "End Time";
			//form_text_box("defaultMonEndTime","",$s_defaultMonEndTime,255);

			form_checkbox("day_tue",$day_tue,"Tuesday","");
			//print "Start Time";
			//form_text_box("defaultTueStartTime","",$s_defaultTueStartTime,255);
			//print "End Time";
			//form_text_box("defaultTueEndTime","",$s_defaultTueEndTime,255);

			form_checkbox("day_wed",$day_wed,"Wednesday","");
			//print "Start Time";
			//form_text_box("defaultWedStartTime","",$s_defaultWedStartTime,255);
			//print "End Time";
			//form_text_box("defaultWedEndTime","",$s_defaultWedEndTime,255);

			form_checkbox("day_thu",$day_thu,"Thursday","");
			//print "Start Time";
			//form_text_box("defaultThuStartTime","",$s_defaultThuStartTime,255);
			//print "End Time";
			//form_text_box("defaultThuEndTime","",$s_defaultThuEndTime,255);
			
			form_checkbox("day_fri",$day_fri,"Friday","");
			//print "Start Time";
			//form_text_box("defaultFriStartTime","",$s_defaultFriStartTime,255);
			//print "End Time";
			//form_text_box("defaultFriEndTime","",$s_defaultFriEndTime,255);

			form_checkbox("day_sat",$day_sat,"Saturday","");
			//print "Start Time";
			//form_text_box("defaultSatStartTime","",$s_defaultSatStartTime,255);
			//print "End Time";
			//form_text_box("defaultSatEndTime","",$s_defaultSatEndTime,255);

			form_checkbox("day_sun",$day_sun,"Sunday","");
			//print "Start Time";
			//form_text_box("defaultSunStartTime","",$s_defaultSunStartTime,255);
			//print "End Time";
			//form_text_box("defaultSunEndTime","",$s_defaultSunEndTime,255);
			?>
		</td>
	</tr>


	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Start Time</font><br>
			The time when the SLA measurement starts. Should be in the format hh:mm.
		</td>
		<td>
			<?php  form_text_box("defaultStartTime","",$s_defaultStartTime,255); ?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">End Time</font><br>
			The time when the SLA measurement ends. Should be in the format hh:mm.
		</td>
		<td>
			<?php  form_text_box("defaultEndTime","",$s_defaultEndTime,255); ?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Short Description</font><br>
			A short description which will be shown on the graph at the start and end of the time frame.
		</td>
		<td>
			<?php  form_text_box("shortDescription","",$s_defaultShortDescription,255); ?>
		</td>
	</tr>

	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Long Description</font><br>
			This is the long description of the outtage/change.
		</td>
		<td>
				<?php form_text_area("longDescription",$s_defaultLongDescription,5,50,""); ?>
		</td>
	</tr>

	<?php

    if ( $dataId > 0)
    {
    	form_hidden_box("update_component_import","1","");
        form_hidden_box("dataId",$dataId,"");
    }
    else
    {
    	form_hidden_box("save_component_import","1","");
    }
	html_end_box();
    form_save_button("CereusReporting_Availability_SLATimeFrame.php", "save");
	
}

include_once("./include/bottom_footer.php");

?>
