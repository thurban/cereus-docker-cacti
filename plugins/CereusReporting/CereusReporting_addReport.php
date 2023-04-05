<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_addReport.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
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
	include_once( "./include/global.php" );
	include_once( "./lib/data_query.php" );
	$_SESSION[ 'custom' ] = FALSE;


	/* set default action */
	if ( !isset( $_REQUEST[ "ReportId" ] ) ) {
		$_REQUEST[ "ReportId" ] = "";
	}
	if ( !isset( $_REQUEST[ "ReportType" ] ) ) {
		$_REQUEST[ "ReportType" ] = "";
	}
	if ( !isset( $_REQUEST[ "action" ] ) ) {
		$_REQUEST[ "action" ] = "";
	}
	if ( !isset( $_REQUEST[ "drp_action" ] ) ) {
		$_REQUEST[ "drp_action" ] = "";
	}
	if ( !isset( $_REQUEST[ "delete_graphs" ] ) ) {
		$_REQUEST[ "delete_graphs" ] = "";
	}
	if ( !isset( $_REQUEST[ "sort_column" ] ) ) {
		$_REQUEST[ "sort_column" ] = "";
	}
	if ( !isset( $_REQUEST[ "sort_direction" ] ) ) {
		$_REQUEST[ "sort_direction" ] = "";
	}

	// Sanitize strings
	$_REQUEST[ "drp_action" ]     = filter_var( $_REQUEST[ "drp_action" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_column" ]    = filter_var( $_REQUEST[ "sort_column" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_direction" ] = filter_var( $_REQUEST[ "sort_direction" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "ReportId" ]       = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );
	$_REQUEST[ "ReportType" ]     = filter_var( $_REQUEST[ "ReportType" ], FILTER_SANITIZE_NUMBER_INT );
	$_REQUEST[ "delete_graphs" ]  = filter_var( $_REQUEST[ "delete_graphs" ], FILTER_SANITIZE_NUMBER_INT );

	input_validate_input_number( $_REQUEST[ "ReportId" ] );
	input_validate_input_number( $_REQUEST[ "ReportType" ] );
	input_validate_input_number( $_REQUEST[ "delete_graphs" ] );

	switch ( $_REQUEST[ "drp_action" ] ) {
		case '2':
			form_graph_delete( $_REQUEST[ "ReportId" ], $_REQUEST[ "delete_graphs" ], $_REQUEST[ "ReportType" ] );
			break;
		case '3':
			header( "Location: CereusReporting_addWeathermapReport.php?ReportId=" . $_REQUEST[ "ReportId" ] );
			break;
		case '4':
			header( "Location: CereusReporting_addTholdReport.php?ReportId=" . $_REQUEST[ "ReportId" ] );
			break;
		case '5':
			header( "Location: CereusReporting_addTholdSumReport.php?ReportId=" . $_REQUEST[ "ReportId" ] );
			break;
		case '6':
			header( "Location: CereusReporting_addAvailSumReport.php?ReportId=" . $_REQUEST[ "ReportId" ] );
			break;
		default:
			break;
	}

	switch ( $_REQUEST[ "action" ] ) {
		case 'save':
			form_save( $_REQUEST[ "ReportId" ] );
			break;
        case 'ajax_hosts_graphs':
            get_allowed_ajax_hostsGraphs(false);
            break;
        case 'ajax_hosts':
            header('Content-Type: application/json');
            get_allowed_ajax_hosts(false);
            exit;
            break;
        case 'ajax_trees':
            header('Content-Type: application/json');
            get_allowed_ajax_trees(false);
            exit;
            break;
		default:
			cr_top_header();
			form_display( $_REQUEST[ "ReportId" ], $_REQUEST[ "ReportType" ] );
			cr_bottom_footer();
			break;
	}

    function get_allowed_ajax_hostsGraphs($include_any = true, $include_none = true, $sql_where = '') {
        $return = array();

        $term = get_filter_request_var('term', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
        if ($term != '') {
            $sql_where .= ($sql_where != '' ? ' AND ' : '') . "name_cache LIKE '%$term%' OR data_source_name LIKE '%$term%'";
        }

        if (get_request_var('term') == '') {
            if ($include_none) {
                $return[] = array('label' => 'None', 'value' => 'None', 'id' => '0');
                    }
                }

        $graph_items = get_allowed_graph_items($sql_where, 'name_cache', read_config_option('autocomplete_rows'));
        if (cacti_sizeof($graph_items)) {
            foreach($graph_items as $gi) {
                $a_graph_list = db_fetch_assoc( '
                        SELECT
                            graph_templates_item.local_graph_id as lgid
                        FROM
                          graph_templates_item
                        WHERE
                          graph_templates_item.task_item_id = '.$gi['id'].'
                        LIMIT 1'
                );
                if ( sizeof( $a_graph_list ) > 0 ) {
                    foreach ( $a_graph_list as $a_graph ) {
                        $return[] = array('label' => $gi['name'], 'value' => $a_graph['lgid']);

                    }
                }

                //$return[] = array('label' => $gi['name'], 'value' => $gi['name'], 'id' => $gi['id']);
            }
        }
        header('Content-Type: application/json');
        print json_encode($return);
        exit;
    }


    function get_allowed_ajax_trees($include_any = true, $include_none = true, $sql_where = '') {
        $return = array();

        $term = get_filter_request_var('term', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
        if ($term != '') {
            $sql_where =  " AND ( graph_tree.name LIKE '%$term%' OR graph_tree_items.title LIKE '%$term%' )";
        }
        $a_tree_list = cr_get_trees($sql_where);
        if ( sizeof( $a_tree_list ) > 0 ) {
            $tree_name = '';

            foreach ( $a_tree_list as $s_tree_item ) {
                if ( function_exists('top_header')) {
                    $s_data         = htmlspecialchars( $s_tree_item[ 'treeid' ] . ';' . $s_tree_item[ 'leafid' ] );
                    $tree_name = $s_tree_item[ 'name' ] . ' - ' .  $s_tree_item[ 'title' ];
                } else {
                    $local_orderKey = preg_replace( "/(0{3,3})+$/", "", $s_tree_item[ 'level' ] );
                    $tier           = ( strlen( $local_orderKey ) / 3 );
                    $s_data         = htmlspecialchars( $s_tree_item[ 'treeid' ] . ';' . $s_tree_item[ 'leafid' ] );
                    $tree_name = $s_tree_item[ 'name' ] . ' ' . str_repeat( '-', $tier * 2 ) . ' ' . $s_tree_item[ 'title' ];
                }
                $return[] = array('label' => $tree_name, 'value' => $s_data);

            }
        }
        header('Content-Type: application/json');
        print json_encode($return);
        exit;
    }

    function form_graph_delete( $report_id, $action_type, $report_type )
	{
		global $colors, $hash_type_names;

		/* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */

				if ( $report_type == 1 ) { // Graph Report
					$current_order = db_fetch_cell( "SELECT MAX(`order`) FROM plugin_nmidCreatePDF_GraphReports WHERE `Id`='" . $matches[ 1 ] . "'" );
					db_execute( "UPDATE `plugin_nmidCreatePDF_GraphReports` SET `order`=`order`-1 where `order` > '$current_order' AND `ReportId` = '$report_id'" );
					db_execute( "DELETE FROM `plugin_nmidCreatePDF_GraphReports` WHERE `Id`='" . $matches[ 1 ] . "'" );
				}
                elseif ( $report_type == 2 ) { // DSSTats Report
					$current_order = db_fetch_cell( "SELECT MAX(`order`) FROM plugin_nmidCreatePDF_DSStatsReports WHERE `Id`='" . $matches[ 1 ] . "'" );
					db_execute( "UPDATE `plugin_nmidCreatePDF_DSStatsReports` SET `order`=`order`-1 where `order` > '$current_order' AND `ReportId` = '$report_id'" );
					db_execute( "DELETE FROM `plugin_nmidCreatePDF_DSStatsReports` WHERE `Id`='" . $matches[ 1 ] . "'" );
				}
                elseif ( $report_type == 3 ) { // Multi Report
					$current_order = db_fetch_cell( "SELECT MAX(`order`) FROM plugin_nmidCreatePDF_MultiGraphReports WHERE `Id`='" . $matches[ 1 ] . "'" );
					db_execute( "UPDATE `plugin_nmidCreatePDF_MultiGraphReports` SET `order`=`order`-1 where `order` > '$current_order' AND `ReportId` = '$report_id'" );
					db_execute( "DELETE FROM `plugin_nmidCreatePDF_MultiGraphReports` WHERE `Id`='" . $matches[ 1 ] . "'" );
				}
			}
		}
	}

	function form_save( $reportId )
	{
		global $colors, $hash_type_names;
		$i_reportShowGraphHeader               = '0';
		$i_reportPrintDetailedFailedPollsTable = '0';
		$s_schedulePrependPDFFile              = '';
		$i_reportPrintDetailedPollsTable       = '0';
		$s_reportPageSize = '';
		$s_reportPageOrientation = '';
		if ( isset ( $_POST[ 'Name' ] ) ) {
			$s_reportName = filter_input( INPUT_POST, 'Name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'CoverPage' ] ) ) {
			$s_reportCoverPage = filter_input( INPUT_POST, 'CoverPage', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'CoverLogo' ] ) ) {
			$s_reportCoverLogo = filter_input( INPUT_POST, 'CoverLogo', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'pageSize' ] ) ) {
			$s_reportPageSize = filter_input( INPUT_POST, 'pageSize', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
			htmlentities( strip_tags( $_POST[ 'pageSize' ] ) );
		}
		if ( isset ( $_POST[ 'pageOrientation' ] ) ) {
			$s_reportPageOrientation = filter_input( INPUT_POST, 'pageOrientation', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
			htmlentities( strip_tags( $_POST[ 'pageOrientation' ] ) );
		}
		if ( isset ( $_POST[ 'pageGraphFormat' ] ) ) {
			$s_reportPageGraphFormat = filter_input( INPUT_POST, 'pageGraphFormat', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
			htmlentities( strip_tags( $_POST[ 'pageGraphFormat' ] ) );
		}
		if ( isset ( $_POST[ 'reportType' ] ) ) {
			$i_reportReportType = filter_input( INPUT_POST, 'reportType', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
			htmlentities( strip_tags( $_POST[ 'reportType' ] ) );
		}
		if ( isset ( $_POST[ 'outputFormat' ] ) ) {
			$i_reportOutputType = filter_input( INPUT_POST, 'outputFormat', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
			htmlentities( strip_tags( $_POST[ 'outputFormat' ] ) );
		}
        $s_reportAuthor = filter_input( INPUT_POST, 'author', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
        $s_reportCustomHeader = filter_input( INPUT_POST, 'customHeader', FILTER_UNSAFE_RAW );
        $s_reportCustomFooter = filter_input( INPUT_POST, 'customFooter', FILTER_UNSAFE_RAW );
		if ( isset ( $_POST[ 'showGraphHeader' ] ) ) {
			$i_reportShowGraphHeader = filter_input( INPUT_POST, 'showGraphHeader', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( $i_reportShowGraphHeader == "on" ) {
			$i_reportShowGraphHeader = '1';
		}
		if ( isset ( $_POST[ 'printDetailedFailedPollsTable' ] ) ) {
			$i_reportPrintDetailedFailedPollsTable = filter_input( INPUT_POST, 'printDetailedFailedPollsTable', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( $i_reportPrintDetailedFailedPollsTable == "on" ) {
			$i_reportPrintDetailedFailedPollsTable = '1';
		}
		if ( isset ( $_POST[ 'printDetailedPollsTable' ] ) ) {
			$i_reportPrintDetailedPollsTable = filter_input( INPUT_POST, 'printDetailedPollsTable', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( $i_reportPrintDetailedPollsTable == "on" ) {
			$i_reportPrintDetailedPollsTable = '1';
		}
		else {
			$i_reportPrintDetailedPollsTable = '0';
		}
		if ( isset ( $_POST[ 'PrependPDFFile' ] ) ) {
			$s_schedulePrependPDFFile = filter_input( INPUT_POST, 'PrependPDFFile', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'printHeader' ] ) ) {
			$s_printHeader = filter_input( INPUT_POST, 'printHeader', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else {
			$s_printHeader = '0';
		}
		if ( $s_printHeader == "on" ) {
			$s_printHeader = '1';
		}
		if ( isset ( $_POST[ 'printFooter' ] ) ) {
			$s_printFooter = filter_input( INPUT_POST, 'printFooter', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else {
			$s_printFooter = '0';
		}

		if ( $s_printFooter == "on" ) {
			$s_printFooter = '1';
		}

		if ( isset ( $_POST[ 'printPageNumbers' ] ) ) {
            $s_printPageNumbers = filter_input( INPUT_POST, 'printPageNumbers', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
        }
        else {
            $s_printPageNumbers = '0';
        }
        if ( $s_printPageNumbers == "on" ) {
            $s_printPageNumbers = '1';
        }

		if ( isset ( $_POST[ 'skipHFCoverPage' ] ) ) {
			$s_skipHFCoverPage = filter_input( INPUT_POST, 'skipHFCoverPage', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		else {
			$s_skipHFCoverPage = '0';
		}
		if ( $s_skipHFCoverPage == "on" ) {
			$s_skipHFCoverPage = '1';
		}
        $s_customReportTitle = filter_input( INPUT_POST, 'customReportTitle', FILTER_UNSAFE_RAW );
        $s_customSubReportTitle = filter_input( INPUT_POST, 'customSubReportTitle', FILTER_UNSAFE_RAW );
		if ( ( isset ( $_POST[ 'Name' ] ) ) && ( isset ( $_POST[ 'save_component_import' ] ) ) ) {
			$i_reportIncludeSubDirs = 0;
			if ( isset ( $_POST[ 'includeSubDirs' ] ) ) {
				$i_reportIncludeSubDirs = strip_tags( $_POST[ 'includeSubDirs' ] );
				if ( $i_reportIncludeSubDirs == "on" ) {
					$i_reportIncludeSubDirs = '1';
				}
				else {
					$i_reportIncludeSubDirs = '0';
				}
			}

			if ( $i_reportReportType == 4 ) { // Multi Reports only support Default Report Formats
				$s_reportPageGraphFormat = 0;
			}
			if ( isset ( $_POST[ 'Description' ] ) ) {
				$s_reportDescription = filter_input( INPUT_POST, 'Description', FILTER_UNSAFE_RAW );
			}
			if ( isset ( $_POST[ 'type' ] ) ) {
				$i_reportType = strip_tags( $_POST[ 'type' ] );
			}
			db_execute( "
			INSERT INTO `plugin_nmidCreatePDF_Reports`
				(
				 `Name`, `Description`,`reportType`, `leafId`, `includeSubDirs`,
				 `type`,`pageSize`,`pageOrientation`, `pageGraphFormat`, `Logo`,
				 `CoverPage`,`outputType`,`showGraphHeader`,`PrependPDFFile`,
				 `author`,`customHeader`,`customFooter`,`printDetailedFailedPollsTable`,
				 `printDetailedPollsTable`,`printHeader`,`printFooter`,`customReportTitle`,
				 `customSubReportTitle`,`skipHFCoverPage`,`printPageNumbers`
				)
			VALUES
				(
				 '$s_reportName', '$s_reportDescription','$i_reportReportType',
				 '-1','$i_reportIncludeSubDirs', '$i_reportType', '$s_reportPageSize',
				 '$s_reportPageOrientation', '$s_reportPageGraphFormat','$s_reportCoverLogo',
				 '$s_reportCoverPage','$i_reportOutputType','$i_reportShowGraphHeader',
				 '$s_schedulePrependPDFFile','$s_reportAuthor',
				 '$s_reportCustomHeader','$s_reportCustomFooter','$i_reportPrintDetailedFailedPollsTable',
				 '$i_reportPrintDetailedPollsTable','$s_printHeader','$s_printFooter','$s_customReportTitle',
				 '$s_customSubReportTitle','$s_skipHFCoverPage','$s_printPageNumbers'
				)
			" );
			header( "Location: CereusReporting_Reports.php" );
		}
		if ( ( isset ( $_POST[ 'Name' ] ) ) && ( isset ( $_POST[ 'update_component_import' ] ) ) ) {
			$i_reportIncludeSubDirs = 0;
			if ( isset ( $_POST[ 'includeSubDirs' ] ) ) {
				$i_reportIncludeSubDirs = strip_tags( $_POST[ 'includeSubDirs' ] );
				if ( $i_reportIncludeSubDirs == "on" ) {
					$i_reportIncludeSubDirs = '1';
				}
				else {
					$i_reportIncludeSubDirs = '0';
				}
			}
			if ( $i_reportReportType == 4 ) { // Multi Reports only support Default Report Formats
				$s_reportPageGraphFormat = 0;
			}
            $s_reportDescription = filter_input( INPUT_POST, 'Description', FILTER_UNSAFE_RAW );
			if ( isset ( $_POST[ 'type' ] ) ) {
				$i_reportType = strip_tags( $_POST[ 'type' ] );
			}


			$sql = "
			UPDATE `plugin_nmidCreatePDF_Reports`
			Set
				Name=:name,
				reportType=:report_type,
				includeSubDirs=:include_subdirs,
				Description=:description,
				pageSize=:page_size,
				Logo=:logo,
				CoverPage=:cover_page,
				pageOrientation=:page_orientation,
				pageGraphFormat=:page_graph_format,
				type=:type,
				outputType=:output_type,
				PrependPDFFile=:prepend_PDF_file,
				showGraphHeader=:show_graph_header,
			    printPageNumbers=:printPageNumbers,
				author=:author,
				customHeader=:custom_header,
				customFooter=:custom_footer,
				printDetailedFailedPollsTable=:print_detailed_failedpolls_table,
				printDetailedPollsTable=:print_detailed_polls_table,
				printHeader=:print_header,
				printFooter=:print_footer,
				skipHFCoverPage=:skip_HF_cover_page,
				customReportTitle=:custom_report_title,
				customSubReportTitle=:custom_subreport_title
			WHERE
				ReportId='$reportId'
			";


			// Get DB Instance
			$db = DBCxn::get();

			$stmt = $db->prepare( $sql );
			$stmt->bindValue( ':name', $s_reportName );
			$stmt->bindValue( ':report_type', $i_reportReportType );
			$stmt->bindValue( ':include_subdirs', $i_reportIncludeSubDirs );
			$stmt->bindValue( ':description', $s_reportDescription );
			$stmt->bindValue( ':page_size', $s_reportPageSize );
			$stmt->bindValue( ':logo', $s_reportCoverLogo );
			$stmt->bindValue( ':cover_page', $s_reportCoverPage );
			$stmt->bindValue( ':page_orientation', $s_reportPageOrientation );
			$stmt->bindValue( ':page_graph_format', $s_reportPageGraphFormat );
			$stmt->bindValue( ':type', $i_reportType );
			$stmt->bindValue( ':output_type', $i_reportOutputType );
			$stmt->bindValue( ':prepend_PDF_file', $s_schedulePrependPDFFile );
			$stmt->bindValue( ':show_graph_header', $i_reportShowGraphHeader );
			$stmt->bindValue( ':author', $s_reportAuthor );
			$stmt->bindValue( ':custom_header', $s_reportCustomHeader );
			$stmt->bindValue( ':custom_footer', $s_reportCustomFooter );
			$stmt->bindValue( ':print_detailed_failedpolls_table', $i_reportPrintDetailedFailedPollsTable );
			$stmt->bindValue( ':print_detailed_polls_table', $i_reportPrintDetailedPollsTable );
			$stmt->bindValue( ':print_header', $s_printHeader );
			$stmt->bindValue( ':print_footer', $s_printFooter );
            $stmt->bindValue( ':printPageNumbers', $s_printPageNumbers );
			$stmt->bindValue( ':skip_HF_cover_page', $s_skipHFCoverPage );
			$stmt->bindValue( ':custom_report_title', $s_customReportTitle );
			$stmt->bindValue( ':custom_subreport_title', $s_customSubReportTitle );
			$stmt->execute();
			$stmt->closeCursor();

			header( "Location: CereusReporting_addReport.php?ReportId=" . $reportId );
		}

	}


	function form_display( $reportId, $report_type )
	{
	global $colors, $hash_type_names, $config, $dir;


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

	$s_defaultName        = '';
	$s_defaultDescription = '';
	// $i_defaultIncludeSubDirs  = 0;
	// $i_defaultLeafId          = 'not defined';
	$i_defaultType                          = 1;
	$i_defaultGraphFormat                   = '0';
	$i_defaultPageOrientation               = 'P';
	$i_defaultPageSize                      = 'A4';
	$s_defaultCoverLogo                     = '';
	$s_defaultCoverPage                     = '';
	$i_defaultReportType                    = 0;
	$i_defaultOutputFormat                  = 0; // 0=PDF, 1=HTML
	$i_defaultShowGraphHeader               = 1;
	$s_defaultPrependPDFFile                = '';
	$s_defaultAuthor                        = '';
	$s_defaultCustomHeader                  = '';
	$s_defaultCustomFooter                  = '';
	$i_defaultPrintDetailedFailedPollsTable = 0;
	$i_defaultPrintDetailedPollsTable       = 0;
	$i_defaultPrintHeader                   = 0;
	$i_defaultPrintFooter                   = 0;
	$i_defaultPrintPageNumbers              = 1;
	$i_defaultSkipHFCoverPage               = 0;
	$s_defaultCustomReportTitle             = '';
	$s_defaultCustomSubReportTitle          = '';
	$s_defaultGraphWidth                    = '';
	$s_defaultGraphHeight                   = '';
	if ( $reportId > 0 ) {
		$a_reports = db_fetch_assoc( "
			SELECT
			  `plugin_nmidCreatePDF_Reports_Types`.`TypeId` as type,
			  `plugin_nmidCreatePDF_Reports_Types`.`Description` as typeDescription,
			  `plugin_nmidCreatePDF_Reports`.`Description` as Description,
			  `plugin_nmidCreatePDF_Reports`.`PrependPDFFile`,
			  `plugin_nmidCreatePDF_Reports`.`leafId`,
			  `plugin_nmidCreatePDF_Reports`.`outputType`,
			  `plugin_nmidCreatePDF_Reports`.`reportType`,
			  `plugin_nmidCreatePDF_Reports`.`Logo`,
			  `plugin_nmidCreatePDF_Reports`.`CoverPage`, 
			  `plugin_nmidCreatePDF_Reports`.`Name`,
			  `plugin_nmidCreatePDF_Reports`.`pageSize`,
			  `plugin_nmidCreatePDF_Reports`.`pageOrientation`,
			  `plugin_nmidCreatePDF_Reports`.`pageGraphFormat`,
			  `plugin_nmidCreatePDF_Reports`.`showGraphHeader`,
			  `plugin_nmidCreatePDF_Reports`.`author`,
			  `plugin_nmidCreatePDF_Reports`.`customHeader`,
			  `plugin_nmidCreatePDF_Reports`.`customFooter`,
			  `plugin_nmidCreatePDF_Reports`.`printDetailedFailedPollsTable`,
			  `plugin_nmidCreatePDF_Reports`.`printDetailedPollsTable`,
			  `plugin_nmidCreatePDF_Reports`.`printHeader`,
			  `plugin_nmidCreatePDF_Reports`.`printFooter`,
              `plugin_nmidCreatePDF_Reports`.`printPageNumbers`, 		       
			  `plugin_nmidCreatePDF_Reports`.`skipHFCoverPage`,
			  `plugin_nmidCreatePDF_Reports`.`customReportTitle`,
			  `plugin_nmidCreatePDF_Reports`.`customSubReportTitle`,
			  `plugin_nmidCreatePDF_Reports`.`customGraphWidth`,
			  `plugin_nmidCreatePDF_Reports`.`customGraphHeight`
		FROM
			  `plugin_nmidCreatePDF_Reports_Types` INNER JOIN
			  `plugin_nmidCreatePDF_Reports` ON `plugin_nmidCreatePDF_Reports`.`type` =
			  `plugin_nmidCreatePDF_Reports_Types`.`TypeId`		
			WHERE ReportId='$reportId'
		" );
		foreach ( $a_reports as $s_report ) {
			$s_defaultName        = $s_report[ 'Name' ];
			$s_defaultDescription = $s_report[ 'Description' ];
			$s_defaultCoverLogo   = $s_report[ 'Logo' ];
			$s_defaultCoverPage   = $s_report[ 'CoverPage' ];
			//$i_defaultIncludeSubDirs = $s_report['includeSubDirs'];	// 1 = true
			//$i_defaultLeafId          = $s_report[ 'leafId' ];
			$i_defaultReportType = $s_report[ 'reportType' ];
			$i_defaultType       = $s_report[ 'type' ];
			//$s_defaultTypeDescription = $s_report[ 'typeDescription' ];
			$i_defaultGraphFormat                   = $s_report[ 'pageGraphFormat' ];
			$i_defaultPageOrientation               = $s_report[ 'pageOrientation' ];
			$i_defaultPageSize                      = $s_report[ 'pageSize' ];
			$i_defaultOutputFormat                  = $s_report[ 'outputType' ];
			$i_defaultShowGraphHeader               = $s_report[ 'showGraphHeader' ];
			$s_defaultPrependPDFFile                = $s_report[ 'PrependPDFFile' ];
			$s_defaultAuthor                        = $s_report[ 'author' ];
			$s_defaultCustomHeader                  = $s_report[ 'customHeader' ];
			$s_defaultCustomFooter                  = $s_report[ 'customFooter' ];
			$i_defaultPrintDetailedFailedPollsTable = $s_report[ 'printDetailedFailedPollsTable' ];
			$i_defaultPrintDetailedPollsTable       = $s_report[ 'printDetailedPollsTable' ];
			$i_defaultPrintHeader                   = $s_report[ 'printHeader' ];
			$i_defaultPrintFooter                   = $s_report[ 'printFooter' ];
            $i_defaultPrintPageNumbers              = $s_report[ 'printPageNumbers' ];
			$i_defaultSkipHFCoverPage               = $s_report[ 'skipHFCoverPage' ];
			$s_defaultCustomReportTitle             = $s_report[ 'customReportTitle' ];
			$s_defaultCustomSubReportTitle          = $s_report[ 'customSubReportTitle' ];
			$s_defaultGraphWidth                    = $s_report[ 'customGraphWidth' ];
			$s_defaultGraphHeight                   = $s_report[ 'customGraphHeight' ];
		}
	}
	if ( $reportId > 0 ) {
		print "<font size=+1>CereusReporting - Add Report</font> ( <a id='downloadLink' href='createPDFReport_defined.php?ReportId=" . $reportId . "&mode=preview'>Preview Report</a> ) <br>\n";
	}
	else {
		print "<font size=+1>CereusReporting - Add Report</font><br>\n";
	}

	print "<hr>\n";

?>
<form name="ReportData" method="post" action="CereusReporting_addReport.php" enctype="multipart/form-data">
		<?php if (function_exists('csrf_get_tokens' )) { ?>
        <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
	<?php } ?>


		<?php

		if ( $reportId > 0 ) {
			html_start_box( "<strong>Report</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
		}
		else {
			html_start_box( "<strong>Report</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
		}

		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
    <td width="50%">
        <font class="textEditTitle">Report Name</font><br>
        The name of the report.
    </td>
    <td>
		<?php form_text_box( "Name", "", $s_defaultName, 255 ); ?>
    </td>
    </tr>

	<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Report Description</font><br>
        The detailed describtion of this report. This will be also be displayed in the report.
    </td>
    <td>
		<?php form_text_area( "Description", $s_defaultDescription, 5, 50, "" ); ?>
    </td>
    </tr>



        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Report Template</font><br>
            The PDF Template to use for this report. Uses default if left empty.
        </td>
        <td>
            <?php
                $fileCount                      = 1;
                $a_PageCoverPage                = array();
                $a_PageCoverPage[ 0 ][ 'name' ] = 'None';
                $a_PageCoverPage[ 0 ][ 'id' ]   = '';

                $a_ReportTemplates = db_fetch_assoc( "
            SELECT
              `plugin_CereusReporting_Reports_templates`.`name` AS `name`,
              `plugin_CereusReporting_Reports_templates`.`templateId` AS `id`
            FROM
              `plugin_CereusReporting_Reports_templates`" );

                $a_ReportTemplates = array_merge( $a_PageCoverPage, $a_ReportTemplates );
                form_dropdown( "CoverPage", $a_ReportTemplates, "name", "id", $s_defaultCoverPage, "", $s_defaultCoverPage, "", "" );
            ?>
        </td>
        </tr>

        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Report Cover Logo</font><br>
            The CoverLogo of this report. Uses default if left empty.
        </td>
        <td>
            <?php
                $fileCount                 = 1;
                $a_PageLogo                = array();
                $a_PageLogo[ 0 ][ 'name' ] = 'None';
                $a_PageLogo[ 0 ][ 'id' ]   = '';
                $a_templates               = array();
                $dirFiles                  = array();

                if ( $dh = opendir( __DIR__ . '/images/' ) ) {
                    while ( ( $file = readdir( $dh ) ) !== FALSE ) {
                        if ( !( is_dir( $file ) ) ) {
                            if ( file_exists( __DIR__ . '/images/' . $file ) ) {
                                $dirFiles[] = $file;
                            }
                        }
                    }
                    closedir( $dh );
                }
                sort( $dirFiles );
                foreach ( $dirFiles as $file ) {
                    if ( preg_match( "/([^.]+)_logo\..*$/i", $file, $matchme ) ) {
                        $templateName = 'images/' . $file;
                        if ( in_array( $templateName, $a_templates ) == FALSE ) {
                            $a_templates[ $templateName ]       = $templateName;
                            $a_PageLogo[ $fileCount ][ 'name' ] = $templateName;
                            $a_PageLogo[ $fileCount ][ 'id' ]   = $templateName;
                            $fileCount++;
                        }
                    }
                }
                form_dropdown( "CoverLogo", $a_PageLogo, "name", "id", $s_defaultCoverLogo, "", $s_defaultCoverLogo, "", "" );
            ?>
        </td>
        </tr>


        <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Report Cover Page</font><br>
            The optional Cover page of this report. Uses none if left empty.
        </td>
        <td>
            <?php
                $fileCount                  = 1;
                $a_CoverFile                = array();
                $a_CoverFile[ 0 ][ 'name' ] = 'None';
                $a_CoverFile[ 0 ][ 'id' ]   = '';
                $a_templates                = array();
                $dirFiles                   = array();

                    if ( $dh = opendir( __DIR__ . '/templates/coverpages/' ) ) {
                        while ( ( $file = readdir( $dh ) ) !== FALSE ) {
                            if ( !( is_dir( $file ) ) ) {
                                if ( file_exists( __DIR__ . '/templates/coverpages/' . $file ) ) {
                                    $dirFiles[] = $file;
                                }
                            }
                        }
                        closedir( $dh );
                    }
                sort( $dirFiles );
                foreach ( $dirFiles as $templateName ) {
                    if ( in_array( $templateName, $a_templates ) == FALSE ) {
                        $a_templates[ $templateName ]        = $templateName;
                        $a_CoverFile[ $fileCount ][ 'name' ] = $templateName;
                        $a_CoverFile[ $fileCount ][ 'id' ]   = $templateName;
                        $fileCount++;
                    }
                }
                form_dropdown( "PrependPDFFile", $a_CoverFile, "name", "id", $s_defaultPrependPDFFile, "", $s_defaultPrependPDFFile, "", "" );
            ?>
        </td>
        </tr>

	<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Report Type</font><br>
        Select if this is a normal report, a graph report or a special DSSTATs report.
    </td>
    <td>
		<?php
			//          $a_ReportTypes[0]['name'] = 'Normal Report';
			//			$a_ReportTypes[0]['id'] = '0';
			$a_ReportTypes                = array();
			$a_ReportTypes[ 3 ][ 'name' ] = 'Multi Report';
			$a_ReportTypes[ 3 ][ 'id' ]   = '3';
			// $a_ReportTypes[ 5 ][ 'name' ] = 'Test Report';
			// $a_ReportTypes[ 5 ][ 'id' ]   = '4';
            $a_ReportTypes[ 2 ][ 'name' ] = 'DSStats Report';
            $a_ReportTypes[ 2 ][ 'id' ]   = '2';
			if ( ( $reportId > 0 ) && ( isset( $i_defaultReportType ) ) ) {
				$a_ReportTypes[ 1 ][ 'name' ] = 'Graph Report';
				$a_ReportTypes[ 1 ][ 'id' ]   = '1';
				echo $a_ReportTypes[ $i_defaultReportType ][ 'name' ];
				form_hidden_box( "reportType", $i_defaultReportType, "" );
			}
			else {
				form_dropdown( "reportType", $a_ReportTypes, "name", "id", $i_defaultReportType, "", $i_defaultReportType, "", "" );
			}

		?>
    </td>
    </tr>

	<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
    <td width="50%">
        <font class="textEditTitle">Add Graph Heading</font><br>
        This will add a header before each graph describing the information being displayed. Essentially this is the
        same as the title of the graph
    </td>
    <td>
		<?php // CRC-27
			if ( $i_defaultShowGraphHeader == 1 ) {
				$i_defaultShowGraphHeader = 'on';
			}
			form_checkbox( "showGraphHeader", $i_defaultShowGraphHeader, "Add Graph Headings", "",0,'','' ,'',true  ); ?>
    </td>
    </tr>

    <?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Add Page Numbers to Report</font><br>
        This will add Pagenumbers to the report footer
    </td>
    <td>
        <?php // CRC-27
            if ( $i_defaultPrintPageNumbers == 1 ) {
                $i_defaultPrintPageNumbers = 'on';
            }
            form_checkbox( "printPageNumbers", $i_defaultPrintPageNumbers, "Add Page Numbers to report", "",0,'','' ,'',true  ); ?>
    </td>
    </tr>


	<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
    <td width="50%">
        <font class="textEditTitle">Default Report Timespan</font><br>
        The default report timespane of this report.
    </td>
    <td>
		<?php
			$a_timeSpans = db_fetch_assoc( "
				SELECT
				  `plugin_nmidCreatePDF_Reports_Types`.`Description` AS `name`,
				  `plugin_nmidCreatePDF_Reports_Types`.`TypeId` AS `id`
				FROM
				  `plugin_nmidCreatePDF_Reports_Types`;
			" );
			form_dropdown( "type", $a_timeSpans, "name", "id", $i_defaultType, "", $i_defaultType, "", "" );
		?>
    </td>
    </tr>

	<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Report Graph Format</font><br>
        Use one or more columns.
    </td>
    <td>
		<?php
			$a_GraphFormat                = array();
			$a_GraphFormat[ 0 ][ 'name' ] = 'Default';
			$a_GraphFormat[ 0 ][ 'id' ]   = '1,0';
			if ( $i_defaultReportType != 4 ) {
					$a_GraphFormat[ 1 ][ 'name' ] = '2 Columns';
					$a_GraphFormat[ 1 ][ 'id' ]   = '2,95';
					$a_GraphFormat[ 2 ][ 'name' ] = '3 Columns';
					$a_GraphFormat[ 2 ][ 'id' ]   = '3,57';

			}
			form_dropdown( "pageGraphFormat", $a_GraphFormat, "name", "id", $i_defaultGraphFormat, "", $i_defaultGraphFormat, "", "" );
		?>
    </td>
    </tr>

	<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
    <td width="50%">
        <font class="textEditTitle">Report Output Format</font><br>
        The Output Format is always PDF
    </td>
    <td>
		<?php
			$a_OutputFormat                = array();
			$a_OutputFormat[ 0 ][ 'name' ] = 'PDF';
			$a_OutputFormat[ 0 ][ 'id' ]   = '0';+
			form_dropdown( "outputFormat", $a_OutputFormat, "name", "id", $i_defaultOutputFormat, "", $i_defaultOutputFormat, "", "" );
		?>
    </td>
    </tr>

	<?php
		// CRC-4
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Author</font><br>
        The author of this report. Defaults to the currently logged on cacti user.
    </td>
    <td>
		<?php form_text_box( "author", "", $s_defaultAuthor, 255 ); ?>
    </td>
    </tr>

	<?php
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
    <td width="50%">
        <font class="textEditTitle">Custom Header</font><br>
        A custom header. If emtpy defaults to the global header.<br>
        Supported Tags: <code>[START][END][AUTHOR][REPORTTITLE][REPORTSUBTITLE][REPORTDATE]</code>
    </td>
    <td>
		<?php
    		form_text_area( "customHeader", $s_defaultCustomHeader, 5, 50, "" );
		?>
    </td>
    </tr>

	<?php
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Custom Footer</font><br>
        A custom footer. If emtpy defaults to the global footer.<br>
        Supported Tags: <code>[START][END][AUTHOR][REPORTTITLE][REPORTDATE]</code>
    </td>
    <td>
		<?php
			form_text_box( "customFooter", "", $s_defaultCustomFooter, 255 );
		?>
    </td>
    </tr>

	<?php
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
    <td width="50%">
        <font class="textEditTitle">Custom Report Title</font><br>
        A custom report title. If emtpy defaults to the global report title.<br>
    </td>
    <td>
		<?php
			$s_globalReportTitle = readConfigOption( 'nmid_pdftitle' );
			if ( strlen( $s_defaultCustomReportTitle ) == 0 ) {
				$s_defaultCustomReportTitle = $s_globalReportTitle;
			}
			form_text_box( "customReportTitle", "", $s_defaultCustomReportTitle, 255 );
		?>
    </td>
    </tr>


	<?php
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Custom Sub Report Title</font><br>
        A custom sub report title footer. If emtpy defaults to the global sub report title.<br>
    </td>
    <td>
		<?php
			$s_globalSubReportTitle = readConfigOption( 'nmid_pdfsubtitle' );
			if ( strlen( $s_defaultCustomSubReportTitle ) == 0 ) {
				$s_defaultCustomSubReportTitle = $s_globalSubReportTitle;
			}
			form_text_box( "customSubReportTitle", "", $s_defaultCustomSubReportTitle, 255 );
		?>
    </td>
    </tr>


	<?php // CRC-5
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
    <td width="50%">
        <font class="textEditTitle">Print detailed (failed) polls table</font><br>
        This will print the detailed polls and/or detailed failed polls table underneath the SLA graph.
    </td>
    <td>
		<?php
			if ( $i_defaultPrintDetailedFailedPollsTable == 1 ) {
				$i_defaultPrintDetailedFailedPollsTable = 'on';
			}
			form_checkbox( "printDetailedFailedPollsTable", $i_defaultPrintDetailedFailedPollsTable, "Print detailed failed polls table", "",0,'','' ,'',true );
		?>
    </td>
    </tr>

	<?php // CRC-5
		form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
    <td width="50%">
        <font class="textEditTitle">Print Header/Footer</font><br>
        This will enable/disable the header/footer for the report. You can enable them within the report using special
        report items.
    </td>
    <td>
		<?php
			if ( $i_defaultPrintHeader == 1 ) {
				$i_defaultPrintHeader = 'on';
			}
			if ( $i_defaultPrintFooter == 1 ) {
				$i_defaultPrintFooter = 'on';
			}
			if ( $i_defaultSkipHFCoverPage == 1 ) {
				$i_defaultSkipHFCoverPage = 'on';
			}
			form_checkbox( "printHeader", $i_defaultPrintHeader, "Add the Header to the report", "",0,'','' ,'',true );
			form_checkbox( "printFooter", $i_defaultPrintFooter, "Add the Footer to the report", "",0,'','' ,'',true  );
			form_checkbox( "skipHFCoverPage", $i_defaultSkipHFCoverPage, "Skip Header/Footer on Coverpage", "",0,'','' ,'',true  );

		?>
    </td>
    </tr>

	<?php

		if ( $reportId > 0 ) {
			form_hidden_box( "update_component_import", "1", "" );
			form_hidden_box( "ReportId", $reportId, "" );
			form_hidden_box( "ReportType", $report_type, "" );
		}
		else {
			form_hidden_box( "save_component_import", "1", "" );
		}
		html_end_box();
		form_save_button( "CereusReporting_Reports.php", "", 'ReportId' );

	?>
	<?php
		//<form name="DataList" method="post" action="CereusReporting_addReport.php" enctype="multipart/form-data">
		//form_hidden_box("ReportId",$reportId,"");
		//form_hidden_box("ReportType",$report_type,"");

		// Graph Report Data
		if ( $reportId > 0 ) {
		$reportType = db_fetch_cell( "SELECT reportType FROM plugin_nmidCreatePDF_Reports WHERE ReportId=" . $reportId );
		if ( $reportType == 2 ) { // DSSTats Reports
		$a_reports = db_fetch_assoc( "
				SELECT
				  `plugin_nmidCreatePDF_DSStatsReports`.`Id`,
				  `plugin_nmidCreatePDF_DSStatsReports`.`DSStatsGraph`,
				  `plugin_nmidCreatePDF_DSStatsReports`.`Description` as description
				FROM
				  `plugin_nmidCreatePDF_DSStatsReports`
				WHERE
				  `plugin_nmidCreatePDF_DSStatsReports`.`ReportId` = $reportId 
				ORDER BY `order`;
			" );

		html_start_box( "<strong>Local DSStats Graphs for this report</strong>", "100%", $colors[ "header" ], "3", "center", "CereusReporting_addDSStatsReport.php?action=add&ReportId=" . $reportId );

		if ( sizeof( $a_reports ) > 0 ) {
			$menu_text = array(
				"Id",
				"DSStats Graph",
				"Description",
				"Order"
			);

			html_header_checkbox( $menu_text, TRUE );
			form_hidden_box( "delete_graphs", "0", "" );
			form_hidden_box( "ReportId", $reportId, "" );
			form_hidden_box( "ReportType", $reportType, "" );
			$i = 0;

			foreach ( $a_reports as $s_report ) {
				form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $s_report[ 'Id' ] );
				$i++;
				form_selectable_cell( $s_report[ 'Id' ], $s_report[ "Id" ] );
				form_selectable_cell( $s_report[ 'DSStatsGraph' ], $s_report[ "Id" ] );
				form_selectable_cell( $s_report[ 'description' ], $s_report[ "Id" ] );
				print "<td>
						<a href='CereusReporting_orderDSStatsGraphs.php?action=item_movedown&report_id=" . $reportId . "&dsstats_id=" . $s_report[ 'Id' ] . "'><img src='" . $config[ 'url_path' ] . "images/move_down.gif' border='0' alt='Move Down'></a><a href='CereusReporting_orderDSStatsGraphs.php?action=item_moveup&report_id=" . $reportId . "&dsstats_id=" . $s_report[ 'Id' ] . "'><img src='" . $config[ 'url_path' ] . "images/move_up.gif' border='0' alt='Move Up'></a>
						</td>\n";

				form_checkbox_cell( 'selected_items', $s_report[ "Id" ] );

				form_end_row();
			}
			html_end_box( FALSE );

			$task_actions = array(
				2 => "Delete"
			);
			cr_draw_actions_dropdown( $task_actions, 0 );
		}
		else {
			print "<tr><td><em>No Graphs exist</em></td></tr>";
			html_end_box( FALSE );
		}

		print "</form>";
	}
        elseif ( $reportType == 3 ) { // Multi Graphs
		$a_reports = db_fetch_assoc( "
                    SELECT
                      `plugin_nmidCreatePDF_MultiGraphReports`.`Id`,
                      `plugin_nmidCreatePDF_MultiGraphReports`.`type`,
                      `plugin_nmidCreatePDF_MultiGraphReports`.`data`,
                      `plugin_nmidCreatePDF_MultiGraphReports`.`order`
                    FROM
                      `plugin_nmidCreatePDF_MultiGraphReports`
                    WHERE
                      `plugin_nmidCreatePDF_MultiGraphReports`.`ReportId` = $reportId
                    ORDER BY `order`;
                " );
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

         .ui-selectmenu-menu {
        z-index: 102;
    }

   .overflow
   {
       height: 200px;
       position: absolute;
       z-index: 1;
   }
    </style>
    <script type='text/javascript' src='libs/jquery.tablednd_0_5.js'></script>

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


        $(document).ready(function () {
            // Initialise the table
            $('#reportItems').tableDnD({
                onDrop: function (table, row) {
                    var order = $.tableDnD.serialize() + '&ReportId=<?php echo $reportId; ?>';
                    $.get("cereusReporting_updateDB.php", order);
                }
            });

            $("#nmidDialogChapter").dialog({
                autoOpen: false,
                modal: true
            });

            $("#nmidDialogOpenerChapter").click(function () {
                $("#nmidDialogChapter").dialog("open");
                return false;
            });

            $("#nmidDialogTitle").dialog({
                autoOpen: false,
                modal: true
            });

            $("#nmidDialogOpenerTitle").click(function () {
                $("#nmidDialogTitle").dialog("open");
                return false;
            });

            $("#nmidDialogText").dialog({
                autoOpen: false,
                modal: true
            });

            $("#nmidDialogOpenerText").click(function () {
                $("#nmidDialogText").dialog("open");
                return false;
            });

            $("#nmidDialogBookmark").dialog({
                autoOpen: false,
                modal: true
            });

            $("#nmidDialogOpenerBookmark").click(function () {
                $("#nmidDialogBookmark").dialog("open");
                return false;
            });

            $("#nmidDialogPDFFile").dialog({
                autoOpen: false,
                modal: true
            });

            $("#nmidDialogOpenerPDFFile").click(function () {
                $("#nmidDialogPDFFile").dialog("open");
                return false;
            });

            $("#nmidDialogRegExp").dialog({
                autoOpen: false,
                width: 400,
                modal: true
            });

            $("#nmidDialogOpenerRegExp").click(function () {
                $("#nmidDialogRegExp").dialog("open");
                return false;
            });

            $("#nmidDialogTreeItem").dialog({
                autoOpen: false,
                width: 400,
                modal: true
            });

            $("#nmidDialogOpenerTreeItem").click(function () {
                $("#nmidDialogTreeItem").dialog("open");
                return false;
            });

            $("#nmidDialogHostItem").dialog({
                autoOpen: false,
                width: 400,
                modal: true
            });

            $("#nmidDialogOpenerHostItem").click(function () {
                $("#nmidDialogHostItem").dialog("open");
                return false;
            });

            $("#nmidDialogGraphItem").dialog({
                autoOpen: false,
                width: 400,
                modal: true
            });

            $("#nmidDialogOpenerGraphItem").click(function () {
                $("#nmidDialogGraphItem").dialog("open");
                return false;
            });

             $( "select[id^=dropdown]" ).selectmenu();
             $( 'div.ui-front:has("ul[id^=dropdown_]")').css('z-index',1005);

        });
    </script>
    <div id="fader">
        <p style="font-size: 30px; text-align: center; vertical-align: middle; color: white;">Generating Preview Report ...</p>
    </div>

    <div id="nmidDialogChapter" title='Add a Chapter to this report' class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="5">
            <input type="hidden" name="save_component_import" value="1">
           <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>
            <br/>

            Chapter: <input type="text" name="Data" value=""> <input type="submit" value="Add" alt="Add">
        </form>
    </div>
    <div id="nmidDialogTitle" title='Add a Title to this report' class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="4">
            <input type="hidden" name="save_component_import" value="1">
            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>

            Title: <input type="text" name="Data" value=""> <input type="submit" value="Add" alt="Add">
        </form>
    </div>
    <div id="nmidDialogText" title='Add a Text to this report' class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="3">
            <input type="hidden" name="save_component_import" value="1">
            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>
            <br/>

            Text: <br/>
            <textarea rows="6" cols="40" name="Data" value=""></textarea><br/>
            <input type="submit" value="Add" alt="Add">
        </form>
    </div>
    <div id="nmidDialogBookmark" title='Add a Bookmark to this report' class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="21">
            <input type="hidden" name="save_component_import" value="1">
            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>
            <br/>

            Bookmark Text: <input type="text" name="Data" value=""> <input type="submit" value="Add" alt="Add">
        </form>
    </div>

    <div id="nmidDialogPDFFile" title='Add a Bookmark to this report' class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="23">
            <input type="hidden" name="save_component_import" value="1">

            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>
            <br/>
            PDF File to add:<br/>
            <select name="Data" id="Data">
				<?php
                    $dirFiles = array();
                    if ( $dh = opendir( __DIR__ . '/server/php/files/' ) ) {
                        while ( ( $file = readdir( $dh ) ) !== FALSE ) {
                            if ( !( is_dir( $file ) ) ) {
                                if ( file_exists( __DIR__ . '/server/php/files/' . $file ) ) {
                                    if ( preg_match( "/.pdf$/i", $file ) ) {
                                        $dirFiles[] = $file;
                                    }
                                }
                            }
                        }
                        closedir( $dh );
                    }
					sort( $dirFiles );
					foreach ( $dirFiles as $templateName ) {
						echo '<option value="' . __DIR__ . '/server/php/files/' . $templateName . '">' . $templateName . '</option>' . "\n";
					}
				?>
            </select>
            <input type="submit" value="Add" alt="Add">
        </form>
    </div>

    <div id="nmidDialogRegExp" title='Add a RegExp item to this report' style="width:450px;"
         class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="24">
            <input type="hidden" name="save_component_import" value="1">
            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>
            <br/>
            Select Tree/Sub-Tree where this regexp should apply for:<br/>
            <select name="Data" id="Data" class="ui-state-default ui-corner-all">
				<?php
					$a_tree_list = cr_get_trees();
					if ( sizeof( $a_tree_list ) > 0 ) {
						foreach ( $a_tree_list as $s_tree_item ) {
							if ( function_exists('top_header')) {
								$s_data         = htmlspecialchars( $s_tree_item[ 'treeid' ] . ';' . $s_tree_item[ 'leafid' ] );
								echo '<option value="' . $s_data . '">' . $s_tree_item[ 'name' ] . ' - ' .  $s_tree_item[ 'title' ] . '</option>';
							} else {
								$local_orderKey = preg_replace( "/(0{3,3})+$/", "", $s_tree_item[ 'level' ] );
								$tier           = ( strlen( $local_orderKey ) / 3 );
								$s_data         = htmlspecialchars( $s_tree_item[ 'treeid' ] . ';' . $s_tree_item[ 'leafid' ] );
								echo '<option value="' . $s_data . '">' . $s_tree_item[ 'name' ] . ' ' . str_repeat( '-', $tier * 2 ) . ' ' . $s_tree_item[ 'title' ] . '</option>';
							}
						}
					}
				?>
            </select><br/>
            Select the Filter type:<br/>
            <select name="DataRegExpItem" id="DataRegExpItem">
                <option value="graph">Filter on graph title</option>
                <option value="host">Filter on host description</option>
            </select><br/>
            Enter the Regular expression:<br/>
            <input size=40 type="text" name="DataRegExp" value=""><br/>
            <input type="submit" value="Add" alt="Add">
        </form>
    </div>

    <div id="nmidDialogTreeItem" title='Add a Tree/Sub-Tree item to this report' style="width:450px;"
         class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="22">
            <input type="hidden" name="save_component_import" value="1">
            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
             <?php } ?>
            <br/>
            Select Tree/Sub-Tree where this regexp should apply for:<br/>
            <script type="text/javascript">
            $( function() {
                var pageName = basename($(location).attr('pathname'));

                // Single Select
                $( "#TreeData_select" ).autocomplete({
                  source: function( request, response ) {
                   // Fetch data
                   $.ajax({
                    url: pageName,
                    type: 'get',
                    dataType: "json",
                    data: {
                       action: 'ajax_trees',
                       term: request.term
                    },
                    success: function( data ) {
                        response( data );
                    }
                   });
                  },
                  autoFocus: true,
                  minLength: 0,
                  select: function (event, ui) {
                   $('#TreeData_select').val(ui.item.label); // display the selected text
                   $('#TreeData').val(ui.item.id); // save selected id to input
                   return false;
                  }
                 }).addClass('ui-state-default ui-selectmenu-text').css('border', 'none').css('background-color', 'transparent');

                 $('#TreeData_click').css('z-index', '4');

                $('#TreeData_wrapper').unbind().dblclick(function() {
                    treeOpen = false;
                    clearTimeout(treeTimer);
                    clearTimeout(clickTimeout);
                    $('#TreeData_select').autocomplete('close');
                }).click(function() {
                    if (treeOpen) {
                        $('#TreeData_select').autocomplete('close');
                        clearTimeout(treeTimer);
                        treeOpen = false;
                    }else{
                        clickTimeout = setTimeout(function() {
                            $('#TreeData_select').autocomplete('search', '');
                            clearTimeout(treeTimer);
                            treeOpen = true;
                        }, 200);
                    }
                }).on('mouseenter', function() {
                    $(this).addClass('ui-state-hover');
                    $('input#TreeData_select').addClass('ui-state-hover');
                }).on('mouseleave', function() {
                    $(this).removeClass('ui-state-hover');
                    $('#TreeData_select').removeClass('ui-state-hover');
                    treeTimer = setTimeout(function() { $('#TreeData_select').autocomplete('close'); }, 800);
                    treeOpen = false;
                });

                var treePrefix = '';
                $('#TreeData_select').autocomplete('widget').each(function() {
                    treePrefix=$(this).attr('id');

                    if (treePrefix != '') {
                        $('ul[id="'+treePrefix+'"]').on('mouseenter', function() {
                            clearTimeout(treeTimer);
                        }).on('mouseleave', function() {
                            treeTimer = setTimeout(function() { $('#TreeData_select').autocomplete('close'); }, 800);
                            $(this).removeClass('ui-state-hover');
                            $('input#TreeData_select').removeClass('ui-state-hover');
                        });
                    }
                });
            });
            </script>

            <span id='TreeData_wrapper' style='width:200px;' class='ui-selectmenu-button ui-selectmenu-button-closed ui-corner-all ui-corner-all ui-button ui-widget'>
			<span id='TreeData_click' class='ui-selectmenu-icon ui-icon ui-icon-triangle-1-s'></span>
                <span class='ui-select-text'>
				    <input type='text' size='28' id='TreeData_select' value=''>
				</span>
		    </span>
            <input type='hidden' id='TreeData' name='TreeData' value=''>
            <br/>
            <input type="submit" value="Add" alt="Add">
        </form>
    </div>



    <div id="nmidDialogHostItem" title='Add a host to this report' style="width:450px;"
         class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="25">
            <input type="hidden" name="save_component_import" value="1">
            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>
            <br/>
            Select the device/host you want to add to the report:<br/>

            <script type="text/javascript">
            $( function() {
                var pageName = basename($(location).attr('pathname'));

                // Single Select
                $( "#HostData_select" ).autocomplete({
                  source: function( request, response ) {
                   // Fetch data
                   $.ajax({
                    url: pageName,
                    type: 'get',
                    dataType: "json",
                    data: {
                       action: 'ajax_hosts',
                       term: request.term
                    },
                    success: function( data ) {
                        response( data );
                    }
                   });
                  },
                  autoFocus: true,
                  minLength: 0,
                  select: function (event, ui) {
                   $('#HostData_select').val(ui.item.label); // display the selected text
                   $('#HostData').val(ui.item.id); // save selected id to input
                   return false;
                  }
                 }).addClass('ui-state-default ui-selectmenu-text').css('border', 'none').css('background-color', 'transparent');;

                 $('#nmidHost_click').css('z-index', '4');
                $('#nmidHost_wrapper').unbind().dblclick(function() {
                    hostOpen = false;
                    clearTimeout(hostTimer);
                    clearTimeout(clickTimeout);
                    $('#HostData_select').autocomplete('close');
                }).click(function() {
                    if (hostOpen) {
                        $('#HostData_select').autocomplete('close');
                        clearTimeout(hostTimer);
                        hostOpen = false;
                    }else{
                        clickTimeout = setTimeout(function() {
                            $('#HostData_select').autocomplete('search', '');
                            clearTimeout(hostTimer);
                            hostOpen = true;
                        }, 200);
                    }
                }).on('mouseenter', function() {
                    $(this).addClass('ui-state-hover');
                    $('input#HostData_select').addClass('ui-state-hover');
                }).on('mouseleave', function() {
                    $(this).removeClass('ui-state-hover');
                    $('#HostData_select').removeClass('ui-state-hover');
                    hostTimer = setTimeout(function() { $('#HostData_select').autocomplete('close'); }, 800);
                    hostOpen = false;
                });

                var hostPrefix = '';
                $('#HostData_select').autocomplete('widget').each(function() {
                    hostPrefix=$(this).attr('id');

                    if (hostPrefix != '') {
                        $('ul[id="'+hostPrefix+'"]').on('mouseenter', function() {
                            clearTimeout(hostTimer);
                        }).on('mouseleave', function() {
                            hostTimer = setTimeout(function() { $('#HostData_select').autocomplete('close'); }, 800);
                            $(this).removeClass('ui-state-hover');
                            $('input#HostData_select').removeClass('ui-state-hover');
                        });
                    }
                });
            });
            </script>

            <span id='nmidHost_wrapper' style='width:200px;' class='ui-selectmenu-button ui-selectmenu-button-closed ui-corner-all ui-corner-all ui-button ui-widget'>
			<span id='nmidHost_click' class='ui-selectmenu-icon ui-icon ui-icon-triangle-1-s'></span>
                <span class='ui-select-text'>
				    <input type='text' size='28' id='HostData_select' value=''>
				</span>
		    </span>
            <input type='hidden' id='HostData' name='HostData' value=''>
            <br/>
            <input type="submit" value="Add" alt="Add">
        </form>
    </div>

    <div id="nmidDialogGraphItem" title='Add a graph to this report' style="width:450px;"
         class="ui-dialog-content ui-widget-content">
        <form name="CereusReporting_ItemForm" method="post" action="CereusReporting_addMultiReport.php">
            <input type="hidden" name="ReportId" value="<?php echo $reportId; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="itemType" value="0">
            <input type="hidden" name="save_component_import" value="1">
            <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
            <?php } ?>
            <br/>
            Select the graph you want to add to  the report:<br/>

            <script type="text/javascript">
            $( function() {
                var pageName = basename($(location).attr('pathname'));

                // Single Select
                $( "#GraphData_select" ).autocomplete({
                  source: function( request, response ) {
                   // Fetch data
                   $.ajax({
                    url: pageName,
                    type: 'get',
                    dataType: "json",
                    data: {
                       action: 'ajax_hosts_graphs',
                       term: request.term
                    },
                    success: function( data ) {
                        response( data );
                    }
                   });
                  },
                  autoFocus: true,
                  minLength: 0,
                  select: function (event, ui) {
                   $('#GraphData_select').val(ui.item.label); // display the selected text
                   $('#GraphData').val(ui.item.value); // save selected id to input
                   return false;
                  }
                 }).addClass('ui-state-default ui-selectmenu-text').css('border', 'none').css('background-color', 'transparent');;

                  $('#nmidGraph_click').css('z-index', '4');
                $('#nmidGraph_wrapper').unbind().dblclick(function() {
                    hostOpen = false;
                    clearTimeout(hostTimer);
                    clearTimeout(clickTimeout);
                    $('#GraphData_select').autocomplete('close');
                }).click(function() {
                    if (hostOpen) {
                        $('#GraphData_select').autocomplete('close');
                        clearTimeout(hostTimer);
                        hostOpen = false;
                    }else{
                        clickTimeout = setTimeout(function() {
                            $('#GraphData_select').autocomplete('search', '');
                            clearTimeout(hostTimer);
                            hostOpen = true;
                        }, 200);
                    }
                }).on('mouseenter', function() {
                    $(this).addClass('ui-state-hover');
                    $('input#GraphData_select').addClass('ui-state-hover');
                }).on('mouseleave', function() {
                    $(this).removeClass('ui-state-hover');
                    $('#GraphData_select').removeClass('ui-state-hover');
                    hostTimer = setTimeout(function() { $('#GraphData_select').autocomplete('close'); }, 800);
                    hostOpen = false;
                });

                var hostPrefix = '';
                $('#GraphData_select').autocomplete('widget').each(function() {
                    hostPrefix=$(this).attr('id');

                    if (hostPrefix != '') {
                        $('ul[id="'+hostPrefix+'"]').on('mouseenter', function() {
                            clearTimeout(hostTimer);
                        }).on('mouseleave', function() {
                            hostTimer = setTimeout(function() { $('#GraphData_select').autocomplete('close'); }, 800);
                            $(this).removeClass('ui-state-hover');
                            $('input#GraphData_select').removeClass('ui-state-hover');
                        });
                    }
                });
            });
            </script>

            <span id='nmidGraph_wrapper' style='width:200px;' class='ui-selectmenu-button ui-selectmenu-button-closed ui-corner-all ui-corner-all ui-button ui-widget'>
			<span id='nmidGraph_click' class='ui-selectmenu-icon ui-icon ui-icon-triangle-1-s'></span>
			    <span class='ui-select-text'>
				    <input type='text' size='28' id='GraphData_select' value=''>
				</span>
		    </span>
            <input type='hidden' id='GraphData' name='GraphData' value=''>
            <br/>
            <input type="submit" value="Add" alt="Add">
        </form>
    </div>

    <table cellpadding=3 cellspacing=0 border=0 bgcolor="#E1E1E1" width="100%">
        <tr>
            <td>
                You can order the items of this report using Drag&Drop functionality.<br/>
                Items in red are not supported in your CereusReporting Edition
            </td>
        </tr>
    </table>

    <table class="table table-striped table-bordered table-condensed" id=reportItems cellpadding=3 cellspacing=0
           border=0
           bgcolor="#E1E1E1" width="100%">
        <tr id=line0 class="nodrop nodrag">

            <td bgcolor="#00438C" style="padding: 3px;" colspan="100">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td bgcolor="#00438C" class="textHeaderDark"><strong>Items for this report</strong></td>
                        <td bgcolor="#00438C" class="textHeaderDark" align="right">
                            <strong>Add new Item:</strong>
                            <button id='nmidDialogOpenerChapter'>
                                <img src="images/chapter.png" alt="Add Chapter"/>
                                <br/>
                                Chapter
                            </button>
                            <button id='nmidDialogOpenerTitle'>
                                <img src="images/title.png" alt="Add Title"/>
                                <br/>
                                Title
                            </button>
                            <button id='nmidDialogOpenerText'>
                                <img src="images/text.png" alt="Add Text"/>
                                <br/>
                                Text
                            </button>
                            <button id='nmidDialogOpenerGraphItem'>
                                <img src="images/graph.png" alt="Add Graph"/>
                                <br/>
                                Graph
                            </button>
                            <button id='nmidDialogOpenerHostItem'>
                                <img src="images/server.png" alt="Add Host"/>
                                <br/>
                                Host
                            </button>
                            <a href="CereusReporting_addMultiReport.php?action=save&itemType=15&save_component_import=1&ReportId=<?php echo $reportId; ?>">
                                <button>
                                    <img src="images/pagebreak.png" alt="Add Pagebreak"/>
                                    <br/>
                                    Pagebreak
                                </button>
                            </a>
							<?php if ( ( EDITION == "CORPORATE" ) || ( isSMBServer() ) ) { ?>
                                <button id='nmidDialogOpenerRegExp'>
                                    <img src="images/search.png" alt="Add RegExp"/>
                                    <br/>
                                    RegExp
                                </button>
                                <button id='nmidDialogOpenerBookmark'>
                                    <img src="images/bookmark.png" alt="Add Bookmark"/>
                                    <br/>
                                    Bookmark
                                </button>
                                <!-- <button>
									<img src="images/image.png" alt="Add Image"/>
									<br/>
									Image
								</button> -->
                                <button id='nmidDialogOpenerPDFFile'>
                                    <img src="images/PDF_file.png" alt="Add PDF File"/>
                                    <br/>
                                    PDF File
                                </button>
                                <button id='nmidDialogOpenerTreeItem'>
                                    <img src="images/diagram.png" alt="Add Tree/Sub-Tree"/>
                                    <br/>
                                    Tree
                                </button>
                                <a href="CereusReporting_addWeathermapReport.php?ReportId=<?php echo $reportId; ?>">
                                    <button>
                                        <img src="images/map.png" alt="Add Weathermap"/>
                                        <br/>
                                        Weathermap
                                    </button>
                                </a>
                                <a href="CereusReporting_addMultiReport.php?action=add&ReportId=<?php echo $reportId; ?>">
                                    <button>
                                        <img src="images/wizard.png" alt="Advanced Items"/>
                                        <br/>
                                        Advanced
                                    </button>
                                </a>
							<?php } ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr id=line0 class="nodrop nodrag" bgcolor='#6d88ad'>
            <td class='textSubHeaderDark'>Id</td>
            <td class='textSubHeaderDark'>Type</td>
            <td class='textSubHeaderDark'>Description</td>
            <td width='1%' align='right' bgcolor='#819bc0' style='padding: 4px; margin: 4px;'><input
                        type='checkbox' style='margin: 0px;' name='all' title='Select All'
                        onClick='SelectAll("chk_",this.checked)'></td>
            <form name='chk' method='post' action='CereusReporting_addReport.php'>
                        <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
        <?php } ?>

                <div style='display:none;'><input type='hidden' id='delete_graphs' name='delete_graphs'
                                                  value='0'></div>
                <div style='display:none;'><input type='hidden' id='ReportId' name='ReportId'
                                                  value='<?php echo $reportId; ?>'></div>
                <div style='display:none;'><input type='hidden' id='ReportType' name='ReportType' value='3'>
                </div>
        </tr>


		<?php


			//html_header_checkbox($menu_text, true);
			//form_hidden_box("delete_graphs","0","");
			//form_hidden_box("ReportId",$reportId,"");
			//form_hidden_box("ReportType",$reportType,"");
			$i = 0;
			foreach ( $a_reports as $s_report ) {
				$description = '';
				$font_color_style = 'style="color: red;"';
				if ( ( EDITION == "CORPORATE" ) || ( isSMBServer() ) ) {
					$font_color_style = '';
				}
				if ( $s_report[ 'type' ] == 'graph' ) { // Retrieve LGID text
					$font_color_style = '';
					$a_lgi_data            = array();
					$a_lgi_data[ 'title' ] = getPreparedDBValue( "
							SELECT title_cache FROM graph_templates_graph WHERE local_graph_id=?", array( $s_report[ 'data' ] ) );
					$a_lgi_data[ 'host' ]  = getPreparedDBValue( "
						SELECT
						  `host`.`description`
						FROM
						  `graph_local` 
						  INNER JOIN
						  `host` ON `graph_local`.`host_id` = `host`.`id`
						WHERE
						  `graph_local`.`id` = ?", array( $s_report[ 'data' ] ) );
					$description           = $a_lgi_data[ 'host' ] . ' [ ' . $a_lgi_data[ 'title' ] . ' ]';
				}
                elseif ( $s_report[ 'type' ] == 'availability_combined' ) { // Retrieve Tree and Leaf text
					$dataArray = preg_split( "/;/", $s_report[ 'data' ] );

					$tree_title = getPreparedDBValue( "SELECT name FROM graph_tree WHERE id=?", array( $dataArray[ 0 ] ) );
					$leaf_title = '';
					if ( $dataArray[ 1 ] > 0 ) {
						$leaf_title = getPreparedDBValue( "SELECT title FROM graph_tree_items WHERE id=?", array( $dataArray[ 1 ] ) );
					}
					// Fix CRC-15
					if ( strlen( $leaf_title ) > 2 ) {
						$description = $tree_title . " -> " . $leaf_title;
					}
					else {
						if ( $dataArray[ 1 ] > 0 ) {
							$host_id          = getPreparedDBValue( "SELECT host_id FROM graph_tree_items WHERE id=?", array( $dataArray[ 1 ] ) );
							$host_description = getPreparedDBValue( "SELECT description FROM host WHERE id=?", array( $host_id ) );
							$host_hostname    = getPreparedDBValue( "SELECT hostname FROM host WHERE id=?", array( $host_id ) );
							if ( strlen( $host_hostname ) > 2 ) {
								if ( strlen( $host_description ) > 2 ) {
									$description = $tree_title . " -> " . $host_description . " [ " . $host_hostname . " ] ";
								}
								else {
									$description = $tree_title . " -> " . $host_hostname;
								}
							}
							else {
								$description = $tree_title;
							}
						}
						else {
							$description = $tree_title;
						}
					}
				}
                elseif ( $s_report[ 'type' ] == 'availability_winservice' ) { // Retrieve Tree and Leaf text
					$dataArray  = preg_split( "/;/", $s_report[ 'data' ] );
					$tree_title = getPreparedDBValue( "SELECT name FROM graph_tree WHERE id=?", array( $dataArray[ 0 ] ) );
					$leaf_title = getPreparedDBValue( "SELECT title FROM graph_tree_items WHERE id=?", array( $dataArray[ 1 ] ) );
					if ( strlen( $leaf_title ) > 2 ) {
						$description = $tree_title . " -> " . $leaf_title;
					}
					else {
						$host_id          = getPreparedDBValue( "SELECT host_id FROM graph_tree_items WHERE id=?", array( $dataArray[ 1 ] ) );
						$host_description = getPreparedDBValue( "SELECT description FROM host WHERE id=?", array( $host_id ) );
						$host_hostname    = getPreparedDBValue( "SELECT hostname FROM host WHERE id=?", array( $host_id ) );
						$description      = $tree_title . " -> " . $host_description . " [ " . $host_hostname . " ] ";
					}
				}
                elseif ( $s_report[ 'type' ] == 'availability_thold' ) { // Retrieve Tree and Leaf text
					$thold_title = "";
					$dataArray   = preg_split( "/;/", $s_report[ 'data' ] );
					$thold_title = getPreparedDBValue( "SELECT name FROM thold_data WHERE id=?", array( $dataArray[ 0 ] ) );
					$description = "Thold -> " . $thold_title;
				}
                elseif ( $s_report[ 'type' ] == 'availability_thold_tree_sum' ) { // Retrieve Tree and Leaf text
					$thold_sum_title = "";
					$dataArray       = preg_split( "/;/", $s_report[ 'data' ] );
					$thold_sum_title = getPreparedDBValue( "SELECT graph_tree.name AS name ,graph_tree_items.title,graph_tree_items.id FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 0 ] ) );
					$thold_sum_title = getPreparedDBValue( "SELECT graph_tree_items.title AS title FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 0 ] ) ) . ' [ ' . $thold_sum_title . ' ]';
					$description     = "Thold Summary Tree -> " . $thold_sum_title;
				}
                elseif ( $s_report[ 'type' ] == 'availability_tree_sum' ) { // Retrieve Tree and Leaf text
					$tree_sum_title = "";
					$dataArray      = preg_split( "/;/", $s_report[ 'data' ] );
					if ( $dataArray[ 1 ] > 0 ) {
						$tree_sum_title = getPreparedDBValue( "SELECT graph_tree.name AS name ,graph_tree_items.title,graph_tree_items.id FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 1 ] ) );
					}
					else {
						$tree_sum_title = getPreparedDBValue( 'SELECT name FROM graph_tree WHERE id=?', array( $dataArray[ 0 ] ) );
					}
					$tree_sum_title = getPreparedDBValue( "SELECT graph_tree_items.title AS title FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 1 ] ) ) . ' [ ' . $tree_sum_title . ' ]';

					$description = "Avail Summary Tree -> " . $tree_sum_title;
				}
                elseif ( $s_report[ 'type' ] == 'tree_item' ) { // Retrieve Tree and Leaf text
					$tree_item_title = "";
					$dataArray       = preg_split( "/;/", $s_report[ 'data' ] );
					if ( $dataArray[ 1 ] > 0 ) {
						$tree_item_title = getPreparedDBValue( "SELECT graph_tree.name AS name ,graph_tree_items.title,graph_tree_items.id FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 1 ] ) );
					}
					else {
						$tree_item_title = getPreparedDBValue( 'SELECT name FROM graph_tree WHERE id=?', array( $dataArray[ 0 ] ) );
					}
					$tree_item_title = getPreparedDBValue( "SELECT graph_tree_items.title AS title FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 1 ] ) ) . ' [ ' . $tree_item_title . ' ]';

					$description = "Tree -> " . $tree_item_title;
				}
                elseif ( $s_report[ 'type' ] == 'regexp' ) { // Retrieve Tree and Leaf text
					$tree_item_title = "";
					$dataJsonArray   = json_decode( $s_report[ 'data' ], TRUE );
					$dataArray       = preg_split( "/;/", $dataJsonArray[ 'data' ] );

					if ( $dataArray[ 1 ] > 0 ) {
						$tree_item_title = getPreparedDBValue( "SELECT graph_tree.name AS name ,graph_tree_items.title,graph_tree_items.id FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 1 ] ) );
					}
					else {
						$tree_item_title = getPreparedDBValue( 'SELECT name FROM graph_tree WHERE id=?', array( $dataArray[ 0 ] ) );
					}
					$tree_item_title = getPreparedDBValue( "SELECT graph_tree_items.title AS title FROM graph_tree_items INNER JOIN graph_tree ON graph_tree.id = graph_tree_items.graph_tree_id WHERE graph_tree_items.id = ?", array( $dataArray[ 1 ] ) ) . ' [ ' . $tree_item_title . ' ]';

					$description = "Tree -> " . $tree_item_title . ' | Filter Type: [' . $dataJsonArray[ 'dataRegExpFilter' ] . '] | RegExp: [' . $dataJsonArray[ 'dataRegExp' ] . ']';
				}
                elseif ( $s_report[ 'type' ] == 'smokeping' ) { // Retrieve LGID text
					$hostDescription = getPreparedDBValue( 'SELECT description FROM host WHERE id=?', array( $s_report[ 'data' ] ) );
					$hostIp          = getPreparedDBValue( 'SELECT hostname FROM host WHERE id=?', array( $s_report[ 'data' ] ) );
					$description     = $hostDescription . ' [ ' . $hostIp . ' ]';
				}
                elseif ( $s_report[ 'type' ] == 'availability' ) { // Retrieve LGID text
					$hostDescription = getPreparedDBValue( 'SELECT description FROM host WHERE id=?', array( $s_report[ 'data' ] ) );
					$hostIp          = getPreparedDBValue( 'SELECT hostname FROM host WHERE id=?', array( $s_report[ 'data' ] ) );
					$description     = $hostDescription . ' [ ' . $hostIp . ' ]';
				}
                elseif ( $s_report[ 'type' ] == 'weathermap' ) { // Retrieve LGID text
					$description = getPreparedDBValue( 'SELECT titlecache FROM weathermap_maps WHERE id=?', array( $s_report[ 'data' ] ) );
				}
                elseif ( $s_report[ 'type' ] == 'pagebreak' ) { // Retrieve LGID text
	                $font_color_style = '';
					$description = 'Insert Page break here';
				}
                elseif ( $s_report[ 'type' ] == 'enable_header' ) { // Retrieve LGID text
					$description = 'Enable Header on next page';
				}
                elseif ( $s_report[ 'type' ] == 'enable_footer' ) { // Retrieve LGID text
					$description = 'Enable Footer on current page';
				}
                elseif ( $s_report[ 'type' ] == 'disable_header' ) { // Retrieve LGID text
					$description = 'Disable Header on next page';
				}
                elseif ( $s_report[ 'type' ] == 'disable_footer' ) { // Retrieve LGID text
					$description = 'Disable Footer on current page';
				}
                elseif ( $s_report[ 'type' ] == 'reportit_report' ) { // Get ReportIt Name
					$description = getPreparedDBValue( "SELECT description FROM reportit_reports WHERE id=?", array( $s_report[ 'data' ] ) );
				}
                elseif ( $s_report[ 'type' ] == 'host' ) { // Get ReportIt Name
	                $font_color_style = '';
	                $my_host_hostname= getPreparedDBValue( "SELECT hostname FROM host WHERE id=?", array( $s_report[ 'data' ] ) );
					$my_host_description = getPreparedDBValue( "SELECT description FROM host WHERE id=?", array( $s_report[ 'data' ] ) );
					$description = htmlspecialchars('Device: '.$my_host_description. ' - ' . $my_host_hostname );
				}
				else {
					if ( $s_report[ 'type' ] == 'text' ) {
						$font_color_style = '';
					} elseif ( $s_report[ 'type' ] == 'chapter' ) {
						$font_color_style = '';
					} elseif ( $s_report[ 'type' ] == 'title' ) {
						$font_color_style = '';
					}

					// Anything else is limited to the first 20 chars
					$description = '(EDIT) ' . $s_report[ 'data' ];
					if ( strlen( $description ) > 80 ) {
						$description = '(EDIT) ' . substr( $s_report[ 'data' ], 0, 80 ) . '...';
					}

				}

				$description = "<a href=CereusReporting_addMultiReport.php?action=edit&ReportId=" . $reportId . "&itemId=" . $s_report[ 'Id' ] . ">" . htmlspecialchars( $description ) . "</a>";


				if ( $i < 1 ) {
					print "<tr id='line" . $s_report[ 'Id' ] . "' class='even'>";
				}
				else {
					print "<tr id='line" . $s_report[ 'Id' ] . "' class='odd'>";
					$i = -1;
				}
				?>
                <td onClick='select_line("<?php echo $s_report[ 'Id' ]; ?>")'>
					<?php echo $s_report[ 'order' ]; ?>
                </td>
                <td <?php echo $font_color_style; ?> onClick='select_line("<?php echo $s_report[ 'Id' ]; ?>")'>
					<?php echo $s_report[ 'type' ]; ?>
                </td>
                <td onClick='select_line("<?php echo $s_report[ 'Id' ]; ?>")'>
                    <a href=CereusReporting_addMultiReport.php?action=edit&ReportId=<?php echo $reportId; ?>&itemId=<?php echo $s_report[ 'Id' ]; ?>><?php echo $description; ?></a>
                </td>
                <td onClick='select_line("<?php echo $s_report[ 'Id' ]; ?>", true)' style='padding: 4px; margin: 4px;'
                    width='1%' align='right'>
                    <input type='checkbox' style='margin: 0px;' id='chk_<?php echo $s_report[ 'Id' ]; ?>'
                           name='chk_<?php echo $s_report[ 'Id' ]; ?>'>
                </td>
                <!-- <td><a href='CereusReporting_orderMultiGraphs.php?action=item_movedown&report_id=3&multi_id=33'><img src='/cacti/images/move_down.gif' border='0' alt='Move Down'></a><a href='CereusReporting_orderMultiGraphs.php?action=item_moveup&report_id=3&multi_id=33'><img src='/cacti/images/move_up.gif' border='0' alt='Move Up'></a></td> -->
                </tr>
				<?php
				$i++;
			}
			if ( readPluginStatus( 'nmidskin' ) ) {
				print "</tbody></table></div></div>";
			}
			else {
				print "</table>";
			}

			$task_actions = array(
				2 => "Delete",
				//3 => "Add Weathermap ( Visual Mode )",
				//4 => "Add Thold Availability Report",
				//5 => "Add Thold Availability Report( Summarized Tree )",
				//6 => "Add Availability Report( Summarized Tree )"
			);
			cr_draw_actions_dropdown( $task_actions, 0 );

			print "</form>";
			}


			}

			}

			cr_bottom_footer();