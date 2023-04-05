<?php
/*******************************************************************************
 *
 * File:         $Id: CereusReporting_Availability_addDowntimeSchedule.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
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
$dir = dirname( __FILE__ );
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
if ( !isset( $_REQUEST[ "dataId" ] ) ) {
	$_REQUEST[ "dataId" ] = "";
}
if ( !isset( $_REQUEST[ "action" ] ) ) {
	$_REQUEST[ "action" ] = "";
}

switch ( $_REQUEST[ "action" ] ) {
	case 'save':
		form_save( filter_var( $_REQUEST[ "dataId" ], FILTER_SANITIZE_NUMBER_INT ) );
		break;
	default:

		cr_top_header();

		form_display( filter_var( $_REQUEST[ "dataId" ], FILTER_SANITIZE_NUMBER_INT ) );
		cr_bottom_footer();
		break;
}


/**
 * Save and update database entires based on form data
 *
 * @param int $dataId
 *
 * @return void
 */
function form_save( $dataId )
{
	global $colors, $hash_type_names;

	// Check validitiy of data.
	if ( $dataId != "" ) {
		if ( isNumber( $dataId ) == FALSE ) {
			return;
		}
	}
	if ( isset ( $_POST[ 'deviceId' ] ) ) {
		$s_dataDeviceId =filter_var( $_REQUEST[ "deviceId" ], FILTER_SANITIZE_NUMBER_INT );
	}
	if ( isset ( $_POST[ 'changeTypeId' ] ) ) {
		$s_dataChangeTypeId =  filter_var( $_REQUEST[ "changeTypeId" ], FILTER_SANITIZE_NUMBER_INT );
	}
	if ( isset ( $_POST[ 'shortDescription' ] ) ) {
		$s_dataShortDescription = filter_var( $_REQUEST[ "shortDescription" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	}
	if ( isset ( $_POST[ 'longDescription' ] ) ) {
		$s_dataLongDescription = filter_var( $_REQUEST[ "longDescription" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	}
	if ( isset ( $_POST[ 'starttime' ] ) ) {
		$s_dataStartTime = strtotime(  filter_var( $_REQUEST[ "starttime" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH ) );
	}
	if ( isset ( $_POST[ 'endtime' ] ) ) {
		$s_dataEndTime = strtotime(  filter_var( $_REQUEST[ "endtime" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH ) );
	}

	if ( ( isset ( $_POST[ 'shortDescription' ] ) ) && ( isset ( $_POST[ 'save_component_import' ] ) ) ) {
		db_execute( "
			INSERT INTO `plugin_nmidCreatePDF_Availability_Change_Table`
				(`deviceId`, `changeTypeId`, `shortDescription`,`longDescription`, `startTimeStamp`, `endTimeStamp`)
			VALUES
				($s_dataDeviceId, '$s_dataChangeTypeId','$s_dataShortDescription', '$s_dataLongDescription',
				'$s_dataStartTime', '$s_dataEndTime')
			" );
	}
	if ( ( isset ( $_POST[ 'shortDescription' ] ) ) && ( isset ( $_POST[ 'update_component_import' ] ) ) ) {
		db_execute( "
			UPDATE `plugin_nmidCreatePDF_Availability_Change_Table`
			Set
				deviceId=$s_dataDeviceId,
				changeTypeId='$s_dataChangeTypeId',
				shortDescription='$s_dataShortDescription',
				longDescription='$s_dataLongDescription',
				startTimeStamp='$s_dataStartTime',
				endTimeStamp='$s_dataEndTime'
			WHERE
				Id='$dataId'
			" );
	}
	header( "Location: CereusReporting_Availability_DowntimeSchedule.php" );

}

/**
 * @param $dataId integer
 *
 * @return void
 */
function form_display( $dataId )
{
global $colors, $hash_type_names, $config;

$i_defaultDeviceId = 0;
$i_defaultChangeTypeId = 0;
$i_defaultStartTimeStamp = time() - 3600;
$i_defaultEndTimeStamp = time();
$s_defaultShortDescription = '';
$s_defaultLongDescription = '';
$i_defaultChangeWinServiceId = 0;
$i_defaultChangeTypeRecurringId = 0;
$dateFormat = readConfigOption( "nmid_pdf_dateformat" );

if ( $dataId > 0 ) {
	$a_reports = db_fetch_assoc( "
			SELECT
			  `plugin_nmidCreatePDF_Availability_Change_Table`.`deviceId`,
			  `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId`,
			  `plugin_nmidCreatePDF_Availability_Change_Table`.`startTimeStamp`,
			  `plugin_nmidCreatePDF_Availability_Change_Table`.`endTimeStamp`,
			  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription`,
			  `plugin_nmidCreatePDF_Availability_Change_Table`.`longDescription`
			FROM
			  `plugin_nmidCreatePDF_Availability_Change_Table`
			WHERE Id='$dataId'
		" );
	foreach ( $a_reports as $s_report ) {
		$i_defaultDeviceId         = $s_report[ 'deviceId' ];
		$i_defaultChangeTypeId     = $s_report[ 'changeTypeId' ];
		$i_defaultStartTimeStamp   = $s_report[ 'startTimeStamp' ]; // 1 = true
		$i_defaultEndTimeStamp     = $s_report[ 'endTimeStamp' ];
		$s_defaultShortDescription = $s_report[ 'shortDescription' ];
		$s_defaultLongDescription  = $s_report[ 'longDescription' ];
	}
}

print "<font size=+1>CereusReporting - Add Availability Data</font><br>\n";
print "<hr>\n";

?>
<form method="post" action="CereusReporting_Availability_addDowntimeSchedule.php" enctype="multipart/form-data">
<?php

if ( $dataId > 0 ) {
	html_start_box( "<strong>Availability Data</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
}
else {
	html_start_box( "<strong>Availability Data</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
}

form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Device Name</font><br>
	The device this data set is for.
</td>
<td>
	<?php
	$a_hosts = db_fetch_assoc( "
				SELECT
					id,
					CONCAT(description,' [',hostname,'] ') as name
				FROM
					host
				ORDER BY
				    description
				;" );
	form_dropdown( "deviceId", $a_hosts, "name", "id", $i_defaultDeviceId, "", $i_defaultDeviceId, "", "" );
	?>
</td>
</tr>


<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Change/Availability Type</font><br>
	The type of outtage/change.
</td>
<td>
	<?php
	$a_changeType = db_fetch_assoc( "
				SELECT
					Id as id,
					shortName as name
				FROM
					plugin_nmidCreatePDF_Availability_Change_Type
				;" );
	form_dropdown( "changeTypeId", $a_changeType, "name", "id", $i_defaultChangeTypeId, "", $i_defaultChangeTypeId, "", "" );
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Select a Service</font><br>
	The Win Service this maintenance schedule applies to.
</td>
<td>
	<?php
	$a_changeWinService = db_fetch_assoc( "
					SELECT
						`data_template_data`.local_data_id as id,
						`data_template_data`.name_cache as name
					FROM
						`data_template_data`,
						`data_local`
					WHERE
						`data_local`.id = `data_template_data`.local_data_id
					AND
					   `data_template_data`.data_source_path LIKE '%service_state%'
				;" );
	$a_changeWinService[ -1 ][ 'id' ] = 0;
	$a_changeWinService[ -1 ][ 'name' ] = 'all services';
	form_dropdown( "changeWinServiceId", $a_changeWinService, "name", "id", $i_defaultChangeWinServiceId, "", $i_defaultChangeWinServiceId, "", "" );
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Short Description</font><br>
	A short description which will be shown on the graph at the start and end of the time frame.
</td>
<td>
	<?php  form_text_box( "shortDescription", "", $s_defaultShortDescription, 255 ); ?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Long Description</font><br>
	This is the long description of the outtage/change.
</td>
<td>
	<?php form_text_area( "longDescription", $s_defaultLongDescription, 5, 50, "" ); ?>
</td>
</tr>


<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Start Date/Time</font><br>
	Date/Time the the outtage/change started
</td>
<td>
	<?php
	if ( $dataId > 0 ) {
		$s_startTime = date( $dateFormat, $i_defaultStartTimeStamp );
	}
	else {
		$s_startTime = date( $dateFormat, time() );
	}
	?>

	<?php if ( function_exists('top_graph_header')) { // Cacti 1.x
		$jsDateFormat = $dateFormat;
		$jsDateFormat = preg_replace( "@Y-m-d H:i@", "yy-mm-dd HH:mm", $jsDateFormat );
		$jsDateFormat = preg_replace( "@m/d/Y H:i@", "mm/dd/yy HH:mm", $jsDateFormat );
		$jsDateFormat = preg_replace( "@d-m-Y H:i@", "dd-mm-yy HH:mm", $jsDateFormat );
		$jsDateTimeFormat = preg_split('/\s/', $jsDateFormat);
		$jsDateFormat = $jsDateTimeFormat[0];
		$jsTimeFormat = $jsDateTimeFormat[1];
		?>
        <script type='text/javascript'>

            var timeOffset=<?php print date('Z');?>;
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
                $('#endDate').click(function() {
                    if (date2Open) {
                        date2Open = false;
                        $('#dateCal2').datetimepicker('hide');
                    } else {
                        date2Open = true;
                        $('#dateCal2').datetimepicker('show');
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
                        $('#starttime').val(dateText);
                    }
                });
                $('#dateCal2').datetimepicker({
                    minuteGrid: 10,
                    stepMinute: 1,
                    showAnim: 'slideDown',
                    numberOfMonths: 1,
                    timeFormat: '<?php print $jsTimeFormat;?>',
                    dateFormat: '<?php print $jsDateFormat;?>',
                    showButtonPanel: false,
                    onSelect: function(dateText, inst) {
                        console.log(dateText);
                        $('#endtime').val(dateText);
                    }
                });
            }

            $(function() {
                initPage();
            });
        </script>
	<?php } else { // Cacti 0.8.x
		$jsDateFormat = $dateFormat;
		$jsDateFormat = preg_replace( "/Y/", "%Y", $jsDateFormat );
		$jsDateFormat = preg_replace( "/m/", "%m", $jsDateFormat );
		$jsDateFormat = preg_replace( "/d/", "%d", $jsDateFormat );
		$jsDateFormat = preg_replace( "/H/", "%H", $jsDateFormat );
		$jsDateFormat = preg_replace( "/i/", "%M", $jsDateFormat );
	?>
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
                        <input type='hidden' name='starttime' id='starttime' size='18' value='<?php print $s_startTime;?>'>
                        <input type='text' name='dateCal1' id='dateCal1' size='18' value='<?php print $s_startTime;?>'>
                        <i id='startDate' class='calendar fa fa-calendar' title='Start Date Selector'></i>
                    </span>
                </td>
			<?php } else { // Cacti 0.8.x ?>
                <td width='150' nowrap style='white-space: nowrap;'>
                    <input type='text' name='starttime' id='myStartTime' title='Start Timestamp' size='14'
                           value='<?php echo $s_startTime; ?>'>
                    &nbsp;<input style='padding-bottom: 4px;' type='image'
                                 src='<?php echo $config[ 'url_path' ]; ?>images/calendar.gif' alt='Start date selector'
                                 title='Start date selector' border='0' align='absmiddle'
                                 onclick="return showCalendar('myStartTime');">&nbsp;
                </td>
			<?php } ?>

        </tr>
	</table>
	<?php
	// echo $s_defaultTypeDescription;
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">End Date/Time</font><br>
	Date/Time the the outtage/change finished
</td>
<td>
	<?php
	if ( $dataId > 0 ) {
		$s_endTime = date( $dateFormat, $i_defaultEndTimeStamp );
	}
	else {
		$s_endTime = date( $dateFormat, time() );
	}
	?>
	<table>
		<tr>
			<?php if ( function_exists('top_graph_header')) { // Cacti 1.x ?>
                <td width='150' nowrap style='white-space: nowrap;'>
                    <span>
                        <input type='hidden' name='endtime' id='endtime' size='18' value='<?php print $s_endTime;?>'>
                        <input type='text' name='dateCal2' id='dateCal2' size='18' value='<?php print $s_endTime;?>'>
                        <i id='endDate' class='calendar fa fa-calendar' title='End Date Selector'></i>
                    </span>
                </td>
			<?php } else { // Cacti 0.8.x ?>
                <td width='150' nowrap style='white-space: nowrap;'>
                    <input type='text' name='endtime' id='myEndTime' title='End Timestamp' size='14'
                           value='<?php echo $s_endTime; ?>'>
                    &nbsp;<input style='padding-bottom: 4px;' type='image'
                                 src='<?php echo $config[ 'url_path' ]; ?>images/calendar.gif' alt='End date selector'
                                 title='End date selector' border='0' align='absmiddle'
                                 onclick="return showCalendar('myEndTime');">&nbsp;
                </td>
			<?php } ?>

        </tr>
	</table>
	<?php
	// echo $s_defaultTypeDescription;
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Is this recurring ?</font><br>
	The type of outtage/change.
</td>
<td>
	<?php
	$a_changeTypeRecurring[ 0 ][ 'id' ] = 1;
	$a_changeTypeRecurring[ 0 ][ 'name' ] = 'no';
	$a_changeTypeRecurring[ 1 ][ 'id' ] = 2;
	$a_changeTypeRecurring[ 1 ][ 'name' ] = 'daily';
	$a_changeTypeRecurring[ 2 ][ 'id' ] = 3;
	$a_changeTypeRecurring[ 2 ][ 'name' ] = 'weekly';
	$a_changeTypeRecurring[ 3 ][ 'id' ] = 4;
	$a_changeTypeRecurring[ 3 ][ 'name' ] = 'bi-weekly';
	$a_changeTypeRecurring[ 4 ][ 'id' ] = 5;
	$a_changeTypeRecurring[ 4 ][ 'name' ] = 'monthly';
	$a_changeTypeRecurring[ 5 ][ 'id' ] = 6;
	$a_changeTypeRecurring[ 5 ][ 'name' ] = 'yearly';
	form_dropdown( "changeTypeRecurringId", $a_changeTypeRecurring, "name", "id", $i_defaultChangeTypeRecurringId, "", $i_defaultChangeTypeRecurringId, "", "" );
	?>
</td>
</tr>
<?php

if ( $dataId > 0 ) {
	form_hidden_box( "update_component_import", "1", "" );
	form_hidden_box( "dataId", $dataId, "" );
}
else {
	form_hidden_box( "save_component_import", "1", "" );
}
html_end_box();
form_save_button( "CereusReporting_Availability_DowntimeSchedule.php", "save" );

}


cr_bottom_footer();

?>
