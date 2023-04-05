<?php
    /*******************************************************************************
     *
     * File:         $Id: CereusReporting_Availability_addTholdSlaExceptions.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
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
    $dir     = dirname( __FILE__ );
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

    function form_save( $dataId )
    {
        global $colors, $hash_type_names;
	    if ( isset ( $_REQUEST[ 'starttime' ] ) ) {
		    $s_dataStartTime = strtotime(  filter_var( $_REQUEST[ "starttime" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH ) );
	    }
	    if ( isset ( $_REQUEST[ 'endtime' ] ) ) {
		    $s_dataEndTime = strtotime(  filter_var( $_REQUEST[ "endtime" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH ) );
	    }
        if ( isset ( $_REQUEST[ 'comment' ] ) ) {
            $s_dataDescription = filter_var( $_REQUEST[ "comment" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
        }
        if ( isset ( $_REQUEST[ 'thold_data_id' ] ) ) {
            $s_dataTholdDataId = filter_var( $_REQUEST[ "thold_data_id" ], FILTER_SANITIZE_NUMBER_INT );
        }
        if ( isset ( $_REQUEST[ 'is_sla_relevant' ] ) ) {
            $s_dataSLARelevant = filter_var( $_REQUEST[ "is_sla_relevant" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
        }
        if ( $s_dataSLARelevant == "on" ) {
            $s_dataSLARelevant = '1';
        }
        else {
            $s_dataSLARelevant = '0';
        }
        if ( ( isset ( $_REQUEST[ 'comment' ] ) ) && ( isset ( $_REQUEST[ 'save_component_import' ] ) ) ) {
            db_execute( "
			INSERT INTO `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`
				(`is_sla_relevant`, `startTimeStamp`, `endTimeStamp`,`comment`,`thold_data_id` )
			VALUES
				('$s_dataSLARelevant', '$s_defaultStartTime','$s_defaultEndTime', '$s_dataDescription', '$s_dataTholdDataId' )
			" );
        }
        if ( ( isset ( $_REQUEST[ 'comment' ] ) ) && ( isset ( $_REQUEST[ 'update_component_import' ] ) ) ) {
            db_execute( "
			UPDATE `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`
			Set
				is_sla_relevant='$s_dataSLARelevant',
				startTimeStamp='$s_defaultStartTime',
				endTimeStamp='$s_defaultEndTime',
				comment='$s_dataDescription',
				thold_data_id='$s_dataTholdDataId'
			WHERE
				Id='$dataId'
			" );
        }
        header( "Location: CereusReporting_Availability_TholdSlaExceptions.php" );

    }

    function form_display( $dataId )
    {
        global $colors, $hash_type_names, $config;
        $dateFormat           = readConfigOption( "nmid_pdf_dateformat" );
        $s_defaultSlaRelevant = 'Mon,Tue,Wed,Thu,Fri';
        $s_defaultStartTime   = date( $dateFormat, time() - 3600 );
        $s_defaultEndTime     = date( $dateFormat, time() );
        ;
        $s_defaultDescription  = '';
        $i_defaultSLATimeFrame = '';

        if ( $dataId > 0 ) {
            $a_reports = db_fetch_assoc( "
			SELECT
			 `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`Id`,
			 `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`is_sla_relevant`,
			 `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`startTimeStamp`,
			 `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`endTimeStamp`,
			 `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`comment`,
			 `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`.`thold_data_id`
		    FROM
			 `plugin_CereusReporting_Availability_Thold_DataLog_Exceptions`
			WHERE Id='$dataId'
		" );
            foreach ( $a_reports as $s_report ) {
                $s_defaultSlaRelevant = $s_report[ 'is_sla_relevant' ];
                $s_defaultStartTime   = $s_report[ 'startTimeStamp' ];
                $s_defaultEndTime     = $s_report[ 'endTimeStamp' ];
                $s_defaultDescription = $s_report[ 'comment' ];
                $i_defaultTholdDataId = $s_report[ 'thold_data_id' ];
            }
            $s_defaultStartTime = date( $dateFormat, $s_defaultStartTime );
            $s_defaultEndTime   = date( $dateFormat, $s_defaultEndTime );
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
        <script type="text/javascript" src="<?php echo $config[ 'url_path' ]; ?>include/jscalendar/calendar.js"></script>
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
    <?php

        print "<font size=+1>CereusReporting - Add Thold SLA Exception Item</font><br>\n";
        print "<hr>\n";

        ?>
	<form method="post" action="CereusReporting_Availability_addTholdSlaExceptions.php" enctype="multipart/form-data">
	<?php

        if ( $dataId > 0 ) {
            html_start_box( "<strong>Availability Thold SLA Exception Item</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
        }
        else {
            html_start_box( "<strong>Availability Thold SLA Exception Item</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
        }


        form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Comment</font><br>
            A short comment which will be shown on the graph at the start and end of the time frame.
        </td>
        <td>
            <?php  form_text_box( "comment", "", $s_defaultDescription, 255 ); ?>
        </td>
        </tr>


        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Associated Thold Data Item</font><br>
            The Thold entry this item belongs to.
        </td>
        <td>
            <?php
            $a_TholdData = db_fetch_assoc( "
				 SELECT
				    `thold_data`.`Id` as id,
				    `thold_data`.`name` as name
				FROM
				    `thold_data`
				ORDER BY
				    `thold_data`.`name`;
			" );
            form_dropdown( "thold_data_id", $a_TholdData, "name", "id", $i_defaultTholdDataId, "", $i_defaultTholdDataId, "", "" );
            ?>
        </td>
        </tr>

        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Start Time</font><br>
            The time when this TimeFrame item starts.
        </td>
		<?php if ( function_exists('top_graph_header')) { // Cacti 1.x ?>
            <td>
                    <span>
                        <input type='hidden' name='starttime' id='starttime' size='18' value='<?php print $s_defaultStartTime;?>'>
                        <input type='text' name='dateCal1' id='dateCal1' size='18' value='<?php print $s_defaultStartTime;?>'>
                        <i id='startDate' class='calendar fa fa-calendar' title='Start Date Selector'></i>
                    </span>
            </td>
		<?php } else { // Cacti 0.8.x ?>
        <td>
            <input type='text' name='starttime' id='starttime' title='TimeFrame Item Start' size='14'
                   value='<?php echo $s_defaultStartTime;?>'>
            &nbsp;<input style='padding-bottom: 4px;' type='image'
                         src='<?php echo $config[ 'url_path' ]; ?>images/calendar.gif' alt='Start date selector'
                         title='Start date selector' border='0' align='absmiddle'
                         onclick="return showCalendar('starttime');">&nbsp;
        </td>
		<?php } ?>
        </tr>

        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">End Time</font><br>
            The time when this TimeFrame item stops.
        </td>
		<?php if ( function_exists('top_graph_header')) { // Cacti 1.x ?>
            <td>
                        <span>
                            <input type='hidden' name='endtime' id='endtime' size='18' value='<?php print $s_defaultEndTime;?>'>
                            <input type='text' name='dateCal2' id='dateCal2' size='18' value='<?php print $s_defaultEndTime;?>'>
                            <i id='endDate' class='calendar fa fa-calendar' title='End Date Selector'></i>
                        </span>
            </td>
		<?php } else { // Cacti 0.8.x ?>
        <td>
            <input type='text' name='endtime' id='endtime' title='TimeFrame Item End' size='14'
                   value='<?php echo $s_defaultEndTime;?>'>
            &nbsp;<input style='padding-bottom: 4px;' type='image'
                         src='<?php echo $config[ 'url_path' ]; ?>images/calendar.gif' alt='End date selector'
                         title='End date selector' border='0' align='absmiddle'
                         onclick="return showCalendar('endtime');">
        </td>
		<?php } ?>
        </tr>

        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">SLA Relevant</font><br>
            This is a SLA relevant date.
        </td>
        <td>
            <?php  form_checkbox( "is_sla_relevant", $s_defaultSlaRelevant, "true", "" ); ?>
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
        form_save_button( "CereusReporting_Availability_TholdSlaExceptions.php", "save" );

    }

    cr_bottom_footer();

?>
