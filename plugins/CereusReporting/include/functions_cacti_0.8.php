<?php
	/*******************************************************************************
 * Copyright (c) 2017. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2017.
 *
 * File:         $Id: functions_cacti_0.8.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
 * Filename:     functions_cacti_0.8.php
 * LastModified: 06.07.17 07:37
 * Modified_On:  $Date: 2018/11/11 17:22:55 $
 * Modified_By:  $Author: thurban $
 *
 ******************************************************************************/

	function CereusReporting_page_buttons_compat( $my_args ) {
		global $config, $colors, $plugin_architecture;


		if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
			$mode = $my_args[ 'mode' ];
			if ( $mode == 'tree' ) {
				if ( CereusReporting_isNewCactiUI() ) {
					$tree_id = $_REQUEST[ 'tree_id' ];
					$leaf_id = $_REQUEST[ 'leaf_id' ];
				}
				else {
					$tree_id = $my_args[ 'treeid' ];
					$leaf_id = $my_args[ 'leafid' ];
				}
				$starttime = $my_args[ 'starttime' ];
				$endtime   = $my_args[ 'endtime' ];
				$timespan  = $my_args[ 'timespan' ];
			}
			elseif ( $mode == 'mrtg' ) {
				$lgid  = $my_args[ 'lgid' ];
				$rraid = $my_args[ 'rraid' ];
			}

			$txt[ 'P' ]   =  'Portrait';
			$txt[ 'L' ]   =  'Landscape';
			$txt[ '0' ]   =  'Default';
			$txt[ '2x2' ] =  '2 Graphs, 2 Columns';
			if ( $plugin_architecture[ 'version' ] == "2.9" ) {
				if ( $mode == 'tree' ) {
					html_graph_start_box( "", "100%", $colors[ "header" ], "3", "center", "" );
					?>
					<tr bgcolor="<?php print $colors[ "panel" ]; ?>" class="noprint">
					<td class="noprint">
					<table width="100%" cellpadding="0" cellspacing="0">
					<tr><td align=right>
					<table><tr><td>
					<?php
				}
				elseif ( $mode == 'mrtg' ) {
					echo "<tr align=right><td align=right>";
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

				<?php
				if ( ( readConfigOption( "nmid_use_css" ) == "1" ) || ( CereusReporting_isNewCactiUI()  == TRUE ) )
				{
				// AND ( CereusReporting_isNewCactiUI()  == FALSE )
				?>
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
				<?php } ?>
                //-->
			</SCRIPT>
			<?php
			echo "<table width=99%><tr><td>&nbsp;</td><td align=right>";
			if ( $mode == 'tree' ) {
				if ( CereusReporting_isNewCactiUI() ) {
					// __csrf_magic: csrfMagicToken
					echo "<input title='Add graphs to report' type='image' id='nmidOpener' src='plugins/CereusReporting/images/Report_Add.png'/>";
					echo "<input title='Create PDF Report' type='image' src='plugins/CereusReporting/images/PDF_file.png' id='nmidCreateOpener' value='DefaultType' alt='DefaultType'>";
					$s_CereusReporting_html_form_content = "<form name=CereusReporting_Create_Form method=post>";
					$s_CereusReporting_html_form_content .= "<input type=hidden name=starttime value='" . $starttime . "'>";
					$s_CereusReporting_html_form_content .= "<input type=hidden name=endtime value='" . $endtime . "'>";
					$s_CereusReporting_html_form_content .= "<input type=hidden name=leaf_id value='" . $_REQUEST[ 'leaf_id' ] . "'>";
					$s_CereusReporting_html_form_content .= "<input type=hidden name=timespan value='" . $timespan . "'>";
					$s_CereusReporting_html_form_content .= "<input type=hidden name=tree_id value='" . $_REQUEST[ 'tree_id' ] . "'>";
					$s_CereusReporting_html_form_content .= "<input type=hidden name=user_id value='" . $_SESSION[ "sess_user_id" ] . "'>";
					if (function_exists('csrf_get_tokens' )) {
						$s_CereusReporting_html_form_content .= "<input type=hidden id='__csrf_magic' name='__csrf_magic' value='" . csrf_get_tokens() . "'>";
					} else {
						$s_CereusReporting_html_form_content .= "<input type=hidden id='__csrf_magic' name='__csrf_magic' value=''>";
                    }

					$s_CereusReporting_html_form_content .= "<input type=hidden name=lgi_fix value=''>";
					if ( preg_match( "/\d+/", $_REQUEST[ 'leaf_id' ] ) == FALSE ) {
						$s_CereusReporting_html_form_content .= '<br/><font color=\"blue\">Please note that you cannot create a standard report on the main tree level.' .
							'You will have to select the <b>\"Include Sub-Leafs\"</b> for this to work.</font><br/><br/>';
					}
					if ( preg_match( "/\d+/", $_REQUEST[ 'leaf_id' ] ) == FALSE ) {
						$s_CereusReporting_html_form_content .= 'Include Sub-Leafs' . " <input type=checkBox checked name='nmid_pdfAddSubLeafs' value=1><br/>";
					} else {
						$s_CereusReporting_html_form_content .= 'Include Sub-Leafs' . " <input type=checkBox name='nmid_pdfAddSubLeafs' value=1><br/>";
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
                        $('#nmidCreateDialog').html("<?php echo $s_CereusReporting_html_form_content; ?>");
                        $('#nmidDialog').html("<?php echo $s_CereusReporting_html_form_add_content; ?>");

                        //-->
					</SCRIPT>
					<?php
				}
				else {
					echo "<input title='Add graphs to report' type='image' id='nmidOpener' src='plugins/CereusReporting/images/Report_Add.png'/>";
					echo "<input title='Create PDF Report'  type='image' src='plugins/CereusReporting/images/PDF_file.png' id='nmidCreateOpener' value='DefaultType' alt='DefaultType'>";
				}

			}
			else {
				// CRC-138 On-Demand reports on "MRTG like view" does not work
				echo "<input type='image' src='plugins/CereusReporting/images/PDF_file.png' onclick=\"setAction('3')\" value='MRTGStyle' alt='MRTGStyle'>";
			}

			if ( CereusReporting_isNewCactiUI() == FALSE ) {
				echo '<div id="nmidDialog" title="' . 'Add Graph(s) to Report' . '">';
				echo "<form name=CereusReporting_Form method=post>";
				if ( $mode == 'tree' ) {
					echo "<input type=hidden name=starttime value='" . $starttime . "'>";
					echo "<input type=hidden name=endtime value='" . $endtime . "'>";
					echo "<input type=hidden name=leaf_id value='" . $_REQUEST[ 'leaf_id' ] . "'>";
					echo "<input type=hidden name=timespan value='" . $timespan . "'>";
					if (function_exists('csrf_get_tokens' )) {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value='" . csrf_get_tokens() . "'>";
					} else {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value=''>";
                    }
					echo "<input type=hidden name=tree_id value='" . $_REQUEST[ 'tree_id' ] . "'>";
					echo "<input type=hidden name=user_id value='" . $_SESSION[ "sess_user_id" ] . "'>";
					echo "<input type=hidden name=lgi_fix value=''>";
					if ( api_user_realm_auth( 'CereusReporting_addReport.php' ) ) {
						echo "<select name='report_id'>" .
							"<OPTGROUP label='Please choose'>";
						$GraphReports = db_fetch_assoc( "
                        SELECT `ReportId`,`Name` FROM `plugin_nmidCreatePDF_Reports` WHERE `reportType` = 1 OR `reportType` = 3 ;
                        " );
						foreach ( $GraphReports as $report ) {
							echo "<option value='" . $report[ 'ReportId' ] . "'>" . $report[ 'Name' ] . "</option>";
						}
						echo "</OPTGROUP>" .
							"</select><br/>";
						echo 'Include Sub-Leafs' . " <input type=checkBox name='nmid_pdfAddSubLeafs' value=1><br/><br/>";
						echo "<input id='nmidOpener' type='image' src='plugins/CereusReporting/images/add.png' onclick=\"setAction('1')\" value='" . 'Add to Report' . "' alt='" . 'Add to Report' . "'>";
					}
				}
				elseif ( $mode == 'mrtg' ) {
					echo "<input type=hidden name=lgid value='" . $lgid . "'>";
					echo "<input type=hidden name=rraid value='" . $rraid . "'>";
					if (function_exists('csrf_get_tokens' )) {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value='" . csrf_get_tokens() . "'>";
					} else {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value=''>";
                    }
					echo "<input type=hidden name=user_id value='" . $_SESSION[ "sess_user_id" ] . "'>";
				}
				echo '</form></div>';

				echo '<div id="nmidCreateDialog" title="' . 'Create Report' . '">';
				echo "<form name=CereusReporting_Create_Form method=post>\n";
				if ( $mode == 'tree' ) {
					echo "<input type=hidden name=starttime value='" . $starttime . "'>";
					echo "<input type=hidden name=endtime value='" . $endtime . "'>";
					echo "<input type=hidden name=leaf_id value='" . $_REQUEST[ 'leaf_id' ] . "'>";
					echo "<input type=hidden name=timespan value='" . $timespan . "'>";
					if (function_exists('csrf_get_tokens' )) {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value='" . csrf_get_tokens() . "'>";
					} else {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value=''>";
                    }
					echo "<input type=hidden name=tree_id value='" . $_REQUEST[ 'tree_id' ] . "'>";
					echo "<input type=hidden name=user_id value='" . $_SESSION[ "sess_user_id" ] . "'>";
					echo "<input type=hidden name=lgi_fix value=''>";
					echo "<table>";
					echo '<tr><td>Include Sub-Leafs</td>' . "<td><input type=checkBox name='nmid_pdfAddSubLeafs' value=1></td></tr>";
					echo '<tr><td>Email Report</td>' . "<td><input id=send_email type=checkBox name='nmid_send_report_email' value=0></td></tr>";
					echo "<tr id='emailField' style='display:none'><td>Target Email</td><td><input type=text name='user_target_email' value='' /></td></tr>";
					echo "</table>";
					echo "<button id='nmidOpener' onclick=\"setAction('2')\" >\n";
					echo "<span class='image'><img src='plugins/CereusReporting/images/Download.png' /></span>\n";
					echo "<span class='text'>Create Report</span>\n";
					echo "</button>";
				}
				elseif ( $mode == 'mrtg' ) {
					echo "<input type=hidden name=lgid value='" . $lgid . "'>";
					echo "<input type=hidden name=rraid value='" . $rraid . "'>";
					if (function_exists('csrf_get_tokens' )) {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value='" . csrf_get_tokens() . "'>";
					} else {
						echo "<input type=hidden name='__csrf_magic' id='__csrf_magic' value=''>";
                    }
					echo "<input type=hidden name=user_id value='" . $_SESSION[ "sess_user_id" ] . "'>";
				}
				echo '</form></div>';
			} // CereusReporting_isNewCactiUI
			else {
				echo '<div id="nmidDialog" title="' . 'Add Graph(s) to Report' . '"></div>';
				echo '<div id="nmidCreateDialog" title="' . 'Create Report' . '"></div>';

			}
			echo "</td></tr></table>\n";

			if ( $plugin_architecture[ 'version' ] == "2.9" ) {
				if ( $mode == 'tree' ) {
					?>
					</td></tr></table>
					</td></tr>
					</table>
					</td>
					</tr>
					<?php
					html_graph_end_box();
					print "<br>";
				}
				elseif ( $mode == 'mrtg' ) {
					echo "</td></tr>";
				}
			}
		}
		return $my_args;
	}

	function CereusReporting_tree_after_compat($param) {
		global $config, $database_default;
		preg_match( "/^(.+),(\d+)$/", $param, $hit );
		include_once( $config[ "library_path" ] . "/adodb/adodb.inc.php" );
		include_once( $config[ "library_path" ] . "/database.php" );
		$dir = dirname( __FILE__ );
		include_once( $dir . '/../functions.php' );

		$startTime = get_current_graph_start();
		$endTime = get_current_graph_end();

		if (api_user_realm_auth( 'CereusReporting_AvailabilityChart.php' )) {
            if (isset ( $hit[ 1 ] )) {
                // We skip hosts for now.
            }
            else {
                // So we're in a leaf
                $tree_id = $_REQUEST[ 'tree_id' ];
                $host_leaf_id = $_REQUEST[ 'leaf_id' ];

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
                $tree_id = $_REQUEST[ 'tree_id' ];
                $host_leaf_id = $_REQUEST[ 'leaf_id' ];
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
                        <td colspan='3' class='textHeaderDark'>
                            <strong>Graph Template:</strong> <?php echo  'Availability Chart (external)'; ?>
                        </td>
                    </tr>
                    <tr align='center' style='background-color: #f9f9f9;'>
                    <td align='center'>
                    <?php
                    print "	<table width='1' cellpadding='0'>\n";
                    print "		<tr>\n";
                    print "			<td valign='top' style='padding: 3px;' class='noprint'>\n";
                    print "				<img src='" . $config[ 'url_path' ] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChart.php?tree_id=' . $tree_id . '&leaf_id=' . $host_leaf_id . '&startTime=' . $startTime . '&endTime=' . $endTime . '&mode=time&data=p' . "' border='0'>\n";
                    print "			</td>\n";
                    print "			<td valign='top' style='padding: 3px;' class='noprint'>";
                    if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
                        print " 	    <input onClick=\"setData('avc_" . $tree_id . "_" . $host_leaf_id . "');\" type=checkbox id='avc_" . $tree_id . "_" . $host_leaf_id . "' name='avc_" . $tree_id . "_" . $host_leaf_id . "' value='" . $tree_id . "_" . $host_leaf_id . "'><br>";
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

    function CereusReporting_doLeafGraphs( $pdf, $leafid, $startTime, $endTime )
    {
	    global $isSmokepingEnabled, $isBoostEnabled, $isBoostCacheEnabled,
	           $boost_png_cache_directory, $orderKey, $graphPerPage, $config,
	           $tree_id, $cgiAddSubLeafs, $phpBinary,$pdfType,$debugModeOn;

	    if ( ini_get('max_execution_time') < 120 ) {
		    set_time_limit ( 120 );
	    }
	    // Get DB Instance
	    $db = DBCxn::get();

	    if ( $pdf->nmidGetPdfType() > 0 ) {
		    $pdf->Bookmark( $pdf->nmidGetHeaderTitle(), 0 );
	    }

	    $sql = "";
	    if ( $leafid == 0 ) {
		    //$orderKey = '___000';
	    }
	    if ( $cgiAddSubLeafs ) {
		    // $orderKey = '___';
	    }

	    $stmt = FALSE;

	    if ( $cgiAddSubLeafs ) {
		    $stmt = $db->prepare( 'SELECT host_id,local_graph_id,rra_id,title,order_key FROM graph_tree_items WHERE graph_tree_id= :treeId  AND order_key LIKE :orderKey ORDER BY  order_key' );
		    $stmt->bindValue( ':treeId', $tree_id );
		    $stmt->bindValue( ':orderKey', $orderKey . '%' );
		    CereusReporting_logger( 'Creating Report with Sub Trees ( Tree => [' . $tree_id. '] Order Key => [' . $orderKey.']', 'debug', 'system' );
	    }
	    else {
		    CereusReporting_logger( 'Creating Report without Sub Trees ( Tree => [' . $tree_id. '] Order Key => [' . $orderKey.']', 'debug', 'system' );

		    $local_stmt = $db->prepare( 'select host_id,order_key from graph_tree_items where graph_tree_id = :treeId  AND order_key like :orderKey ORDER BY  order_key' );
		    $local_stmt->bindValue( ':treeId', $tree_id );
		    $local_stmt->bindValue( ':orderKey', $orderKey . '000%' );
		    $local_stmt->execute();
		    $host_id = $local_stmt->fetchColumn();
		    $local_stmt->closeCursor();

		    $local_stmt = $db->prepare( 'select local_graph_id,order_key from graph_tree_items where graph_tree_id = :treeId  AND order_key like :orderKey ORDER BY  order_key' );
		    $local_stmt->bindValue( ':treeId', $tree_id );
		    $local_stmt->bindValue( ':orderKey', $orderKey . '000%' );
		    $local_stmt->execute();
		    $local_graph_id = $local_stmt->fetchColumn();
		    $local_stmt->closeCursor();

		    if ( $host_id > 0 ) {
			    $stmt = $db->prepare( 'select host_id,local_graph_id,rra_id,order_key from graph_tree_items where graph_tree_id = :treeId AND host_id = :hostId  AND order_key like :orderKey ORDER BY  order_key' );
			    $stmt->bindValue( ':treeId', $tree_id );
			    $stmt->bindValue( ':hostId', $host_id );
			    $stmt->bindValue( ':orderKey', $orderKey . '___000%' );
		    }
		    if ( $leafid == 0 ) {
			    $stmt = $db->prepare( 'select host_id,local_graph_id,rra_id,title,order_key from graph_tree_items where graph_tree_id = :treeId AND rra_id=5 AND local_graph_id= :lgid AND order_key like :orderKey ORDER BY  order_key' );
			    $stmt->bindValue( ':treeId', $tree_id );
			    $stmt->bindValue( ':hostId', $host_id );
			    $stmt->bindValue( ':orderKey', $orderKey . '___000%' );
		    }
	    }

	    if ( isset ( $stmt ) == FALSE ) {
		    $pdf->WriteHTML( '<h3>Error: No Items can be found !<h3>', FALSE, FALSE );
		    CereusReporting_logger( 'No Items available !', "fatal", "DoLeafGraphs" );
		    return;
	    }
	    if ( $stmt == FALSE ) {
		    return;
	    }
	    $stmt->setFetchMode( PDO::FETCH_ASSOC );
	    $result = $stmt->execute();

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


	    if ( $result ) {
		    list( $micro, $seconds ) = explode( " ", microtime() );
		    $start = $seconds + $micro;
		    $last_host = -1;
		    while ( $row = $stmt->fetch() ) {
			    $local_orderKey      = preg_replace( "/(0{3,3})+$/", "", $row[ 'order_key' ] );
			    $tier                = ( strlen( $local_orderKey ) / 3 ) - ( strlen( $orderKey ) / 3 ) + 1;

			    if ( $row[ 'host_id' ] > 0 ) {
				    // Check if the host_id changed
				    if ( $row[ 'host_id' ] != $last_host ) {
					    // Check if this isn't the first host entry
					    if ( $last_host > 0 ) {
						    if ( $pdfType == MPDF_ENGINE ) {
							    $control_text = '<pagebreak />';
						    }
                            elseif ( $pdfType == TCPDF_ENGINE ) {
							    $control_text = '<tcpdf method="AddPage" />';
						    }
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
				    if ( $pdfType == MPDF_ENGINE ) {
					    $control_text = '<bookmark content="' . $hostname . '" level="' . $tier . '" />';
				    } elseif ( $pdfType == TCPDF_ENGINE ) {
					    $params = $pdf->serializeTCPDFtagParameters(array($hostname, $tier-1, -1, '', '', array(0,0,0)));
					    $control_text  = '<tcpdf method="Bookmark" params="'.$params.'" />';
				    }
				    $html    = '<div class="nmidChapterText"><table width="100%"><tr><td class="nmidChapterText">' . $hostname . '</td></tr></table></div>'.$control_text.'<br />';
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
					    if ( file_exists( 'parallelGraphRetriever.exe' ) ) {
						    $image_file = $pdf->nmidGetWorkerDir() . "\\" . $subRow[ 'lgid' ] . '.png';
					    }
					    $command = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " 0 " . $startTime . " " . $endTime . " " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
					    $title   = $subRow[ "title_cache" ];
					    $gtier       = $tier + 1;
					    $lgid    = $subRow[ 'lgid' ];

					    // Download file and store to tmp dir:
					    CereusReporting_logger( 'Retrieving Graph ['. $title .']['.$lgid.'] from host [' . $hostname . '] at Tier [' . $gtier . ']', 'debug', 'DoLeafGraphs' );
					    $secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
					    curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height=&width=',$image_file);

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


					    // Download file and store to tmp dir:
					    $secure_key = sha1( $subRow['lgid'] . '0' . $startTime . $endTime . SECURE_URL_KEY);
					    curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$subRow['lgid'].'&rraid=0&start='.$startTime.'&end='.$endTime.'&height=&width=',$image_file);

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
				    if ( $pdfType == MPDF_ENGINE ) {
					    $control_text = '<bookmark content="' . $row[ 'title' ] . '" level="' . $tier . '" />';
				    } elseif ( $pdfType == TCPDF_ENGINE ) {
					    $params = $pdf->serializeTCPDFtagParameters(array($row[ 'title' ], $tier-1, -1, '', '', array(0,0,0)));
					    $control_text  = '<tcpdf method="Bookmark" params="'.$params.'" />';
				    }
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
	    }


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
	    global $orderKey, $tree_id, $cgiAddSubLeafs;
	    $isSmokepingEnabled = readPluginStatus( 'nmidSmokeping' ) || FALSE;
	    $orderKey           = getPreparedDBValue( 'SELECT order_key FROM graph_tree_items WHERE id=?;',array($leafid) );
	    $orderKey           = preg_replace( "/(0{3,3})+$/", "", $orderKey );
	    CereusReporting_logger( 'Adding Leaf ( Leaf ID => [' . $leafid . '] Order Key => [' . $orderKey . ']', 'debug', 'system' );
	    $subRows = '';
	    if ( $cgiAddSubLeafs ) {
		    $sql = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE graph_tree_id=? AND order_key LIKE ?;";
		    $subRows = cr_db_fetch_assoc_prepared($sql,array($tree_id,$orderKey.'%'));
	    }
	    else {
		    $host_id = getPreparedDBValue( "SELECT host_id FROM graph_tree_items WHERE graph_tree_id=? AND order_key LIKE ?;", array($tree_id,$orderKey.'000%') );
		    $sql     = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE graph_tree_id=? AND host_id=? AND order_key LIKE ?;";
		    $subRows = cr_db_fetch_assoc_prepared($sql,array($tree_id,$host_id,$orderKey . '___000%'));
	    }
	    foreach( $subRows as $row ) {
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

		if ( $leaf_id > 0 ) {
			$orderKey = getPreparedDBValue( 'SELECT MIN(order_key) AS order_key FROM graph_tree_items WHERE id=?;', array( $leaf_id ) );
		}
		else {
			$orderKey = '';
		}
		$orderKey = preg_replace( "/(0{3,3})+$/", "", $orderKey );



		$sql = "";

		$stmt = FALSE;

		$stmt = $db->prepare( 'SELECT host_id,local_graph_id,rra_id,title,order_key FROM graph_tree_items WHERE graph_tree_id= :treeId  AND order_key LIKE :orderKey ORDER BY  order_key' );
		$stmt->bindValue( ':treeId', $tree_id );
		$stmt->bindValue( ':orderKey', $orderKey . '%' );
		CereusReporting_logger( 'Creating Report with Sub Trees ( Tree => [' . $tree_id. '] Order Key => [' . $orderKey.']', 'debug', 'system' );

		if ( isset ( $stmt ) == FALSE ) {
			$pdf->WriteHTML( '<h3>Error: No Items can be found !<h3>', FALSE, FALSE );
			CereusReporting_logger( 'No Items available !', "fatal", "DoLeafGraphs" );
			return;
		}
		if ( $stmt == FALSE ) {
			return;
		}
		$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$result = $stmt->execute();

		if ( $result ) {
			list( $micro, $seconds ) = explode( " ", microtime() );
			$start = $seconds + $micro;
			$last_host = -1;
			while ( $row = $stmt->fetch() ) {
				$local_orderKey      = preg_replace( "/(0{3,3})+$/", "", $row[ 'order_key' ] );
				$tier                = ( strlen( $local_orderKey ) / 3 ) - ( strlen( $orderKey ) / 3 );

				// Add Hosts
				if ( $row[ 'host_id' ] > 0 ) {
					// Check if the host_id changed
					if ( $row[ 'host_id' ] != $last_host ) {
						// Check if this isn't the first host entry
						if ( $last_host > 0 ) {
							if ( $pdfType == MPDF_ENGINE ) {
								$control_text = '<pagebreak />';
							}
                            elseif ( $pdfType == TCPDF_ENGINE ) {
								$control_text = '<tcpdf method="AddPage" />';
							}
							// Add a pagebreak before the new host begins
							printControlText( $pdf, '', $control_text, $tier, 'pagebreak' );
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
					} elseif ( $pdfType == TCPDF_ENGINE ) {
						$params = $pdf->serializeTCPDFtagParameters(array($hostname, $tier, -1, '', '', array(0,0,0)));
						$control_text  = '<tcpdf method="Bookmark" params="'.$params.'" />';
					}
					$html    = '<div class="nmidTitleText"><table width="100%"><tr><td class="nmidTitleText">' . $hostname . '</td></tr></table></div>'.$control_text.'<br />';
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
						$command = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " " . $row[ 'rra_id' ] . " " . $startTime . " " . $endTime . " " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
						$title   = $subRow[ "title_cache" ];
						$gtier       = $tier + 1;
						if ( $pdfType == TCPDF_ENGINE ) {
							$gtier       = $tier + 2;
						}

						$lgid    = $subRow[ 'lgid' ];

						CereusReporting_logger( 'Adding graph ['.$subRow[ 'lgid' ].'] for [' . $hostname . '] at Tier [' . $gtier . ']', 'debug', 'system' );

						// Download file and store to tmp dir:
						$secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
						curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height=&width=',$image_file);

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
						$command = $phpBinary . " create_image.php " . $subRow[ 'lgid' ] . " " . $row[ 'rra_id' ] . " $startTime $endTime " . $subRow[ 'height' ] . " " . $subRow[ 'width' ] . " > " . $image_file;
						$title   = $subRow[ "title_cache" ];
						CereusReporting_logger( 'Adding LGID [' . $title . ']', 'debug', 'system' );


						// Download file and store to tmp dir:
						$secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
						curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height=&width=',$image_file);

						$gtier   = $tier + 1;
						$lgid    = $subRow[ 'lgid' ];
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
					$html = '<div class="nmidChapterText"><table width="100%"><tr><td class="nmidChapterText">' . $row[ 'title' ] . '</td></tr></table></div>'.$control_text.'<br />';
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
			CereusReporting_logger( 'Database retrieval time: [' . $cacti_stats. ']', 'debug', 'system' );

		}
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

		if ( $leaf_id > 0 ) {
			$orderKey = getPreparedDBValue( 'SELECT MIN(order_key) AS order_key FROM graph_tree_items WHERE id=?;', array( $leaf_id ) );
		}
		else {
			$orderKey = '';
		}
		$orderKey = preg_replace( "/(0{3,3})+$/", "", $orderKey );

		$sql = "";

		$stmt = FALSE;

		$stmt = $db->prepare( 'SELECT host_id,local_graph_id,rra_id,title,order_key FROM graph_tree_items WHERE graph_tree_id= :treeId  AND order_key LIKE :orderKey ORDER BY  order_key' );
		$stmt->bindValue( ':treeId', $tree_id );
		$stmt->bindValue( ':orderKey', $orderKey . '%' );
		CereusReporting_logger( 'Creating Regexp Report with Sub Trees ( Tree => [' . $tree_id. '] Order Key => [' . $orderKey.'] Filter ['.$filter_mode.'] RegExp ['.$regexp_string.'] ) ', 'debug', 'system' );

		if ( isset ( $stmt ) == FALSE ) {
			$pdf->WriteHTML( '<h3>Error: No Items can be found !<h3>', FALSE, FALSE );
			CereusReporting_logger( 'No Items available !', "fatal", "DoLeafGraphs" );
			return;
		}
		if ( $stmt == FALSE ) {
			return;
		}
		$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$result = $stmt->execute();



		if ( $result ) {
			list( $micro, $seconds ) = explode( " ", microtime() );
			$start = $seconds + $micro;
			while ( $row = $stmt->fetch() ) {
				$local_orderKey      = preg_replace( "/(0{3,3})+$/", "", $row[ 'order_key' ] );
				$tier                = $global_tier;
				// CereusReporting_logger( 'Checking ROW | host_id=[' . $row[ 'host_id' ] . '] | local_graph_id=[' . $row[ 'local_graph_id' ]. ']', 'debug', 'system' );

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
						$content    = $pdf->nmidGetWorkerFileContent() . 'ctrltext' . '@' . $command . '@' . $html . '@1@' . $image_file . '@' . $lgid . "\n";
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
								if ( $pdfType == TCPDF_ENGINE ) {
									$gtier = $tier + 2;
								}
							} else {
								$gtier = $tier;
							}
							if (
								( ($filter_mode == 'graph') && ( preg_match($regexp_string, $title) ) )
								||
								($filter_mode == 'host')
							) {
								$lgid = $subRow[ 'lgid' ];

								CereusReporting_logger( 'Adding graph [' . $subRow[ 'lgid' ] . '] for [' . $hostname . '] at Tier [' . $gtier . ']', 'debug', 'system' );

								// Download file and store to tmp dir:
								$secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY );
								curl_download( readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/create_image.php?key=' . $secure_key . '&lgid=' . $lgid . '&rraid=0&start=' . $startTime . '&end=' . $endTime . '&height=&width=', $image_file );

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


							// Download file and store to tmp dir:
							$secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY );
							curl_download( readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config[ 'url_path' ] . 'plugins/CereusReporting/create_image.php?key=' . $secure_key . '&lgid=' . $lgid . '&rraid=0&start=' . $startTime . '&end=' . $endTime . '&height=&width=', $image_file );

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
	}


	/**
	 * @return bool
	 */
	function CereusReporting_isNewCactiUI()
	{
		global $config;

		$cacti_version = db_fetch_cell("select cacti from version;");
		// get minor and major version number
		preg_match( "@(\d+)\.(\d+).(\d+)(\w*)@", $cacti_version, $version_match );

		$version_major        = $version_match[ 1 ];
		$version_minor        = $version_match[ 2 ];
		$version_build        = $version_match[ 3 ];
		$version_build_minor = $version_match[ 4 ];

		if ( $version_major < 1 ) {  // 0.
			if ( $version_minor < 9 ) {  // 0.8
				if ( $version_build < 8 ) { // 0.8.7
					return FALSE;
				}
				else {  // 0.8.8
					if ( ord( $version_build_minor ) < 99 ) { // 0.8.8a 0.8.8b
						return FALSE;
					}
				}
			}
		}
		// 0.8.8c and greater
		return TRUE;
	}