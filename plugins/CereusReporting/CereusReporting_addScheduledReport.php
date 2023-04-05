<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_addScheduledReport.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
	 * Modified_On:  $Date: 2018/11/11 17:22:55 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	* Encoding:     UTF-8
	* Status:       -
	* License:      Commercial
	* Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
	$dir = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );
	include_once( "./include/auth.php" );
	include_once( "./lib/data_query.php" );
	$_SESSION[ 'custom' ] = FALSE;

    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "header" ] = '';


	/* set default action */
	if ( !isset( $_REQUEST[ "ScheduleId" ] ) ) {
		$_REQUEST[ "ScheduleId" ] = "";
	}
	if ( !isset( $_REQUEST[ "action" ] ) ) {
		$_REQUEST[ "action" ] = "";
	}

	switch ( $_REQUEST[ "action" ] ) {
		case 'save':
			form_save( $_REQUEST[ "ScheduleId" ] );
			break;
		default:
			cr_top_header();
			form_display( $_REQUEST[ "ScheduleId" ] );
			cr_bottom_footer();
			break;
	}

	function form_save( $scheduleId )
	{
		global $colors, $hash_type_names;
		$i_scheduleScheduleStatus = 0;
		$i_scheduleIsRecurring    = 0;
		$i_archiveReport          = 0;
		$s_scheduleAttachment     = '';
		$i_archiveUserGroupId     = 0;

        // Get DB Instance
		$db = DBCxn::get();

    	if ( isset ( $_REQUEST[ 'Name' ] ) ) {
			$s_scheduleName = htmlentities( strip_tags( $_REQUEST[ 'Name' ] ) );
		}
		if ( isset ( $_REQUEST[ 'Recipients' ] ) ) {
			$s_scheduleRecipients = filter_input( INPUT_POST, 'Recipients', FILTER_UNSAFE_RAW );
		}
		if ( isset ( $_REQUEST[ 'RecipientsBcc' ] ) ) {
			$s_scheduleRecipientsBcc = filter_input( INPUT_POST, 'RecipientsBcc', FILTER_UNSAFE_RAW );
		}
		if ( isset ( $_REQUEST[ 'reportId' ] ) ) {
			$s_scheduleReportId = htmlentities( strip_tags( $_REQUEST[ 'reportId' ] ) );
		}
		if ( isset ( $_REQUEST[ 'scheduletime' ] ) ) {
			$s_scheduleDate = strtotime( $_REQUEST[ 'scheduletime' ] );
		}
		if ( isset ( $_REQUEST[ 'recurringFrequency' ] ) ) {
			$s_scheduleFrequency = htmlentities( strip_tags( $_REQUEST[ 'recurringFrequency' ] ) );
		}
		if ( isset ( $_REQUEST[ 'archiveUserGroup' ] ) ) {
			$i_archiveUserGroupId = filter_input( INPUT_POST, 'archiveUserGroup', FILTER_UNSAFE_RAW );
		}
		if ( isset ( $_REQUEST[ 'Attachment' ] ) ) {
			$s_scheduleAttachment = filter_input( INPUT_POST, 'Attachment', FILTER_UNSAFE_RAW );
		}
		if ( isset ( $_REQUEST[ 'isRecurring' ] ) ) {
			$i_scheduleIsRecurring = htmlentities( strip_tags( $_REQUEST[ 'isRecurring' ] ) );
			if ( $i_scheduleIsRecurring == "on" ) {
				$i_scheduleIsRecurring = '1';
			}
			else {
				$i_scheduleIsRecurring = '0';
			}
		}
		if ( isset ( $_REQUEST[ 'isActive' ] ) ) {
			$i_scheduleScheduleStatus = strip_tags( $_REQUEST[ 'isActive' ] );
			if ( $i_scheduleScheduleStatus == "on" ) {
				$i_scheduleScheduleStatus = '1';
			}
			else {
				$i_scheduleScheduleStatus = '0';
			}
		}
		if ( isset ( $_REQUEST[ 'archiveReport' ] ) ) {
			$i_archiveReport = strip_tags( $_REQUEST[ 'archiveReport' ] );
			if ( $i_archiveReport == "on" ) {
				$i_archiveReport = '1';
			}
			else {
				$i_archiveReport = '0';
			}
		}
		else {
			$i_archiveReport = 0;
		}
		if ( isset ( $_REQUEST[ 'Description' ] ) ) {
			$s_scheduleDescription = filter_input( INPUT_POST, 'Description', FILTER_UNSAFE_RAW );
		}
        CereusReporting_logger( 'Saving Schedule Report ['.$s_scheduleName.']', 'debug', "schedule" );

        if ( ( isset ( $_REQUEST[ 'Name' ] ) ) && ( isset ( $_REQUEST[ 'save_component_import' ] ) ) ) {
            CereusReporting_logger( 'START: Saving NEW Report Schedule', 'debug', "schedule" );

            $myStmt = $db->prepare( "INSERT INTO `plugin_nmidCreatePDF_Reports_scheduler`
				(`Name`, `Date`, `isRecurring`,`frequency`, `Status`, `ReportID`,`Recipients`,`RecipientsBcc`,
				`Description`,`Attachments`,`archiveReport`,`archiveUserGroupId`,`lastRunDate`)
			VALUES
			(:name,:date,:isrecurring,:frequency,:status,:reportid,:recipients,:recipientsbcc,:description,
			 :attachments,:archivereport,:archiveusergroupid,'')" );
			$myStmt->bindValue( ':name', $s_scheduleName );
			$myStmt->bindValue( ':date', $s_scheduleDate );
			$myStmt->bindValue( ':isrecurring', $i_scheduleIsRecurring );
			$myStmt->bindValue( ':frequency', $s_scheduleFrequency );
			$myStmt->bindValue( ':status', $i_scheduleScheduleStatus );
			$myStmt->bindValue( ':reportid', $s_scheduleReportId );
			$myStmt->bindValue( ':recipients', $s_scheduleRecipients );
			$myStmt->bindValue( ':recipientsbcc', $s_scheduleRecipientsBcc );
			$myStmt->bindValue( ':description', $s_scheduleDescription );
			$myStmt->bindValue( ':attachments', $s_scheduleAttachment );
			$myStmt->bindValue( ':archivereport', $i_archiveReport );
			$myStmt->bindValue( ':archiveusergroupid', $i_archiveUserGroupId );
			if ( $myStmt->execute() == FALSE ) {
                CereusReporting_logger( 'SQL ERROR: Saving NEW Report Schedule', 'debug', "schedule" );
                $my_debug_sql_statement = "INSERT INTO `plugin_nmidCreatePDF_Reports_scheduler`
				(`Name`, `Date`, `isRecurring`,`frequency`, `Status`, `ReportID`,`Recipients`,`RecipientsBcc`,
				`Description`,`Attachments`,`archiveReport`,`archiveUserGroupId`,`lastRunDate`)
			    VALUES
			    ('$s_scheduleName','$s_scheduleDate',$i_scheduleIsRecurring,'$s_scheduleFrequency',
			    $i_scheduleScheduleStatus,$s_scheduleReportId,'$s_scheduleRecipients','$s_scheduleRecipientsBcc',
			    '$s_scheduleDescription','$s_scheduleAttachment',$i_archiveReport,$i_archiveUserGroupId,'')";
                CereusReporting_logger( 'SQL STATEMENT: '.$my_debug_sql_statement, 'debug', "schedule" );
            } else {
                CereusReporting_logger( 'SQL SAVE SUCCESSFULL: Saving NEW Report Schedule', 'debug', "schedule" );
            }
            CereusReporting_logger( 'FINISHED: Saving NEW Report Schedule', 'debug', "schedule" );
        }
		if ( ( isset ( $_REQUEST[ 'Name' ] ) ) && ( isset ( $_REQUEST[ 'update_component_import' ] ) ) ) {
            CereusReporting_logger( 'START: Saving UPDATED Report Schedule', 'debug', "schedule" );

            $myStmt = $db->prepare(
			'UPDATE `plugin_nmidCreatePDF_Reports_scheduler`
			Set
				Name=:name,
				Date=:date,
				isRecurring=:isrecurring,
				frequency=:frequency,
				Status=:status,
				ReportID=:reportid,
				Recipients=:recipients,
				RecipientsBcc=:recipientsbcc,
				Description=:description,
				Attachments=:attachments,
				archiveReport=:archivereport,
				archiveUserGroupId=:archiveusergroupid
			WHERE
				ScheduleId=:scheduleId
			');

			$myStmt->bindValue( ':name', $s_scheduleName );
			$myStmt->bindValue( ':date', $s_scheduleDate );
			$myStmt->bindValue( ':isrecurring', $i_scheduleIsRecurring );
			$myStmt->bindValue( ':frequency', $s_scheduleFrequency );
			$myStmt->bindValue( ':status', $i_scheduleScheduleStatus );
			$myStmt->bindValue( ':reportid', $s_scheduleReportId );
			$myStmt->bindValue( ':recipients', $s_scheduleRecipients );
			$myStmt->bindValue( ':recipientsbcc', $s_scheduleRecipientsBcc );
			$myStmt->bindValue( ':description', $s_scheduleDescription );
			$myStmt->bindValue( ':attachments', $s_scheduleAttachment );
			$myStmt->bindValue( ':archivereport', $i_archiveReport );
			$myStmt->bindValue( ':archiveusergroupid', $i_archiveUserGroupId );
			$myStmt->bindValue( ':scheduleId', $scheduleId );
            if ( $myStmt->execute() == FALSE ) {
                CereusReporting_logger( 'SQL ERROR: Saving UPDATED Report Schedule', 'debug', "schedule" );
            } else {
                CereusReporting_logger( 'SQL SAVE SUCCESSFULL: Saving NEW Report Schedule', 'debug', "schedule" );
            }
            CereusReporting_logger( 'FINISHED: Saving UPDATED Report Schedule', 'debug', "schedule" );

        }
		header( "Location: CereusReporting_scheduler.php" );
	}

	function form_display($scheduleId) {
	global $colors, $hash_type_names, $config;

	$s_defaultName = '';
	$s_defaultDescription = '';
	$s_defaultAttachment = '';
	$i_defaultReport = 0;
	$i_defaultIsRecurring = 0; // no
	$s_defaultRecurring = '';
	$i_defaultTime = time();
	$s_defaultRecipients = '';
	$s_defaultRecipientsBcc = '';
	$i_defaultScheduleStatus = '1'; // active
	$i_defaultArchiveReport = '0';
	$i_archiveUserGroupId = 0;

	if ( $scheduleId > 0 ) {
		$a_reports = db_fetch_assoc( "
			SELECT
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Description`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Attachments`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Recipients`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`RecipientsBcc`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`ScheduleId`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Name`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Date`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`isRecurring`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`archiveReport`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`frequency`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`Status`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`ReportID`,
			  `plugin_nmidCreatePDF_Reports_scheduler`.`archiveUserGroupId`
			FROM
			  `plugin_nmidCreatePDF_Reports_scheduler`
			WHERE ScheduleId='$scheduleId'
		" );
		foreach ( $a_reports as $s_report ) {
			$s_defaultName           = $s_report[ 'Name' ];
			$s_defaultDescription    = $s_report[ 'Description' ];
			$s_defaultAttachment     = $s_report[ 'Attachments' ];
			$i_defaultReport         = $s_report[ 'ReportID' ]; // 1 = true
			$i_defaultIsRecurring    = $s_report[ 'isRecurring' ];
			$s_defaultRecurring      = $s_report[ 'frequency' ];
			$i_defaultTime           = $s_report[ 'Date' ];
			$s_defaultRecipients     = $s_report[ 'Recipients' ];
			$s_defaultRecipientsBcc  = $s_report[ 'RecipientsBcc' ];
			$i_defaultScheduleStatus = $s_report[ 'Status' ];
			$i_defaultArchiveReport  = $s_report[ 'archiveReport' ];
			$i_archiveUserGroupId    = $s_report[ 'archiveUserGroupId' ];
		}
	}

	print "<font size=+1>CereusReporting - Add Report Schedule</font><br>\n";
	print "<hr>\n";

?>
<form method="post" action="CereusReporting_addScheduledReport.php" enctype="multipart/form-data">
<?php

	if ( $scheduleId > 0 ) {
		html_start_box( "<strong>Report Schedule</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
	}
	else {
		html_start_box( "<strong>Report Schedule</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
	}

	form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Schedule Name</font><br>
	The name of this report schedule.
</td>
<td>
	<?php form_text_box( "Name", "", $s_defaultName, 255 ); ?>
</td>
</tr>


<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Report</font><br>
	The name of the report to schedule.
</td>
<td>
	<?php
		$a_reports = db_fetch_assoc( "
				SELECT
				  `plugin_nmidCreatePDF_Reports`.`ReportId` as id,
				  `plugin_nmidCreatePDF_Reports`.`Name` as name
 				FROM
				  `plugin_nmidCreatePDF_Reports`
				ORDER BY
				    `plugin_nmidCreatePDF_Reports`.`Name`;
			" );
		form_dropdown( "reportId", $a_reports, "name", "id", $i_defaultReport, "", $i_defaultReport, "", "" );
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Schedule Description</font><br>
	The detailed describtion of this report. This will be also be displayed in the report.
</td>
<td>
	<?php form_text_area( "Description", $s_defaultDescription, 5, 50, "" ); ?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Schedule is Recurring</font><br>
	This report will be scheduled on a recurring basis.
</td>
<td>
	<?php
		if ( $i_defaultIsRecurring == 1 ) {
			$i_defaultIsRecurring = 'on';
		}
		form_checkbox( "isRecurring", $i_defaultIsRecurring, "This is a recurring report schedule", "" );
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Recurring frequence</font><br>
	This is the recurring frequence the report will be created. <br><i>This is only valid for recurring schedules.</i>
</td>
<td>
	<?php
		$a_recurring[ 0 ][ 'name' ] = 'hourly';
		$a_recurring[ 0 ][ 'id' ] = 'h';
		$a_recurring[ 1 ][ 'name' ] = 'daily';
		$a_recurring[ 1 ][ 'id' ] = 'd';
		$a_recurring[ 2 ][ 'name' ] = 'weekly';
		$a_recurring[ 2 ][ 'id' ] = 'w';
		$a_recurring[ 3 ][ 'name' ] = 'monthly';
		$a_recurring[ 3 ][ 'id' ] = 'm';
		$a_recurring[ 4 ][ 'name' ] = 'yearly';
		$a_recurring[ 4 ][ 'id' ] = 'y';
		form_dropdown( "recurringFrequency", $a_recurring, "name", "id", $s_defaultRecurring, "", $s_defaultRecurring, "", "" );
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Schedule</font><br>
	<small>Date/Time the report will be scheduled</small>
</td>
<td>
	<?php
		$dateFormat = readConfigOption( "nmid_pdf_dateformat" );

		$s_scheduleTime = date( $dateFormat, time() );
		if ( $scheduleId > 0 ) {
			$s_scheduleTime = $i_defaultTime;
		}

		if ( function_exists('top_graph_header')) { // Cacti 1.x
		$jsDateFormat = $dateFormat;
		$jsDateFormat = preg_replace( "@Y-m-d H:i@", "yy-mm-dd HH:mm", $jsDateFormat );
		$jsDateFormat = preg_replace( "@m/d/Y H:i@", "mm/dd/yy HH:mm", $jsDateFormat );
		$jsDateFormat = preg_replace( "@d-m-Y H:i@", "dd-mm-yy HH:mm", $jsDateFormat );
		$jsDateTimeFormat = preg_split('/\s/', $jsDateFormat);
		$jsDateFormat = $jsDateTimeFormat[0];
		$jsTimeFormat = $jsDateTimeFormat[1];
		if ( $scheduleId > 0 ) {
			$s_scheduleTime = date( $dateFormat, $i_defaultTime );
		}
		?>
        <script type='text/javascript'>
            var date1Open  = false;
            var date2Open  = false;

            function initPage() {
                $('#startDate').click(function() {
                    if (date1Open) {
                        date1Open = false;
                        $('#dateCal1').datetimepicker('hide');
                    } else {
                        date1Open = true;
                        $('#dateCal1').datetimepicker('show');
                    }
                });

                $('#dateCal1').datetimepicker({
                    minuteGrid: 10,
                    stepMinute: 1,
                    showAnim: 'slideDown',
                    numberOfMonths: 1,
                    timeFormat: '<?php print $jsTimeFormat;?>',
                    dateFormat: '<?php print $jsDateFormat;?>',
                    showButtonPanel: false,
                    onSelect: function(dateText, inst) {
                        console.log(dateText);
                        $('#scheduletime').val(dateText);
                    }
                });
            }

            $(function() {
                initPage();
            });
        </script>
	    <?php } else { // Cacti 0.8.x ?>

			<script type="text/javascript"
			        src="<?php echo $config[ 'url_path' ]; ?>include/jscalendar/calendar.js"></script>
			<script type="text/javascript"
			        src="<?php echo $config[ 'url_path' ]; ?>include/jscalendar/lang/calendar-en.js"></script>
			<script type="text/javascript"
			        src="<?php echo $config[ 'url_path' ]; ?>include/jscalendar/calendar-setup.js"></script>
			<script type='text/javascript'>
				// Initialize the calendar
				calendar = null;

				// This function displays the calendar associated to the input field 'id'
				function showCalendar(id) {
					var el = document.getElementById(id);
					if (calendar != null) {
						// we already have some calendar created
						calendar.hide();  // so we hide it first.
					} else {
						// first-time call, create the calendar.
						var cal = new Calendar(true, null, selected, closeHandler);
						cal.weekNumbers = false;  // Do not display the week number
						cal.showsTime = true;     // Display the time
						cal.time24 = true;        // Hours have a 24 hours format
						cal.showsOtherMonths = false;    // Just the current month is displayed
						calendar = cal;                  // remember it in the global var
						cal.setRange(1900, 2070);        // min/max year allowed.
						cal.create();
					}

					calendar.setDateFormat('%Y-%m-%d %H:%M');    // set the specified date format
					calendar.parseDate(el.value);                // try to parse the text in field
					calendar.sel = el;                           // inform it what input field we use

					// Display the calendar below the input field
					calendar.showAtElement(el, "Br");        // show the calendar

					return false;
				}

				// This function update the date in the input field when selected
				function selected(cal, date) {
					cal.sel.value = date;      // just update the date in the input field.
				}

				// This function gets called when the end-user clicks on the 'Close' button.
				// It just hides the calendar without destroying it.
				function closeHandler(cal) {
					cal.hide();                        // hide the calendar
					calendar = null;
				}
			</script>
            <?php } ?>
			<table>
				<tr>
					<?php if ( function_exists('top_graph_header')) { // Cacti 1.x ?>
                        <td width='150' nowrap style='white-space: nowrap;'>
                        <span>
                            <input type='hidden' name='scheduletime' id='scheduletime' size='18' value='<?php print $s_scheduleTime;?>'>
                            <input type='text' name='dateCal1' id='dateCal1' size='18' value='<?php print $s_scheduleTime;?>'>
                            <i id='startDate' class='calendar fa fa-calendar' title='Start Date Selector'></i>
                        </span>
                        </td>
					<?php } else { // Cacti 0.8.x ?>
                        <td width='150' nowrap style='white-space: nowrap;'>
                            <input type='text' name='scheduletime' id='scheduletime' title='Report Start Timestamp'
                                   size='14'
                                   value='<?php echo $s_scheduleTime; ?>'>
                            &nbsp;<input style='padding-bottom: 4px;' type='image'
                                         src='<?php echo $config[ 'url_path' ]; ?>images/calendar.gif'
                                         alt='Start date selector'
                                         title='Start date selector' border='0' align='absmiddle'
                                         onclick="return showCalendar('scheduletime');">&nbsp;
                        </td>
                    <?php } ?>
				</tr>
			</table>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Recipients</font><br>
	The email address(s) of the report recipients. Separate the emails with a Semi-Colon (;).
</td>
<td>
	<?php form_text_area( "Recipients", $s_defaultRecipients, 5, 50, "" ); ?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Recipients Bcc</font><br>
	The BCC email address(s) of the report recipients. Separate the emails with a Semi-Colon (;).
</td>
<td>
	<?php form_text_area( "RecipientsBcc", $s_defaultRecipientsBcc, 5, 50, "" ); ?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Schedule Status</font><br>
	Report schedules can be disabled.
</td>
<td>
	<?php
		if ( $i_defaultScheduleStatus == 1 ) {
			$i_defaultScheduleStatus = 'on';
		}
		form_checkbox( "isActive", $i_defaultScheduleStatus, "This report schedule is active", "on" );
	?>
</td>
</tr>

<?php
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
		<td width="50%">
			<font class="textEditTitle">Archive Report</font><br>
			The report will be archived after creation.
		</td>
		<td>
			<?php
				if ( $i_defaultArchiveReport == 1 ) {
					$i_defaultArchiveReport = 'on';
				}
				form_checkbox( "archiveReport", $i_defaultArchiveReport, "Archive this report after creation", "off" );
			?>
		</td>
		</tr>

		<?php
		$a_groups = db_fetch_assoc( "
			SELECT DISTINCT
			  `plugin_nmidCreatePDF_UserGroups`.`GroupId`,
			  `plugin_nmidCreatePDF_UserGroups`.`Name`
			FROM
			  `plugin_nmidCreatePDF_UserGroups`
			ORDER BY `plugin_nmidCreatePDF_UserGroups`.`Name`;
		" );
		$count = 0;
		foreach ( $a_groups as $s_group ) {
			$a_groupList[ $count ][ 'name' ] = $s_group[ 'Name' ];
			$a_groupList[ $count ][ 'id' ]   = $s_group[ 'GroupId' ];
			$count++;
		}
		?>

		<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
		<td width="50%">
			<font class="textEditTitle">Archive User Group</font><br>
			The user group this report will belong to.
		</td>
		<td>
			<?php
				form_dropdown( "archiveUserGroup", $a_groupList, "name", "id", $i_archiveUserGroupId, "", $i_archiveUserGroupId, "", "" );
			?>
		</td>
		</tr>

	<?php

	form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Schedule Attachments</font><br>
	You can add one or more files to sent along the report.
	The files need to be stored in a local directory accessible by the Cacti system user.
</td>
<td>
	<?php form_text_area( "Attachment", $s_defaultAttachment, 5, 50, "" ); ?>
</td>
</tr>




<?php
	if ( $scheduleId > 0 ) {
		form_hidden_box( "update_component_import", "1", "" );
		form_hidden_box( "ScheduleId", $scheduleId, "" );
	}
	else {
		form_hidden_box( "save_component_import", "1", "" );
	}
	html_end_box();
	form_save_button( "CereusReporting_scheduler.php", "save" );

	}
	cr_bottom_footer();

?>
