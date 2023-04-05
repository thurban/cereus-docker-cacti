<?php
	/*******************************************************************************
	 *
	 * File:         $Id: setup_device_api.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
	 * Modified_On:  $Date: 2018/11/11 17:22:55 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2015 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	function plugin_CereusReporting_device_action_array( $action )
	{
		global $config, $database_default;
/*
		if ( function_exists('top_graph_header') == false ) {
			include_once( $config[ "library_path" ] . "/adodb/adodb.inc.php" );
		}
		include_once( $config[ "library_path" ] . "/database.php" );
*/
		$a_slaItems = db_fetch_assoc( "
		SELECT
			id,
			shortDescription
		FROM
			`plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
	    " );

		foreach ( $a_slaItems as $s_slaItem ) {
			$action[ 'plugin_nmidCreatePDF_device_sla_id_' . $s_slaItem[ 'id' ] ] = 'Set SLA time to (' . $s_slaItem[ 'shortDescription' ] . ')';
		}
		$action[ 'plugin_CereusReporting_device_add_dts' ]    = 'Add device(s) to Downtime Schedule';
		$action[ 'plugin_CereusReporting_device_remove_dts' ] = 'Remove device(s) from Downtime Schedule';

		return $action;
	}


	function plugin_CereusReporting_device_action_prepare( $save )
	{
		# globals used
		global $config, $colors;

		if ( preg_match( '/plugin_nmidCreatePDF_device_sla_id_(\d+)/', $save[ "drp_action" ], $matches ) ) { /* sla item id*/
			/* find out which (if any) hosts have been checked, so we can tell the user */
			if ( isset( $save[ "host_array" ] ) ) {
				/* list affected hosts */
				print "<tr>";
				print "<td class='textArea' bgcolor='#" . $colors[ "form_alternate1" ] . "'>" .
					"<p>" . 'Are you sure you want to change the SLA type of the following hosts' . " ?</p>" .
					"<p><ul>" . $save[ "host_list" ] . "</ul></p>" .
					"</td>";
				print "</tr>";
			}
		}
		elseif ( preg_match( '/plugin_CereusReporting_device_add_dts/', $save[ "drp_action" ], $matches ) ) { /* downtime schedule */
			/* find out which (if any) hosts have been checked, so we can tell the user */
			if ( isset( $save[ "host_array" ] ) ) {

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
				$drop_text  = '<select id="dts_items" name="dts_items">';
				foreach ( $a_slaItems as $s_slaItem ) {
					$drop_text .= '<option value="' . $s_slaItem[ 'shortDescription' ] . '">' . $s_slaItem[ 'shortDescription' ] . '</option>';
				}
				$drop_text .= '</select>';
				/* list affected hosts */
				print "<tr>";
				print "<td class='textArea' bgcolor='#" . $colors[ "form_alternate1" ] . "'>" .
					"<p>" . 'You have selected the following devices:' . "</p>" .
					"<p><ul>" . $save[ "host_list" ] . "</ul></p>" .
					"<p>" . 'Please select the Availability Downtime Schedule to add to the selcted devices.' . "</p>" .
					$drop_text .
					"</td>";
				print "</tr>";
			}
		}
		elseif ( preg_match( '/plugin_CereusReporting_device_remove_dts/', $save[ "drp_action" ], $matches ) ) { /* downtime schedule */
			/* find out which (if any) hosts have been checked, so we can tell the user */
			if ( isset( $save[ "host_array" ] ) ) {

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
				$drop_text  = '<select id="dts_items" name="dts_items">';
				foreach ( $a_slaItems as $s_slaItem ) {
					$drop_text .= '<option value="' . $s_slaItem[ 'shortDescription' ] . '">' . $s_slaItem[ 'shortDescription' ] . '</option>';
				}
				$drop_text .= '</select>';
				/* list affected hosts */
				print "<tr>";
				print "<td class='textArea' bgcolor='#" . $colors[ "form_alternate1" ] . "'>" .
					"<p>" . 'You have selected the following devices:' . "</p>" .
					"<p><ul>" . $save[ "host_list" ] . "</ul></p>" .
					"<p>" . 'Please select the Availability Downtime Schedule to remove the selcted devices from.' . "</p>" .
					$drop_text .
					"</td>";
				print "</tr>";
			}
		}
		return $save; # required for next hook in chain
	}

	function plugin_CereusReporting_device_action_execute( $action )
	{
		global $config;

		# it's our turn
		if ( preg_match( '/plugin_nmidCreatePDF_device_sla_id_(\d+)/', $action, $matches ) ) { /* nmidSmokeping Server x */
			if ( isset( $_POST[ "selected_items" ] ) ) {
				$selected_items = unserialize( stripslashes( $_POST[ "selected_items" ] ) );
				for ( $i = 0;
				( $i < count( $selected_items ) );
				      $i++ ) {
					/* ================= input validation ================= */
					input_validate_input_number( $selected_items[ $i ] );
					/* ==================================================== */

					$data              = array();
					$data[ "host_id" ] = $selected_items[ $i ];
					$slaItemId         = $matches[ 1 ];
					CereusReporting_logger( 'Adding device ['.$data[ "host_id" ].'] to SLA ID [' . $slaItemId . ']', "debug", "device_add_slaid" );
					db_execute( "UPDATE host SET nmid_host_sla_timeframe = $slaItemId WHERE id=" . $data[ "host_id" ] );
				}
			}
		}
		# it's our turn
		elseif ( preg_match( '/plugin_CereusReporting_device_add_dts/', $action ) ) { /* downtime schedule */
			if ( isset( $_POST[ "selected_items" ] ) ) {
				if ( isset( $_POST[ "dts_items" ] ) ) {
					$dts_item = $_REQUEST[ "dts_items" ];
					CereusReporting_logger( 'Adding devices to [' . $dts_item . ']', "debug", "device_add_dts" );

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
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription`='" . $dts_item . "'
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

						CereusReporting_logger( 'Adding DTS to [' . $s_dataDeviceId . ']', "debug", "device_add_dts" );

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
		elseif ( preg_match( '/plugin_CereusReporting_device_remove_dts/', $action ) ) { /* downtime schedule */
			if ( isset( $_POST[ "selected_items" ] ) ) {
				if ( isset( $_POST[ "dts_items" ] ) ) {
					$dts_item = $_REQUEST[ "dts_items" ];
					CereusReporting_logger( 'Removing devices from [' . $dts_item . ']', "info", "device_add_dts" );

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
						  `plugin_nmidCreatePDF_Availability_Change_Table`.`shortDescription`='" . $dts_item . "'
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

						CereusReporting_logger( 'Removing DTS from [' . $s_dataDeviceId . ']', "info", "device_add_dts" );

						db_execute( "DELETE FROM `plugin_nmidCreatePDF_Availability_Change_Table`
							WHERE
							deviceId = $s_dataDeviceId
							AND
							shortDescription = '" . $dts_item . "';
						" );

					}
				}
			}
		}
		return $action;
	}