<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_scheduler.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
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
	if ( !isset( $_REQUEST[ "mode" ] ) ) {
		$_REQUEST[ "mode" ] = "";
	}
	if ( !isset( $_REQUEST[ "scheduleName" ] ) ) {
		$_REQUEST[ "scheduleName" ] = "";
	}
	if ( !isset( $_REQUEST[ "scheduleId" ] ) ) {
		$_REQUEST[ "scheduleId" ] = "";
	}


	// CRC-7 - No Copy function at report page nor report schedule
	if ( $_REQUEST[ "mode" ] == "duplicate" ) {
		form_duplicate_execute();
		exit;
	}

	switch ( $_REQUEST[ "drp_action" ] ) {
		case '2':
			form_delete();
			break;
		case '3':
			form_run_now();
			break;
		case '4':
			form_disable_schedule();
			break;
		case '5':
			form_enable_schedule();
			break;
		case '6':
			form_duplicate();
			break;
		default:
			cr_top_header();
			form_display();
			cr_bottom_footer();
			break;
	}

	// CRC-7 - No Copy function at report page nor report schedule
	function form_duplicate_execute()
	{
		global $config, $colors;

        // Get DB Instance
        $db = DBCxn::get();
        $scheduleName = $_REQUEST[ "scheduleName" ];
		$scheduleId = $_REQUEST[ "scheduleId" ];

		$sql = "Insert Into
		plugin_nmidCreatePDF_Reports_scheduler (
			Name,
			Date,
			lastRunDate,
			isRecurring,
			frequency,
			Status,
			Creator,
			ReportID,
			Recipients,
			Description,
			Attachments,
			archiveReport,
			archiveUserGroupId,
			runNow
		)
	Select
		:scheduleName,
		Date,
		lastRunDate,
		isRecurring,
		frequency,
		Status,
		Creator,
		ReportID,
		Recipients,
		Description,
		Attachments,
		archiveReport,
		archiveUserGroupId,
		runNow
	From
		plugin_nmidCreatePDF_Reports_scheduler
	Where
		ScheduleId=:scheduleId;
	";
        $stmt = $db->prepare( $sql );
        $stmt->bindValue( ':scheduleName', $scheduleName);
        $stmt->bindValue( ':scheduleId', $scheduleId );
        $stmt->execute();
        $stmt->closeCursor();

		header( "Location: " . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php" );
	}

	function form_duplicate()
	{
		global $config, $colors;

		include( $config[ "include_path" ] . "/top_header.php" );

		$scheduleId = '';
		/* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				$scheduleId = $matches[ 1 ];
			}
		}

		$old_schedule_name = db_fetch_cell( "select Name from plugin_nmidCreatePDF_Reports_scheduler where ScheduleId=$scheduleId" );

		$fields_cereusreporting_schedule_duplicate = array(
			"scheduleName" => array(
				"method"        => "textbox",
				"friendly_name" => "New Schedule Name",
				"description"   => "A useful name for this Schedule.",
				"value"         => $old_schedule_name . '_new',
				"max_length"    => "255",
				"size"          => "60"
			),
			"mode"         => array(
				"method" => "hidden",
				"value"  => "duplicate"
			),
			"scheduleId"   => array(
				"method" => "hidden",
				"value"  => $scheduleId
			)
		);

		$type = "Duplicate";

		print "<table align='center' width='80%'><tr><td>\n";
		html_start_box( "<strong>CereusReporting - " . $type . " Schedule</strong>", "100%", $colors[ "header" ], "3", "center", "" );
		print "<tr><td bgcolor='#FFFFFF'>\n";

		print "<p>When you click 'Continue', the following Schedule will be duplicated. You can optionally change the title format for the new Schedule.</p>
		   <p>Press <b>'Duplicate'</b> to proceed with the duplication, or <b>'Cancel'</b> to return to the Schedule menu.</p>
			</td></tr>";

		html_end_box();
		print "<form action='" . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php' method='post'>\n";
		html_start_box( "<strong>Schedule " . $type . " Settings</strong>", "100%", '', "3", "center", "" );
		draw_edit_form( array(
			                "config" => array(),
			                "fields" => inject_form_variables( $fields_cereusreporting_schedule_duplicate, array() ) )
		);
		html_end_box();
		cereusReporting_confirm_button( "Duplicate", $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php" );
		print "</td></tr></table>\n";
		exit;
	}

	function cereusReporting_confirm_button( $action, $cancel_url )
	{
		?>
		<table align='center' width='100%' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>
			<tr>
				<td bgcolor="#f5f5f5" align="right">
					<input name='<?php print 'return' ?>' type='submit' value='Cancel'>
					<input name='Duplicate' type='submit' value='Duplicate'>
				</td>
			</tr>
		</table>
		</form>
	<?php
	}

	function form_disable_schedule()
	{
		global $colors, $hash_type_names, $config;
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				db_execute( "UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET Status=0 WHERE `ScheduleId`='" . $matches[ 1 ] . "'" );
			}
		}
		header( "Location: " . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php" );
	}

	function form_enable_schedule()
	{
		global $colors, $hash_type_names, $config;
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				db_execute( "UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET Status=1 WHERE `ScheduleId`='" . $matches[ 1 ] . "'" );
			}
		}
		header( "Location: " . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php" );
	}

	function form_run_now()
	{
		global $colors, $hash_type_names, $config;
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				db_execute( 'UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET runNow=1 WHERE ScheduleId=' . $matches[ 1 ] );
			}
		}
		header( "Location: " . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php" );
	}

	function form_delete()
	{
		global $colors, $hash_type_names, $config;

		/* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_Reports_scheduler` where `ScheduleId`='" . $matches[ 1 ] . "'" );
			}
		}
		header( "Location: " . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php" );
	}

	function form_edit()
	{

	}

	function form_display()
	{
		global $colors, $hash_type_names, $config;
		print "<font size=+1>CereusReporting - Report Schedules</font><br>\n";
		print "<hr>\n";

		$username = db_fetch_cell( "select username from user_auth where id=" . $_SESSION[ "sess_user_id" ] );

		$where_clause = '';
		if ( isset( $_REQUEST[ "sort_column" ] ) ) {
			if (
				( $_REQUEST[ "sort_column" ] == 'Date' )
				|| ( $_REQUEST[ "sort_column" ] == 'ReportID' )
				|| ( $_REQUEST[ "sort_column" ] == 'Description' )
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
		$scheduleValues = db_fetch_assoc( "
    SELECT
      `plugin_nmidCreatePDF_Reports_scheduler`.`Name`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`isRecurring`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`Recipients`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`Description`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`Date`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`lastRunDate`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`frequency`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`Status`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`runNow`,
      `plugin_nmidCreatePDF_Reports_scheduler`.`ScheduleId`
    FROM
      `plugin_nmidCreatePDF_Reports_scheduler` 
    " );

		print "<form name=chk method=POST action='" . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_scheduler.php'>\n";

		html_start_box( "<strong>Report Schedules</strong>", "100%", '', "3", "center", $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_addScheduledReport.php?action=add" );

		form_hidden_box( "save_component_import", "1", "" );

		if ( sizeof( $scheduleValues ) > 0 ) {
			$menu_text = array(
				"ScheduleId"  => array( "Id", "ASC" ),
				"Name"        => array( "Name", "ASC" ),
				"isRecurring" => array( "Is Recurring", "ASC" ),
				"recurring"   => array( "Repeating", "ASC" ),
				"Date"        => array( "Next Run", "ASC" ),
				"lastRunDate" => array( "Last Run", "ASC" ),
				"Description" => array( "Description", "ASC" )
			);

			html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

			$i = 0;

			foreach ( $scheduleValues as $schedule ) {
				$dateFormat  = readConfigOption( "nmid_pdf_dateformat" );
				$nextRunDate = date( "$dateFormat", $schedule[ 'Date' ] );
				form_alternate_row( 'line' . $schedule[ 'ScheduleId' ] );
				$i++;
				form_selectable_cell( $schedule[ 'ScheduleId' ], $schedule[ "ScheduleId" ] );
				form_selectable_cell( "<a href='" . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_addScheduledReport.php?action=update&ScheduleId=" . $schedule[ "ScheduleId" ] . "'><b>" . $schedule[ 'Name' ] . "</b></a>", $schedule[ 'ScheduleId' ], 250 );
				if ( $schedule[ 'isRecurring' ] == 1 ) {
					$isRecurring = 'true';
				}
				else {
					$isRecurring = 'false';
				}
				if ( $schedule[ 'Status' ] == 1 ) {
					$statusImage = $config[ 'url_path' ] . 'plugins/CereusReporting/images/active.png';
				}
				else {
					$statusImage = $config[ 'url_path' ] . 'plugins/CereusReporting/images/disabled.png';
					$nextRunDate = "Never";
				}
				if ( $schedule[ 'runNow' ] == 1 ) {
					$statusImage = $config[ 'url_path' ] . 'plugins/CereusReporting/images/scheduled.png';
					$nextRunDate = "Now";
				}
				form_selectable_cell( $isRecurring, $schedule[ "ScheduleId" ] );
				$frequency[ 'h' ] = 'hourly';
				$frequency[ 'd' ] = 'daily';
				$frequency[ 'w' ] = 'weekly';
				$frequency[ 'm' ] = 'monthly';
				$frequency[ 'y' ] = 'yearly';
				form_selectable_cell( $frequency[ $schedule[ 'frequency' ] ], $schedule[ "ScheduleId" ] );
				form_selectable_cell( "<img src=\"" . $statusImage . "\"/>" . $nextRunDate, $schedule[ "ScheduleId" ] );
				form_selectable_cell( date( "$dateFormat", (int)$schedule[ 'lastRunDate' ] ), $schedule[ "ScheduleId" ] );
				form_selectable_cell( $schedule[ 'Description' ], $schedule[ "ScheduleId" ] );
				form_checkbox_cell( 'selected_items', $schedule[ "ScheduleId" ] );
				form_end_row();
			}

			html_end_box( FALSE );

			$task_actions = array(
				1 => "Please select an action",
				2 => "Delete",
				3 => "Run Now",
				4 => "Disable Schedule",
				5 => "Enable Schedule",
				6 => "Duplicate"
			);
			draw_actions_dropdown( $task_actions );
		}
		else {
			print "<tr><td><em>No Report Schedules exist</em></td></tr>";
			html_end_box( FALSE );
		}

		print "</form>";
	}


?>
