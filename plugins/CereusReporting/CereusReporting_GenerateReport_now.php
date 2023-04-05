<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_GenerateReport_now.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
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
	if ( !isset( $_REQUEST[ "reportId" ] ) ) {
		$_REQUEST[ "reportId" ] = "";
	}
	if ( !isset( $_REQUEST[ "action" ] ) ) {
		$_REQUEST[ "action" ] = "";
	}
	if ( !isset( $_REQUEST[ "mode" ] ) ) {
		$_REQUEST[ "mode" ] = "";
	}



	if ( $_REQUEST[ "mode" ] == 'inline' ) {
		cr_top_header();
		form_display( $_REQUEST[ "reportId" ] );
		cr_bottom_footer();
	}
	else {
		cr_top_graph_header();
		form_display( $_REQUEST[ "reportId" ] );
		cr_bottom_footer();
	}


	function form_display($reportId)
	{
	global $colors, $hash_type_names, $config;

	$defaultName = "";
	$defaultDescription = "";
	$defaultUser = "";
	$defaultEmail = "";
	$s_defaultName = '';
	$s_defaultDescription = '';
	$i_defaultIncludeSubDirs = 0;
	$i_defaultLeafId = 'not defined';
	$i_defaultReportType = 0;
	$i_defaultType = 1;
	$i_defaultTimeInSeconds = 3600;
	$s_defaultTypeDescription = 'On Demand';

	if ( $reportId > 0 ) {
		$a_reports = db_fetch_assoc( "
			SELECT
			  `plugin_nmidCreatePDF_Reports_Types`.`TypeId` as type,
			  `plugin_nmidCreatePDF_Reports_Types`.`timeInSeconds` as timeInSeconds,
			  `plugin_nmidCreatePDF_Reports_Types`.`Description` as typeDescription,
			  `plugin_nmidCreatePDF_Reports`.`Description` as Description,
			  `plugin_nmidCreatePDF_Reports`.`reportType`,
			  `plugin_nmidCreatePDF_Reports`.`leafId`,
			  `plugin_nmidCreatePDF_Reports`.`includeSubDirs`, 
			  `plugin_nmidCreatePDF_Reports`.`Name`
			FROM
			  `plugin_nmidCreatePDF_Reports_Types` INNER JOIN
			  `plugin_nmidCreatePDF_Reports` ON `plugin_nmidCreatePDF_Reports`.`type` =
			  `plugin_nmidCreatePDF_Reports_Types`.`TypeId`		
			WHERE ReportId='$reportId'
		" );
		foreach ( $a_reports as $s_report ) {
			$s_defaultName            = $s_report[ 'Name' ];
			$i_defaultTimeInSeconds   = $s_report[ 'timeInSeconds' ];
			$s_defaultDescription     = $s_report[ 'Description' ];
			$i_defaultIncludeSubDirs  = $s_report[ 'includeSubDirs' ]; // 1 = true
			$i_defaultLeafId          = $s_report[ 'leafId' ];
			$i_defaultReportType      = $s_report[ 'reportType' ];
			$i_defaultType            = $s_report[ 'type' ];
			$s_defaultTypeDescription = $s_report[ 'typeDescription' ];
		}
	}

	if ( readConfigOption( "nmid_use_css" ) == "1" ) {
		if ( CereusReporting_isNewCactiUI() ) {
			// 0.8.8c and greater
		}
		else {
			echo '<link href="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/css/ui-lightness/jquery-ui-1.9.2.custom.min.css" type="text/css" rel="stylesheet">';
			echo '<script src="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/js/jquery-1.8.3.js"></script>';
			echo '<script src="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/js/jquery-ui-1.9.2.custom.min.js"></script>';
		}
	}
?>
<style>
    .image_off, #home:hover .image_on {
        display: none
    }

    .image_on, #home:hover .image_off {
        display: block
    }

    #fader {
        opacity: 0.5;
        background: black;
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: none;
    }
</style>
<script type="text/javascript">
    var setCookie = function(name, value, expiracy) {
        var exdate = new Date();
        exdate.setTime(exdate.getTime() + expiracy * 1000);
        var c_value = escape(value) + ((expiracy == null) ? "" : "; expires=" + exdate.toUTCString());
        document.cookie = name + "=" + c_value + '; path=/';
    };

    var getCookie = function(name) {
        var i, x, y, ARRcookies = document.cookie.split(";");
        for (i = 0; i < ARRcookies.length; i++) {
            x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
            y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
            x = x.replace(/^\s+|\s+$/g, "");
            if (x == name) {
                return y ? decodeURI(unescape(y.replace(/\+/g, ' '))) : y; //;//unescape(decodeURI(y));
            }
        }
    };

    $('#downloadLink').click(function() {
        $('#fader').css('display', 'block');
        setCookie('downloadStarted', 0, 100); //Expiration could be anything... As long as we reset the value
        setTimeout(checkDownloadCookie, 1000); //Initiate the loop to check the cookie.
        document.CereusReporting_Form.submit();
    });
    var downloadTimeout;
    var checkDownloadCookie = function() {
        if (getCookie("downloadStarted") == 1) {
            setCookie("downloadStarted", "false", 100); //Expiration could be anything... As long as we reset the value
            $('#fader').css('display', 'none');
        } else {
            downloadTimeout = setTimeout(checkDownloadCookie, 1000); //Re-run this function in 1 second.
        }
    };
</script>
<?php

	print "<font size=+1>CereusReporting - Generate Report</font><br>\n";
	print "<hr>\n";


?>
<form method="post" name="CereusReporting_Form" action="createPDFReport_defined.php" enctype="multipart/form-data">
<?php
	form_hidden_box( "ReportId", $reportId, "" );

	html_start_box( "<strong>Report</strong>", "100%", $colors[ "header" ], "3", "center", "" );

	form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Name</font><br>
	The name of the report.
</td>
<td>
	<?php echo $s_defaultName; ?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Description</font><br>
	The detailed describtion of this report. This will be also be displayed in the report.
</td>
<td>
	<?php echo $s_defaultDescription; ?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Type</font><br>
	Select if this is a normal report, a graph report or a special DSSTATs report.
</td>
<td>
	<?php
		$a_reportType = array();
		$a_reportType[ 0 ] = 'Normal Report';
		$a_reportType[ 1 ] = 'Graph Report';
		$a_reportType[ 2 ] = 'DSStats Report';
		$a_reportType[ 3 ] = 'Multi Report';

		echo $a_reportType[ $i_defaultReportType ];
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Report includes sub leafs</font><br>
	The report can include sub leafs. <i>This is only valid for non graph reports.</i>
</td>
<td>
	<?php
		if ( $i_defaultIncludeSubDirs == 1 ) {
			echo "true";
		}
		else {
			echo "false";
		}
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
<td width="50%">
	<font class="textEditTitle">Default Report Timespan</font><br>
	The default report timespane of this report.
</td>
<td>
	<?php
		echo $s_defaultTypeDescription;
	?>
</td>
</tr>

<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
<td width="50%">
	<font class="textEditTitle">Report Timespan</font><br>
	Timespan for this report to use
</td>
<td>
	<?php
		$s_endTime = time();
		$s_startTime = time();
		if ( $s_defaultTypeDescription == "On Demand" ) {
			$s_startTime = $s_startTime - 3600;
		}
		elseif ( $s_defaultTypeDescription == "Yesterday" ) {
			# Ticket ID: 7 - Yesterday calculated wrong
			$s_startTime = $s_startTime - 86400;
			$s_startTime = mktime( 0, 0, 0, date( 'm', $s_startTime ), date( 'd', $s_startTime ), date( 'Y', $s_startTime ) );
			$s_endTime   = mktime( 0, 0, 0, date( 'm', $s_endTime ), date( 'd', $s_endTime ), date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "Last Week" ) {
			$s_startTime = $s_startTime - ( ( date( 'N', $s_startTime ) - 1 ) * 86400 );
			$s_endTime   = $s_startTime;
			$s_startTime = $s_startTime - ( 7 * 86400 );
			$s_startTime = mktime( 0, 0, 0, date( 'm', $s_startTime ), date( 'd', $s_startTime ), date( 'Y', $s_startTime ) );
			$s_endTime   = mktime( 0, 0, 0, date( 'm', $s_endTime ), date( 'd', $s_endTime ), date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "Last Month" ) {
			$s_startTime = strtotime( 'last month', $s_startTime );
			$s_startTime = mktime( 0, 0, 0, date( 'm', $s_startTime ), 1, date( 'Y', $s_startTime ) );
			$s_endTime   = mktime( 0, 0, 0, date( 'm', $s_endTime ), 1, date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "Last Year" ) {
			$s_startTime = mktime( 0, 0, 0, 1, 1, date( 'Y', $s_startTime ) - 1 );
			$s_endTime   = mktime( 0, 0, 0, 1, 1, date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "1 Hour" ) {
			$s_startTime = strtotime( '-1 hour', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Day" ) {
			$s_startTime = strtotime( '-1 day', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Week" ) {
			$s_startTime = strtotime( '-1 week', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Month" ) {
			$s_startTime = strtotime( '-1 month', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Year" ) {
			$s_startTime = strtotime( '-1 year', $s_startTime );
		}
		else {
			$minutesLeftToFullHour = ( 60 - date( "M", $s_startTime ) ) * 60;
			$s_endTime             = mktime( date( "H", $s_startTime + $minutesLeftToFullHour ), 0, 0, date( "m", $s_startTime + $minutesLeftToFullHour ), date( "d", $s_startTime + $minutesLeftToFullHour ), date( "Y", $s_startTime + $minutesLeftToFullHour ) );
			$s_startTime           = $s_endTime - $i_defaultTimeInSeconds;
			$s_startTime           = mktime( date( "H", $s_startTime ), 0, 0, date( "m", $s_startTime ), date( "d", $s_startTime ), date( "Y", $s_startTime ) );
		}
		$dateFormat = readConfigOption( "nmid_pdf_dateformat" );
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
            var graph_start=<?php print $s_startTime;?>;
            var graph_end=<?php print $s_endTime;?>;
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
                        $('#date1').val(dateText);
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
                        $('#date2').val(dateText);
                    }
                });
            }

            $(function() {
                initPage();
            });
        </script>
    <?php } else { // Cacti 0.8.x
	    $s_startTime = date( "$dateFormat", $s_startTime );
	    $s_endTime = date( "$dateFormat", $s_endTime );
	    $jsDateFormat = $dateFormat;
	    $jsDateFormat = preg_replace( "/Y/", "%Y", $jsDateFormat );
	    $jsDateFormat = preg_replace( "/m/", "%m", $jsDateFormat );
	    $jsDateFormat = preg_replace( "/d/", "%d", $jsDateFormat );
	    $jsDateFormat = preg_replace( "/H/", "%H", $jsDateFormat );
	    $jsDateFormat = preg_replace( "/i/", "%M", $jsDateFormat );
        ?>
        <script type="text/javascript" src="<?php echo $config['url_path']; ?>include/jscalendar/calendar.js"></script>
        <script type="text/javascript" src="<?php echo $config['url_path']; ?>include/jscalendar/lang/calendar-en.js"></script>
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
                calendar.setDateFormat('<?php echo $jsDateFormat; ?>');    // set the specified date format
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

    <div id="fader">
        <p style="font-size: 30px; text-align: center; vertical-align: middle; color: white;">Generating Report ...</p>
    </div>
    <table>
        <tr>
            <td nowrap style='white-space: nowrap;' width='30'>
                &nbsp;<strong>From:</strong>&nbsp;
            </td>
            <?php if ( function_exists('top_graph_header')) { // Cacti 1.x
	            $s_startTime_string = date( "$dateFormat", $s_startTime );
	            ?>

                <td width='150' nowrap style='white-space: nowrap;'>
                    <span>
                        <input type='hidden' name='date1' id='date1' size='18' value='<?php print $s_startTime_string;?>'>
                        <input type='text' name='dateCal1' id='dateCal1' size='18' value='<?php print $s_startTime_string;?>'>
                        <i id='startDate' class='calendar fa fa-calendar' title='Start Date Selector'></i>
                    </span>
                </td>
            <?php } else { // Cacti 0.8.x ?>
                <td width='150' nowrap style='white-space: nowrap;'>
                    <input type='text' name='starttime' id='starttime' title='Report Start Timestamp' size='14'
                           value='<?php echo $s_startTime; ?>'>
                    &nbsp;<input style='padding-bottom: 4px;' type='image'
                                 src='<?php echo $config[ 'url_path' ]; ?>images/calendar.gif' alt='Start date selector'
                                 title='Start date selector' border='0' align='absmiddle'
                                 onclick="return showCalendar('starttime');">&nbsp;
                </td>
            <?php } ?>
            <?php if ( $i_defaultReportType <> 4 ) { ?>
                <td nowrap style='white-space: nowrap;' width='20'>
                    &nbsp;<strong>To:</strong>&nbsp;
                </td>
	            <?php if ( function_exists('top_graph_header')) { // Cacti 1.x
		            $s_endTime_string = date( "$dateFormat", $s_endTime );
		            ?>
                    <td width='150' nowrap style='white-space: nowrap;'>
                        <span>
                            <input type='hidden' name='date2' id='date2' size='18' value='<?php print $s_endTime_string;?>'>
                            <input type='text' name='dateCal2' id='dateCal2' size='18' value='<?php print $s_endTime_string;?>'>
                            <i id='endDate' class='calendar fa fa-calendar' title='End Date Selector'></i>
                        </span>
                    </td>
	            <?php } else { // Cacti 0.8.x ?>
                    <td width='150' nowrap style='white-space: nowrap;'>
                        <input type='text' name='endtime' id='endtime' title='Report End Timestamp' size='14'
                               value='<?php echo $s_endTime; ?>'>
                        &nbsp;<input style='padding-bottom: 4px;' type='image'
                                     src='<?php echo $config[ 'url_path' ]; ?>images/calendar.gif'
                                     alt='End date selector'
                                     title='End date selector' border='0' align='absmiddle'
                                     onclick="return showCalendar('endtime');">
                    </td>
	            <?php } ?>
            <?php } ?>
        </tr>
    </table>
</td>
</tr>

		<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
		<td width="50%">
			<font class="textEditTitle">Archive Report</font><br>
			The report will be archived after creation.
		</td>
		<td>
			<?php
				form_checkbox( "archiveReport", '', "Archive this report after creation", "" );
			?>
		</td>
		</tr>

		<?php
		$a_groupList = array();
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

		<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
		<td width="50%">
			<font class="textEditTitle">Archive User Group</font><br>
			The user group this report will belong to.
		</td>
		<td>
			<?php
				form_dropdown( "archiveUserGroup", $a_groupList, "name", "id", '', "", '', "", "" );
			?>
		</td>
		</tr>
	<?php


	html_end_box();
	// form_save_button( "CereusReporting_GenerateReports.php", "create" );
	?>
    <table align="center" width="100%" style="background-color: #ffffff; border: 1px solid #bbbbbb;">
        <tbody><tr>
            <td bgcolor="#f5f5f5" align="right">
                <input type="hidden" name="action" value="save">
                <input type="button" onclick="cactiReturnTo(&quot;CereusReporting_GenerateReports.php&quot;)" value="Cancel">
                <button id='downloadLink'>
                    <span class='text'>Create Report</span>
                </button>
            </td>
        </tr>
        </tbody></table>
    <?php

	}

	cr_bottom_footer();

?>
