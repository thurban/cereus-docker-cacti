<?php
	/*******************************************************************************
 * Copyright (c) 2017. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2017.
 *
 * File:         $Id: functions_cacti_1.0.0.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 * Filename:     functions_cacti_1.0.0.php
 * LastModified: 06.07.17 07:37
 * Modified_On:  $Date: 2020/12/10 07:06:31 $
 * Modified_By:  $Author: thurban $
 *
 ******************************************************************************/



	function CereusReporting_page_buttons_compat( $my_args ) {
		global $config, $colors, $plugin_architecture;

		$tree_id      = $my_args[ 'treeid' ];
		$leaf_id      = $my_args[ 'leafid' ];
		$mode         = $my_args[ 'mode' ];
		$starttime    = $my_args[ 'starttime' ];
		$endtime      = $my_args[ 'endtime' ];
		$timespan     = $my_args[ 'timespan' ];

		$txt[ 'P' ]   =  'Portrait';
		$txt[ 'L' ]   =  'Landscape';
		$txt[ '0' ]   =  'Default';
		$txt[ '2x2' ] =  '2 Graphs, 2 Columns';
		?>
		<div id="fader">
			<p style="font-size: 30px; text-align: center; vertical-align: middle; color: white;">Generating Report ...</p>
		</div>
		<style>
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

            .ui-dialog { z-index: 50 !important ;}
		</style>
		<SCRIPT TYPE="text/javascript">
            <!--
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

            var downloadTimeout;
            var checkDownloadCookie = function() {
                if (getCookie("downloadStarted") == 1) {
                    setCookie("downloadStarted", "false", 100); //Expiration could be anything... As long as we reset the value
                    $('#fader').css('display', 'none');
                } else {
                    downloadTimeout = setTimeout(checkDownloadCookie, 1000); //Re-run this function in 1 second.
                }
            };


            function setAction(a) {
                if ( a == 1 ) {
                    document.CereusReporting_Form.action = "<?php echo $config[ 'url_path' ]; ?>plugins/CereusReporting/addToGraphReport.php";
                    $('#fader').css('display', 'block');
                    setCookie('downloadStarted', 0, 100); //Expiration could be anything... As long as we reset the value
                    setTimeout(checkDownloadCookie, 1000); //Initiate the loop to check the cookie.
                }
                if ( a == 2 ) {
                    document.CereusReporting_Create_Form.action = "<?php echo $config[ 'url_path' ]; ?>plugins/CereusReporting/createPDFReport.php";
                    $('#fader').css('display', 'block');
                    setCookie('downloadStarted', 0, 100); //Expiration could be anything... As long as we reset the value
                    setTimeout(checkDownloadCookie, 1000); //Initiate the loop to check the cookie.
                }
                if ( a == 3 ) {
                    document.CereusReporting_Form.action = "<?php echo $config[ 'url_path' ]; ?>plugins/CereusReporting/createPDFReport_mrtgStyle.php";
                    $('#fader').css('display', 'block');
                    setCookie('downloadStarted', 0, 100); //Expiration could be anything... As long as we reset the value
                    setTimeout(checkDownloadCookie, 1000); //Initiate the loop to check the cookie.
                }
                if ( a == 4 ) {
                    document.CereusReporting_Form.action = "<?php echo $config[ 'url_path' ]; ?>plugins/CereusReporting/CereusReporting_createTemplateReport.php";
                    $('#fader').css('display', 'block');
                    setCookie('downloadStarted', 0, 100); //Expiration could be anything... As long as we reset the value
                    setTimeout(checkDownloadCookie, 1000); //Initiate the loop to check the cookie.
                }
                if (a == 5) {
                    document.CereusReporting_Form.action = "<?php echo $config[ 'url_path' ]; ?>plugins/CereusReporting/createPDFReport.php";
                    $('#fader').css('display', 'block');
                    setCookie('downloadStarted', 0, 100); //Expiration could be anything... As long as we reset the value
                    setTimeout(checkDownloadCookie, 1000); //Initiate the loop to check the cookie.
                }
                $("#nmidCreateDialog").dialog("close");
                document.CereusReporting_Form.submit()

            }

            function setData(a) {
                if ( document.CereusReporting_Form.lgi_fix.value.match(";"+a) ) {
                    document.CereusReporting_Form.lgi_fix.value = document.CereusReporting_Form.lgi_fix.value.replace(";" + a, "");
                    document.CereusReporting_Create_Form.lgi_fix.value = document.CereusReporting_Create_Form.lgi_fix.value.replace(";" + a, "");
                } else {
                    document.CereusReporting_Form.lgi_fix.value = document.CereusReporting_Form.lgi_fix.value + ";" + a;
                    document.CereusReporting_Create_Form.lgi_fix.value = document.CereusReporting_Create_Form.lgi_fix.value + ";" + a;
                }
            }

            $(function () {
                $("#send_email").click(function() {
                    $("#emailField")[this.checked ? "show" : "hide"]();
                });

                $("#nmidDialog").dialog({
                    modal: true,
                    autoOpen: false
                });

                $("#nmidOpener").click(function () {
                    $("#nmidDialog").dialog("open");
                    return false;
                });

                $("#nmidCreateDialog").dialog({
                    modal: true,
                    autoOpen: false
                });

                $("#nmidCreateOpener").click(function () {
                    $("#nmidCreateDialog").dialog("open");
                    return false;
                });

            });
            //-->
		</SCRIPT>
		<?php
		echo "<table width=99%><tr><td>&nbsp;</td><td align=right>";
		if ( ( $mode == 'tree')  || ( $mode == 'preview' ) ) {

		    // Get Coverpage infos:
            $fileCount                  = 1;
            $a_templates                = array();
            $a_templates[]              = 'None';
            $dirFiles                   = array();

            if ( $dh = opendir( __DIR__ . '/../templates/coverpages/' ) ) {
                while ( ( $file = readdir( $dh ) ) !== FALSE ) {
                    if ( !( is_dir( $file ) ) ) {
                        $dirFiles[] = $file;
                    }
                }
                closedir( $dh );
            }
            sort( $dirFiles );

            foreach ( $dirFiles as $templateName ) {
                if ( in_array( $templateName, $a_templates ) == FALSE ) {
                    $a_templates[]        = $templateName;
                    $fileCount++;
                }
            }


            // __csrf_magic: csrfMagicToken
			echo "<input title='Add graphs to report' type='image' id='nmidOpener' src='plugins/CereusReporting/images/Report_Add.png'/>";
			echo "<input title='Create PDF Report' type='image' src='plugins/CereusReporting/images/PDF_file.png' id='nmidCreateOpener' value='DefaultType' alt='DefaultType'>";
			$s_CereusReporting_html_form_content = "<form id='CereusReportingCreateForm' name=CereusReporting_Create_Form method=post>";
			$s_CereusReporting_html_form_content .= "<input type=hidden name=starttime value='" . $starttime . "'>";
			$s_CereusReporting_html_form_content .= "<input type=hidden name=endtime value='" . $endtime . "'>";
			$s_CereusReporting_html_form_content .= "<input type=hidden name=leaf_id value='" . $leaf_id . "'>";
			$s_CereusReporting_html_form_content .= "<input type=hidden name=timespan value='" . $timespan . "'>";
			$s_CereusReporting_html_form_content .= "<input type=hidden name=tree_id value='" . $tree_id . "'>";
			$s_CereusReporting_html_form_content .= "<input type=hidden name=user_id value='" . $_SESSION[ "sess_user_id" ] . "'>";
            $s_CereusReporting_html_form_content .= "Please choose a coverpage: <select style=' z-index: 1103;' name='report_coverpage'>" .
            "<OPTGROUP label='Please choose a coverpage'>";
            foreach ( $a_templates as $coverpage_file ) {
                $s_CereusReporting_html_form_content .= "<option value='" . $coverpage_file . "'>" . $coverpage_file . "</option>";
            }

            $s_CereusReporting_html_form_content .= "</OPTGROUP></select><br/>";
            if (function_exists('csrf_get_tokens' )) {
				$s_CereusReporting_html_form_content .= "<input type=hidden id='__csrf_magic' name='__csrf_magic' value='" . csrf_get_tokens() . "'>";
			} else {
				$s_CereusReporting_html_form_content .= "<input type=hidden id='__csrf_magic' name='__csrf_magic' value=''>";
            }
			$s_CereusReporting_html_form_content .= "<input type=hidden name=lgi_fix value=''>";

			if ( $mode == 'tree' ) {
                if ( preg_match( "/\d+/", $leaf_id ) == FALSE ) {
                    $s_CereusReporting_html_form_content .= '<br/><font color=\"blue\">Please note that you cannot create a standard report on the main tree level.' .
                        'You will have to select the <b>\"Include Sub-Leafs\"</b> for this to work.</font><br/><br/>';
                }
                if ( preg_match( "/\d+/", $leaf_id ) == FALSE ) {
                    $s_CereusReporting_html_form_content .= 'Include Sub-Leafs' . " <input type=checkBox checked name='nmid_pdfAddSubLeafs' value=1><br/>";
                } else {
                    $s_CereusReporting_html_form_content .= 'Include Sub-Leafs' . " <input type=checkBox name='nmid_pdfAddSubLeafs' value=1><br/>";
                }
            }
			$s_CereusReporting_html_form_add_content = $s_CereusReporting_html_form_content;
			$s_CereusReporting_html_form_content .= "Email Report<input id='send_email' type=checkBox name='nmid_send_report_email' value=0><br/>".
				"<div id='emailField' >Target Email :<input type=text name='user_target_email' value='' /><br/></div>";
			if ( api_user_realm_auth( 'CereusReporting_addReport.php' ) ) {
				$s_CereusReporting_html_form_add_content .= "<select name='report_id'>" .
					"<OPTGROUP label='Please choose'>";
				$GraphReports = db_fetch_assoc( "
    SELECT `ReportId`,`Name` FROM `plugin_nmidCreatePDF_Reports` WHERE `reportType` = 1 OR `reportType` = 3 ;
    " );
				if (is_array($GraphReports)) {
					foreach ( $GraphReports as $report ) {
						$s_CereusReporting_html_form_add_content .= "<option value='" . $report[ 'ReportId' ] . "'>" . $report[ 'Name' ] . "</option>";
					}
				}
				$s_CereusReporting_html_form_add_content .= "</OPTGROUP></select><br/>";
			}
			//$s_CereusReporting_html_form_content .= "Target Email ( Leave empty to download ): <input type=text name=user_target_email value='' />";
			$s_CereusReporting_html_form_content .= "<button id='nmidOpener' onclick=\\\"setAction('2')\\\" >";
			$s_CereusReporting_html_form_content .= "<span class='image'><img src='" . $config[ 'url_path' ] . "plugins/CereusReporting/images/Download.png' /></span>";
			$s_CereusReporting_html_form_content .= "<span class='text'>Create Report</span>";
			$s_CereusReporting_html_form_content .= "</button></form>";
			$s_CereusReporting_html_form_add_content = preg_replace( "/CereusReporting_Create_Form/", 'CereusReporting_Form', $s_CereusReporting_html_form_add_content );
			$s_CereusReporting_html_form_add_content .= "<input type='image' onclick=\\\"setAction('1')\\\" src='" . $config[ 'url_path' ] . "plugins/CereusReporting/images/add.png'/></form>";

			?>
			<SCRIPT TYPE="text/javascript">
                <!--
                if ( $( "#CereusReportingCreateForm" ).length ) {
                    // form already exists
                    $('#nmidCreateDialog').html("<?php echo $s_CereusReporting_html_form_content; ?>");
                    $('#nmidCreateDialog').dialog( "moveToTop" );
                    $('#nmidDialog').html("<?php echo $s_CereusReporting_html_form_add_content; ?>");
                    $('#nmidDialog').dialog( "moveToTop" );
                }  else {
                    var txt1 = "<div id='nmidDialog' title='Add Graph(s) to Report'></div>";
                    var txt2 = "<div id='nmidCreateDialog'  title='Create Report'></div>";
                    $("body").append(txt1, txt2);
                    $('#nmidCreateDialog').html("<?php echo $s_CereusReporting_html_form_content; ?>");
                    $('#nmidDialog').html("<?php echo $s_CereusReporting_html_form_add_content; ?>");
                    $('#nmidCreateDialog').dialog( "moveToTop" );
                    $('#nmidDialog').dialog( "moveToTop" );
                }
                //-->
			</SCRIPT>
			<?php
		}
		else {
			// CRC-138 On-Demand reports on "MRTG like view" does not work
			echo "<input type='image' src='plugins/CereusReporting/images/PDF_file.png' onclick=\"setAction('3')\" value='MRTGStyle' alt='MRTGStyle'>";
		}

		// echo '<div id="nmidDialog" sortof="'.time().'" title="' . 'Add Graph(s) to Report' . '"></div>';
		// echo '<div id="nmidCreateDialog" sortof="'.time().'"  title="' . 'Create Report' . '"></div>';

		echo "</td></tr></table>\n";

		// return $my_args;
	}

	function CereusReporting_tree_after_compat($param) {
		global $config, $database_default;

		preg_match( "/^(.+),(\d+)$/", $param, $hit );
		include_once( $config[ "library_path" ] . "/database.php" );
		$dir = dirname( __FILE__ );
		include_once( $dir . '/../functions.php' );

        if ( isset_request_var('action') ) {
            $mode = get_request_var('action');
        }
		$tree_id = 0;
		$node_id = 0;
		$hgdata  = 0;

		if (isset_request_var('node')) {
			$parts = explode('-', get_request_var('node'));

			// Check for tree anchor
			if (strpos(get_request_var('node'), 'tree_anchor') !== false) {
				$tree_id = $parts[1];
				$node_id = 0;
			}elseif (strpos(get_request_var('node'), 'tbranch') !== false) {
				// Check for branch
				$node_id = $parts[1];
				$tree_id = db_fetch_cell_prepared('SELECT graph_tree_id 
				FROM graph_tree_items 
				WHERE id = ?',
				array($node_id));
			}
		}


		$startTime = get_current_graph_start();
		$endTime = get_current_graph_end();

		if (api_user_realm_auth( 'CereusReporting_AvailabilityChart.php' )) {
            if (isset ( $hit[ 1 ] )) {
                // We skip hosts for now.
            }
            else {
                // So we're in a leaf
                $host_leaf_id = $node_id;

                // We now need to get the real host_id
                $leaf_title = db_fetch_cell( "select title from graph_tree_items where id=$host_leaf_id" );
                $host_id = 0;
                if (preg_match( "@\[NMIDWM\-(.*)\]\s(.*)@", $leaf_title, $matches )) {
                    $weathermap_id = $matches[ 1 ];
                    $dir = dirname( __FILE__ );
                    $filename = $dir . '/../weathermap/output/' . $weathermap_id . '.html';
                    $handle = fopen( $filename, "r" );
                    $contents = fread( $handle, filesize( $filename ) );
                    fclose( $handle );
                    $contents = preg_replace( '@weathermap-cacti-plugin.php@', $config[ 'url_path' ] . '/plugins/weathermap/weathermap-cacti-plugin.php', $contents );
                    $url = $config[ 'url_path' ] . 'plugins/weathermap/weathermap-cacti-plugin.php?action=viewimage&id=' . $weathermap_id;
                    ?>
                    <tr bgcolor='#6d88ad'>
                    <tr bgcolor='#a9b7cb'>
                        <td colspan='3' class='textHeaderDark'>
                            <strong><?php echo  'Weathermap Report'; ?>
                        </td>
                    </tr>
                    <tr align='center' style='background-color: #f9f9f9;'>
                    <td align='center'>
                    <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
                    <script type="text/javascript"
                            src="<?php echo $config[ 'url_path' ] . '/plugins/weathermap/'; ?>overlib.js"><!--
                        overLIB(c)
                        Erik
                        Bosrup --></script>
                    <?php echo $contents; ?>
                    <?php
                }
                if (preg_match( "@\[NMIDWS\-(.*)\]\s(.*)@", $leaf_title, $matches )) {
                    $my_leaf_id = $matches[ 1 ];
                    if (readPluginStatus( 'nmidWeb2' )) {
                        print "<div class='sidebarBox portlet' id='item-av01'>\n";
                        print "<div class='portlet-header'>" .  'Windows Services Report' . "</div>\n";
                        print "<div class='guideListing portlet-content'><center>\n";
                    } else {
                        ?>
                        <tr bgcolor='#6d88ad'>
                        <tr bgcolor='#a9b7cb'>
                            <td colspan='3' class='textHeaderDark'>
                                <strong><?php echo  'Windows Services Report'; ?>
                            </td>
                        </tr>
                        <tr align='center' style='background-color: #f9f9f9;'>
                        <td align='center'>
                        <!-- MAIN CONTENT HERE -->
                        <?php
                        print "	<table width='1' cellpadding='0'>\n";
                        print "		<tr>\n";
                        print "			<td valign='top' style='padding: 3px;' class='noprint'>\n";
                        print "				<img src='" . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_winserviceAvailabilityChart.php?spaceLeft=300&tree_id=' . $tree_id . '&leaf_id=' . $my_leaf_id . '&startTime=' . $startTime . '&endTime=' . $endTime . '&mode=time&data=p' . "' border='0'>\n";
                        print "			</td>\n";
                        print "			<td valign='top' style='padding: 3px;' class='noprint'>";
                        if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
                            print " 	    <input onClick=\"setData('avwsc_" . $tree_id . "_" . $my_leaf_id . "');\" type=checkbox id='avwsc_" . $tree_id . "_" . $my_leaf_id . "' name='avwsc_" . $tree_id . "_" . $my_leaf_id . "' value='" . $tree_id . "_" . $my_leaf_id . "'><br>";
                            if ( $mode == 'preview' )  {
                                print "<script>setData('avwsc_" . $tree_id . "_" . $my_leaf_id . "');</script>";
                            }
                        }
                        print "         </td>";
                        print "		</tr>\n";
                        print "</table>\n";
                    }
                }

                if (preg_match( "@\[NMIDTH\-(.*)\]\s(.*)@", $leaf_title, $matches )) {
                    $dataType = $matches[ 1 ];
                    if (readPluginStatus( 'nmidWeb2' )) {
                        print "<div class='sidebarBox portlet' id='item-av01'>\n";
                        print "<div class='portlet-header'>" .  'Threshold Report' . "</div>\n";
                        print "<div class='guideListing portlet-content'><center>\n";
                    } else {
                        ?>
                        <tr bgcolor='#6d88ad'>
                        <tr bgcolor='#a9b7cb'>
                            <td colspan='3' class='textHeaderDark'>
                                <strong>Threshold Report
                            </td>
                        </tr>
                        <tr align='center' style='background-color: #f9f9f9;'>
                        <td align='center'>
                        <?php
                    }
                    if ( preg_match( "@^leaf@", $dataType ) ) {
                        // Leaf
                    }
                    elseif ( preg_match( "@^tree@", $dataType ) ) {
                        // Tree
                    }
                    else {
                        if ( preg_match( "@^host(.*)@", $dataType, $hostMatches ) ) {
                            $host_id = $hostMatches[ 1 ];
                            // Host
                            $a_hostTholdStates = db_fetch_assoc( "
                                        SELECT 
                                            FROM_UNIXTIME(`time`) as time,
                                            host_id,
                                            threshold_value,
                                            current,
                                            `status`,
                                            `description`
                                        FROM
                                           `plugin_thold_log`
                                        WHERE 
                                            host_id = $host_id
                                        ORDER BY
                                            time
                                    " );

                            print "<table width=100%>\n";
                            foreach ( $a_hostTholdStates as $a_hostTholdState ) {
                                print "<tr>" .
                                    "<td>" . $a_hostTholdState[ 'host_id' ] . "</td>" .
                                    "<td>" . $a_hostTholdState[ 'time' ] . "</td>" .
                                    "<td>" . $a_hostTholdState[ 'threshold_value' ] . "</td>" .
                                    "<td>" . $a_hostTholdState[ 'current' ] . "</td>" .
                                    "<td>" . $a_hostTholdState[ 'status' ] . "</td>" .
                                    "<td>" . $a_hostTholdState[ 'description' ] . "</td>" .
                                    "</tr>\n";
                            }
                            print "</table>\n";
                        }
                    }
                    if ( readPluginStatus( 'nmidWeb2' ) ) {
                        print "</center></div></div>";
                    }
                    else {
                        print "</td></tr></tr>";
                    }
                }
            }
		}

		if ( read_config_option( 'nmid_avail_addTable' ) ) {
			$globalSLA = readConfigOption( 'nmid_avail_globalSla' );
			if (api_user_realm_auth( 'CereusReporting_AvailabilityChart.php' )) {
                // Availability Reports are only available in Pro and Corporate Edition
                $tree_id = -1;
                $host_leaf_id = -1;
                $node_id = -1;


                if (isset_request_var('node')) {
                    $parts = explode('-', get_request_var('node'));

                    // Check for tree anchor
                    if (strpos(get_request_var('node'), 'tree_anchor') !== false) {
                        $tree_id = $parts[1];
                        $node_id = 0;
                    }elseif (strpos(get_request_var('node'), 'tbranch') !== false) {
                        // Check for branch
                        $node_id = $parts[1];
                        $tree_id = db_fetch_cell_prepared('SELECT graph_tree_id 
                          FROM graph_tree_items 
                          WHERE id = ?',array($node_id));
                    }
                }

                $host_leaf_id  = $node_id;

                if (isset ( $hit[ 1 ] )) {
                    // no hostname set
                    $host_name = $hit[ 1 ];
                    $host_leaf_id = $hit[ 2 ];

                    $host_id = db_fetch_cell( "select host_id from graph_tree_items where id=$host_leaf_id" );
                    $host_sla = db_fetch_cell( "select nmid_host_sla from host where id=$host_id" );
                    $slaValue = $globalSLA;
                    $slaString = '';
                    if ( $host_sla > 0 ) {
                        $slaValue = $host_sla;
                    }
                    ?>
                    <tr bgcolor='#6d88ad'>
                    <tr bgcolor='#a9b7cb'>
                        <td colspan='<?php print get_request_var('columns'); ?>' class='textHeaderDark'>
                            <strong>Graph Template:</strong> <?php echo  'Availability Chart (external)'; ?>
                        </td>
                    </tr>
                    <tr align='center' style='background-color: #f9f9f9;'>
                    <td align='center' colspan="<?php print get_request_var('columns'); ?>">
                    <?php
                    print "	<table width='1' cellpadding='0'>\n";
                    print "		<tr>\n";
                    print "			<td valign='top' style='padding: 3px;' class='noprint'>\n";
                    print "				<img src='" . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChart.php?tree_id=' . $tree_id . '&leaf_id=' . $host_leaf_id . '&startTime=' . $startTime . '&endTime=' . $endTime . '&mode=time&data=p' . "' border='0'>\n";
                    print "			</td>\n";
                    print "			<td valign='top' style='padding: 3px;' class='noprint'>";
                    if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
                        print " 	    <input onClick=\"setData('avc_" . $tree_id . "_" . $host_leaf_id . "');\" type=checkbox id='avc_" . $tree_id . "_" . $host_leaf_id . "' name='avc_" . $tree_id . "_" . $host_leaf_id . "' value='" . $tree_id . "_" . $host_leaf_id . "'><br>";
                        if ( $mode == 'preview' )  {
                            print "<script>setData('avc_" . $tree_id . "_" . $host_leaf_id . "');</script>";
                        }

                    }
                    print "         </td>";
                    print "		</tr>\n";
                    print "</table>\n";

                    if ( read_config_option( 'nmid_avail_addTable' ) ) {
                        list ( $availability, $totalPolls, $failedPolls ) = get_device_availability( $host_id, $startTime - 1, $endTime + 1 );

                        print "<table>\n";
                        print "	<tr>\n";
                        print " 	<th style='border-bottom: 2px solid #000000;'>" .  'Total Polls' . "</th>\n";
                        print " 	<th style='border-bottom: 2px solid #000000;'>" .  'Failed Polls' . "</th>\n";
                        print " 	<th style='border-bottom: 2px solid #000000;'>" .  'Availability' . "</th>\n";
                        print " 	<th style='border-bottom: 2px solid #000000;'>" . $slaString . " SLA</th>\n";
                        print "	</tr>\n";
                        print "	<tr>\n";
                        print " 	<td align=center>" . number_format( $totalPolls ) . "</td>\n";
                        print " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $failedPolls ) . "</td>\n";
                        $fontColor = '#007700';
                        if ( $availability < $slaValue ) {
                            $fontColor = '#aa0000';
                        }
                        print " 	<td style='border-left: 1px solid #000000;' align=center><font color=" . $fontColor . "><b>" . number_format( $availability, 2 ) . "%</b></font></td>\n";
                        print " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $slaValue, 2 ) . "%</td>\n";
                        print "	</tr>\n";
                        print "</table>\n";
                    }
                    print "</td></tr></tr>";

                } else {
                    if (CereusReporting_getHostCountFromLeaf( $host_leaf_id, $tree_id ) > 0) {
                        // no hostname set
                        if (readPluginStatus( 'nmidWeb2' )) {
                            print "<div class='sidebarBox portlet' id='item-av01'>\n";
                            print "<div class='portlet-header'>Graph Template:</strong> " .  'Availability Chart' . "</div>\n";
                            print "<div class='guideListing portlet-content'><center>\n";
                        } else {
                            ?>
                            <tr bgcolor='#6d88ad'>
                            <tr bgcolor='#a9b7cb'>
                                <td colspan='3' class='textHeaderDark'>
                                    <strong>Graph Template:</strong> <?php echo  'Availability Chart (external)'; ?>
                                </td>
                            </tr>
                            <tr align='center' style='background-color: #f9f9f9;'>
                            <td align='center'>
                            <?php
                        }
                        print "	<table width='1' cellpadding='0'>\n";
                        print "		<tr>\n";
                        print "			<td valign='top' style='padding: 3px;' class='noprint'>\n";
                        print "				<img src='" . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChart.php?tree_id=' . $tree_id . '&leaf_id=' . $host_leaf_id . '&startTime=' . $startTime . '&endTime=' . $endTime . '&mode=time&data=p' . "' border='0'>\n";
                        print "			</td>\n";
                        print "			<td valign='top' style='padding: 3px;' class='noprint'>";
                        if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
                            print " 	    <input onClick=\"setData('avc_" . $tree_id . "_" . $host_leaf_id . "');\" type=checkbox id='avc_" . $tree_id . "_" . $host_leaf_id . "' name='avc_" . $tree_id . "_" . $host_leaf_id . "' value='" . $tree_id . "_" . $host_leaf_id . "'><br>";
                            if ( $mode == 'preview' )  {
                                print "<script>setData('avc_" . $tree_id . "_" . $host_leaf_id . "');</script>";
                            }
                        }
                        print "         </td>";
                        print "		</tr>\n";
                        print "</table>\n";
                    }
                }

                // Windows Service Status
                if (isset ( $hit[ 1 ] )) {
                    $dir = dirname( __FILE__ );
                    include_once( __DIR__ . '/../modules/availability/polling_functions.php' );
                    // hostname set
                    $host_name = $hit[ 1 ];
                    $host_leaf_id = $hit[ 2 ];
                    $startTime = get_current_graph_start();
                    $endTime = get_current_graph_end();
                    $host_id = db_fetch_cell( "select host_id from graph_tree_items where id=$host_leaf_id" );
                    $host_sla = db_fetch_cell( "select nmid_host_sla from host where id=$host_id" );
                    $slaValue = $globalSLA;
                    if ( $host_sla > 0 ) {
                        $slaValue = $host_sla;
                    }

                    if (readPluginStatus( "storeLastPoll" )) {
                        $hostHasWinServices = FALSE;
                        // Check for Windows Services
                        if ( read_config_option( "extended_paths" ) == "on" ) {
                            $a_hostServiceStates = db_fetch_assoc( "
                                            SELECT
                                            `data_template_data`.local_data_id,
                                            `data_template_data`.name_cache
                                            FROM
                                            `data_template_data`,
                                            `data_local`,
                                            `data_template_rrd`
                                            WHERE
                                            `data_local`.id = `data_template_data`.local_data_id
                                            AND
                                            `data_template_rrd`.Local_data_id = `data_template_data`.local_data_id
                                            AND
                                            `data_local`.host_id = " . $host_id . "
                                            AND
                                               `data_template_rrd`.data_source_name LIKE '%service_state%'
                                        " );
                        } else {
                            $a_hostServiceStates = db_fetch_assoc( "
                                        SELECT
                                            `data_template_data`.local_data_id,
                                            `data_template_data`.name_cache
                                        FROM
                                            `data_template_data`,
                                            `data_local`
                                        WHERE
                                            `data_local`.id = `data_template_data`.local_data_id
                                        AND
                                            `data_local`.host_id = " . $host_id . "
                                        AND
                                           `data_template_data`.data_source_path LIKE '%service_state%'
                                    " );
                        }
                        if ( count( $a_hostServiceStates ) > 0 ) {
                            $hostHasWinServices = TRUE;
                        }
                        if (read_config_option( "nmid_avail_addWinServiceTable" )) {
                            if ($hostHasWinServices) {
                                foreach ($a_hostServiceStates as $a_hostServiceState) {
                                    $s_timeframes_sql = "
                                                        SELECT
                                                            sum(failed_polls) as failed_polls
                                                        FROM
                                                            plugin_nmidCreatePDF_AvailabilityFailedPolls_Table
                                                        WHERE
                                                            timeStamp > $startTime
                                                        AND
                                                            timeStamp < $endTime
                                                        AND
                                                            ldid = " . $a_hostServiceState[ 'local_data_id' ] . "
                                                    ";
                                    list ( $availability, $totalPolls, $hostFailedPolls ) = get_device_availability( $host_id, $startTime, $endTime );
                                    $hostSLATimeFrame = db_fetch_cell( 'select nmid_host_sla_timeframe from host where id=' . $host_id );
                                    $globalSLATimeFrame = readConfigOption( 'nmid_avail_globalSlaTimeFrame' );
                                    $slaTimeFrame = $globalSLATimeFrame;
                                    if ( $hostSLATimeFrame > 0 ) {
                                        $slaTimeFrame = $hostSLATimeFrame;
                                    }
                                    $totalPolls = CereusReporting_getGraphTotalPolls( $a_hostServiceState[ 'local_data_id' ], $startTime, $endTime, $slaTimeFrame );
                                    $failedPolls = CereusReporting_getFailedServicePolls( $host_id, $a_hostServiceState[ 'local_data_id' ], $startTime, $endTime );
                                    //$failedPolls = db_fetch_cell( $s_timeframes_sql );// + $hostFailedPolls;

                                    $availability = 100;
                                    if ( $totalPolls > 0 ) {
                                        $availability = ( 100 * ( $totalPolls - $failedPolls ) ) / $totalPolls;
                                    }

                                    if (readPluginStatus( 'nmidWeb2' )) {
                                        print "<div class='sidebarBox portlet' id='item-av01'>\n";
                                        print "<div class='portlet-header'>Graph Template:</strong>  " . $a_hostServiceState[ 'name_cache' ] . " SLA Table</div>\n";
                                        print "<div class='guideListing portlet-content'><center>\n";
                                    } else {
                                        ?>
                                        <tr bgcolor='#6d88ad'>
                                        <tr bgcolor='#a9b7cb'>
                                            <td colspan='3' class='textHeaderDark'>
                                                <strong>Graph
                                                    Template:</strong> <?php echo $a_hostServiceState[ 'name_cache' ]; ?>
                                                <?php echo  'SLA Table'; ?>
                                            </td>
                                        </tr>
                                        <tr align='center' style='background-color: #f9f9f9;'>
                                        <td align='center'>
                                        <?php
                                    }

                                    print "<table>\n";
                                    print "	<tr>\n";
                                    print " 	<th style='border-bottom: 2px solid #000000;'>" .  'Total Polls' . "</th>\n";
                                    print " 	<th style='border-bottom: 2px solid #000000;'>" .  'Failed Polls' . "</th>\n";
                                    print " 	<th style='border-bottom: 2px solid #000000;'>" .  'Availability' . "</th>\n";
                                    print " 	<th style='border-bottom: 2px solid #000000;'>" . $slaString . " SLA</th>\n";
                                    print "	</tr>\n";
                                    print "	<tr>\n";
                                    print " 	<td align=center>" . number_format( $totalPolls ) . "</td>\n";
                                    print " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $failedPolls ) . "</td>\n";
                                    $fontColor = '#007700';
                                    if ( $availability < $slaValue ) {
                                        $fontColor = '#aa0000';
                                    }
                                    print " 	<td style='border-left: 1px solid #000000;' align=center><font color=" . $fontColor . "><b>" . number_format( $availability, 2 ) . "%</b></font></td>\n";
                                    print " 	<td style='border-left: 1px solid #000000;' align=center>" . number_format( $slaValue, 2 ) . "%</td>\n";
                                    print "	</tr>\n";
                                    print "</table>\n";

                                    if ( readPluginStatus( 'nmidWeb2' ) ) {
                                        print "</center></div></div>";
                                    }
                                    else {
                                        print "</td></tr></tr>";
                                    }
                                } // end foreach host win service states
                            }
                        }

                        if (read_config_option( "nmid_avail_addWinServiceGraph" )) {
                            if ($hostHasWinServices) {
                                if (readPluginStatus( 'nmidWeb2' )) {
                                    print "<div class='sidebarBox portlet' id='item-av01'>\n";
                                    print "<div class='portlet-header'>Graph Template:</strong> " .  'Win Services Availability Table' . "</div>\n";
                                    print "<div class='guideListing portlet-content'><center>\n";
                                } else {
                                    ?>
                                    <tr bgcolor='#6d88ad'>
                                    <tr bgcolor='#a9b7cb'>
                                        <td colspan='3' class='textHeaderDark'>
                                            <strong>Graph Template:</strong> <?php echo $a_hostServiceState[ 'name_cache' ]; ?>
                                            <?php echo  'SLA Table'; ?>
                                        </td>
                                    </tr>
                                    <tr align='center' style='background-color: #f9f9f9;'>
                                    <td align='center'>
                                    <?php
                                }

                                print "	<table width='1' cellpadding='0'>\n";
                                print "		<tr>\n";
                                print "			<td valign='top' style='padding: 3px;' class='noprint'>\n";
                                print "				<img src='" . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_winserviceAvailabilityChart.php?spaceLeft=300&tree_id=' . $tree_id . '&leaf_id=' . $host_leaf_id . '&startTime=' . $startTime . '&endTime=' . $endTime . '&mode=time&data=p' . "' border='0'>\n";
                                print "			</td>\n";
                                print "			<td valign='top' style='padding: 3px;' class='noprint'>";
                                if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
                                    print " 	    <input onClick=\"setData('avwsc_" . $tree_id . "_" . $host_leaf_id . "');\" type=checkbox id='avwsc_" . $tree_id . "_" . $host_leaf_id . "' name='avwsc_" . $tree_id . "_" . $host_leaf_id . "' value='" . $tree_id . "_" . $host_leaf_id . "'><br>";
                                    if ( $mode == 'preview' )  {
                                        print "<script>setData('avwsc_" . $tree_id . "_" . $host_leaf_id . "');</script>";
                                    }
                                }
                                print "         </td>";
                                print "		</tr>\n";
                                print "</table>\n";
                            }
                        }
                    }
                } else {
                    if (CereusReporting_getHostCountFromLeaf( $host_leaf_id, $tree_id ) > 0) {
                        // no hostname set
                        if (read_config_option( "nmid_avail_addWinServiceGraph" )) {
                            if (readPluginStatus( 'nmidWeb2' )) {
                                print "<div class='sidebarBox portlet' id='item-av01'>\n";
                                print "<div class='portlet-header'>Graph Template:</strong> Availability Chart</div>\n";
                                print "<div class='guideListing portlet-content'><center>\n";
                            } else {
                                ?>
                                <tr bgcolor='#6d88ad'>
                                <tr bgcolor='#a9b7cb'>
                                    <td colspan='3' class='textHeaderDark'>
                                        <strong>Graph Template:</strong> <?php echo  'Win Services Availability Chart (external)'; ?>
                                    </td>
                                </tr>
                                <tr align='center' style='background-color: #f9f9f9;'>
                                    <td align='center'>
                                <?php
                            }
                            print "	<table width='1' cellpadding='0'>\n";
                            print "		<tr>\n";
                            print "			<td valign='top' style='padding: 3px;' class='noprint'>\n";
                            print "				<img src='" . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_winserviceAvailabilityChart.php?spaceLeft=300&tree_id=' . $tree_id . '&leaf_id=' . $host_leaf_id . '&startTime=' . $startTime . '&endTime=' . $endTime . '&mode=time&data=p' . "' border='0'>\n";
                            print "			</td>\n";
                            print "			<td valign='top' style='padding: 3px;' class='noprint'>";
                            if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
                                print " 	    <input onClick=\"setData('avwsc_" . $tree_id . "_" . $host_leaf_id . "');\" type=checkbox id='avwsc_" . $tree_id . "_" . $host_leaf_id . "' name='avwsc_" . $tree_id . "_" . $host_leaf_id . "' value='" . $tree_id . "_" . $host_leaf_id . "'><br>";
                                if ( $mode == 'preview' )  {
                                    print "<script>setData('avwsc_" . $tree_id . "_" . $host_leaf_id . "');</script>";
                                }
                            }
                            print "         </td>";
                            print "		</tr>\n";
                            print "</table>\n";
                        }
                    }
                }
			}
		}
		return $param;
	}

	/**
	 * @return bool
	 */
	function CereusReporting_isNewCactiUI()
	{
		return TRUE;
	}

	function cr_get_graph_items( $a_item_array, $tree_id, $branch_id, $level = 0, $add_sub_branches = false ) {

	    if ( $branch_id < 0 ) { $branch_id = 0; }
        $a_tree_items = db_fetch_assoc('
			SELECT
				graph_tree.id AS tree_id,
				graph_tree.name AS name,
				graph_tree_items.title AS title,
				graph_tree_items.id AS branch_id,
				graph_tree_items.local_graph_id as local_graph_id,
				graph_tree_items.host_id as host_id,
				graph_tree_items.parent AS parent_id
			FROM
			  	graph_tree_items
			INNER JOIN
				graph_tree
			ON
			  	graph_tree.id = graph_tree_items.graph_tree_id
			WHERE
			  ( 
			    	graph_tree.enabled="on"
			    	AND
			    	graph_tree_items.id='.$branch_id.'
			    	AND
			    	graph_tree.id='.$tree_id.'
			      
			  )
			ORDER BY 
				tree_id, parent_id, position');

        foreach ( $a_tree_items as $a_tree_item ) {
            $a_tree_item['level'] = $level;
            if ( $a_tree_item[ 'host_id' ] > 0 ) {
                $a_item_array[] = $a_tree_item;
            }
            if ( $a_tree_item[ 'local_graph_id' ] > 0 ) {
                $a_item_array[] = $a_tree_item;
            }
        }

		// Check for any sub-branches
        if ( $add_sub_branches ) {
	        $a_sub_tree_items = db_fetch_assoc( '
            SELECT
                graph_tree.id AS tree_id,
                graph_tree.name AS name,
                graph_tree_items.title AS title,
                graph_tree_items.id AS branch_id,
                graph_tree_items.local_graph_id AS local_graph_id,
                graph_tree_items.host_id AS host_id,
                graph_tree_items.parent AS parent_id
            FROM
                graph_tree_items
            INNER JOIN
                graph_tree
            ON
                graph_tree.id = graph_tree_items.graph_tree_id
            WHERE
              ( 
                    graph_tree.enabled="on"
                    AND
                    graph_tree_items.parent=' . $branch_id . '
                    AND
			    	graph_tree.id=' . $tree_id . '
              )
            ORDER BY 
                tree_id, parent_id, position' );

	        foreach ( $a_sub_tree_items as $a_sub_tree_item ) {
                $a_item_array = cr_get_graph_items( $a_item_array, $tree_id, $a_sub_tree_item[ 'branch_id' ], $level + 1, $add_sub_branches );
	        }
        }
        return $a_item_array;
	}

	function CereusReporting_doLeafGraphs( $pdf, $leafid, $startTime, $endTime )
	{
		global $isSmokepingEnabled, $config, $tree_id, $cgiAddSubLeafs, $phpBinary,$debugModeOn;

		if ( ini_get('max_execution_time') < 120 ) {
			set_time_limit ( 120 );
		}
		// Get DB Instance
		$db = DBCxn::get();

		if ( $pdf->nmidGetPdfType() > 0 ) {
			$pdf->Bookmark( $pdf->nmidGetHeaderTitle(), 0 );
		}

		// Get all items for this tree
		$a_item_array = array();
		$a_item_array  = cr_get_graph_items( $a_item_array, $tree_id, $leafid, 0,  $cgiAddSubLeafs );

		if ( $cgiAddSubLeafs ) {
			CereusReporting_logger( 'Creating Report with Sub Trees ( Tree => [' . $tree_id . '] Branch_Id=> [' . $leafid.']', 'debug', 'system' );
		}
		else {
			CereusReporting_logger( 'Creating Report without Sub Trees ( Tree => [' . $tree_id . '] Branch_Id => [' . $leafid.']', 'debug', 'system' );
		}

		if ( sizeof( $a_item_array ) == 0 ) {
			$pdf->WriteHTML( '<h3>Error: No Items can be found !<h3>', FALSE, FALSE );
			CereusReporting_logger( 'No Items available !', "fatal", "DoLeafGraphs" );
			return;
		}

		$wf_dir = sys_get_temp_dir() . '/' . time() . '-' . $leafid . '-' . $startTime . '-' . $endTime;
		$pdf->nmidSetWorkerFile( $wf_dir . '/workerfile' );
		mkdir( $wf_dir );
		$pdf->nmidSetWorkerDir( $wf_dir );

		if ( $pdf->nmidGetPrintHeader() ) {
			printControlText( $pdf, 0, '<sethtmlpageheader name="myheader" value="1" show-this-page="1" />', 0,'enable_header' );
			CereusReporting_logger( 'Printing Header', "info", "DoLeafGraphs" );
		}
		if ( $pdf->nmidGetPrintFooter() ) {
			printControlText( $pdf, 0, '<sethtmlpagefooter name="myfooter" value="1" show-this-page="1" />', 0,'enable_footer' );
			CereusReporting_logger( 'Printing Footer', "info", "DoLeafGraphs" );
		}


        list( $micro, $seconds ) = explode( " ", microtime() );
        $start = $seconds + $micro;
        $last_host = -1;
        foreach ( $a_item_array as $row  ) {
            $tier                = $row['level'];

            if ( $row[ 'host_id' ] > 0 ) {
                // Check if the host_id changed
                if ( $row[ 'host_id' ] != $last_host ) {
                    // Check if this isn't the first host entry
                    if ( $last_host > 0 ) {
                        $control_text = '<tcpdf method="AddPage" />';

	                    // Add a pagebreak before the new host begins
	                    printControlText( $pdf, '', $control_text, $tier, 'pagebreak' );
                    }
                    $last_host = $row[ 'host_id' ];
                }
                $local_stmt = $db->prepare( "select concat(concat( concat(description,' ( '),hostname),' )') from host where id = :hostid" );
                $local_stmt->bindValue( ':hostid', $row[ 'host_id' ] );
                $local_stmt->execute();
                $hostname = $local_stmt->fetchColumn();
                $local_stmt->closeCursor();
                CereusReporting_logger( 'Host found [' . $hostname . '] at Tier [' . $tier . ']', 'debug', 'DoLeafGraphs' );

                $control_text = '';

                $params = $pdf->serializeTCPDFtagParameters(array($hostname, $tier, -1, '', '', array(0,0,0)));
                $control_text  = '<tcpdf method="Bookmark" params="'.$params.'" />';

                if ( readConfigOption("nmid_pdf_ondemand_show_header") == "on" ) {
                    $html    = '<div class="nmidChapterText"><table width="100%"><tr><td class="nmidChapterText">' . $hostname . '</td></tr></table></div>'.$control_text.'<br />';
                } else {
                    $html    = $control_text;
                }
                $command    = '';
                $image_file = '';
                $lgid       = '';
                $content = $pdf->nmidGetWorkerFileContent() . 'ctrltext' . '@' . $command . '@' . $html . '@1@' . $image_file . '@' . $lgid . "\n";
                $pdf->nmidSetWorkerFileContent( $content );

                $local_stmt = $db->prepare( 'SELECT
                    graph_templates_graph.id as id,
                    graph_templates_graph.local_graph_id as lgid,
                    graph_templates_graph.height as height,
                    graph_templates_graph.width as width,
                    graph_templates_graph.title_cache as title_cache,
                    graph_templates.name as name,
                    graph_local.host_id as host_id
                    FROM (graph_local,graph_templates_graph)
                    LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
                    WHERE graph_local.id=graph_templates_graph.local_graph_id
                    AND host_id = :hostId' );
                $local_stmt->bindValue( ':hostId', $row[ 'host_id' ] );
                $local_stmt->setFetchMode( PDO::FETCH_ASSOC );
                $local_stmt->execute();

                while ( $subRow = $local_stmt->fetch() ) {

                    $image_file = $pdf->nmidGetWorkerDir() . "/" . $subRow[ 'lgid' ] . '.png';
                    $command = ''; // $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " 0 " . $startTime . " " . $endTime . " " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
                    $title   = $subRow[ "title_cache" ];
                    $gtier       = $tier + 1;
                    $lgid    = $subRow[ 'lgid' ];

                    // Download file and store to tmp dir:
                    CereusReporting_logger( 'Retrieving Graph ['. $title .']['.$lgid.'] from host [' . $hostname . '] at Tier [' . $gtier . ']', 'debug', 'DoLeafGraphs' );
		    $reportId = $reportId ?? '-1';
                    $customGraphWidth  = getDBValue( 'customGraphWidth', 'SELECT customGraphWidth FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    $customGraphHeight = getDBValue( 'customGraphHeight', 'SELECT customGraphHeight FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    if ( !isNumber( $customGraphWidth ) || ( strlen( $customGraphWidth ) < 1 ) ) {
                        $customGraphWidth = $subRow[ 'width' ];
                    }
                    if ( !isNumber( $customGraphHeight ) || ( strlen( $customGraphHeight ) < 1 ) ) {
                        $customGraphHeight = $subRow[ 'height' ];
                    }

                    $secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
                    curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$customGraphHeight.'&width='.$customGraphWidth,$image_file);

                    $content     = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );
                }
                $local_stmt->closeCursor();

                if ( $isSmokepingEnabled ) {

                    $local_stmt = $db->prepare( 'select nwmgmt_settings from host where id = :hostId' );
                    $local_stmt->bindValue( ':hostId', $row[ 'host_id' ] );
                    $local_stmt->execute();
                    $isHostEnabled = $local_stmt->fetchColumn();
                    $local_stmt->closeCursor();

                    if ( preg_match( "/^s1/", $isHostEnabled ) == 1 ) {
                        $local_stmt = $db->prepare( 'select description from host where id = :hostId' );
                        $local_stmt->bindValue( ':hostId', $row[ 'host_id' ] );
                        $local_stmt->execute();
                        $title = $local_stmt->fetchColumn() . ' - Smokeping Graph';
                        $local_stmt->closeCursor();
                        CereusReporting_logger( 'Smokeping found [' . $title. ']', 'debug', 'system' );

                        $image_file = $pdf->nmidGetWorkerDir() . "/" . $row[ 'host_id' ] . '-' . $startTime . '-' . $endTime . '.name';
                        $command    = $phpBinary . " cereusReporting_getSmokePingImage.php " . $row[ 'host_id' ] . " $startTime $endTime > " . $image_file;
                        $gtier   = $tier + 1;
                        $lgid       = '-1';
                        $content = $pdf->nmidGetWorkerFileContent() . 'smokeping' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                        $pdf->nmidSetWorkerFileContent( $content );

                        // Download file and store to tmp dir:
                        $secure_key = sha1( $row[ 'host_id' ] . $startTime . $endTime . SECURE_URL_KEY);
                        $graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/cereusReporting_getSmokePingImage.php?key='.$secure_key.'&hostId='. $row[ 'host_id' ].'&start='.$startTime.'&end='.$endTime;
                        curl_download($graph_image_url,$image_file);
                    }
                }

                // Availability Graph
                $slaTime_id = -1;
                if ( readConfigOption( 'nmid_avail_addGraph' )  && ( $cgiAddSubLeafs == false ) )  {
                    $image_file = $pdf->nmidGetWorkerDir() . '/' . $leafid . '-' . $tree_id . '-' . $startTime . '-' . $endTime . '_availabilityCombined.png';
                    $command    = $phpBinary . " cereusReporting_serverAvailabilityChartCLI.php $leafid $tree_id $slaTime_id $startTime $endTime > " . $image_file;
                    $title      = 'Availability Report';
                    $local_stmt = $db->prepare( 'SELECT `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`
                        FROM
                          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
                        WHERE
                          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`= :slaTimeId' );
                    $local_stmt->bindValue( ':slaTimeId', $slaTime_id );
                    $local_stmt->execute();
                    $sla_short_descr = $local_stmt->fetchColumn();
                    $local_stmt->closeCursor();

                    $title   = 'Availability Report ( ' . $sla_short_descr . ' )';
                    CereusReporting_logger( 'Availability Report found [' . $title. ']', 'debug', 'system' );
                    $title   = '';
                    $lgid    = 0;
                    $gtier   = $tier + 1;
                    $content = $pdf->nmidGetWorkerFileContent() . 'availability_combined' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );

                    // Download file and store to tmp dir:
                    $secure_key = sha1( $leafid . $tree_id . $slaTime_id . $startTime . $endTime . SECURE_URL_KEY);
                    $graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChartCLI.php?key='.$secure_key.'&leafId='. $leafid.'&treeId='. $tree_id.'&slaTimeId='. $slaTime_id.'&start='.$startTime.'&end='.$endTime;
                    curl_download($graph_image_url,$image_file);

                }
                if ( $cgiAddSubLeafs == false ) {
                    $gtier = $tier + 1;
                    if ( $leafid > 0 ) {
                        printAvailabilityCombinedTable( $pdf, $tree_id, $leafid, $startTime, $endTime, $gtier, $slaTime_id );
                        if ( readConfigOption( 'nmid_avail_addDetailedTable' ) ) {
                            printDetailedAvailabilityCombinedTable( $pdf, '-1', $tree_id, $leafid, $startTime, $endTime, $gtier, $slaTime_id );
                        }
                    }
                    else {
                        printAvailabilityCombinedTreeTable( $pdf, '-1', $tree_id, $startTime, $endTime, $gtier, $slaTime_id );
                        if ( readConfigOption( 'nmid_avail_addDetailedTable' ) ) {
                            printDetailedAvailabilityCombinedTreeTable( $pdf, '-1', $tree_id, $leafid, $startTime, $endTime, $gtier, $slaTime_id );
                        }
                    }
                }
            }
            else if ( $row[ 'local_graph_id' ] > 0 ) {
                CereusReporting_logger( 'Local Graph Id found [' . $row[ 'local_graph_id' ]. ']', 'debug', 'DoLeafGraphs' );
                $local_stmt = $db->prepare( 'SELECT
                    graph_templates_graph.id as id,
                    graph_templates_graph.local_graph_id as lgid,
                    graph_templates_graph.height as height,
                    graph_templates_graph.width as width,
                    graph_templates_graph.title_cache as title_cache,
                    graph_templates.name as name,
                    graph_local.host_id as host_id
                    FROM (graph_local,graph_templates_graph)
                    LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
                    WHERE graph_local.id=graph_templates_graph.local_graph_id
                    AND graph_templates_graph.local_graph_id  = :lgid' );
                $local_stmt->bindValue( ':lgid', $row[ 'local_graph_id' ] );
                $local_stmt->setFetchMode( PDO::FETCH_ASSOC );
                $local_stmt->execute();

                while ( $subRow = $local_stmt->fetch() ) {
                    $image_file = $pdf->nmidGetWorkerDir() . "/lgid_" . $subRow[ 'lgid' ] . '.png';
                    $command = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " " . $row[ 'rra_id' ] . " $startTime $endTime " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
                    $title   = $subRow[ "title_cache" ];

                    CereusReporting_logger( 'Adding LGID [' . $title . ']['.$subRow['lgid'].']', 'debug', 'DoLeafGraphs' );
                    $customGraphWidth  = getDBValue( 'customGraphWidth', 'SELECT customGraphWidth FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    $customGraphHeight = getDBValue( 'customGraphHeight', 'SELECT customGraphHeight FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    if ( !isNumber( $customGraphWidth ) || ( strlen( $customGraphWidth ) < 1 ) ) {
                        $customGraphWidth = $subRow[ 'width' ];
                    }
                    if ( !isNumber( $customGraphHeight ) || ( strlen( $customGraphHeight ) < 1 ) ) {
                        $customGraphHeight = $subRow[ 'height' ];
                    }

                    // Download file and store to tmp dir:
                    $secure_key = sha1( $subRow['lgid'] . '0' . $startTime . $endTime . SECURE_URL_KEY);
                    curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$subRow['lgid'].'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$customGraphHeight.'&width='.$customGraphWidth,$image_file);

                    $gtier   = $tier + 1;
                    $lgid    = $subRow[ 'lgid' ];
                    $content = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );
                }
                $local_stmt->closeCursor();

            }
            else if ( strlen($row[ 'title' ]) > 0 ) {
                CereusReporting_logger( 'Sub Leaf header found [' . $row[ 'title' ] . '] at Tier [' . $tier . ']', 'debug', 'DoLeafGraphs' );

                $control_text = '';
                $params = $pdf->serializeTCPDFtagParameters(array($row[ 'title' ], $tier, -1, '', '', array(0,0,0)));
                $control_text  = '<tcpdf method="Bookmark" params="'.$params.'" />';

                $html = '<div class="nmidChapterText"><table width="100%"><tr><td class="nmidChapterText">' . $row[ 'title' ] . '</td></tr></table></div><bookmark content="' . $row[ 'title' ] . '" level="' . $tier . '" /><br />';
                $command    = '';
                $image_file = '';
                $lgid       = '';
                $content    = $pdf->nmidGetWorkerFileContent() . 'chapter' . '@' . $command . '@' . $html . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
                $pdf->nmidSetWorkerFileContent( $content );

            }
        }
        list( $micro, $seconds ) = explode( " ", microtime() );
        $end = $seconds + $micro;
        $cacti_stats = sprintf( "Time:%01.4f ", round( $end - $start, 4 ) );
        CereusReporting_logger( 'DoLeafGraphs Creation time: [' . $cacti_stats. ']', 'debug', 'DoLeafGraphs' );


        // Download all files
        CereusReporting_logger('Downloading all report graphs', 'debug', 'ReportEngine');
        multi_curl_download();
        CereusReporting_logger('Finished Downloading all report graphs', 'debug', 'ReportEngine');


        $fh = fopen( $pdf->nmidGetWorkerFile(), "w+" );
		fwrite( $fh, $pdf->nmidGetWorkerFileContent() );
		fclose( $fh );

		$fh = fopen( $pdf->nmidGetWorkerFile(), "r" );
		while ( $line = fgets( $fh ) ) {
			$a_data     = preg_split( "/@/", $line );
			$type       = $a_data[ 0 ];
			$cmd        = $a_data[ 1 ];
			$title      = $a_data[ 2 ];
			$tier       = $a_data[ 3 ];
			$image_file = $a_data[ 4 ];
			$lgid       = $a_data[ 5 ];
			$lgid       = preg_replace( "/\n/", "", $lgid );
			if ( $type == 'graph' ) {
				if ( file_exists( $image_file ) ) {
					if ( is_dir( $image_file ) ) {

					}
					else {
						addImage( $pdf, $title, $image_file, $lgid, $tier );
						CereusReporting_logger( 'Adding image [' . $image_file. ']', 'debug', 'system' );
					}
				}
			}
            elseif ( $type == 'availability' ) {
				if ( file_exists( $image_file ) ) {
					if ( is_dir( $image_file ) ) {

					}
					else {
						addImage( $pdf, $title, $image_file, $lgid, $tier );
						CereusReporting_logger( 'Adding image [' . $image_file. ']', 'debug', 'system' );
					}
				}
			}
            elseif ( $type == 'availability_combined' ) {
                if ( file_exists( $image_file ) ) {
                    addImage( $pdf, $title, $image_file, $lgid, $tier );
                    CereusReporting_logger( 'Adding image [' . $image_file. ']', 'debug', 'system' );
                }
			}
            elseif ( $type == 'text' ) {
				if ( strlen($title) > 0 ) {
					CereusReporting_logger( 'Adding text', 'debug', 'system' );
					printTextToReport( $pdf, $title );
				}
			}
            elseif ( $type == 'ctrltext' ) {
				if ( strlen( $title ) > 0 ) {
					CereusReporting_logger( 'Adding ctrltext', 'debug', 'system' );
					printControlTextToReport( $pdf, $title );
				}
			}
            elseif ( $type == 'title' ) {
				if ( strlen($title) > 0 ) {
					CereusReporting_logger( 'Adding title', 'debug', 'system' );
					printTitleToReport( $pdf, $title );
				}
			}
            elseif ( $type == 'chapter' ) {
				if ( strlen($title) > 0 ) {
					CereusReporting_logger( 'Adding chapter', 'debug', 'system' );
					printChapterToReport( $pdf, $title, $tier );
				}
			}
            elseif ( $type == 'pagebreak' ) {
				CereusReporting_logger( 'Adding pagebreak', 'debug', 'system' );
				printControlTextToReport( $pdf, $title );
			}
            elseif ( $type == 'enable_header' ) {
				CereusReporting_logger( 'Enabling Header', 'debug', 'system' );
				printControlTextToReport( $pdf, $title );
			}
            elseif ( $type == 'enable_footer' ) {
				CereusReporting_logger( 'Enabling Footer', 'debug', 'system' );
				printControlTextToReport( $pdf, $title );
			}
            elseif ( $type == 'disable_header' ) {
				CereusReporting_logger( 'Disable Header', 'debug', 'system' );
				printControlTextToReport( $pdf, $title );
			}
            elseif ( $type == 'disable_footer' ) {
				CereusReporting_logger( 'Disable Footer', 'debug', 'system' );
				printControlTextToReport( $pdf, $title );
			}
            elseif ( $type == 'smokeping' ) {
				$file = $image_file;
				if ( file_exists( $file ) ) {
					if ( filesize( $file ) > 0 ) {
						$f         = fopen( $file, 'r' );
						$imageFile = fread( $f, filesize( $file ) );
						fclose( $f );
						if ( file_exists( $imageFile ) ) {
							if ( is_dir( $imageFile ) ) {

							}
							else {
								addImage( $pdf, $title, $imageFile, $lgid, $tier );
								CereusReporting_logger( 'Adding smokeping image [' . $image_file. ']', 'debug', 'system' );
							}
						}
					}
				}
			}
		}
		fclose( $fh );

		CereusReporting_cleanup_files($pdf, $debugModeOn);
		/*
		$fh = fopen( $pdf->nmidGetWorkerFile(), "r" );
		while ( $line = fgets( $fh ) ) {
			$a_data     = preg_split( "/@/", $line );
			$image_file = $a_data[ 4 ];
			if ( file_exists( $image_file ) ) {
				CereusReporting_logger( 'Deleting image [' . $image_file . ']', 'debug', 'system' );
				unlink( $image_file );
			}
		}
		fclose( $fh );

		*/

	}

	function CereusReporting_addLeafToReport( $leafid, $reportId )
	{
		global $tree_id, $cgiAddSubLeafs;
		$isSmokepingEnabled = readPluginStatus( 'nmidSmokeping' ) || FALSE;

		// Get all items for this tree
		$a_item_array = array();
		$a_item_array  = cr_get_graph_items( $a_item_array, $tree_id, $leafid, 0,  $cgiAddSubLeafs );

		CereusReporting_logger( 'Adding Leaf ( Leaf ID => [' . $leafid . '] Tree Id => [' . $tree_id . ']', 'debug', 'system' );

		foreach( $a_item_array as $row ) {
			if ( $row[ 'host_id' ] > 0 ) {
				CereusReporting_logger( 'Adding Host ( Host ID => [' . $row[ 'host_id' ] . ']', 'debug', 'system' );
				$sql       = "
				SELECT
				graph_templates_graph.id AS id,
				graph_templates_graph.local_graph_id AS lgid,
				graph_templates_graph.height AS height,
				graph_templates_graph.width AS width, 
				graph_templates_graph.title_cache AS title_cache,
				graph_templates.name AS name,
				graph_local.host_id AS host_id
				FROM (graph_local,graph_templates_graph)
				LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
				WHERE graph_local.id=graph_templates_graph.local_graph_id
				AND host_id = ?";
				$subResult = cr_db_fetch_assoc_prepared($sql,array($row[ 'host_id' ]));
				foreach ( $subResult as $subRow ) {
					addGraphToReport( $subRow[ 'lgid' ], $reportId );
				}
				if ( $isSmokepingEnabled ) {
					$current_nwmgmt_settings = db_fetch_cell( "SELECT nwmgmt_settings FROM host WHERE id=" . $row[ 'host_id' ] );
					if ( preg_match( "/^s0/", $current_nwmgmt_settings ) == 0 ) // Smokeping is set on this host
					{
						addGraphToReport( "sp_" . $row[ 'host_id' ], $reportId );
					}
				}
				/* We need to add the Availability Report, too. We simply assume all
				hosts have a availability chart ( which is normally true ) */
				addGraphToReport( "av_" . $row[ 'host_id' ], $reportId );
			}
			else if ( $row[ 'local_graph_id' ] > 0 ) {
				CereusReporting_logger( 'Adding Host ( Local Graph ID => [' . $row[ 'local_graph_id' ] . ']', 'debug', 'system' );
				$sql       = "
				SELECT
				graph_templates_graph.id AS id,
				graph_templates_graph.local_graph_id AS lgid,
				graph_templates_graph.height AS height,
				graph_templates_graph.width AS width,
				graph_templates_graph.title_cache AS title_cache,
				graph_templates.name AS name,
				graph_local.host_id AS host_id
				FROM (graph_local,graph_templates_graph)
				LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
				WHERE graph_local.id=graph_templates_graph.local_graph_id
				AND graph_templates_graph.local_graph_id  = ?";
				$subResult = cr_db_fetch_assoc_prepared($sql,array($row[ 'local_graph_id' ]));
				foreach ( $subResult as $subRow ) {
					addGraphToReport( $subRow[ 'lgid' ], $reportId );
				}
			}
		}
	}

	function CereusReporting_printTreeItemGraph( $pdf, $reportId, $data, $startTime, $endTime, $global_tier ) {
		global $isSmokepingEnabled, $isBoostEnabled, $isBoostCacheEnabled,
		       $boost_png_cache_directory, $orderKey, $graphPerPage, $config,
		       $phpBinary,$pdfType;


		// Get DB Instance
		$db = DBCxn::get();

		$sqlArray = preg_split( "/;/", $data );
		$tree_id  = $sqlArray[ 0 ];
		$leaf_id  = $sqlArray[ 1 ];

		// Get all items for this tree
		$a_item_array = array();
		$a_item_array  = cr_get_graph_items( $a_item_array, $tree_id, $leaf_id, 0,  1 );

		CereusReporting_logger( 'Creating Report with Sub Trees ( Tree => [' . $tree_id. '] Leaf Id => [' . $leaf_id .']', 'debug', 'system' );

		if ( sizeof($a_item_array) == 0  ) {
			$pdf->WriteHTML( '<h3>Error: No Items can be found !<h3>', FALSE, FALSE );
			CereusReporting_logger( 'No Items available !', "fatal", "DoLeafGraphs" );
			return;
		}

        list( $micro, $seconds ) = explode( " ", microtime() );
        $start = $seconds + $micro;
		$tier                = $global_tier;
		$last_host = -1;

		foreach ( $a_item_array as $row) {
		    $html = '';
            // Add Hosts
            if ( $row[ 'host_id' ] > 0 ) {
	            // Check if the host_id changed
	            if ( $row[ 'host_id' ] != $last_host ) {
		            // Check if this isn't the first host entry
		            if ( $last_host > 0 ) {
                        $control_text = '<tcpdf method="AddPage" />';
		            }
		            $last_host = $row[ 'host_id' ];
	            }
	            $local_stmt = $db->prepare( "select concat(concat( concat(description,' ( '),hostname),' ) ') from host where id = :hostid" );
	            $local_stmt->bindValue( ':hostid', $row[ 'host_id' ] );
	            $local_stmt->execute();
	            $hostname = $local_stmt->fetchColumn();
	            $local_stmt->closeCursor();
	            CereusReporting_logger( 'Host found [' . $hostname . '] at Tier [' . $tier . ']', 'debug', 'system' );

	            $control_text = '';
	            if ( $pdfType == MPDF_ENGINE ) {
		            $control_text = '<bookmark content="' . $hostname . '" level="' . $tier . '" />';
	            }
                elseif ( $pdfType == TCPDF_ENGINE ) {
		            $params       = $pdf->serializeTCPDFtagParameters( array( $hostname, $tier, -1, '', '',
			                                                               array( 0, 0, 0 ) ) );
		            $control_text = '<tcpdf method="Bookmark" params="' . $params . '" />';
                }
	            $print_subtree_header = FALSE;
	            if ( $print_subtree_header ) {
                    $html       = '<div class="nmidTitleText"><table width="100%"><tr><td class="nmidTitleText">' . $hostname . '</td></tr></table></div>' . $control_text . '<br />';
                    $command    = '';
                    $image_file = '';
                    $lgid       = '';
                    $content    = $pdf->nmidGetWorkerFileContent() . 'ctrltext' . '@' . $command . '@' . $html . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );
                }
                $local_stmt = $db->prepare( 'SELECT
                    graph_templates_graph.id as id,
                    graph_templates_graph.local_graph_id as lgid,
                    graph_templates_graph.height as height,
                    graph_templates_graph.width as width,
                    graph_templates_graph.title_cache as title_cache,
                    graph_templates.name as name,
                    graph_local.host_id as host_id
                    FROM (graph_local,graph_templates_graph)
                    LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
                    WHERE graph_local.id=graph_templates_graph.local_graph_id
                    AND host_id = :hostId' );
                $local_stmt->bindValue( ':hostId', $row[ 'host_id' ] );
                $local_stmt->setFetchMode( PDO::FETCH_ASSOC );
                $local_stmt->execute();

                while ( $subRow = $local_stmt->fetch() ) {

                    $image_file = $pdf->nmidGetWorkerDir() . "/" . $subRow[ 'lgid' ] . '.png';
                    $command = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " 0 " . $startTime . " " . $endTime . " " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
                    $title   = $subRow[ "title_cache" ];
                    $gtier       = $tier + 1;
                    $lgid    = $subRow[ 'lgid' ];

                    $customGraphWidth  = getDBValue( 'customGraphWidth', 'SELECT customGraphWidth FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    $customGraphHeight = getDBValue( 'customGraphHeight', 'SELECT customGraphHeight FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    if ( !isNumber( $customGraphWidth ) || ( strlen( $customGraphWidth ) < 1 ) ) {
                        $customGraphWidth = $subRow[ 'width' ];
                    }
                    if ( !isNumber( $customGraphHeight ) || ( strlen( $customGraphHeight ) < 1 ) ) {
                        $customGraphHeight = $subRow[ 'height' ];
                    }

                    CereusReporting_logger( 'Adding graph ['.$subRow[ 'lgid' ].'] for [' . $hostname . '] at Tier [' . $gtier . ']', 'debug', 'system' );

                    // Download file and store to tmp dir:
                    $secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
                    curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$customGraphHeight.'&width='.$customGraphWidth,$image_file);

                    $content     = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );
                }
                $local_stmt->closeCursor();

                if ( $isSmokepingEnabled ) {

                    $local_stmt = $db->prepare( 'select nwmgmt_settings from host where id = :hostId' );
                    $local_stmt->bindValue( ':hostId', $row[ 'host_id' ] );
                    $local_stmt->execute();
                    $isHostEnabled = $local_stmt->fetchColumn();
                    $local_stmt->closeCursor();

                    if ( preg_match( "/^s1/", $isHostEnabled ) == 1 ) {
                        $local_stmt = $db->prepare( 'select description from host where id = :hostId' );
                        $local_stmt->bindValue( ':hostId', $row[ 'host_id' ] );
                        $local_stmt->execute();
                        $title = $local_stmt->fetchColumn() . ' - Smokeping Graph';
                        $local_stmt->closeCursor();
                        CereusReporting_logger( 'Smokeping found [' . $title. ']', 'debug', 'system' );

                        $image_file = $pdf->nmidGetWorkerDir() . "/" . $row[ 'host_id' ] . '-' . $startTime . '-' . $endTime . '.name';
                        $command    = $phpBinary . " cereusReporting_getSmokePingImage.php " . $row[ 'host_id' ] . " $startTime $endTime > " . $image_file;
                        $gtier   = $tier + 1;
                        $lgid       = '-1';
                        $content = $pdf->nmidGetWorkerFileContent() . 'smokeping' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                        $pdf->nmidSetWorkerFileContent( $content );

                        // Download file and store to tmp dir:
                        $secure_key = sha1( $row[ 'host_id' ] . $startTime . $endTime . SECURE_URL_KEY);
                        $graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/cereusReporting_getSmokePingImage.php?key='.$secure_key.'&hostId='. $row[ 'host_id' ].'&start='.$startTime.'&end='.$endTime;
                        curl_download($graph_image_url,$image_file);
                    }
                }
            }
            // Add graphs
            else if ( $row[ 'local_graph_id' ] > 0 ) {
                CereusReporting_logger( 'Local Graph Id found [' . $row[ 'local_graph_id' ]. ']', 'debug', 'system' );
                $local_stmt = $db->prepare( 'SELECT
                    graph_templates_graph.id as id,
                    graph_templates_graph.local_graph_id as lgid,
                    graph_templates_graph.height as height,
                    graph_templates_graph.width as width,
                    graph_templates_graph.title_cache as title_cache,
                    graph_templates.name as name,
                    graph_local.host_id as host_id
                    FROM (graph_local,graph_templates_graph)
                    LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
                    WHERE graph_local.id=graph_templates_graph.local_graph_id
                    AND graph_templates_graph.local_graph_id  = :lgid' );
                $local_stmt->bindValue( ':lgid', $row[ 'local_graph_id' ] );
                $local_stmt->setFetchMode( PDO::FETCH_ASSOC );
                $local_stmt->execute();

                while ( $subRow = $local_stmt->fetch() ) {
                    $image_file = $pdf->nmidGetWorkerDir() . "/" . $subRow[ 'lgid' ] . '.png';
                    if ( file_exists( 'parallelGraphRetriever.exe' ) ) {
                        $image_file = $pdf->nmidGetWorkerDir() . "\\" . $subRow[ 'lgid' ] . '.png';
                    }

                    $customGraphWidth  = getDBValue( 'customGraphWidth', 'SELECT customGraphWidth FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    $customGraphHeight = getDBValue( 'customGraphHeight', 'SELECT customGraphHeight FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                    if ( !isNumber( $customGraphWidth ) || ( strlen( $customGraphWidth ) < 1 ) ) {
                        $customGraphWidth = $subRow[ 'width' ];
                    }
                    if ( !isNumber( $customGraphHeight ) || ( strlen( $customGraphHeight ) < 1 ) ) {
                        $customGraphHeight = $subRow[ 'height' ];
                    }

                    $command = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " 0 $startTime $endTime " . $customGraphHeight . " " . $customGraphWidth . " > " . $image_file;
	                $gtier   = $tier + 1;
	                $lgid    = $subRow[ 'lgid' ];
	                $title   = $subRow[ "title_cache" ];
                    CereusReporting_logger( 'Adding LGID [' . $lgid . ']', 'debug', 'system' );

                    // Download file and store to tmp dir:
                    $secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
                    curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$customGraphHeight.'&width='.$customGraphWidth,$image_file);

                    $content = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );
                }
                $local_stmt->closeCursor();

            }
            // Sub-Tree
            else if ( strlen($row[ 'title' ]) > 0 ) {
                CereusReporting_logger( 'Sub Leaf header found [' . $row[ 'title' ] . '] at Tier [' . $tier . ']', 'debug', 'system' );

                $control_text = '';
                if ( $pdfType == MPDF_ENGINE ) {
                    $control_text = '<bookmark content="' . $row[ 'title' ] . '" level="' . $tier . '" />';
                } elseif ( $pdfType == TCPDF_ENGINE ) {
                    $params = $pdf->serializeTCPDFtagParameters(array($row[ 'title' ], $tier, -1, '', '', array(0,0,0)));
                    $control_text  = '<tcpdf method="Bookmark" params="'.$params.'" />';
                }
	            $print_subtree_header = FALSE;
	            if ( $print_subtree_header ) {
		            $html       = '<div class="nmidChapterText"><table width="100%"><tr><td class="nmidChapterText">' . $row[ 'title' ] . '</td></tr></table></div>' . $control_text . '<br />';
		            $command    = '';
		            $image_file = '';
		            $lgid       = '';
		            $content    = $pdf->nmidGetWorkerFileContent() . 'ctrltext' . '@' . $command . '@' . $html . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
		            $pdf->nmidSetWorkerFileContent( $content );
	            }
            }
        }
        list( $micro, $seconds ) = explode( " ", microtime() );
        $end = $seconds + $micro;
        $cacti_stats = sprintf( "Time:%01.4f ", round( $end - $start, 4 ) );
        CereusReporting_logger( 'Database retrieval time: [' . $cacti_stats. ']', 'debug', 'system' );
    }

	function CereusReporting_printRegExpItemGraph( $pdf, $reportId, $data, $startTime, $endTime, $global_tier )
	{
		global $isSmokepingEnabled, $isBoostEnabled, $isBoostCacheEnabled,
		       $boost_png_cache_directory, $orderKey, $graphPerPage, $config,
		       $phpBinary,$pdfType;


		// Get DB Instance
		$db = DBCxn::get();

		$dataJsonArray  = json_decode($data, true);
		$filter_mode    = $dataJsonArray['dataRegExpFilter'];
		$regexp_string  = $dataJsonArray['dataRegExp'];

		$sqlArray       = preg_split( "/;/", $dataJsonArray[ 'data' ] );
		$tree_id  = $sqlArray[ 0 ];
		$leaf_id  = $sqlArray[ 1 ];

		// Get all items for this tree
		$a_item_array = array();
		$a_item_array  = cr_get_graph_items( $a_item_array, $tree_id, $leaf_id, 0,  1 );

		CereusReporting_logger( 'Creating Regexp Report with Sub Trees ( Tree => [' . $tree_id. '] Leaf Id => [' . $leaf_id.'] Filter ['.$filter_mode.'] RegExp ['.$regexp_string.'] ) ', 'debug', 'system' );

		if ( sizeof($a_item_array) == 0  ) {
			$pdf->WriteHTML( '<h3>Error: No Items can be found !<h3>', FALSE, FALSE );
			CereusReporting_logger( 'No Items available !', "fatal", "RegExp" );
			return;
		}

		list( $micro, $seconds ) = explode( " ", microtime() );
		$start = $seconds + $micro;
		$tier                = $global_tier;
		foreach ( $a_item_array as $row) {
			//$tier                = $row['level'];

            // Add Hosts
            if ( $row[ 'host_id' ] > 0 ) {
                $local_stmt = $db->prepare( "select concat(concat( concat(description,' ( '),hostname),' ) ') from host where id = :hostid" );
                $local_stmt->bindValue( ':hostid', $row[ 'host_id' ] );
                $local_stmt->execute();
                $hostname = $local_stmt->fetchColumn();
                $local_stmt->closeCursor();
                if (
                    ( ($filter_mode == 'host') && ( preg_match($regexp_string, $hostname) ) )
                    ||
                    ($filter_mode == 'graph')
                ) {
                    CereusReporting_logger( 'Host found [' . $hostname . '] at Tier [' . $tier . ']', 'debug', 'system' );

                    $control_text = '';
                    if ( $pdfType == MPDF_ENGINE ) {
                        $control_text = '<bookmark content="' . $hostname . '" level="' . $tier . '" />';
                    }
                    elseif ( $pdfType == TCPDF_ENGINE ) {
                        $params       = $pdf->serializeTCPDFtagParameters( array( $hostname, $tier, -1, '', '',
                                                                               array( 0, 0, 0 ) ) );
                        $control_text = '<tcpdf method="Bookmark" params="' . $params . '" />';
                    }
                    $html       = '<div class="nmidTitleText"><table width="100%"><tr><td class="nmidTitleText">' . $hostname . '</td></tr></table></div>' . $control_text . '<br />';
                    $command    = '';
                    $image_file = '';
                    $lgid       = '';
                    $content    = $pdf->nmidGetWorkerFileContent() . 'text' . '@' . $command . '@' . $html . '@'.$tier.'@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );

                    $local_stmt = $db->prepare( 'SELECT
                        graph_templates_graph.id as id,
                        graph_templates_graph.local_graph_id as lgid,
                        graph_templates_graph.height as height,
                        graph_templates_graph.width as width,
                        graph_templates_graph.title_cache as title_cache,
                        graph_templates.name as name,
                        graph_local.host_id as host_id
                        FROM (graph_local,graph_templates_graph)
                        LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
                        WHERE graph_local.id=graph_templates_graph.local_graph_id
                        AND host_id = :hostId' );
                    $local_stmt->bindValue( ':hostId', $row[ 'host_id' ] );
                    $local_stmt->setFetchMode( PDO::FETCH_ASSOC );
                    $local_stmt->execute();

                    while ( $subRow = $local_stmt->fetch() ) {
                        $image_file = $pdf->nmidGetWorkerDir() . "/" . $subRow[ 'lgid' ] . '.png';
                        $command    = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " 0 " . $startTime . " " . $endTime . " " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
                        $title      = $subRow[ "title_cache" ];
                        if ($filter_mode == 'host') {
                            $gtier = $tier + 1;
                        } else {
                            $gtier = $tier + 1;
                        }
                        if (
                            ( ($filter_mode == 'graph') && ( preg_match($regexp_string, $title) ) )
                            ||
                            ($filter_mode == 'host')
                        ) {
                            $lgid = $subRow[ 'lgid' ];

                            $customGraphWidth  = getDBValue( 'customGraphWidth', 'SELECT customGraphWidth FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                            $customGraphHeight = getDBValue( 'customGraphHeight', 'SELECT customGraphHeight FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                            if ( !isNumber( $customGraphWidth ) || ( strlen( $customGraphWidth ) < 1 ) ) {
                                $customGraphWidth = $subRow[ 'width' ];
                            }
                            if ( !isNumber( $customGraphHeight ) || ( strlen( $customGraphHeight ) < 1 ) ) {
                                $customGraphHeight = $subRow[ 'height' ];
                            }

                            CereusReporting_logger( 'Adding graph [' . $subRow[ 'lgid' ] . '] for [' . $hostname . '] at Tier [' . $gtier . ']', 'debug', 'system' );

                            // Download file and store to tmp dir:
                            $secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY );
                            curl_download( readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/create_image.php?key=' . $secure_key . '&lgid=' . $lgid . '&rraid=0&start=' . $startTime . '&end=' . $endTime . '&height='.$customGraphHeight.'&width='.$customGraphWidth, $image_file );

                            $content = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                            $pdf->nmidSetWorkerFileContent( $content );
                        }
                    }
                    $local_stmt->closeCursor();
                }
            }
            // Add graphs
            else if ( ( $row[ 'local_graph_id' ] > 0 ) && ( $filter_mode == 'graph' ) ) {
                CereusReporting_logger( 'Local Graph Id found [' . $row[ 'local_graph_id' ]. ']', 'debug', 'system' );
                $local_stmt = $db->prepare( 'SELECT
                    graph_templates_graph.id as id,
                    graph_templates_graph.local_graph_id as lgid,
                    graph_templates_graph.height as height,
                    graph_templates_graph.width as width,
                    graph_templates_graph.title_cache as title_cache,
                    graph_templates.name as name,
                    graph_local.host_id as host_id
                    FROM (graph_local,graph_templates_graph)
                    LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
                    WHERE graph_local.id=graph_templates_graph.local_graph_id
                    AND graph_templates_graph.local_graph_id  = :lgid' );
                $local_stmt->bindValue( ':lgid', $row[ 'local_graph_id' ] );
                $local_stmt->setFetchMode( PDO::FETCH_ASSOC );
                $local_stmt->execute();

                while ( $subRow = $local_stmt->fetch() ) {
                    $image_file = $pdf->nmidGetWorkerDir() . "/" . $subRow[ 'lgid' ] . '.png';
                    if ( file_exists( 'parallelGraphRetriever.exe' ) ) {
                        $image_file = $pdf->nmidGetWorkerDir() . "\\" . $subRow[ 'lgid' ] . '.png';
                    }
                    $command = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " 0 $startTime $endTime " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
                    $title   = $subRow[ "title_cache" ];
                    if ( preg_match($regexp_string, $title) ) {
                        CereusReporting_logger( 'Adding LGID [' . $title . ']', 'debug', 'system' );
                        $customGraphWidth  = getDBValue( 'customGraphWidth', 'SELECT customGraphWidth FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                        $customGraphHeight = getDBValue( 'customGraphHeight', 'SELECT customGraphHeight FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
                        if ( !isNumber( $customGraphWidth ) || ( strlen( $customGraphWidth ) < 1 ) ) {
                            $customGraphWidth = $subRow[ 'width' ];
                        }
                        if ( !isNumber( $customGraphHeight ) || ( strlen( $customGraphHeight ) < 1 ) ) {
                            $customGraphHeight = $subRow[ 'height' ];
                        }

                        // Download file and store to tmp dir:
                        $secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY );
                        curl_download( readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/create_image.php?key=' . $secure_key . '&lgid=' . $lgid . '&rraid=0&start=' . $startTime . '&end=' . $endTime . '&height='.$customGraphHeight.'&width='.$customGraphWidth, $image_file );

                        $gtier   = $tier;
                        $lgid    = $subRow[ 'lgid' ];
                        $content = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
                        $pdf->nmidSetWorkerFileContent( $content );
                    }
                }
                $local_stmt->closeCursor();

            }
		}

        list( $micro, $seconds ) = explode( " ", microtime() );
        $end = $seconds + $micro;
        $cacti_stats = sprintf( "Time:%01.4f ", round( $end - $start, 4 ) );
        CereusReporting_logger( 'Database retrieval time: [' . $cacti_stats. ']', 'debug', 'system' );

	}
