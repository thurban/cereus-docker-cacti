<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_Reports.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
	 * Modified_On:  $Date: 2018/11/11 17:22:55 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	include_once( 'functions.php' );
	include_once( __DIR__.'/include/functions_compat.php' );

	$dir = __DIR__;
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );
	include( "./include/auth.php" );
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
	if ( !isset( $_REQUEST[ "Duplicate" ] ) ) {
		$_REQUEST[ "Duplicate" ] = "";
	}
	else {
		$_REQUEST[ "Duplicate" ] = "Duplicate";
	}
	if ( !isset( $_REQUEST[ "reportName" ] ) ) {
		$_REQUEST[ "reportName" ] = "";
	}
	if ( !isset( $_REQUEST[ "reportId" ] ) ) {
		$_REQUEST[ "reportId" ] = "";
	}

	// CRC-7 - No Copy function at report page nor report schedule
	if ( $_REQUEST[ "Duplicate" ] == "Duplicate" ) {
		form_duplicate_execute();
	}

	switch ( $_REQUEST[ "drp_action" ] ) {
		case '3':
			cr_top_header();
			form_duplicate();
			cr_bottom_footer();
			break;
		case '2':
			form_delete();
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
		$reportId = $_REQUEST[ "reportId" ];

		$sql = "Insert Into
		plugin_nmidCreatePDF_Reports (
			Name,
			Logo,
			CoverPage,
			includeSubDirs,
			leafId,
			reportType,
			type,
			pageSize,
			pageOrientation,
			pageGraphFormat,
			Description,
			outputType,
			showGraphHeader,
			`PrependPDFFile`,
			`AppendPDFFile`,
			`author`,
			`customHeader`,
			`customFooter`,
			`printDetailedFailedPollsTable`,
			`printDetailedPollsTable`,
			`printHeader`,`printFooter`,
			`customReportTitle`,
			`customSubReportTitle`,
			`skipHFCoverPage`
		)
	Select
		:reportName,
		Logo,
		CoverPage,
		includeSubDirs,
		leafId,
		reportType,
		type,
		pageSize,
		pageOrientation,
		pageGraphFormat,
		Description,
		outputType,
		showGraphHeader,
		`PrependPDFFile`,
		`AppendPDFFile`,
		`author`,
		`customHeader`,
		`customFooter`,
		`printDetailedFailedPollsTable`,
		`printDetailedPollsTable`,
		`printHeader`,`printFooter`,
		`customReportTitle`,
		`customSubReportTitle`,
		`skipHFCoverPage`
	From
		plugin_nmidCreatePDF_Reports
	Where
		ReportId= :reportId;
	";
		$stmt = $db->prepare( $sql );
		$stmt->bindValue( ':reportName', $_REQUEST[ "reportName" ] );
		$stmt->bindValue( ':reportId', $reportId );
		$stmt->execute();
		$stmt->closeCursor();

		$stmt = $db->prepare( 'SELECT ReportId FROM plugin_nmidCreatePDF_Reports WHERE Name=:reportName' );
		$stmt->bindValue( ':reportName', $_REQUEST[ "reportName" ] );
		$stmt->execute();
		$newReportId = $stmt->fetchColumn();
		$stmt->closeCursor();

		$stmt = $db->prepare( 'SELECT reportType FROM plugin_nmidCreatePDF_Reports WHERE Name=:reportName' );
		$stmt->bindValue( ':reportName', $_REQUEST[ "reportName" ] );
		$stmt->execute();
		$newReportType = $stmt->fetchColumn();
		$stmt->closeCursor();

		$sql = "";
		if ( $newReportType == 3 ) {
			// MultiReport
			$sql = "
			INSERT INTO
				plugin_nmidCreatePDF_MultiGraphReports
				(`ReportId`,`order`,`data`,`type`)
			SELECT
				$newReportId,`order`,`data`,`type`
			FROM
				plugin_nmidCreatePDF_MultiGraphReports
			WHERE
				`ReportId`=:reportId;
		";
		}
		if ( $newReportType == 2 ) {
			// DSStats Reports
			$sql = "
			INSERT INTO
				plugin_nmidCreatePDF_DSStatsReports
				(`ReportId`,`DSStatsGraph`,`order`,`data`,`group`,`Description`)
			SELECT
				$newReportId,`DSStatsGraph`,`order`,`data`,`group`,`Description`
			FROM
				plugin_nmidCreatePDF_DSStatsReports
			WHERE
				`ReportId`=:reportId;
		";
		}
		if ( $newReportType == 1 ) {
			// Default Reports
			$sql = "
			INSERT INTO
				plugin_nmidCreatePDF_GraphReports
				(ReportId,local_graph_id,order,data,group,Description)
			SELECT
				$newReportId,local_graph_id,ORDER,data,GROUP,Description
			FROM
				plugin_nmidCreatePDF_GraphReports
			WHERE
				ReportId=:reportId;
		";
		}
		$stmt = $db->prepare( $sql );
		$stmt->bindValue( ':reportId', $reportId );
		$stmt->execute();
		$stmt->closeCursor();
		header( "Location: CereusReporting_Reports.php" );
	}

	function form_duplicate()
	{
		global $config, $colors;

		$reportId = '';
		/* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				$reportId = $matches[ 1 ];
			}
		}

		$old_report_name = db_fetch_cell( "SELECT Name FROM plugin_nmidCreatePDF_Reports WHERE ReportId=$reportId" );

		$fields_cereusreporting_report_duplicate = array(
			"reportName" => array(
				"method"        => "textbox",
				"friendly_name" => "New Report Name",
				"description"   => "A useful name for this Report.",
				"value"         => $old_report_name . '_new',
				"max_length"    => "255",
				"size"          => "60"
			),
			"mode"       => array(
				"method" => "hidden",
				"value"  => "duplicate"
			),
			"Duplicate"       => array(
				"method" => "hidden",
				"value"  => "Duplicate"
			),
			"reportId"   => array(
				"method" => "hidden",
				"value"  => $reportId
			)
		);

		$type = "Duplicate";

		print "<table align='center' width='80%'><tr><td>\n";
		html_start_box( "<strong>CereusReporting - " . htmlspecialchars( $type ) . " Report</strong>", "100%", htmlspecialchars( $colors[ "header" ] ), "3", "center", "" );
		print "<tr><td bgcolor='#FFFFFF'>\n";

		print "<p>When you click 'Continue', the following Report will be duplicated. You can optionally change the title format for the new Report.</p>
		   <p>Press <b>'Duplicate'</b> to proceed with the duplication, or <b>'Cancel'</b> to return to the Reports menu.</p>
			</td></tr>";

		html_end_box();

		//print "<form action='CereusReporting_Reports.php' method='post'>\n";
		html_start_box( "<strong>Report " . htmlspecialchars( $type ) . " Settings</strong>", "100%", htmlspecialchars( $colors[ "header" ] ), "3", "center", "" );
		draw_edit_form( array(
			                "config" => array(),
			                "fields" => inject_form_variables( $fields_cereusreporting_report_duplicate, array() ) )
		);
		html_end_box();
		cereusReporting_confirm_button( "Duplicate", "CereusReporting_Reports.php" );
        print "</td></tr></table>\n";
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

	function form_delete()
	{
		global $colors, $hash_type_names;

		/* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				// CRC-6 Deleting a report does not delete associated schedule
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_Reports_scheduler` where `ReportId`='" . $matches[ 1 ] . "'" );

				// Defaults
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_Reports` where `ReportId`='" . $matches[ 1 ] . "'" );
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_GraphReports` where `ReportId`='" . $matches[ 1 ] . "'" );
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_MultiGraphReports` where `ReportId`='" . $matches[ 1 ] . "'" );
				db_execute( "DELETE FROM `plugin_nmidCreatePDF_DSStatsReports` where `ReportId`='" . $matches[ 1 ] . "'" );

			}
		}
		header( "Location: CereusReporting_Reports.php" );
	}

	function form_edit()
	{

	}

	function form_display() {
        global $colors, $config;
        print "<font size=+1>CereusReporting - Reports</font><br>\n";
        print "<hr>\n";

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

        <div id="fader">
            <p style="font-size: 30px; text-align: center; vertical-align: middle; color: white;">Generating Preview Report ...</p>
        </div>


        <?php
        $username = db_fetch_cell( "select username from user_auth where id=" . $_SESSION[ "sess_user_id" ] );

        $where_clause = '';
        if ( isset( $_REQUEST[ "sort_column" ] ) ) {
            if (
                ( $_REQUEST[ "sort_column" ] == 'ReportID' )
                || ( $_REQUEST[ "sort_column" ] == 'Name' )
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
        $a_reports = db_fetch_assoc( "
            SELECT
              `plugin_nmidCreatePDF_Reports`.`ReportId`,
              `plugin_nmidCreatePDF_Reports`.`Name`,
              `plugin_nmidCreatePDF_Reports`.`CoverPage`,
              `plugin_nmidCreatePDF_Reports`.`reportType`,
              `plugin_nmidCreatePDF_Reports`.`Description` AS Description,
              `plugin_nmidCreatePDF_Reports_Types`.`Description` AS type
            FROM
              `plugin_nmidCreatePDF_Reports` INNER JOIN
              `plugin_nmidCreatePDF_Reports_Types` ON `plugin_nmidCreatePDF_Reports`.`type`
                = `plugin_nmidCreatePDF_Reports_Types`.`TypeId`;
        " );

        print "<form name=chk method=POST action=CereusReporting_Reports.php>\n";

        html_start_box( "<strong>CereusReporting  - Reports</strong>", "100%", htmlspecialchars( $colors[ "header" ] ), "3", "center", "CereusReporting_addReport.php?action=add" );

        form_hidden_box( "save_component_import", "1", "" );

        if (sizeof( $a_reports ) > 0) {

            $menu_text = array(
            //"ID" => array("ReportId", "ASC"),
            "Name"        => array( "Name", "ASC" ),
            "Description" => array( "Description", "ASC" ),
            "reportType"  => array( "Report Type", "ASC" ),
            "CoverPage"   => array( "Report Template", "ASC" ),
            "type"   => array( "Schedule Type", "ASC" ),
            "action" => array( "Action", "ASC" )
            );

            html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

            $i = 0;
            $limit = 100;

            $a_reportType = array();
            $a_reportType[ 0 ] = 'Normal Report';
            $a_reportType[ 1 ] = 'Graph Report';
            $a_reportType[ 2 ] = 'DSStats Report';
            $a_reportType[ 3 ] = 'Multi Report';

            foreach ($a_reports as $s_report)
            {
                $showReport = TRUE;
                if ($showReport) {
                    form_alternate_row_color( '', '', $i, 'line' . $s_report[ 'ReportId' ] );
                    $i++;
                    //form_selectable_cell("<img src='images/Report.png'/>", $s_report["ReportId"]);
                    form_selectable_cell( "<a href='" . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_addReport.php?action=update&ReportType=" . $s_report[ 'reportType' ] . "&ReportId=" . $s_report[ "ReportId" ] . "'><img style='border:0px' src='" . $config[ 'url_path' ] . "plugins/CereusReporting/images/Report.png'/><b>" . htmlspecialchars( $s_report[ 'Name' ] ) . "</b></a>", $s_report[ 'ReportId' ], 250 );
                    $description = $s_report[ 'Description' ];
                    $description = preg_replace( "/<br>/", "", $description );
                    if ( strlen( $description ) > $limit ) {
                        $description = substr( $description, 0, strrpos( substr( $description, 0, $limit ), ' ' ) ) . '...';
                    }
                    $description = htmlspecialchars( $description );
                    form_selectable_cell( $description, $s_report[ "ReportId" ] );
                    form_selectable_cell( $a_reportType[ $s_report[ 'reportType' ] ], $s_report[ "ReportId" ] );
                    $template_name = getPreparedDBValue( 'SELECT name FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($s_report[ "CoverPage" ]) );
                    form_selectable_cell( $template_name, $s_report[ "ReportId" ] );
                    form_selectable_cell( $s_report[ 'type' ], $s_report[ "ReportId" ] );
                    form_selectable_cell( "<a id='downloadLink' href='" . $config[ 'url_path' ] . "plugins/CereusReporting/createPDFReport_defined.php?ReportId=" . $s_report[ "ReportId" ] . "&mode=preview'>Preview</a> / " .
                                          "<a href='" . $config[ 'url_path' ] . "plugins/CereusReporting/CereusReporting_GenerateReport_now.php?mode=inline&reportId=" . $s_report[ "ReportId" ] . "'>Generate</a>"
                        , $s_report[ 'ReportId' ], 250 );

                    form_checkbox_cell( 'selected_items', $s_report[ "ReportId" ] );

                    form_end_row();
                }
            }
            html_end_box( FALSE );

            $task_actions = array(
                1 => "Please select an action",
                2 => "Delete",
                3 => "Duplicate"
            );
            draw_actions_dropdown( $task_actions );
        } else {
            print "<tr>
                <td><em>No Reports exist</em></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                </tr>";
            html_end_box( FALSE );
        }
        print "</form>";
    }
