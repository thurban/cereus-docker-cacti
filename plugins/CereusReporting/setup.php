<?php
	/*******************************************************************************
	 * File:         $Id: setup.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 *
	 *******************************************************************************/

	// Clean up the code
	include( __DIR__.'/include/setup_graph_api.php' );
	include( __DIR__.'/include/setup_device_api.php' );
	include( __DIR__.'/include/setup_database.php' );
	if ( function_exists('top_header')) {
		include_once( __DIR__.'/include/functions_cacti_1.0.0.php' );
	} else {
		include_once( __DIR__.'/include/functions_cacti_0.8.php' );
	}

	function plugin_CereusReporting_install()
	{
		$nmidCreatePDF = db_fetch_cell( "SELECT version FROM plugin_config WHERE directory='nmidCreatePDF'" );
		if ( $nmidCreatePDF > 0 ) {
			CereusReporting_update_nmidCreatePDF();
		}
		else {
			api_plugin_register_hook( 'CereusReporting', 'draw_navigation_text', 'CereusReporting_draw_navigation_text', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'poller_bottom', 'CereusReporting_check_scheduledReports', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'top_header_tabs', 'CereusReporting_show_tab', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'top_graph_header_tabs', 'CereusReporting_show_tab', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'config_arrays', 'CereusReporting_config_arrays', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'config_settings', 'CereusReporting_config_settings', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'config_form', 'CereusReporting_config_form', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'console_after', 'CereusReporting_console_after', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'tree_after', 'CereusReporting_tree_after', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'page_buttons', 'CereusReporting_page_buttons', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'graph_buttons', 'CereusReporting_graph_buttons', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'graph_buttons_thumbnails', 'CereusReporting_graph_buttons', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'tree_view_page_end', 'CereusReporting_tree_view_page_end', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'api_device_save', 'CereusReporting_api_device_save', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'graph_tree_page_buttons', 'CereusReporting_page_buttons', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'graph_tree_page_header_buttons', 'CereusReporting_page_buttons', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'weathermap_page_selection_top', 'CereusReporting_printWeathermapButton', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'device_action_array', 'plugin_CereusReporting_device_action_array', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'device_action_prepare', 'plugin_CereusReporting_device_action_prepare', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'device_action_execute', 'plugin_CereusReporting_device_action_execute', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'graph_action_array', 'plugin_CereusReporting_graph_action_array', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'graph_action_prepare', 'plugin_CereusReporting_graph_action_prepare', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'graph_action_execute', 'plugin_CereusReporting_graph_action_execute', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'top_graph_jquery_function', 'plugin_CereusReporting_top_graph_jquery_function', 'setup.php' );
			api_plugin_register_realm( 'CereusReporting', 'CereusReporting_ReportTemplates.php,CereusReporting_ReportTemplates_Add.php,CereusReporting_orderDSStatsGraphs.php,CereusReporting_addDSStatsReport.php,addToDSStatsReport.php,CereusReporting_createTemplateReport.php,CereusReporting_orderGraphs.php,CereusReporting_scheduler.php,CereusReporting_addScheduledReport.php,addToGraphReport.php,CereusReporting_Reports.php,CereusReporting_addReport.php,CereusReporting_addMultiReport.php,CereusReporting_orderMultiGraphs.php,CereusReporting_Backup.php,CereusReporting_support.php,CereusReporting_debug.php,CereusReporting_ArchiveUserGroups.php,CereusReporting_ArchiveUserGroups_Add.php,CereusReporting_ArchiveUserGroups_AddUser.php,CereusReporting_addWeathermapReport.php,CereusReporting_addTholdReport.php,CereusReporting_addTholdSumReport.php,CereusReporting_addAvailSumReport.php,CereusReporting_addReportTemplate.php,CereusReporting_managePDFFiles.php', 'Plugin -> CereusReporting - Define PDF Reports', 1195 );
			api_plugin_register_realm( 'CereusReporting', 'CereusReporting_doArchiveReport.php,CereusReporting_Archive.php,CereusReporting_GenerateReports.php,CereusReporting_GenerateReport_now.php', 'Plugin -> CereusReporting - Generate PDF Reports', 1196 );
			api_plugin_register_realm( 'CereusReporting', 'CereusReporting_Availability_DowntimeSchedule.php,CereusReporting_Availability_addDowntimeSchedule.php,CereusReporting_Availability_SLATimeFrame.php,CereusReporting_Availability_addSLATimeFrame.php,CereusReporting_Availability_addSLATimeFrameItem.php,CereusReporting_Availability_SLATimeFrameItem.php,CereusReporting_Availability_TholdSlaExceptions.php,CereusReporting_Availability_addTholdSlaExceptions.php', 'Plugin -> CereusReporting - Manage Availability Data', 1197 );
			api_plugin_register_realm( 'CereusReporting', 'CereusReporting_AvailabilityChart.php', 'Plugin -> CereusReporting - View Availability Charts', 1199 );
			api_plugin_register_realm( 'CereusReporting', 'CereusReporting_userArchive.php', 'Plugin -> CereusReporting - View Archived Reports', 1200 );
			CereusReporting_setup_table_new();
		}
	}



	function CereusReporting_draw_navigation_text( $nav )
	{

		$nav[ "CereusReporting_Reports.php:" ]          = array( "title"   =>  'CereusReporting Reports',
		                                                         "mapping" => "index.php:",
		                                                         "url"     => "CereusReporting_Reports.php",
		                                                         "level"   => "1" );
		$nav[ "CereusReporting_Reports.php:actions" ] = array( "title"   => 'CereusReporting Reports',
		                                                       "mapping" => "index.php:",
		                                                       "url"     => "CereusReporting_Reports.php",
		                                                       "level"   => "2" );
		$nav[ "CereusReporting_addReport.php:" ]        = array( "title"   =>  'CereusReporting Report',
		                                                         "mapping" => "index.php:,?",
		                                                         "url"     => "CereusReporting_addReport.php",
		                                                         "level"   => "1" );
		$nav[ "CereusReporting_addReport.php:add" ]     = array( "title" =>  '(Add)', "mapping" => "index.php:,?",
		                                                         "url"   => "CereusReporting_addReport.php",
		                                                         "level" => "2" );
		$nav[ "CereusReporting_addReport.php:update" ]  = array( "title" =>  '(Edit)', "mapping" => "index.php:,?",
		                                                         "url"   => "CereusReporting_addReport.php",
		                                                         "level" => "2" );
		$nav[ "CereusReporting_addReport.php:actions" ] = array( "title"   =>  '(Actions)',
		                                                         "mapping" => "index.php:,?",
		                                                         "url"     => "CereusReporting_addReport.php",
		                                                         "level"   => "2" );

		// Report Templates
        $nav[ "CereusReporting_ReportTemplates.php:" ]          = array( "title"   =>  'CereusReporting Report Templates',
		                                                         "mapping" => "index.php:",
		                                                         "url"     => "CereusReporting_ReportTemplates.php",
		                                                         "level"   => "1" );
		$nav[ "CereusReporting_ReportTemplates.php:actions" ] = array( "title"   => 'CereusReporting Report Templates',
		                                                       "mapping" => "index.php:",
		                                                       "url"     => "CereusReporting_ReportTemplates.php",
		                                                       "level"   => "2" );
		$nav[ "CereusReporting_addReportTemplate.php:" ]        = array( "title"   =>  'CereusReporting Report Template',
		                                                         "mapping" => "index.php:,?",
		                                                         "url"     => "CereusReporting_addReportTemplate.php",
		                                                         "level"   => "2" );
		$nav[ "CereusReporting_addReportTemplate.php:add" ]     = array( "title" =>  '(Add)', "mapping" => "index.php:,?",
		                                                         "url"   => "CereusReporting_addReportTemplate.php",
		                                                         "level" => "2" );
		$nav[ "CereusReporting_addReportTemplate.php:update" ]  = array( "title" =>  '(Edit)', "mapping" => "index.php:,?",
		                                                         "url"   => "CereusReporting_addReportTemplate.php",
		                                                         "level" => "2" );
		$nav[ "CereusReporting_addReportTemplate.php:actions" ] = array( "title"   =>  '(Actions)',
		                                                         "mapping" => "index.php:,?",
		                                                         "url"     => "CereusReporting_addReportTemplate.php",
		                                                         "level"   => "2" );

		$nav[ "CereusReporting_managePDFFiles.php:" ]          = array( "title"   =>  'Manage CereusReporting External PDF Files',
		                                                                 "mapping" => "index.php:",
		                                                                 "url"     => "CereusReporting_managePDFFiles.php",
		                                                                 "level"   => "1" );

		// Report Scheduler
		$nav[ "CereusReporting_scheduler.php:" ]                = array( "title"   =>  'CereusReporting Report Schedules',
		                                                                 "mapping" => "index.php:",
		                                                                 "url"     => "CereusReporting_scheduler.php",
		                                                                 "level"   => "1" );
		$nav[ "CereusReporting_addScheduledReport.php:add" ]    = array( "title"   =>  '(Add)',
		                                                                 "mapping" => "index.php:,?",
		                                                                 "url"     => "CereusReporting_addScheduledReport.php",
		                                                                 "level"   => "2" );
		$nav[ "CereusReporting_addScheduledReport.php:update" ] = array( "title"   =>  '(Edit)',
		                                                                 "mapping" => "index.php:,?",
		                                                                 "url"     => "CereusReporting_addScheduledReport.php",
		                                                                 "level"   => "2" );

		// Report Generation
		$nav[ "CereusReporting_GenerateReports.php:" ]    = array( "title"   =>  'CereusReporting Generate Report',
		                                                           "mapping" => "index.php:",
		                                                           "url"     => "CereusReporting_GenerateReports.php",
		                                                           "level"   => "1" );
		$nav[ "CereusReporting_GenerateReport_now.php:" ] = array( "title"   =>  'CereusReporting Generate Report Now',
		                                                           "mapping" => "index.php:,?",
		                                                           "url"     => "CereusReporting_GenerateReport_now",
		                                                           "level"   => "2" );

		$nav[ "CereusReporting_createTemplateReport.php:" ] = array( "title"   =>  'Create new Template Report',
		                                                             "mapping" => "index.php:,?",
		                                                             "url"     => "CereusReporting_createTemplateReport.php",
		                                                             "level"   => "1" );

		// Archiving
		$nav[ "CereusReporting_Archive.php:" ]                 = array( "title"   =>  'CereusReporting Archive',
		                                                                "mapping" => "index.php:",
		                                                                "url"     => "CereusReporting_Archive.php",
		                                                                "level"   => "1" );
		$nav[ "CereusReporting_doArchiveReport.php:view" ]     = array( "title"   =>  '(View)',
		                                                                "mapping" => "index.php:,?",
		                                                                "url"     => "CereusReporting_doArchiveReport.php",
		                                                                "level"   => "2" );
		$nav[ "CereusReporting_doArchiveReport.php:download" ] = array( "title"   =>  '(Download)',
		                                                                "mapping" => "index.php:,?",
		                                                                "url"     => "CereusReporting_doArchiveReport.php",
		                                                                "level"   => "2" );

		// DSStats Support
		$nav[ "CereusReporting_addDSStatsReport.php:" ]    = array( "title"   =>  'DSStats Reports',
		                                                            "mapping" => "index.php:",
		                                                            "url"     => "CereusReporting_addDSStatsReport.php",
		                                                            "level"   => "2" );
		$nav[ "CereusReporting_addDSStatsReport.php:add" ] = array( "title"   =>  'DSStats Reports',
		                                                            "mapping" => "index.php:,?",
		                                                            "url"     => "CereusReporting_addDSStatsReport.php",
		                                                            "level"   => "2" );

		$nav[ "CereusReporting_addAvailSumReport.php:" ]    = array( "title"   =>  'Add Availability Summary Report',
		                                                            "mapping" => "index.php:",
		                                                            "url"     => "CereusReporting_addAvailSumReport.php",
		                                                            "level"   => "1" );

		// Multi Report
		$nav[ "CereusReporting_addMultiReport.php:" ]        = array( "title"   =>  'Multi Reports',
		                                                              "mapping" => "index.php:",
		                                                              "url"     => "CereusReporting_addMultiReport.php",
		                                                              "level"   => "1" );
		$nav[ "CereusReporting_addMultiReport.php:edit" ]    = array( "title"   =>  '(Edit)',
		                                                              "mapping" => "index.php:,?",
		                                                              "url"     => "CereusReporting_addMultiReport.php",
		                                                              "level"   => "2" );
		$nav[ "CereusReporting_addMultiReport.php:add" ]     = array( "title"   =>  '(Add)',
		                                                              "mapping" => "index.php:,?",
		                                                              "url"     => "CereusReporting_addMultiReport.php",
		                                                              "level"   => "2" );
		$nav[ "CereusReporting_addMultiReport.php:refresh" ] = array( "title"   =>  '(Add)',
		                                                              "mapping" => "index.php:,?",
		                                                              "url"     => "CereusReporting_addMultiReport.php",
		                                                              "level"   => "2" );
		$nav[ "CereusReporting_addWeathermapReport.php:" ]   = array( "title"   =>  '(Add Weathermap)',
		                                                              "mapping" => "index.php:,?",
		                                                              "url"     => "CereusReporting_addMultiReport.php",
		                                                              "level"   => "1" );



		// Support Page - CRC-17
		$nav[ "CereusReporting_debug.php:" ]   = array( "title" =>  'Support Page', "mapping" => "index.php:",
		                                                "url"   => "CereusReporting_debug.php", "level" => "1" );
		$nav[ "CereusReporting_support.php:" ] = array( "title" =>  'Support Page', "mapping" => "index.php:",
		                                                "url"   => "CereusReporting_support.php", "level" => "1" );

		// Backup
		$nav[ "CereusReporting_Backup.php:" ]    = array( "title" =>  'Backup Reports', "mapping" => "index.php:",
		                                                  "url"   => "CereusReporting_Backup.php", "level" => "1" );
		$nav[ "CereusReporting_Backup.php:add" ] = array( "title" =>  '(Add)', "mapping" => "index.php:,?",
		                                                  "url"   => "CereusReporting_Backup.php", "level" => "2" );

		// Availability Data
		$nav[ "CereusReporting_Availability_DowntimeSchedule.php:" ]          = array( "title"   =>  'Availability Data',
		                                                                               "mapping" => "index.php:",
		                                                                               "url"     => "CereusReporting_Availability_DowntimeSchedule.php",
		                                                                               "level"   => "1" );
		$nav[ "CereusReporting_Availability_addDowntimeSchedule.php:add" ]    = array( "title"   =>  '(Add)',
		                                                                               "mapping" => "index.php:,?",
		                                                                               "url"     => "CereusReporting_Availability_addDowntimeSchedule.php",
		                                                                               "level"   => "2" );
		$nav[ "CereusReporting_Availability_addDowntimeSchedule.php:update" ] = array( "title"   =>  '(Update)',
		                                                                               "mapping" => "index.php:,?",
		                                                                               "url"     => "CereusReporting_Availability_addDowntimeSchedule.php",
		                                                                               "level"   => "2" );

		$nav[ "CereusReporting_Availability_SLATimeFrame.php:" ]          = array( "title"   =>  'SLA TimeFrame Item Data',
		                                                                           "mapping" => "index.php:",
		                                                                           "url"     => "CereusReporting_Availability_SLATimeFrame.php",
		                                                                           "level"   => "1" );
		$nav[ "CereusReporting_Availability_addSLATimeFrame.php:add" ]    = array( "title"   =>  '(Add)',
		                                                                           "mapping" => "index.php:,?",
		                                                                           "url"     => "CereusReporting_Availability_addSLATimeFrame.php",
		                                                                           "level"   => "2" );
		$nav[ "CereusReporting_Availability_addSLATimeFrame.php:update" ] = array( "title"   =>  '(Update)',
		                                                                           "mapping" => "index.php:,?",
		                                                                           "url"     => "CereusReporting_Availability_addSLATimeFrame.php",
		                                                                           "level"   => "2" );

		$nav[ "CereusReporting_Availability_SLATimeFrameItem.php:" ]          = array( "title"   =>  'SLA TimeFrame Item Data',
		                                                                               "mapping" => "index.php:",
		                                                                               "url"     => "CereusReporting_Availability_SLATimeFrameItem.php",
		                                                                               "level"   => "1" );
		$nav[ "CereusReporting_Availability_addSLATimeFrameItem.php:add" ]    = array( "title"   =>  '(Add)',
		                                                                               "mapping" => "index.php:,?",
		                                                                               "url"     => "CereusReporting_Availability_addSLATimeFrameItem.php",
		                                                                               "level"   => "2" );
		$nav[ "CereusReporting_Availability_addSLATimeFrameItem.php:update" ] = array( "title"   =>  '(Update)',
		                                                                               "mapping" => "index.php:,?",
		                                                                               "url"     => "CereusReporting_Availability_addSLATimeFrameItem.php",
		                                                                               "level"   => "2" );

		$nav[ "CereusReporting_Availability_TholdSlaExceptions.php:" ]          = array( "title"   =>  'Thold SLA Exception Data',
		                                                                                 "mapping" => "index.php:",
		                                                                                 "url"     => "CereusReporting_Availability_TholdSlaExceptions.php",
		                                                                                 "level"   => "1" );
		$nav[ "CereusReporting_Availability_addTholdSlaExceptions.php:add" ]    = array( "title"   =>  '(Add)',
		                                                                                 "mapping" => "index.php:,?",
		                                                                                 "url"     => "CereusReporting_Availability_addTholdSlaExceptions.php",
		                                                                                 "level"   => "2" );
		$nav[ "CereusReporting_Availability_addTholdSlaExceptions.php:update" ] = array( "title"   =>  '(Update)',
		                                                                                 "mapping" => "index.php:,?",
		                                                                                 "url"     => "CereusReporting_Availability_addTholdSlaExceptions.php",
		                                                                                 "level"   => "2" );

		// User Groups
		$nav[ "CereusReporting_ArchiveUserGroups.php:" ]            = array( "title"   =>  'User Groups',
		                                                                     "mapping" => "index.php:,?",
		                                                                     "url"     => "CereusReporting_ArchiveUserGroups.php",
		                                                                     "level"   => "1" );
		$nav[ "CereusReporting_ArchiveUserGroups_Add.php:add" ]     = array( "title"   =>  '(Add)',
		                                                                     "mapping" => "index.php:,?",
		                                                                     "url"     => "CereusReporting_ArchiveUserGroups.php",
		                                                                     "level"   => "2" );
		$nav[ "CereusReporting_ArchiveUserGroups_Add.php:update" ]  = array( "title"   =>  '(Edit)',
		                                                                     "mapping" => "index.php:,?",
		                                                                     "url"     => "CereusReporting_ArchiveUserGroups.php",
		                                                                     "level"   => "2" );
		$nav[ "CereusReporting_ArchiveUserGroups_AddUser.php:add" ] = array( "title"   =>  '(Add User)',
		                                                                     "mapping" => "index.php:,?",
		                                                                     "url"     => "CereusReporting_ArchiveUserGroups.php",
		                                                                     "level"   => "3" );

		$nav[ "CereusReporting_userArchive.php:" ] = array( "title" =>  'User Archive', "mapping" => "index.php:,?",
		                                                    "url"   => "CereusReporting_userArchive.php",
		                                                    "level" => "1" );

		// Weathermap Reports

		return $nav;
	}

	function CereusReporting_printWeathermapButton( $args )
	{
		global $config;
		print "<td align=right><a href='" . $args[ 'mapId' ] . "'><img border=0 src='" . $config[ 'url_path' ] . "plugins/CereusReporting/images/PDF_file.png'></a></td>";
	}

	function CereusReporting_check_scheduledReports()
	{
        global $config, $database_default;
        include_once( __DIR__ . '/functions.php' );
		$phpBinary = readConfigOption( "path_php_binary" );

		// Check if the plugin_CereusAvailability_Table table exists first before proceeding:
        $s_sql = 'show tables from `' . $database_default . '`';
        $result = db_fetch_assoc( $s_sql );
        $a_tables = array();
        $sql = array();

        foreach ( $result as $index => $array ) {
            foreach ( $array as $table ) {
                $a_tables[strtolower($table)] = strtolower($table);
            }
        }
        if ( array_key_exists ( 'plugin_cereusavailability_table', $a_tables ) ) {
            // START Availability Report Data Generation
            if ( read_config_option( 'nmid_avail_winservice_enabled' ) ) {
                if ( readPluginStatus( 'storeLastPoll' ) ) {
                    CereusReporting_process_service_states();
                }
            }
            if ( read_config_option( 'nmid_avail_enabled' ) ) {
                CereusReporting_process_polls();
            }
            // END Availability Report Data Generation
            if ( read_config_option( 'nmid_pdfscheduletype' ) == 'cron' ) {
                // Cron scheduler is being used ...
            } else {
                chdir( __DIR__ );
                include_once( __DIR__ . '/functions.php' );

                CereusReporting_logger( 'Starting Scheduled Report Check', "info", "scheduler" );

                exec_background( $phpBinary, __DIR__ . '/cron_pdf_scheduler.php' );

                CereusReporting_logger( 'Scheduled Report Check Finished', "info", "scheduler" );
            }
        }
	}

	function CereusReporting_show_tab()
	{
		global $config, $user_auth_realms, $user_auth_realm_filenames;

		$dir = dirname( __FILE__ );
		include_once( $dir . '/functions.php' );
		$cp = '';
		if ( isset( $user_auth_realm_filenames[basename( 'CereusReporting_GenerateReports.php' )] ) ) {
			$realm_id2 = $user_auth_realm_filenames[basename( 'CereusReporting_GenerateReports.php' )];
		}
		$class         = 'light-blue';
		if ( api_user_realm_auth( 'CereusReporting_userArchive.php' ) ) {
			if ( basename( $_SERVER[ 'PHP_SELF' ] ) == 'CereusReporting_userArchive.php' ) {
				$cp = TRUE;
				$class = 'blue';
			}
            print '<a href="' . $config[ 'url_path' ] . 'plugins/CereusReporting/CereusReporting_userArchive.php"><img src="' . $config[ 'url_path' ] . 'plugins/CereusReporting/images/tab_CereusReporting' . ( $cp ? '_red' : '' ) . '.gif" alt="CereusReporting" align="absmiddle" border="0"></a>';

		}
	}

	function CereusReporting_config_form()
	{
		global $fields_tree_edit, $fields_host_edit;
		$dir = dirname( __FILE__ );
		include_once( $dir . '/functions.php' );

		$a_CereusReporting_cover_page       = array();
		$a_CereusReporting_cover_page[ '' ] = 'None';

		$a_ReportTemplates = db_fetch_assoc("SELECT
				  `plugin_CereusReporting_Reports_templates`.`name` AS `name`,
				  `plugin_CereusReporting_Reports_templates`.`templateId` AS `id`
				FROM
				  `plugin_CereusReporting_Reports_templates`
                ORDER BY 
                  `plugin_CereusReporting_Reports_templates`.`name`");


		if (is_array($a_ReportTemplates)) {
			foreach ( $a_ReportTemplates as $a_templates ) {
				$a_CereusReporting_cover_page[ $a_templates[ 'id' ] ] = $a_templates[ 'name' ];
			}
		}

		$fileCount                          = 1;
		$a_CereusReporting_cover_logo       = array();
		$a_CereusReporting_cover_logo[ '' ] = 'None';
		$a_templates                        = array();
		$dirFiles                           = array();

		if ( is_dir( $dir . '/images/' ) ) {
			if ( $dh = opendir( $dir . '/images/' ) ) {
				while ( ( $file = readdir( $dh ) ) !== FALSE ) {
					if ( !( is_dir( $file ) ) ) {
						if ( file_exists( $dir . '/images/' . $file ) ) {
							$dirFiles[ ] = $file;
						}
					}
				}
			}
		}
		sort( $dirFiles );
		foreach ( $dirFiles as $file ) {
			if ( preg_match( "/([^.]+)_logo\..*$/i", $file, $matchme ) ) {
				$templateName = 'images/' . $file;
				if ( in_array( $templateName, $a_templates ) == FALSE ) {
					$a_templates[ $templateName ]                  = $templateName;
					$a_CereusReporting_cover_logo[ $templateName ] = $templateName;
					$fileCount++;
				}
			}
		}

        /* only needed for the Pro and Corporate Edition */
        $temp = array(
            "CereusReporting_cover_page" => array(
                "method"        => "drop_array",
                "friendly_name" =>  'PDF Cover Page',
                "description"   =>  'PDF File to add as cover page for this tree.',
                "value"         => "|arg1:CereusReporting_cover_page|",
                "array"         => $a_CereusReporting_cover_page,
            ),
            "CereusReporting_cover_logo" => array(
                "method"        => "drop_array",
                "friendly_name" =>  'PDF Cover Logo',
                "description"   =>  'Image file to use for the logo.',
                "value"         => "|arg1:CereusReporting_cover_logo|",
                "array"         => $a_CereusReporting_cover_logo,
            )
        );

        if ( isset( $fields_tree_edit ) ) {
            $fields_tree_edit = array_merge( $fields_tree_edit, $temp );
        }
        else {
            $fields_tree_edit = $temp;
        }

        $sql    = "SELECT
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`
        FROM
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`";
        $result = db_fetch_assoc( $sql );

        $ar_slaTimeFrames        = array();
        $ar_slaTimeFrames[ "0" ] = "none";
        foreach ( $result as $row ) {
            $ar_slaTimeFrames[ $row[ 'Id' ] ] = $row[ 'shortDescription' ];
        }

        $fields_host_edit2 = $fields_host_edit;
        $fields_host_edit3 = array();
        foreach ( $fields_host_edit2 as $f => $a ) {
            $fields_host_edit3[ $f ] = $a;
            if ( $f == 'disabled' ) {
                $fields_host_edit3[ "nmid_host_sla" ]           = array(
                    "method"        => "textbox",
                    "friendly_name" =>  'Host SLA',
                    "description"   =>  'HOST SLA for the Availability Report.',
                    "value"         => "|arg1:nmid_host_sla|",
                    "max_length"    => "10",
                    "form_id"       => FALSE
                );
                $fields_host_edit3[ "nmid_host_sla_timeframe" ] = array(
                    "method"        => "drop_array",
                    "friendly_name" =>  'Host SLA TimeFrame',
                    "description"   =>  'HOST SLA TimeFrame for the Availability Report.',
                    "value"         => "|arg1:nmid_host_sla_timeframe|",
                    "max_length"    => "10",
                    "array"         => $ar_slaTimeFrames,
                    "form_id"       => FALSE
                );
            }
        }
        $fields_host_edit = $fields_host_edit3;
	}

	function CereusReporting_api_device_save( $save )
	{
		$dir = dirname( __FILE__ );
		include_once( $dir . '/functions.php' );
        if ( isset( $_POST[ 'nmid_host_sla' ] ) ) {
            $save[ "nmid_host_sla" ] = form_input_validate( $_POST[ 'nmid_host_sla' ], "nmid_host_sla", "", TRUE, 10 );
        }
        else {
            $save[ 'nmid_host_sla' ] = form_input_validate( '', "nmid_host_sla", "", TRUE, 10 );
        }
        if ( isset( $_POST[ 'nmid_host_sla_timeframe' ] ) ) {
            $save[ "nmid_host_sla_timeframe" ] = form_input_validate( $_POST[ 'nmid_host_sla_timeframe' ], "nmid_host_sla_timeframe", "", TRUE, 10 );
        }
        else {
            $save[ 'nmid_host_sla_timeframe' ] = form_input_validate( '', "nmid_host_sla_timeframe", "", TRUE, 10 );
        }
		return $save;
	}

	function CereusReporting_graph_buttons( $args )
	{
	    // TODO: Check for compatibility with Cacti 1.0
		global $config;
		if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
			$local_graph_id = $args[ 1 ][ 'local_graph_id' ];
			$checked = '';
            if ($_REQUEST['action'] == 'preview') {
                $checked = 'checked';
            }
            if ( $args[ 1 ][ 'rra' ] == "all" ) {
                echo '<input type=checkbox '.$checked.' onClick="setData(\'lgi_' . $local_graph_id . '\');"  id="lgi_' . $local_graph_id . '" name="lgi_' . $local_graph_id . '" value="' . $local_graph_id . '"><br>' . "\n";
                if ($_REQUEST['action'] == 'preview') {
                    echo '<script>setData(\'lgi_' . $local_graph_id . '\');</script>';
                }
            }
            else {
                echo '<input disabled type=checkbox ' . $checked . ' checked id="lgi_' . $local_graph_id . '" name="lgi_' . $local_graph_id . '" value="' . $local_graph_id . '"><br>' . "\n";
                if ( $_REQUEST[ 'action' ] == 'preview' ) {
                    echo '<script>setData(\'lgi_' . $local_graph_id . '\');</script>';
                }
            }
		}
	}

	function CereusReporting_tree_view_page_end( $args )
	{
		global $config;
		if ( api_user_realm_auth( 'CereusReporting_GenerateReports.php' ) ) {
			$mainUrl = $_SERVER[ 'PHP_SELF' ];
			if ( preg_match( "/graph.php/i", $mainUrl ) ) {
				echo '<tr>';
			}
			//echo "</form>\n";
			if ( preg_match( "/graph.php/i", $mainUrl ) ) {
				echo '</tr>';
			}
		}
	}

	function plugin_CereusReporting_uninstall()
	{
		// Do any extra Uninstall stuff here
        return true;
	}

	function plugin_CereusReporting_top_graph_jquery_function()
	{
		?>
		$( "#nmidDialog" ).dialog({
		autoOpen: false
		});

		$( "#nmidOpener" ).click(function() {
		$( "#nmidDialog" ).dialog( "open" );
		return false;
		});

		$( "#nmidCreateDialog" ).dialog({
		autoOpen: false
		});

		$( "#nmidCreateOpener" ).click(function() {
		$( "#nmidCreateDialog" ).dialog( "open" );
		return false;
		});
		<?php
		return;
	}

	function CereusReporting_page_header_buttons( $my_args )
	{
		CereusReporting_page_buttons( $my_args );
	}

	function CereusReporting_page_buttons( $my_args )
	{
		CereusReporting_page_buttons_compat( $my_args );
		// return $my_args;
	}

	function plugin_CereusReporting_check_config()
	{
		// Here we will check to ensure everything is configured
		CereusReporting_check_upgrade();

		return TRUE;
	}

	function plugin_CereusReporting_upgrade()
	{
		// Here we will upgrade to the newest version
		CereusReporting_check_upgrade();
		return TRUE;
	}

	function plugin_CereusReporting_version()
	{
		return CereusReporting_version();
	}

	function CereusReporting_check_upgrade()
	{
		// We will only run this on pages which really need that data ...
		$files = array( 'CereusReporting_orderGraphs.php,CereusReporting_scheduler.php,CereusReporting_addScheduledReport.php,addToGraphReport.php,CereusReporting_Reports.php,CereusReporting_addReport.php' );
		if ( isset( $_SERVER[ 'PHP_SELF' ] ) && !in_array( basename( $_SERVER[ 'PHP_SELF' ] ), $files ) ) {
			return;
		}

		$nmidCreatePDF = db_fetch_cell( "SELECT version FROM plugin_config WHERE directory='nmidCreatePDF'" );
		if ( $nmidCreatePDF > 0 ) {
			CereusReporting_update_nmidCreatePDF();
		}

		$current = CereusReporting_version();
		$current = $current[ 'version' ];
		$old     = db_fetch_cell( "SELECT version FROM plugin_config WHERE directory='CereusReporting'" );
		if ( $current != $old ) {
			CereusReporting_setup_table( $old );
		}

	}

	function CereusReporting_update_nmidCreatePDF()
	{
		global $plugins, $config;
		// Change plugin hooks to CereusReporting
		db_execute( 'UPDATE plugin_hooks SET name = "CereusReporting" where name = "nmidCreatePDF"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_printWeathermapButton" where hook = "weathermap_page_selection_top" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_api_device_save" where hook = "api_device_save" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_page_buttons" where hook = "graph_tree_page_buttons" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_graph_buttons" where hook = "graph_buttons_thumbnails" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_tree_view_page_end" where hook = "tree_view_page_end" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_graph_buttons" where hook = "graph_buttons" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_tree_after" where hook = "tree_after" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_page_buttons" where hook = "page_buttons" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_console_after" where hook = "console_after" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_config_form" where hook = "config_form" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_config_settings" where hook = "config_settings" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_config_arrays" where hook = "config_arrays" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_show_tab" where hook = "top_graph_header_tabs" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_show_tab" where hook = "top_header_tabs" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_check_scheduledReports" where hook = "poller_bottom" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "CereusReporting_draw_navigation_text" where hook = "draw_navigation_text" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "plugin_CereusReporting_device_action_array" where hook = "device_action_array" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "plugin_CereusReporting_device_action_prepare" where hook = "device_action_prepare" AND name="CereusReporting"' );
		db_execute( 'UPDATE plugin_hooks SET function = "plugin_CereusReporting_device_action_execute" where hook = "device_action_execute" AND name="CereusReporting"' );

		db_execute( 'UPDATE plugin_db_changes SET plugin = "CereusReporting" where plugin = "nmidCreatePDF"' );
		db_execute( 'DELETE FROM plugin_config where directory = "nmidCreatePDF"' );
		db_execute( 'UPDATE plugin_realms SET plugin = "CereusReporting" where plugin = "nmidCreatePDF"' );

		db_execute( 'UPDATE plugin_realms SET file = "debug.php,CereusReporting_orderDSStatsGraphs.php,CereusReporting_addDSStatsReport.php,addToDSStatsReport.php,CereusReporting_createTemplateReport.php,CereusReporting_orderGraphs.php,CereusReporting_scheduler.php,CereusReporting_addScheduledReport.php,addToGraphReport.php,CereusReporting_Reports.php,CereusReporting_addReport.php,CereusReporting_addMultiReport.php,CereusReporting_orderMultiGraphs.php,CereusReporting_Backup.php,CereusReporting_support.php,CereusReporting_debug.php,CereusReporting_ArchiveUserGroups.php,CereusReporting_ArchiveUserGroups_Add.php,CereusReporting_ArchiveUserGroups_AddUser.php,CereusReporting_addWeathermapReport.php,CereusReporting_addTholdReport.php,CereusReporting_addTholdSumReport.php,CereusReporting_addAvailSumReport.php" where display = "NMID - Define PDF Reports"' );
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_Availability_DowntimeSchedule.php,CereusReporting_Availability_addDowntimeSchedule.php,CereusReporting_Availability_SLATimeFrame.php,CereusReporting_Availability_addSLATimeFrame.php,CereusReporting_Availability_addSLATimeFrameItem.php,CereusReporting_Availability_SLATimeFrameItem.php,CereusReporting_Availability_TholdSlaExceptions.php,CereusReporting_Availability_addTholdSlaExceptions.php,CereusReporting_Availability_TholdSlaExceptions.php" where display = "NMID - Manage Availability Data"' );
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_doArchiveReport.php,CereusReporting_Archive.php,CereusReporting_GenerateReports.php,CereusReporting_GenerateReport_now.php" where display = "NMID - Generate PDF Reports"' );
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_AvailabilityChart.php" where display = "NMID - View Availability Charts"' );
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_userArchive.php" where display = "NMID - View Archived Reports"' );

		db_execute( 'UPDATE plugin_realms SET display = "Plugin -> CereusReporting - Define PDF Reports" WHERE display = "NMID - Define PDF Reports"' );
		db_execute( 'UPDATE plugin_realms SET display = "Plugin -> CereusReporting - Generate PDF Reports" WHERE display = "NMID - Generate PDF Reports"' );
		db_execute( 'UPDATE plugin_realms SET display = "Plugin -> CereusReporting - Manage Availability Data" WHERE display = "NMID - Manage Availability Data"' );
		db_execute( 'UPDATE plugin_realms SET display = "Plugin -> CereusReporting - View Availability Charts" WHERE display = "NMID - View Availability Charts"' );
		db_execute( 'UPDATE plugin_realms SET display = "Plugin -> CereusReporting - View Archived Reports" WHERE display = "NMID - View Archived Reports"' );

		db_execute( 'DELETE FROM plugin_config WHERE directory = "nmidCreatePDF"' );
	}

	function CereusReporting_check_dependencies()
	{
		global $plugins, $config;

		// we need to check for the availability of the settings plugin
		if (
			( !in_array( 'settings', $plugins ) ) &&
			( db_fetch_cell( "SELECT directory FROM plugin_config WHERE directory='settings' AND status=1" ) == "" )
		) {
			// it's not in the global plugins var ( old PIA mode ) AND
			// it is not configured via the new PIA mode
			return FALSE;
		}
		return TRUE;
	}


	function CereusReporting_console_after()
	{
		global $config, $plugins;
		$dir = dirname( __FILE__ );
		CereusReporting_check_upgrade();
		include_once( $dir . '/functions.php' );

		$current = CereusReporting_version();
		$current = $current[ 'version' ];
		$old     = db_fetch_cell( "SELECT version FROM plugin_config WHERE directory='CereusReporting'" );
		if ( $current != $old ) {
			CereusReporting_setup_table( $old );
		}
	}

	function CereusReporting_config_settings()
	{
		global $tabs, $settings, $tabs_graphs, $settings_graphs;
		$dir = dirname( __FILE__ );
		include_once( $dir . '/functions.php' );

		$tabs[ "cereusreporting" ] = "CereusReporting";

		// Check for available ReportEngines
		$mpdf_dir                = dirname( __FILE__ ) . '//ReportEngines//mpdf';
		$ar_ReportEngines        = array();
		//$ar_ReportEngines[ "0" ] = "FPDF";
		if ( is_dir( $mpdf_dir ) ) {
			$ar_ReportEngines[ "1" ] = "mPDF";
		}
		$tcpdf_dir = dirname( __FILE__ ) . '//ReportEngines//tcpdf';
		if ( is_dir( $tcpdf_dir ) ) {
			$ar_ReportEngines[ "2" ] = "TCPDF";
		}

		$sql    = "SELECT
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`,
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`shortDescription`
        FROM
          `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`";
		$result = db_fetch_assoc( $sql );

		$ar_slaTimeFrames        = array();
		$ar_slaTimeFrames[ "0" ] = "none";
		foreach ( $result as $row ) {
			$ar_slaTimeFrames[ $row[ 'Id' ] ] = $row[ 'shortDescription' ];
		}

        $result = db_fetch_assoc("
				SELECT
				  `plugin_CereusReporting_Reports_templates`.`name` AS `name`,
				  `plugin_CereusReporting_Reports_templates`.`templateId` AS `id`
				FROM
				  `plugin_CereusReporting_Reports_templates`");

        $ar_reportTemplates        = array();
        $ar_reportTemplates[ "0" ] = "none";
        foreach ( $result as $row ) {
            $ar_reportTemplates[ $row[ 'id' ] ] = $row[ 'name' ];
        }


        $licenseExpiry = "never";

		// Add additional user specific settings
		$temp = array();

		if ( isset ( $tabs_graphs[ 'CereusReporting' ] ) == FALSE ) {
			$tabs_graphs[ 'CereusReporting' ] = '';
		}

		$temp_design = array(
			"nmid_cr_design_header"               => array(
				"friendly_name" => "Report Design",
				"method"        => "spacer",
			),
			"nmid_cr_design_availchart_name_size" => array(
				"friendly_name" =>  'Availability Chart Name Size',
				"description"   =>  'The size of the column containing the device names (in pixel).',
				"method"        => "textbox",
				"default"       => "180",
				"max_length"    => 6,
			),
			"nmid_cr_design_report_file_template" => array(
				"friendly_name" =>  'Report File Template',
				"description"   =>  'The Report file template to be used for the report file name.',
				"method"        => "textbox",
				"default"       => "[REPORTTITLE]",
				"max_length"    => 512,
			)
		);
		$temp        = array_merge( $temp, $temp_design );

        $temp_availaiblity = array(
            "nmid_avail_header"             => array(
                "friendly_name" => 'CereusReporting - Availability Addon  - ' . EDITION . ' Edition',
                "method"        => "spacer"
            ),
            "nmid_avail_enabled"            => array(
                "friendly_name" =>  'Enable the Availability module',
                "description"   =>  'This box enables or disables the availability module',
                "method"        => "checkbox",
                "max_length"    => "255"
            )
        );
        if ( read_config_option( 'nmid_avail_enabled' )  ) {
            $temp_availaiblity = array(
                "nmid_avail_header"             => array(
                    "friendly_name" => 'CereusReporting - Availability ' . 'Addon' . ' - ' . EDITION . " Edition",
                    "method"        => "spacer",
                ),
                "nmid_avail_enabled"            => array(
                    "friendly_name" => 'Enable the Availability module',
                    "description"   => 'This box enables or disables the availability module',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                ),
                "nmid_avail_winservice_enabled" => array(
                    "friendly_name" => 'Enable the WinService state addon',
                    "description"   => 'This box enables or disables the Windows Service availability addon module',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                ),
                "nmid_avail_globalSla"          => array(
                    "friendly_name" => 'Global SLA Threshold',
                    "description"   => 'The global SLA threshold. This can be overwritten on a per Device basis.',
                    "method"        => "textbox",
                    "default"       => "95.99",
                    "max_length"    => 10,
                ),
                "nmid_avail_globalSlaTimeFrame" => array(
                    "friendly_name" => 'Global SLA TimeFrame',
                    "description"   => 'The global SLA TimeFrame. This time frame will be used for calculating SLA measurements',
                    "method"        => "drop_array",
                    "default"       => "0",
                    "array"         => $ar_slaTimeFrames
                ),
                "nmid_avail_chartWidth"         => array(
                    "friendly_name" => 'Availability Chart Width',
                    "description"   => 'The width of the availability chart.',
                    "method"        => "textbox",
                    "default"       => "900",
                    "max_length"    => 4,
                ),
                "nmid_avail_chartHeight"        => array(
                    "friendly_name" => 'Availability Chart Height',
                    "description"   => 'The height of the availability chart',
                    "method"        => "textbox",
                    "default"       => "270",
                    "max_length"    => 4,
                ),
                "nmid_avail_maxDevices"         => array(
                    "friendly_name" => 'Availability Max Devices',
                    "description"   => 'The number of devices the combined availability chart can have on the Report. Will be ignored for the ABC separated view',
                    "method"        => "textbox",
                    "default"       => "20",
                    "max_length"    => 3,
                ),
                "nmid_avail_sort_option"        => array(
                    "friendly_name" => 'Grouping option',
                    "description"   => 'Type of grouping used for the combined availability chart.',
                    "method"        => "drop_array",
                    "default"       => "devices",
                    "array"         => array(
                        "devices" => 'Device Count',
                        "abc"     => 'ABC Grouping'
                    )
                ),
                "nmid_avail_font"               => array(
                    "friendly_name" => 'Availability Font Type',
                    "description"   => 'The font type to be used for the title.',
                    "method"        => "drop_array",
                    "default"       => "helvBI.ttf",
                    "array"         => array(
                        "helvBI.ttf"  => "Helvetia",
                        "timesbi.ttf" => "Times"
                    )
                ),
                "nmid_avail_addTable"           => array(
                    "friendly_name" => 'Add availaibility table to tree view',
                    "description"   => 'This will add the availability data table after the tree view graph.',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                ),
                "nmid_avail_addDetailedTable"   => array(
                    "friendly_name" => 'Add detailed failed polls report table to tree view',
                    "description"   => 'This will add a failed polls report table after the tree view graph.',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                ),
                "nmid_avail_addGraph"           => array(
                    "friendly_name" => 'Add availaibility graph to report',
                    "description"   => 'This will add the availability graph to a report.',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                ),
                "nmid_avail_addWinServiceTable" => array(
                    "friendly_name" => 'Add WinService availaibility table to tree view',
                    "description"   => 'This will add the WinService availability data table after the tree view graph.',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                ),
                "nmid_avail_addWinServiceGraph" => array(
                    "friendly_name" => 'Add WinService availaibility graph to tree view',
                    "description"   => 'This will add the WinService availability graph to the tree view graph.',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                ),
                "nmid_avail_offSLATransparent"  => array(
                    "friendly_name" => 'Show Transparent area during non-SLA relevant time frames.',
                    "description"   => 'If enabled, non-SLA relevant areas will be transparent, otherwise the area will be shown as a semi transparentlight gray area.',
                    "method"        => "checkbox",
                    "max_length"    => "255"
                )
            );
        }
        $temp              = array_merge( $temp, $temp_availaiblity );


		$cr_default_server = '';
		if ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$cr_default_server = $_SERVER['SERVER_NAME'];
        }


        $dateFormatArray = array(
            "Y-m-d H:i" => "2012-01-30 20:19",
            "m/d/Y H:i" => "01/30/2012 20:19",
            "d-m-Y H:i" => "30-01-2012 20:19"
        );


		$temp_general = array(
			"nmid_pdfheader"            => array(
				"friendly_name" => 'CereusReporting - ' .  'Report Definition',
				"method"        => "spacer",
			),
			"nmid_pdffontname"          => array(
				"friendly_name" =>  'Font Name',
				"description"   =>  'Choose which font you want to use.',
				"method"        => "drop_array",
				"default"       => "helvetica",
				"array"         => array(
					"helvetica" => "Helvetica",
					"courier"   => "Courier"
				)
			),
			"nmid_pdffontsize"          => array(
				"friendly_name" =>  'Header Font Size',
				"description"   =>  'Choose which font size you want to have your header in.',
				"method"        => "drop_array",
				"default"       => "10",
				"array"         => array(
					"6"  => "6",
					"8"  => "8",
					"10" => "10",
					"12" => "12",
					"14" => "14"
				)
			),
			"nmid_settings_header"      => array(
                "friendly_name" => "CereusReporting - General - " . EDITION . " Edition",
                "method"        => "spacer",
			),
			"nmid_pdfPrintHeaderFooter" => array(
				"friendly_name" =>  'Print Header/Footer to PDF',
				"description"   =>  'Enables/Disabled the printing of the Header and Footer. Should be disabled when using a CoverPage/Template.',
				"method"        => "checkbox",
				"max_length"    => "255"
			),
			"nmid_pdflogo"              => array(
				"friendly_name" =>  'Report Logo',
				"description"   =>  'The company logo you want to be displayed on the PDF report. The default logo is images/default_logo.png. Needs to be a PNG file.',
				"method"        => "textbox",
				"max_length"    => 255,
			),
            "nmid_pdf_ondemand_show_header"      => array(
                "friendly_name" =>  'Show report title on OnDemand reports',
                "description"   =>  'Shows a report title on OnDemand reports',
                "method"        => "checkbox",
                "max_length"    => "255"
            ),
            "nmid_pdf_ondemand_show_page_numbers"      => array(
                "friendly_name" =>  'Show page numbers on OnDemand reports',
                "description"   =>  'Adds page numbers to OnDemand reports',
                "method"        => "checkbox",
                "max_length"    => "255"
            ),
            "nmid_global_default_report_template" => array(
                "friendly_name" => 'Global Report Template for OnDemand Reports',
                "description"   => 'The report template to use for OnDemand reports',
                "method"        => "drop_array",
                "default"       => "0",
                "array"         => $ar_reportTemplates
            ),
			"nmid_pdfUserHostname"      => array(
				"friendly_name" =>  'Use "HOSTNAME(IP)" in host only reports',
				"description"   =>  'Displays "Report for host hostname(a.b.c.d)" instead of the default report title',
				"method"        => "checkbox",
				"max_length"    => "255"
			),
			"nmid_pdfSetLinks"          => array(
				"friendly_name" =>  'Graphs link back to cacti',
				"description"   =>  'A click on a graph in the PDF Report links back to the cacti page for that graph',
				"method"        => "checkbox",
				"max_length"    => "255"
			),
			"nmid_compressArchive"          => array(
				"friendly_name" =>  'Zip/Compress archived reports',
				"description"   =>  'Archived reports will be compressed using the ZIP format',
				"method"        => "checkbox",
				"max_length"    => "255"
			),
			"nmid_pdfCactiServerUrl"    => array(
				"friendly_name" =>  'Cacti Host/Server URL',
				"description"   =>  'The Cacti Host/Server URL to be used in the graph links',
				"method"        => "textbox",
				"default"       => $cr_default_server,
				"max_length"    => 255
			),
            "cr_max_http_connections"  => array(
                "friendly_name" =>  'Concurrent HTTP requests',
                "description"   =>  'MAX concurrent HTTP requests for report graph retrieval',
                "method"        => "drop_array",
                "default"       => "4",
                "array"         => array(
                    "4"  => "4",
                    "6"  => "6",
                    "8" => "8",
                    "10" => "10",
                    "12" => "12",
                    "16" => "16",
                    "20" => "20",
                    "25" => "25",
                    "30" => "30"
                )
            ),
            "use_http_basic_auth"    => array(
                "friendly_name" =>  'Use Basic Auth for graph chart retrieval',
                "description"   =>  'If your server is secured with basic auth, you should enable this option and set a username and password in the next boxes',
                "method"        => "checkbox",
                "max_length"    => "255"
            ),
            "use_http_basic_auth_username"    => array(
                "friendly_name" =>  'Basic Auth username',
                "description"   =>  'The username being used for http basic auth',
                "method"        => "textbox",
                "max_length"    => 255,
            ),
            "use_http_basic_auth_password"    => array(
                "friendly_name" =>  'Basic Auth password',
                "description"   =>  'The password being used for http basic auth',
                "method"        => "textbox_password",
                "max_length"    => 255,
            ),
			"nmid_archiveDir"           => array(
				"friendly_name" =>  'Archive Directory',
				"description"   =>  'Absolute path to Archive directory for storing the PDF Reports.',
				"method"        => "textbox",
				"default"       => __DIR__."/archive",
				"max_length"    => 255,
			),
			"nmid_cr_weathermap_output_dir" => array(
				"friendly_name" =>  'Weathermap output directory',
				"description"   =>  'Path to the weathermap output directory. This directory contains the generated weathermap images.',
				"method"        => "textbox",
				"max_length"    => 512,
			),
			"nmid_use_css"              => array(
				"friendly_name" => 'Load JQuery Script/CSS',
				"description"   => 'Load the CSS and Script files for JQuery.',
				"method"        => "drop_array",
				"array"         => array( 1 => "True", 0 => "False" ),
				"default"       => 0
			),
//            "nmid_UpdateDir" => array(
//                "friendly_name" => "Update Download path",
//                "description" => "Updates will be downloaded to this directory.",
//                "method" => "textbox",
//				"default" => "updates",
//                "max_length" => 255,
//                ),
			"nmid_pdfscheduletype"      => array(
				"friendly_name" =>  'Scheduler being used',
				"description"   =>  'The poller is fine for small reports, but it is still recommended to use cron or windows scheduling.',
				"method"        => "drop_array",
				"default"       => "poller",
				"array"         => array(
					"poller" => "Poller",
					"cron"   => "Cron"
				)
			),
			"nmid_pdf_debug"            => array(
				"friendly_name" =>  'Logging mode',
				"description"   =>  'Enable/Disable the logging mode',
				"method"        => "drop_array",
				"default"       => "0",
				"array"         => array(
					"0" => "None",
					"1" => "Fatal",
					"2" => "Error",
					"3" => "Warning",
					"4" => "Notice/Info",
					"5" => "Debug"
				)
			),
		    "nmid_pdf_dateformat"       => array(
				"friendly_name" =>  'Date Format',
				"description"   =>  'Date format for reports',
				"method"        => "drop_array",
				"default"       => "0",
				"array"         => $dateFormatArray
			),
			"nmid_pdf_type"             => array(
				"friendly_name" =>  'PDF Module to use',
				"description"   =>  'Choose which PDF Engine you want to use.',
				"method"        => "drop_array",
				"default"       => "2",
				"array"         => $ar_ReportEngines
			),
            'nmid_pdfUseUnicode'     => array(
	            "friendly_name" =>  'Use Unicode',
	            "description"   =>  'Unicode support will increase PDF filesize and processing time.<br><i>Only supported with the mPDF Engine</i>',
	            "method"        => "checkbox",
	            "max_length"    => "255"
            )
		);
		$temp         = array_merge( $temp, $temp_general );

		// Unicode Support for mPDF
		if ( is_dir( $mpdf_dir ) && ( read_config_option( 'nmid_pdf_type' ) == MPDF_ENGINE ) ) {
			$temp[ "nmid_pdfUnicodeFontSet" ] = array(
				"friendly_name" =>  'Unicode FontSet',
				"description"   =>  'FontSet to be used.<br><i>Only supported with the mPDF Engine</i>',
				"method"        => "drop_array",
				"default"       => "0",
				"array"         => array(
					""           => "Western European languages (English, Italian, German)",
					"iso-8859-2" => "iso8859-2 - Central and Eastern European (Polish, Croatian, Czech, Slovak, Slovenian, Serbian, and Hungarian)",
					"win-1251"   => "win-1251 - Cyrillic (Russian, Bulgarian, Serbian, Macedonian and Bulgarian",
					"iso-8859-4" => "iso-8859-4 - Baltic (Estonian, Latvian, Lithuanian, Greenlandic)",
					"iso-8859-7" => "iso-8859-7 - Greek language (monotonic orthography)",
					"iso-8859-9" => "iso-8859-9 - Latin-5 (Turkish, Kurdish)",
					"SJIS"       => "SJIS - Japanese",
					"GBK"        => "GBK - Chinese - Simplified",
					"BIG5"       => "BIG5 - Chinese - Traditional",
					"UHC"        => "UHC - Korean",
					"UTF-8"      => "UTF-8 - Any CJK language"
				)
			);
		}


		if ( isset( $settings[ "cereusreporting" ] ) ) {
			$settings[ "cereusreporting" ] = array_merge( $settings[ "cereusreporting" ], $temp );
		}
		else {
			$settings[ "cereusreporting" ] = $temp;
		}

	}


	function CereusReporting_tree_after( $param )
	{
		$param = CereusReporting_tree_after_compat( $param );
		return $param;
	}


	function CereusReporting_config_arrays()
	{
		global $user_auth_realms, $user_auth_realm_filenames, $menu, $config;
		$dir = dirname( __FILE__ );
		include_once( $dir . '/functions.php' );
		$temp = array();

		$temp_header = array(
			"plugins/CereusReporting/CereusReporting_Reports.php" =>  'Manage Reports'
		);
		$temp        = array_merge( $temp, $temp_header );

		// Plugins Section
        $temp_arch = array(
            "plugins/CereusReporting/CereusReporting_ReportTemplates.php" =>  'Manage Templates',
            "plugins/CereusReporting/CereusReporting_managePDFFiles.php" => 'Import PDF files',
            "plugins/CereusReporting/CereusReporting_Archive.php"           =>  'Manage Archive',
            "plugins/CereusReporting/CereusReporting_ArchiveUserGroups.php" => ' - ' .  'User Groups'
        );
        $temp      = array_merge( $temp, $temp_arch );
		// Plugins Section
        $temp_schdl = array(
            "plugins/CereusReporting/CereusReporting_scheduler.php" =>  'Manage Report Schedule'
        );
        $temp       = array_merge( $temp, $temp_schdl );
        $temp_avail = array(
            "plugins/CereusReporting/CereusReporting_Availability_DowntimeSchedule.php"   =>  'Availability',
            "plugins/CereusReporting/CereusReporting_Availability_TholdSlaExceptions.php" => ' - ' .  'Thold SLA Exception',
            "plugins/CereusReporting/CereusReporting_Availability_SLATimeFrame.php"       => ' - ' .  'SLA Times',
            "plugins/CereusReporting/CereusReporting_Availability_SLATimeFrameItem.php"   => ' - ' .  'SLA Times Items'
        );
        $temp       = array_merge( $temp, $temp_avail );

		$temp_footer = array(
			"plugins/CereusReporting/CereusReporting_Backup.php" =>  'Backup/Restore',
			"plugins/CereusReporting/CereusReporting_debug.php"  =>  'Debug Info'
			//"plugins/CereusReporting/CereusReporting_support.php" => 'Support'
		);
		$temp        = array_merge( $temp, $temp_footer );

		$sql = 'SELECT count(id) as DSCount  FROM data_template_data WHERE data_source_path!="NULL" AND data_source_path!="" AND active="on";';
		$dsCount = getDBValue( "DSCount", $sql ) + 50;


		//if ( !( EDITION == "CORPORATE" ) ) {
		$temp_footer = array(
			"https://www.urban-software.com/product/cereusreporting-professional-2/" => 'Purchase',
		);
		// $temp        = array_merge( $temp, $temp_footer );
		//}

		if ( isset( $menu[ "CereusReporting" ] ) ) {
			$menu[ "CereusReporting" ] = array_merge( $temp, $menu[ "CereusReporting" ] );
		}
		else {
			$menu[ "CereusReporting" ] = $temp;
		}
	}

	/**
	 * @return array
	 */
	function CereusReporting_version()
	{
 		global $config;
		$info = parse_ini_file($config['base_path'] . '/plugins/CereusReporting/INFO', true);
		return $info['info'];
	}

	/**
	 * @param $old_version
	 */
	function CereusReporting_setup_table( $old_version )
	{
		$dir = dirname( __FILE__ );
		include_once( $dir . '/functions.php' );

		CereusReporting_setup_table_new();
		$version_info = CereusReporting_version();
		db_execute( 'UPDATE plugin_config SET version = "' . $version_info[ 'version' ] . '" WHERE directory = "CereusReporting"' );
		db_execute( 'DELETE FROM plugin_config WHERE directory = "nmidCreatePDF"' );

		api_plugin_register_realm( 'CereusReporting', 'CereusReporting_AvailabilityChart.php', 'NMID - View Availability Charts', 1199 );
		api_plugin_register_realm( 'CereusReporting', 'CereusReporting_userArchive.php', 'NMID - View Archived Reports', 1200 );

		// get minor and major version number
		preg_match( "@(\d+)\.(\d+).(\d+)@", $old_version, $version_match );

		$version_major = $version_match[ 1 ];
		$version_minor = $version_match[ 2 ];
		$version_build = $version_match[ 3 ];

		// Version 0.xx
		if ( $version_major < 1 ) {
			api_plugin_register_hook( 'CereusReporting', 'tree_after', 'CereusReporting_tree_after', 'setup.php' );
			api_plugin_register_hook( 'CereusReporting', 'api_device_save', 'CereusReporting_api_device_save', 'setup.php' );
		}

		// Version 1.xx
		if ( $version_major < 2 ) {
			if ( $version_minor < 51 ) {
				$sql = "INSERT INTO `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table` (`id`, `defaultDays`,`defaultStartTime`,`defaultEndTime`,`shortDescription`,`longDescription` ) " .
					"VALUES (1,'Mon,Tue,Wed,Thu,Fri,Sat,Sun','00:00','23:59','24x7','Default SLA TimeFrame');";
				db_execute( $sql );
			}
			if ( $version_minor < 56 ) {
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Archives', array( 'name'     => 'UserGroupId',
				                                                                                     'type'     => 'mediumint(25)',
				                                                                                     'unsigned' => 'unsigned',
				                                                                                     'NULL'     => FALSE,
				                                                                                     'default'  => '0' ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports_scheduler', array( 'name'     => 'archiveUserGroupId',
				                                                                                              'type'     => 'mediumint(25)',
				                                                                                              'unsigned' => 'unsigned',
				                                                                                              'NULL'     => FALSE,
				                                                                                              'default'  => '0' ) );
			}
			if ( $version_minor < 70 ) {
				$nmid_avail_maxRawData = readConfigOption( 'nmid_avail_maxRawData' );
				db_execute( 'UPDATE settings SET value=' . $nmid_avail_maxRawData . ' WHERE name = "nmid_avail_PollMaxRawData"' );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports_scheduler', array( 'name'     => 'runNow',
				                                                                                              'type'     => 'mediumint(25)',
				                                                                                              'unsigned' => 'unsigned',
				                                                                                              'NULL'     => FALSE,
				                                                                                              'default'  => '0' ) );
			}
			if ( $version_minor < 72 ) {
				api_plugin_register_hook( 'CereusReporting', 'weathermap_page_selection_top', 'CereusReporting_printWeathermapButton', 'setup.php' );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports_scheduler', array( 'name' => 'Attachments',
				                                                                                              'type' => 'text',
				                                                                                              'NULL' => TRUE ) );
			}
			if ( $version_build < 32 ) {
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'showGraphHeader',
				                                                                                    'type'    => 'varchar(5)',
				                                                                                    'NULL'    => FALSE,
				                                                                                    'default' => '1' ) );
			}
			if ( $version_build < 34 ) {
				db_execute( "ALTER TABLE `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`  DROP PRIMARY KEY,  ADD PRIMARY KEY (`Id`, `defaultDays`);" );
			}
			if ( $version_minor < 83 ) {
				api_plugin_register_hook( 'CereusReporting', 'device_action_array', 'plugin_nmidCreatePDF_device_action_array', 'setup.php' );
				api_plugin_register_hook( 'CereusReporting', 'device_action_prepare', 'plugin_nmidCreatePDF_device_action_prepare', 'setup.php' );
				api_plugin_register_hook( 'CereusReporting', 'device_action_execute', 'plugin_nmidCreatePDF_device_action_execute', 'setup.php' );

			}
		}

		// Version 2.xx
		if ( $version_major < 3 ) {
			// Version < 2.10
			if ( $version_minor < 1 ) {
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports_scheduler', array( 'name' => 'RecipientsBcc',
				                                                                                              'type' => 'text',
				                                                                                              'NULL' => TRUE ) );

			}
			if ( $version_build < 67 ) {
				// Index creation
				db_execute( 'CREATE INDEX deviceId_typeId_timeStamp_index ON plugin_nmidCreatePDF_Availability_Table ( deviceId, typeId, timeStamp )' );
				db_execute( 'CREATE INDEX deviceId_failed_polls_timeStamp_total_polls_index ON plugin_nmidCreatePDF_Availability_Table ( deviceId, failed_polls, timeStamp, total_polls )' );
			}

			if ( $version_build < 68 ) {
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name' => 'PrependPDFFile',
				                                                                                    'type' => 'text',
				                                                                                    'NULL' => TRUE ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name' => 'AppendPDFFile',
				                                                                                    'type' => 'text',
				                                                                                    'NULL' => TRUE ) );

			}
			if ( $version_build < 70 ) {
				api_plugin_register_hook( 'CereusReporting', 'graph_tree_page_header_buttons', 'CereusReporting_page_header_buttons', 'setup.php' );
			}
			if ( $version_build < 72 ) {
				# CRC-4
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'author',
				                                                                                    'type'    => 'varchar(255)',
				                                                                                    'NULL'    => true ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'customHeader',
				                                                                                    'type'    => 'varchar(255)',
				                                                                                    'NULL'    => true ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'customFooter',
				                                                                                    'type'    => 'varchar(255)',
				                                                                                    'NULL'    => true ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'printDetailedFailedPollsTable',
				                                                                                    'type'    => 'varchar(5)',
				                                                                                    'NULL'    => FALSE,
				                                                                                    'default' => '0' ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'printDetailedPollsTable',
				                                                                                    'type'    => 'varchar(5)',
				                                                                                    'NULL'    => FALSE,
				                                                                                    'default' => '0' ) );

			}
			if ( $version_build < 78 ) {
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'printHeader', 'type' => 'varchar(5)',
				                               'NULL'    => FALSE,
				                               'default' => '0' ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'printFooter', 'type' => 'varchar(5)',
				                               'NULL'    => FALSE,
				                               'default' => '0' ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'customReportTitle', 'type' => 'varchar(255)', 'NULL' => true ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'customSubReportTitle', 'type' => 'varchar(255)', 'NULL' => true ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'skipHFCoverPage', 'type' => 'varchar(5)',
				       'NULL'    => FALSE,
				       'default' => '0' ) );
			}

			if ( $version_build < 86 ) {
				//
				api_plugin_register_hook( 'CereusReporting', 'graph_action_array', 'plugin_CereusReporting_graph_action_array', 'setup.php' );
				api_plugin_register_hook( 'CereusReporting', 'graph_action_prepare', 'plugin_CereusReporting_graph_action_prepare', 'setup.php' );
				api_plugin_register_hook( 'CereusReporting', 'graph_action_execute', 'plugin_CereusReporting_graph_action_execute', 'setup.php' );
			}
			if ( $version_build < 87 ) {
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'customGraphWidth',
				                                                                                    'type'    => 'varchar(255)',
				                                                                                    'NULL'    => true ) );
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'customGraphHeight',
				                                                                                    'type'    => 'varchar(255)',
				                                                                                    'NULL'    => true ) );
			}
			if ( $version_build < 88 ) {
				api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Availability_Table', array( 'name'    => 'isAggregated',
				                                                                                               'type'    => 'varchar(2)',
				                                                                                               'NULL'    => FALSE,
				                                                                                               'default' => '0' ) );
				db_execute( 'UPDATE plugin_nmidCreatePDF_Availability_Table set isAggregated=1' );

			}
		}

        // Version 3.xx
		if ( $version_major < 4 ) {
			// Version < 3.10
			if ( $version_minor < 1 ) {
				if ( $version_build < 18 ) {
					api_plugin_db_add_column( 'CereusReporting', 'plugin_CereusReporting_Reports_templates', array( 'name'    => 'header_template', 'type' => 'text', 'NULL' => FALSE,
					                              'default' => '' ) );
					api_plugin_db_add_column( 'CereusReporting', 'plugin_CereusReporting_Reports_templates', array( 'name'    => 'footer_template', 'type' => 'text', 'NULL' => FALSE,
					                              'default' => '' ) );
					api_plugin_db_add_column( 'CereusReporting', 'plugin_CereusReporting_Reports_templates',  array( 'name'    => 'report_title', 'type' => 'varchar(255)', 'NULL' => FALSE,
					                              'default' => '' ) );
					api_plugin_db_add_column( 'CereusReporting', 'plugin_CereusReporting_Reports_templates', array( 'name'    => 'report_subtitle', 'type' => 'varchar(255)', 'NULL' => FALSE,
					                              'default' => '' ) );
				}
				if ( $version_build < 19 ) {
				    db_execute( "ALTER TABLE cacti.plugin_CereusReporting_Reports_templates MODIFY templateId MEDIUMINT(25) NOT NULL AUTO_INCREMENT;");
					db_execute( "INSERT INTO cacti.plugin_CereusReporting_Reports_templates (templateId, name, description, template_file, page_size, page_orientation, custom_graph_width, custom_graph_height, page_margin_top, page_margin_bottom, page_margin_left, page_margin_right, header_template, footer_template, report_title, report_subtitle) VALUES (-1,'ColorCircle Template', 'ColorCircle Template', 'ColorCircle_Report_Template-P-A4.pdf', 'A4', 'P', '800', '100', 30, 15, 5, 5, '', '', '', '');");
				}
				if ( $version_build < 23 ) {
					api_plugin_db_add_column( 'CereusReporting', 'plugin_CereusReporting_Reports_templates', array( 'name'    => 'page_footer_margin_bottom',
                                                                                                                    'type'    => 'mediumint(5)',
                                                                                                                    'NULL'    => FALSE,
                                                                                                                    'default' => '5' ) );
                }
			}
			// Version > 3.10
            if ( $version_build < 96 ) {
                api_plugin_db_add_column( 'CereusReporting', 'plugin_nmidCreatePDF_Reports', array( 'name'    => 'printPageNumbers', 'type' => 'varchar(5)',
                                                                                                    'NULL'    => FALSE,
                                                                                                    'default' => '0' ) );
            }
        }

		// Default updates
		db_execute( 'UPDATE plugin_hooks SET status = 1 where name = "CereusReporting" AND hook="api_device_save"' );
		db_execute( 'UPDATE plugin_hooks SET status = 1 where name = "CereusReporting" AND hook="tree_after"' );
		db_execute( 'UPDATE plugin_hooks SET status = 1 where name = "CereusReporting" AND hook="weathermap_page_selection_top"' );
		db_execute( 'UPDATE plugin_hooks SET status = 1 where name = "CereusReporting" AND hook="device_action_array"' );
		db_execute( 'UPDATE plugin_hooks SET status = 1 where name = "CereusReporting" AND hook="device_action_prepare"' );
		db_execute( 'UPDATE plugin_hooks SET status = 1 where name = "CereusReporting" AND hook="device_action_execute"' );

		// Always do this:
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_ReportTemplates.php,CereusReporting_ReportTemplates_Add.php,CereusReporting_orderDSStatsGraphs.php,CereusReporting_addDSStatsReport.php,addToDSStatsReport.php,CereusReporting_createTemplateReport.php,CereusReporting_orderGraphs.php,CereusReporting_scheduler.php,CereusReporting_addScheduledReport.php,addToGraphReport.php,CereusReporting_Reports.php,CereusReporting_addReport.php,CereusReporting_addMultiReport.php,CereusReporting_orderMultiGraphs.php,CereusReporting_Backup.php,CereusReporting_support.php,CereusReporting_debug.php,CereusReporting_ArchiveUserGroups.php,CereusReporting_ArchiveUserGroups_Add.php,CereusReporting_ArchiveUserGroups_AddUser.php,CereusReporting_addWeathermapReport.php,CereusReporting_addTholdReport.php,CereusReporting_addTholdSumReport.php,CereusReporting_addAvailSumReport.php,CereusReporting_addReportTemplate.php,CereusReporting_managePDFFiles.php" WHERE display = "Plugin -> CereusReporting - Define PDF Reports"' );
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_doArchiveReport.php,CereusReporting_Archive.php,CereusReporting_GenerateReports.php,CereusReporting_GenerateReport_now.php" WHERE display = "Plugin -> CereusReporting - Generate PDF Reports"' );
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_Availability_DowntimeSchedule.php,CereusReporting_Availability_addDowntimeSchedule.php,CereusReporting_Availability_SLATimeFrame.php,CereusReporting_Availability_addSLATimeFrame.php,CereusReporting_Availability_addSLATimeFrameItem.php,CereusReporting_Availability_SLATimeFrameItem.php,CereusReporting_Availability_TholdSlaExceptions.php,CereusReporting_Availability_addTholdSlaExceptions.php,CereusReporting_Availability_TholdSlaExceptions.php" WHERE display = "Plugin -> CereusReporting - Manage Availability Data"' );
		db_execute( 'UPDATE plugin_realms SET file = "CereusReporting_AvailabilityChart.php" WHERE display = "Plugin -> CereusReporting - View Availability Charts"' );

	}
