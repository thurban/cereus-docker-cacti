<?php
	/*******************************************************************************
	 *
	 * File:         $Id: setup_database.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2015 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	function CereusReporting_setup_table_new()
	{
		global $config, $database_default;
		include_once( $config[ "library_path" ] . "/database.php" );

		// Check if the CereusReporting tables are present

		$s_sql = 'show tables from `' . $database_default . '`';
		$result = db_fetch_assoc( $s_sql );
		$a_tables = array();

		$sql = array();

		foreach ( $result as $index => $array ) {
			foreach ( $array as $table ) {
				$a_tables[strtolower($table)] = strtolower($table);
			}
		}

		api_plugin_db_add_column( 'CereusReporting', 'graph_tree', array( 'name'    => 'CereusReporting_cover_page',
		                                                                  'type'    => 'varchar(1024)', 'NULL' => TRUE,
		                                                                  'default' => '' ) );
		api_plugin_db_add_column( 'CereusReporting', 'graph_tree', array( 'name'    => 'CereusReporting_cover_logo',
		                                                                  'type'    => 'varchar(1024)', 'NULL' => TRUE,
		                                                                  'default' => '' ) );
		api_plugin_db_add_column( 'CereusReporting', 'host', array( 'name' => 'nmid_host_sla',
		                                                            'type' => 'varchar(1024)',
		                                                            'NULL' => TRUE, 'default' => '' ) );
		api_plugin_db_add_column( 'CereusReporting', 'host', array( 'name'    => 'nmid_host_sla_timeframe',
		                                                            'type'    => 'varchar(1024)', 'NULL' => TRUE,
		                                                            'default' => '' ) );

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_reports_scheduler', $a_tables ) ) {
			// Create Report Schedule Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'ScheduleId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Name', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Date', 'type' => 'varchar(1024)', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'lastRunDate', 'type' => 'varchar(255)', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'isRecurring', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'frequency', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => 'Active' );
			$data[ 'columns' ][] = array( 'name'    => 'Status', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '1' );
			$data[ 'columns' ][] = array( 'name' => 'Creator', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'ReportID', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Recipients', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'RecipientsBcc', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Attachments', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'archiveReport', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'     => 'archiveUserGroupId', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned', 'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'runNow', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'primary' ]   = 'ScheduleId';
			$data[ 'keys' ][]    = array( 'name' => 'lastRunDate', 'columns' => 'lastRunDate' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Report Schedules';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Reports_scheduler', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_reports', $a_tables ) ) {
			// Create Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'ReportId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Name', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Logo', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'CoverPage', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'includeSubDirs', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'leafId', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'reportType', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '0' ); // 0 = on demand, 1 = hourly, 2 = daily, 3 = weekly, 4 = monthly, 5 = yearly
			$data[ 'columns' ][] = array( 'name'    => 'type', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'pageSize', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'pageOrientation', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'PrependPDFFile', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'AppendPDFFile', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'pageGraphFormat', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'outputType', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'showGraphHeader', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '1' );
			$data[ 'columns' ][] = array( 'name'    => 'author', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'customHeader', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'customFooter', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'printDetailedFailedPollsTable', 'type' => 'varchar(5)',
			                              'NULL'    => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'printDetailedPollsTable', 'type' => 'varchar(5)',
			                              'NULL'    => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'printHeader', 'type' => 'varchar(5)',
			                              'NULL'    => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'printFooter', 'type' => 'varchar(5)',
			                              'NULL'    => FALSE,
			                              'default' => '0' );
            $data[ 'columns' ][] = array( 'name'    => 'printPageNumbers', 'type' => 'varchar(5)',
                                          'NULL'    => FALSE,
                                          'default' => '1' );
			$data[ 'columns' ][] = array( 'name'    => 'skipHFCoverPage', 'type' => 'varchar(5)',
			                              'NULL'    => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'customReportTitle', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'customSubReportTitle', 'type' => 'varchar(255)','NULL'    => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'customGraphWidth', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'customGraphHeight', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'ReportId';
			$data[ 'keys' ][]    = array( 'name' => 'leafId', 'columns' => 'leafId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Reports';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', $data );
		}

		if ( !array_key_exists ( 'plugin_cereusreporting_reports_templates', $a_tables ) ) {
			// Create Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'templateId', 'type' => 'mediumint(25)', 'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'name', 'type' => 'varchar(255)', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'template_file', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'page_size', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'page_orientation', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'custom_graph_width', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'custom_graph_height', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'page_margin_top', 'type' => 'mediumint(5)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'page_margin_bottom', 'type' => 'mediumint(5)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'page_margin_left', 'type' => 'mediumint(5)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'page_margin_right', 'type' => 'mediumint(5)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'page_footer_margin_bottom', 'type' => 'mediumint(5)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '5' );
			$data[ 'columns' ][] = array( 'name'    => 'header_template', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'footer_template', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'report_title', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'report_subtitle', 'type' => 'varchar(255)', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'templateId';
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Report Templates';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_CereusReporting_Reports_templates', $data );
			$sql[] = "INSERT INTO plugin_CereusReporting_Reports_templates (templateId, name, description, template_file, page_size, page_orientation, custom_graph_width, custom_graph_height, page_margin_top, page_margin_bottom, page_margin_left, page_margin_right, header_template, footer_template, report_title, report_subtitle) VALUES (-1,'ColorCircle Template', 'ColorCircle Template', 'ColorCircle_Report_Template-P-A4.pdf', 'A4', 'P', '800', '100', 30, 15, 5, 5, '', '', '', '');";
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_archives', $a_tables ) ) {
			// Create Report Archive Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'ArchiveId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Name', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'startDate', 'type' => 'varchar(1024)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'endDate', 'type' => 'varchar(1024)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'archiveDate', 'type' => 'varchar(1024)', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'filePath', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'ReportId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'UserGroupId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'primary' ]   = 'ArchiveId';
			$data[ 'keys' ][]    = array( 'name' => 'ReportId', 'columns' => 'ReportId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Graph Report Archive';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Archives', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_usergroups', $a_tables ) ) {
			// Create Report Archive Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'GroupId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Name', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'GroupId';
			$data[ 'keys' ][]    = array( 'name' => 'GroupId', 'columns' => 'GroupId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Archive User Group';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_UserGroups', $data );

			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_UserGroups` (`GroupId`, `Name`,`Description` ) VALUES (1, 'Default Group','Default Group');";
		}

		if ( !array_key_exists ( 'plugin_cereusreporting_queue', $a_tables ) ) {
			// Create Report Archive Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'run_script', 'type' => 'varchar(255)', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'script_params', 'type' => 'text', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'completed', 'type' => 'int(1)', 'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'inserted_datetime', 'type' => 'datetime', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'completed_datetime', 'type' => 'datetime', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'task_hash', 'type' => 'varchar(64)', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'method', 'type' => "enum('POST','GET')", 'NULL' => FALSE );
			$data[ 'primary' ]   = 'id';
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Task Queue';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_CereusReporting_queue', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_usergrouplist', $a_tables ) ) {
			// Create Report Archive Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'GroupListId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'UserId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'UserGroupId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'primary' ]   = 'GroupListId';
			$data[ 'keys' ][]    = array( 'name' => 'GroupListId', 'columns' => 'GroupListId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Archive User Group List';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_UserGroupList', $data );

			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_UserGroupList` (`GroupListId`, `UserId`,`UserGroupId` ) VALUES (1, 1, 1);";

		}

		// Table for the "Read Report" List
		if ( !array_key_exists ( 'plugin_nmidcreatepdf_userreportlist', $a_tables ) ) {
			// Create Report Archive Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name'     => 'ReportReadId', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'UserId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'ArchiveId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'Status', 'type' => 'varchar(5)', 'NULL' => FALSE,
			                              'default' => '2' );
			$data[ 'primary' ]   = 'ReportReadId';
			$data[ 'keys' ][]    = array( 'name' => 'ReportReadId', 'columns' => 'ReportReadId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Archive User Report List';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_UserReportList', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_usergroupreports', $a_tables ) ) {
			// Create Report Archive Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name'     => 'GroupReportId', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'ArchiveId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'UserGroupId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'default' => '0' );
			$data[ 'primary' ]   = 'GroupReportId';
			$data[ 'keys' ][]    = array( 'name' => 'GroupReportId', 'columns' => 'GroupReportId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Archive Report Group List';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_UserGroupReports', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_graphreports', $a_tables ) ) {
			// Create Graph Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'ReportId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'local_graph_id', 'type' => 'mediumint(25)', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'order', 'type' => 'mediumint(25)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'group', 'type' => 'mediumint(25)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'keys' ][]    = array( 'name' => 'ReportId', 'columns' => 'ReportId' );
			$data[ 'comment' ]   = 'CereusReporting Graph Reports';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_GraphReports', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_multigraphreports', $a_tables ) ) {
			// Create MultiGraph Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'ReportId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'order', 'type' => 'mediumint(25)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'data', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'type', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'ReportId', 'columns' => 'ReportId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting MultiGraph Reports';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_MultiGraphReports', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_dsstatsreports', $a_tables ) ) {
			// Create DSStats Graph Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'ReportId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'DSStatsGraph', 'type' => 'text', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'order', 'type' => 'mediumint(25)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'group', 'type' => 'mediumint(25)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'ReportId', 'columns' => 'ReportId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting DSStats Reports';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_DSStatsReports', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_reports_types', $a_tables ) ) {
			// Create Report type
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'TypeId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'timeInSeconds', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'TypeId';
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'keys' ][]    = array( 'name' => 'timeInSeconds', 'columns' => 'timeInSeconds' );
			$data[ 'comment' ]   = 'CereusReporting Report Types';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Reports_Types', $data );

			// Add Default Report types
			// special report types
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('0','On Demand');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('-1','1 Month');"; // calculated based on the month days
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('-2','1 Year');"; // calculated on the number of days of that year
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('-3', 'Yesterday');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('-4', 'Last Week');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('-5', 'Last Month');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('-6', 'Last Year');";
			// normal report types
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('3600','1 Hour');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('7200','2 Hour');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('14400','4 Hour');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('21600','6 Hour');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('43200','12 Hour');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('86400','1 Day');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('172800','2 Days');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('259200','3 Days');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Reports_Types` (`timeInSeconds`, `Description`) VALUES ('604800','1 Week');";
		}

		if ( !array_key_exists ( 'plugin_cereusavailability_table', $a_tables ) ) {
			//
			// Create Availability Report
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'deviceId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'     => 'orig_total_polls', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'     => 'orig_failed_polls', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'total_polls', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'     => 'failed_polls', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE );
			$data[ 'keys' ][]    = array( 'name' => 'deviceId', 'columns' => 'deviceId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Report Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_CereusAvailability_Table', $data );
		}


		if ( !array_key_exists ( 'plugin_nmidcreatepdf_availability_table', $a_tables ) ) {
			//
			// Create Availability Report
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'deviceId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'typeId', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => 'i' );
			$data[ 'columns' ][] = array( 'name'    => 'timeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'     => 'orig_total_polls', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'     => 'orig_failed_polls', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned', 'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'total_polls', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'     => 'failed_polls', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'isLastEntry', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'isAggregated', 'type' => 'varchar(2)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'keys' ][]    = array( 'name' => 'deviceId', 'columns' => 'deviceId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Report Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Availability_Table', $data );
			$sql[] = "ALTER TABLE `plugin_nmidCreatePDF_Availability_Table` ADD INDEX `timeStamp` (`timeStamp`), ADD INDEX `deviceSelection` (`deviceId`, `typeId`)";
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_availabilityfailedpolls_table', $a_tables ) ) {
			//
			// Create Availability Report
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'deviceId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'timeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'     => 'failed_polls', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE );
			$data[ 'columns' ][] = array( 'name' => 'ldid', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'keys' ][]    = array( 'name' => 'deviceId', 'columns' => 'deviceId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Failed Polls Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_AvailabilityFailedPolls_Table', $data );
			$sql[] = "ALTER TABLE `plugin_nmidCreatePDF_AvailabilityFailedPolls_Table` ADD INDEX `timeStamp` (`timeStamp`), ADD INDEX `ldid` (`ldid`)";
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_availability_change_table', $a_tables ) ) {
			//
			// Create Availability Change Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'deviceId', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'changeTypeId', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '1' );
			$data[ 'columns' ][] = array( 'name'    => 'startTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'endTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'shortDescription', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'longDescription', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'deviceId', 'columns' => 'deviceId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Change Report Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Availability_Change_Table', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_availability_slatimeframe_table', $a_tables ) ) {
			//
			// Create Availability Change Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'defaultDays', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '1,2,3,4,5' ); // Mon-Fri
			$data[ 'columns' ][] = array( 'name'    => 'defaultStartTime', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '08:00' ); // 08:00
			$data[ 'columns' ][] = array( 'name'    => 'defaultEndTime', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '18:00' ); // 18:00
			$data[ 'columns' ][] = array( 'name' => 'shortDescription', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'longDescription', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability SLA TimeFrame Master Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Availability_SLATimeFrame_Table', $data );

			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table` (`id`, `defaultDays`,`defaultStartTime`,`defaultEndTime`,`shortDescription`,`longDescription` ) " .
				"VALUES (1,'Mon,Tue,Wed,Thu,Fri,Sat,Sun','00:00','23:59','24x7','Default SLA TimeFrame');";
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_availability_slatimeframeitems_table', $a_tables ) ) {
			//
			// Create Availability Change Report Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name'     => 'slaTimeFrameId', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'slaEnabled', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'startTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'endTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'slaTimeFrameId', 'columns' => 'slaTimeFrameId' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability SLA TimeFrame Items Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Availability_SLATimeFrameItems_Table', $data );
		}

		if ( !array_key_exists ( 'plugin_nmidcreatepdf_availability_change_type', $a_tables ) ) {
			//
			// Create Availability Change Type Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name'    => 'shortName', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'decreaseAvailability', 'type' => 'varchar(255)',
			                              'NULL'    => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'Description', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'shortName', 'columns' => 'shortName' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Change Type Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_nmidCreatePDF_Availability_Change_Type', $data );

			// Add default entries
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Availability_Change_Type` (`shortName`,`Description`,`decreaseAvailability` ) VALUES ('planned','Planned downtime','0');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Availability_Change_Type` (`shortName`,`Description`,`decreaseAvailability` ) VALUES ('emergency','Emmergency downtime','1');";
			$sql[] = "INSERT INTO `plugin_nmidCreatePDF_Availability_Change_Type` (`shortName`,`Description`,`decreaseAvailability` ) VALUES ('other','Unplanned downtime','1');";
		}

		// CRC-3 Add Thold based availability charts
		if ( !array_key_exists ( 'plugin_cereusreporting_availability_hostdown', $a_tables ) ) {
			//
			// Create Availability Host Down Table
			// This table stores host down events for availability reporting on host down status.
			//
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name' => 'hostid', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'startTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'endTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'hostid', 'columns' => 'hostid' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Host Down Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_CereusReporting_Availability_HostDown', $data );
		}

		// CRC-3 Add Thold based availability charts
		if ( !array_key_exists ( 'plugin_cereusreporting_availability_thold_reportitems', $a_tables ) ) {
			//
			// Create Availability Thold Report Items Table
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name'     => 'thold_Data_id', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'thold_Data_id', 'columns' => 'thold_Data_id' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Thold Report Items Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_CereusReporting_Availability_Thold_ReportItems', $data );
		}

		// CRC-3 Add Thold based availability charts
		if ( !array_key_exists ( 'plugin_cereusreporting_availability_thold_datalog_exceptions', $a_tables ) ) {
			//
			// Create Availability Host Down Table
			// This table stores host down events for availability reporting on host down status.
			$data                = array();
			$data[ 'columns' ][] = array( 'name' => 'Id', 'type' => 'mediumint(25)', 'unsigned' => 'unsigned',
			                              'NULL' => FALSE, 'auto_increment' => TRUE );
			$data[ 'columns' ][] = array( 'name'     => 'thold_data_id', 'type' => 'mediumint(25)',
			                              'unsigned' => 'unsigned',
			                              'NULL'     => FALSE );
			$data[ 'columns' ][] = array( 'name'    => 'startTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'endTimeStamp', 'type' => 'varchar(255)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name'    => 'is_sla_relevant', 'type' => 'varchar(2)', 'NULL' => FALSE,
			                              'default' => '0' );
			$data[ 'columns' ][] = array( 'name' => 'comment', 'type' => 'text', 'NULL' => TRUE );
			$data[ 'primary' ]   = 'Id';
			$data[ 'keys' ][]    = array( 'name' => 'thold_data_id', 'columns' => 'thold_data_id' );
			$data[ 'type' ]      = 'InnoDB';
			$data[ 'comment' ]   = 'CereusReporting Availability Thold Data Log Exceptions Table';
			api_plugin_db_table_create( 'CereusReporting', 'plugin_CereusReporting_Availability_Thold_DataLog_Exceptions', $data );
		}

		# now run all SQL commands
		if ( !empty( $sql ) ) {
			for ( $a = 0;
			      $a < count( $sql );
			      $a++ ) {
				$result = db_execute( $sql[ $a ] );
			}
		}
	}