<?php
    /*******************************************************************************
     *
     * File:         $Id: CereusReporting_Availability_addSLATimeFrameItem.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
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
            form_save( $_REQUEST[ "dataId" ] );
            break;
        default:

            cr_top_header();

            form_display( $_REQUEST[ "dataId" ] );
            cr_bottom_footer();
            break;
    }

    function form_save( $dataId )
    {
        global $colors, $hash_type_names, $db;

	    if ( isset ( $_REQUEST[ 'starttime' ] ) ) {
		    $s_defaultStartTime = strtotime( $_REQUEST[ 'starttime' ] );
	    }
	    if ( isset ( $_REQUEST[ 'endtime' ] ) ) {
		    $s_defaultEndTime = strtotime( $_REQUEST[ 'endtime' ] );
	    }
	    if ( isset ( $_REQUEST[ 'description' ] ) ) {
		    $s_dataDescription =  $_REQUEST[ 'description' ];
	    }
	    if ( isset ( $_REQUEST[ 'slaRelevant' ] ) ) {
		    $s_dataSLARelevant =  $_REQUEST[ 'slaRelevant' ];
	    }
	    if ( isset ( $_REQUEST[ 'slaId' ] ) ) {
		    $s_dataSLAId =  $_REQUEST[ 'slaId' ];
	    }


	    if ( ( isset ( $_REQUEST[ 'description' ] ) ) && ( isset ( $_REQUEST[ 'save_component_import' ] ) ) ) {
            $sql = "
			INSERT INTO `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`
				(`slaEnabled`, `startTimeStamp`, `endTimeStamp`,`description`, `slaTimeFrameId`)
			VALUES
				(:s_dataSLARelevant, :s_defaultStartTime,:_s_defaultEndTime, :s_dataDescription,
				:s_dataSLAId)
			";
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':s_dataSLARelevant', $s_dataSLARelevant, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultStartTime', $s_defaultStartTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultEndTime', $s_defaultEndTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataDescription', $s_dataDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataSLAId', $s_dataSLAId, PDO::PARAM_STR );
            $stmt->execute();
        }
        if ( ( isset ( $_REQUEST[ 'description' ] ) ) && ( isset ( $_REQUEST[ 'update_component_import' ] ) ) ) {
            $sql = "
			UPDATE `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`
			Set
				slaEnabled=:s_dataSLARelevant,
				startTimeStamp=:s_defaultStartTime,
				endTimeStamp=:s_defaultEndTime,
				description=:s_dataDescription,
				slaTimeFrameId=:s_dataSLAId
			WHERE
				Id=:dataId
			" ;
            $stmt = $db->prepare( $sql);
            $stmt->bindValue( ':s_dataSLARelevant', $s_dataSLARelevant, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultStartTime', $s_defaultStartTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_defaultEndTime', $s_defaultEndTime, PDO::PARAM_STR );
            $stmt->bindValue( ':s_dataDescription', $s_dataDescription, PDO::PARAM_STR );
            $stmt->bindValue( ':dataId', $dataId, PDO::PARAM_STR );
            $stmt->execute();
        }
        header( "Location: CereusReporting_Availability_SLATimeFrameItem.php" );

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
			 `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`Id`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`slaEnabled`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`startTimeStamp`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`endTimeStamp`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`description`,
			 `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`.`slaTimeFrameId`
		        FROM
			 `plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table`
			WHERE Id='$dataId'
		" );
            foreach ( $a_reports as $s_report ) {
                $s_defaultSlaRelevant  = $s_report[ 'slaEnabled' ];
                $s_defaultStartTime    = $s_report[ 'startTimeStamp' ];
                $s_defaultEndTime      = $s_report[ 'endTimeStamp' ]; // 1 = true
                $s_defaultDescription  = $s_report[ 'description' ];
                $i_defaultSLATimeFrame = $s_report[ 'slaTimeFrameId' ];
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

        print "<font size=+1>CereusReporting - Add SLA TimeFrame Item Data</font><br>\n";
        print "<hr>\n";


        ?>
	<form method="post" action="CereusReporting_Availability_addSLATimeFrameItem.php" enctype="multipart/form-data">
	<?php

        if ( $dataId > 0 ) {
            html_start_box( "<strong>Availability SLA TimeFrame Item</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
        }
        else {
            html_start_box( "<strong>Availability SLA TimeFrame Item</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
        }


        form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Description</font><br>
            A short description which will be shown on the graph at the start and end of the time frame.
        </td>
        <td>
            <?php  form_text_box( "description", "", $s_defaultDescription, 255 ); ?>
        </td>
        </tr>


        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Associated SLA TimeFrame</font><br>
            The SLA TimeFrame this item belongs to.
        </td>
        <td>
            <?php
            $a_SLATimeFrame = db_fetch_assoc( "
				 SELECT
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id` as id,
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription` as name
				FROM
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
				ORDER BY
				    `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`;
			" );
            form_dropdown( "slaId", $a_SLATimeFrame, "name", "id", $i_defaultSLATimeFrame, "", $i_defaultSLATimeFrame, "", "" );
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
            <?php  form_checkbox( "slaRelevant", $s_defaultSlaRelevant, "true", "" ); ?>
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
        form_save_button( "CereusReporting_Availability_SLATimeFrameItem.php", "save" );

    }

    cr_bottom_footer();

?>
