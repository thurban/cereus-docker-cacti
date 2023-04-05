<?php
	/*******************************************************************************
	 *
	 * File:         $Id: setup_graph_api.php,v 4f914587f18d 2016/05/03 05:00:23 thurban $
	 * Modified_On:  $Date: 2016/05/03 05:00:23 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2015 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	function plugin_CereusReporting_graph_action_array( $action )
	{
		$action[ 'plugin_CereusReporting_graph_add_to_report' ]      = 'Add graph(s) to CereusReport';
		$action[ 'plugin_CereusReporting_graph_remove_from_report' ] = 'Remove graph(s) from CereusReport';

		return $action;
	}

	function plugin_CereusReporting_graph_action_prepare( $save )
	{
		# globals used
		global $config, $colors;

		if ( preg_match( '/plugin_CereusReporting_graph_add_to_report/', $save[ "drp_action" ], $matches ) ) { /* downtime schedule */
			/* find out which (if any) hosts have been checked, so we can tell the user */
			if ( isset( $save[ "graph_array" ] ) ) {

				$a_reportItems = db_fetch_assoc( "
					SELECT DISTINCT
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId`,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`startTimeStamp`,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`endTimeStamp`,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription` AS shortDescription,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`longDescription`
					FROM
					  `plugin_nmidCreatePDF_Availability_Change_Table`
			    " );
				$drop_text     = '<select id="report_items" name="report_items">';
				foreach ( $a_reportItems as $s_reportItem ) {
					$drop_text .= '<option value="' . $s_reportItem[ 'shortDescription' ] . '">' . $s_reportItem[ 'shortDescription' ] . '</option>';
				}
				$drop_text .= '</select>';
				/* list affected hosts */
				print "<tr>";
				print "<td class='textArea' bgcolor='#" . $colors[ "form_alternate1" ] . "'>" .
					"<p>" . 'You have selected the following graph(s):' . "</p>" .
					"<p><ul>" . $save[ "graph_list" ] . "</ul></p>" .
					"<p>" . 'Please select the Report to add to the selcted graph(s) to.' . "</p>" .
					$drop_text .
					"</td>";
				print "</tr>";
			}
		}
		elseif ( preg_match( '/plugin_CereusReporting_graph_remove_from_report/', $save[ "drp_action" ], $matches ) ) { /* downtime schedule */
			/* find out which (if any) hosts have been checked, so we can tell the user */
			if ( isset( $save[ "graph_array" ] ) ) {

				$a_slaItems = db_fetch_assoc( "
					SELECT DISTINCT
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId`,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`startTimeStamp`,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`endTimeStamp`,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription` AS shortDescription,
					  `plugin_nmidCreatePDF_Availability_Change_Table`.`longDescription`
					FROM
					  `plugin_nmidCreatePDF_Availability_Change_Table`
			    " );
				$drop_text  = '<select id="report_items" name="report_items">';
				foreach ( $a_reportItems as $s_reportItem ) {
					$drop_text .= '<option value="' . $s_reportItem[ 'shortDescription' ] . '">' . $s_reportItem[ 'shortDescription' ] . '</option>';
				}
				$drop_text .= '</select>';
				/* list affected hosts */
				print "<tr>";
				print "<td class='textArea' bgcolor='#" . $colors[ "form_alternate1" ] . "'>" .
					"<p>" . 'You have selected the following graph(s):' . "</p>" .
					"<p><ul>" . $save[ "graph_list" ] . "</ul></p>" .
					"<p>" . 'Please select the Report to remove the selcted graph(s) from.' . "</p>" .
					$drop_text .
					"</td>";
				print "</tr>";
			}
		}
		return $save; # required for next hook in chain
	}

	function plugin_CereusReporting_graph_action_execute( $action )
	{
		global $config;

		if ( preg_match( '/plugin_CereusReporting_graph_add_to_report/', $action ) ) { /* downtime schedule */
			if ( isset( $_POST[ "selected_items" ] ) ) {
				if ( isset( $_POST[ "report_items" ] ) ) {
					$report_item = $_REQUEST[ "report_items" ];
					CereusReporting_logger( 'Adding graph(s) to [' . $report_item . ']', "info", "graph_add_report" );

					$a_slaItems = db_fetch_assoc( "
						SELECT DISTINCT
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId` AS changeTypeId,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`startTimeStamp` AS startTimeStamp,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`endTimeStamp` AS endTimeStamp,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription` AS shortDescription,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`longDescription` AS longDescription
						FROM
						  `plugin_nmidCreatePDF_Availability_Change_Table`
						WHERE
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription`='" . $report_item . "'
				    " );
					foreach ( $a_slaItems as $s_slaItem ) {
						$s_dataChangeTypeId     = $s_slaItem[ 'changeTypeId' ];
						$s_dataShortDescription = $s_slaItem[ 'shortDescription' ];
						$s_dataLongDescription  = $s_slaItem[ 'longDescription' ];
						$s_dataStartTime        = $s_slaItem[ 'startTimeStamp' ];
						$s_dataEndTime          = $s_slaItem[ 'endTimeStamp' ];
					}
					$selected_items = unserialize( stripslashes( $_REQUEST[ "selected_items" ] ) );
					for ( $i = 0;
					( $i < count( $selected_items ) );
					      $i++ ) {
						/* ================= input validation ================= */
						input_validate_input_number( $selected_items[ $i ] );
						/* ==================================================== */

						$data           = array();
						$s_dataDeviceId = $selected_items[ $i ];

						CereusReporting_logger( 'Adding Graph to [' . $s_dataDeviceId . ']', "info", "graph_add_report" );

						db_execute( "
							INSERT INTO `plugin_nmidCreatePDF_Availability_Change_Table`
								(`deviceId`, `changeTypeId`, `shortDescription`,`longDescription`, `startTimeStamp`, `endTimeStamp`)
							VALUES
								($s_dataDeviceId, '$s_dataChangeTypeId','$s_dataShortDescription', '$s_dataLongDescription',
								'$s_dataStartTime', '$s_dataEndTime')
						" );
					}
				}
			}
		}
		elseif ( preg_match( '/plugin_CereusReporting_graph_remove_from_report/', $action ) ) { /* downtime schedule */
			if ( isset( $_POST[ "selected_items" ] ) ) {
				if ( isset( $_POST[ "report_items" ] ) ) {
					$report_item = $_REQUEST[ "report_items" ];
					CereusReporting_logger( 'Removing graph(s) from [' . $report_item . ']', "info", "graph_remove_report" );

					$a_slaItems = db_fetch_assoc( "
						SELECT DISTINCT
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`changeTypeId` AS changeTypeId,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`startTimeStamp` AS startTimeStamp,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`endTimeStamp` AS endTimeStamp,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription` AS shortDescription,
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`longDescription` AS longDescription
						FROM
						  `plugin_nmidCreatePDF_Availability_Change_Table`
						WHERE
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription`='" . $report_item . "'
				    " );
					foreach ( $a_slaItems as $s_slaItem ) {
						$s_dataChangeTypeId     = $s_slaItem[ 'changeTypeId' ];
						$s_dataShortDescription = $s_slaItem[ 'shortDescription' ];
						$s_dataLongDescription  = $s_slaItem[ 'longDescription' ];
						$s_dataStartTime        = $s_slaItem[ 'startTimeStamp' ];
						$s_dataEndTime          = $s_slaItem[ 'endTimeStamp' ];
					}
					$selected_items = unserialize( stripslashes( $_REQUEST[ "selected_items" ] ) );
					for ( $i = 0;
					( $i < count( $selected_items ) );
					      $i++ ) {
						/* ================= input validation ================= */
						input_validate_input_number( $selected_items[ $i ] );
						/* ==================================================== */

						$data           = array();
						$s_dataDeviceId = $selected_items[ $i ];

						CereusReporting_logger( 'Removing DTS from [' . $s_dataDeviceId . ']', "info", "graph_remove_report" );

						db_execute( "DELETE FROM `plugin_nmidCreatePDF_Availability_Change_Table`
							WHERE
							deviceId = $s_dataDeviceId
							AND
							shortDescription = '" . $report_item . "';
						" );

					}
				}
			}
		}
		return $action;
	}