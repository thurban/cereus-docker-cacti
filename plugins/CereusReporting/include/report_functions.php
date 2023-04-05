<?php
	/*******************************************************************************
	 *
	 * File:         $Id: report_functions.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2016 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	include_once('html_functions.php');

	// MRTG Style Report Generation
	// No performance improvements needed here
	function doRRAPrint( $pdf, $lgid )
	{
		global $isSmokepingEnabled, $isBoostEnabled, $isBoostCacheEnabled,
		       $boost_png_cache_directory, $startTime, $endTime, $leafid, $lgi, $config,
		       $phpBinary;

		// Get DB Instance
		$db = DBCxn::get();

		$stmt = $db->prepare( ' SELECT
            rra.id as rraid,
            rra.name as title_cache
        FROM
            (graph_templates_item,data_template_data_rra,data_template_rrd,data_template_data,rra)
        WHERE
            graph_templates_item.task_item_id=data_template_rrd.id
        AND
            data_template_rrd.local_data_id=data_template_data.local_data_id
        AND
            data_template_data.id=data_template_data_rra.data_template_data_id
        AND
            data_template_data_rra.rra_id=rra.id
        AND
            graph_templates_item.local_graph_id= :lgid
        GROUP BY
            rra.id
        ORDER BY
            rra.timespan' );
		$stmt->bindValue( ':lgid', $lgid );
		$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$stmt->execute();

		$startTime = 0;
		$endTime   = 0;
		if ( $pdf->nmidGetPdfType() > 0 ) {
			$pdf->Bookmark( $pdf->nmidGetHeaderTitle(), 0 );
		}
		while ( $row = $stmt->fetch() ) {
			if ( file_exists( sys_get_temp_dir() . '/' . $lgid . $row[ 'rraid' ] . '.png' ) ) {
				unlink( sys_get_temp_dir() . '/' . $lgid . $row[ 'rraid' ] . '.png' );
			}
			CereusReporting_logger( "Creating image for $lgid.", 'debug', 'image' );

            // Download file and store to tmp dir:
            $image_file = sys_get_temp_dir() . '/' . $lgid . $row[ 'rraid' ] . '.png';
            $secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
            $graph_width = readConfigOption( 'nmid_cr_default_graph_width' );
            $graph_height = readConfigOption( 'nmid_cr_default_graph_height' );
            $graph_theme = readConfigOption("selected_theme");

            $graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$graph_height.'&width='.$graph_width.'&theme='.$graph_theme;
            curl_download($graph_image_url,$image_file);


		}
		$stmt->closeCursor();

        // Download all files
        CereusReporting_logger('Downloading all report graphs', 'debug', 'ReportEngine');
        multi_curl_download();
        CereusReporting_logger('Finished Downloading all report graphs', 'debug', 'ReportEngine');

        $stmt = $db->prepare( ' SELECT
            rra.id as rraid,
            rra.name as title_cache
        FROM
            (graph_templates_item,data_template_data_rra,data_template_rrd,data_template_data,rra)
        WHERE
            graph_templates_item.task_item_id=data_template_rrd.id
        AND
            data_template_rrd.local_data_id=data_template_data.local_data_id
        AND
            data_template_data.id=data_template_data_rra.data_template_data_id
        AND
            data_template_data_rra.rra_id=rra.id
        AND
            graph_templates_item.local_graph_id= :lgid
        GROUP BY
            rra.id
        ORDER BY
            rra.timespan' );
        $stmt->bindValue( ':lgid', $lgid );
        $stmt->setFetchMode( PDO::FETCH_ASSOC );
        $stmt->execute();

        while ( $row = $stmt->fetch() ) {
            //system( $phpBinary . " create_image.php " . $lgid . " " . $row[ 'rraid' ] . " " . $startTime . " " . $endTime . " " . "100" . " " . "800" . " > ".sys_get_temp_dir() . '/' . $lgid . $row[ 'rraid' ] . '.png' );
            addImage( $pdf, $row[ "title_cache" ], sys_get_temp_dir() . '/' . $lgid . $row[ 'rraid' ] . '.png', $lgid );
        }
        $stmt->closeCursor();

    }

	// On-Demand/Instant Report Generation
	function doLgiPrint( $pdf, $lgi, $leafid, $startTime, $endTime )
	{
		global $phpBinary, $config ;

		// Get DB Instance
		$db = DBCxn::get();

		if ( $pdf->nmidGetPdfType() > 0 ) {
			$pdf->Bookmark( $pdf->nmidGetHeaderTitle(), 0 );
		}

		$wf_dir = sys_get_temp_dir() . '/' . time() . '-' . $leafid . '-' . $startTime . '-' . $endTime;

		$pdf->nmidSetWorkerFile( $wf_dir . '/workerfile' );
		mkdir( $wf_dir );
		$pdf->nmidSetWorkerDir( $wf_dir );

		if ( $pdf->nmidGetPrintFooter() ) {
			printControlText( $pdf, 0, '<sethtmlpageheader name="myheader" value="1" show-this-page="1" />', 0,'enable_header' );
		}
		if ( $pdf->nmidGetPrintHeader() ) {
			printControlText( $pdf, 0, '<sethtmlpagefooter name="myfooter" value="1" show-this-page="1" />', 0,'enable_footer' );
		}


		foreach ( $lgi as $lgID ) {
			if ( preg_match( "/^([0-9]+)$/", $lgID, $matches ) ) {
				$stmt = $db->prepare( 'SELECT
                    graph_templates_graph.id as id,
                    graph_templates_graph.local_graph_id as lgid,
                    graph_templates_graph.height as height,
                    graph_templates_graph.width as width,
                    graph_templates_graph.title_cache as title_cache,
                    graph_templates.name as name,
                    graph_local.host_id as hostid
                    FROM (graph_local,graph_templates_graph)
                    LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
                    WHERE graph_local.id=graph_templates_graph.local_graph_id
                    AND graph_templates_graph.local_graph_id  = :lgid' );
				$stmt->bindValue( ':lgid', $lgID );
				$stmt->setFetchMode( PDO::FETCH_ASSOC );
				$stmt->execute();

				while ( $row = $stmt->fetch() ) {
					$image_file = $pdf->nmidGetWorkerDir() . "/" . $row[ 'lgid' ] . '.png';
					if ( file_exists( 'parallelGraphRetriever.exe' ) ) {
						$image_file = $pdf->nmidGetWorkerDir() . "\\" . $row[ 'lgid' ] . '.png';
					}
					CereusReporting_logger( 'Adding image for ' . $row[ 'lgid' ], 'debug', 'image' );
					$command = $phpBinary . " create_image.php " . $row[ 'lgid' ] . " 0 " . $startTime . " " . $endTime . " " . $row[ 'height' ] . " " . $row[ 'width' ] . " > " . $image_file;
					$title   = $row[ "title_cache" ];

					$tier    = '';
					$lgid    = $row[ 'lgid' ];
					$content = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
					$pdf->nmidSetWorkerFileContent( $content );

					// Download file and store to tmp dir:
					$secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
                    $graph_width = readConfigOption( 'nmid_cr_default_graph_width' );
                    $graph_height = readConfigOption( 'nmid_cr_default_graph_height' );
                    $graph_theme = readConfigOption("selected_theme");

                    $graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$graph_height.'&width='.$graph_width.'&theme='.$graph_theme;
					curl_download($graph_image_url,$image_file);
				}
			}
			// SMokeping Graph
			elseif ( preg_match( "/^sp_([0-9]+)$/", $lgID, $matches ) ) {
				$host_id = $matches[ 1 ];

				$stmt = $db->prepare( 'select nwmgmt_settings from host where id= :hostId' );
				$stmt->bindValue( ':hostId', $host_id );
				$stmt->execute();
				$isHostEnabled = $stmt->fetchColumn();

				if ( preg_match( "/^s1/", $isHostEnabled ) == 1 ) {
					// $title      = getDBValue( 'description', "select description from host where id=" . $host_id ) ;
					$stmt = $db->prepare( 'select description from host where id= :hostId' );
					$stmt->bindValue( ':hostId', $host_id );
					$stmt->execute();
					$title = $stmt->fetchColumn() . ' - Smokeping Graph';
					CereusReporting_logger( 'Adding smokeping graph for ' . $host_id, 'debug', 'image' );
					$image_file = $pdf->nmidGetWorkerDir() . "/" . $host_id . '-' . $startTime . '-' . $endTime . '.name';
					$command    = $phpBinary . " cereusReporting_getSmokePingImage.php $host_id $startTime $endTime > " . $image_file;
					$tier       = '';
					$lgid       = '-1';
					$content    = $pdf->nmidGetWorkerFileContent() . 'smokeping' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
					$pdf->nmidSetWorkerFileContent( $content );

					// Download file and store to tmp dir:
					$secure_key = sha1( $host_id . $startTime . $endTime . SECURE_URL_KEY);
					$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/cereusReporting_getSmokePingImage.php?key='.$secure_key.'&hostId='. $host_id.'&start='.$startTime.'&end='.$endTime;
					curl_download($graph_image_url,$image_file);

				}
			}
			// Availability Graph
			elseif ( preg_match( "/^avc_([0-9]+)_([0-9]+)$/", $lgID, $matches ) ) {
                $tree_id    = $matches[ 1 ];
                $leafid     = $matches[ 2 ];
                $slaTime_id = -1;
                if ( readConfigOption( 'nmid_avail_addGraph' ) ) {
                    CereusReporting_logger( 'Adding availability graph for leaf ' . $leafid . ' and tree ' . $tree_id, 'debug', 'image' );
                    $image_file = $pdf->nmidGetWorkerDir() . '/' . $leafid . '-' . $tree_id . '-' . $startTime . '-' . $endTime . '_availabilityCombined.png';
                    $command    = $phpBinary . " cereusReporting_serverAvailabilityChartCLI.php $leafid $tree_id $slaTime_id $startTime $endTime > " . $image_file;
                    $title      = 'Availability Report';
                    $tier       = '';
                    $lgid       = '-1';
                    $reportId   = '0';
                    $content    = $pdf->nmidGetWorkerFileContent() . 'availability_combined' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
                    $pdf->nmidSetWorkerFileContent( $content );

                    // Download file and store to tmp dir:
                    $secure_key = sha1( $leafid . $tree_id . $slaTime_id . $startTime . $endTime . SECURE_URL_KEY);
                    $graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/cereusReporting_serverAvailabilityChartCLI.php?key='.$secure_key.'&leafId='. $leafid.'&treeId='. $tree_id.'&slaTimeId='. $slaTime_id.'&start='.$startTime.'&end='.$endTime;
                    curl_download($graph_image_url,$image_file);

                }
                printAvailabilityCombinedTable( $pdf, $tree_id, $leafid, $startTime, $endTime, $tier, $slaTime_id );
                if ( readConfigOption( 'nmid_avail_addDetailedTable' ) ) {
                    //printDetailedAvailabilityCombinedTable( $pdf, '-1', $tree_id, $leafid, $startTime, $endTime, $tier, $slaTime_id );
                }
            }
		}

		$fh = fopen( $pdf->nmidGetWorkerFile(), "w+" );
		fwrite( $fh, $pdf->nmidGetWorkerFileContent() );
		fclose( $fh );

		// Download all files
        CereusReporting_logger('Downloading all report graphs', 'debug', 'ReportEngine');
        multi_curl_download();
        CereusReporting_logger('Finished Downloading all report graphs', 'debug', 'ReportEngine');


		CereusReporting_logger( 'Executing graph generation from workerfile [' . $pdf->nmidGetWorkerFile() . ']', 'debug', 'system' );
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
					addImage( $pdf, $title, $image_file, $lgid );
					unlink( $image_file );
				}
			}
			elseif ( $type == 'availability' ) {
				if ( file_exists( $image_file ) ) {
					addImage( $pdf, $title, $image_file, $lgid );
					unlink( $image_file );
				}
			}
			elseif ( $type == 'availability_combined' ) {
                if ( file_exists( $image_file ) ) {
                    addImage( $pdf, $title, $image_file, $lgid );
                    unlink( $image_file );
                }
			}
			elseif ( $type == 'text' ) {
				printTextToReport( $pdf, $title );
			}
			elseif ( $type == 'sqlstatement' ) {
				printSQLDataToReport( $pdf, $title );
			}
			elseif ( $type == 'text' ) {
				printTextToReport( $pdf, $title );
			}
			elseif ( $type == 'title' ) {
				printTitleToReport( $pdf, $title );
			}
			elseif ( $type == 'chapter' ) {
				printChapterToReport( $pdf, $title, 1 );
			}
			elseif ( $type == 'pagebreak' ) {
				printControlTextToReport( $pdf, $title );
			}
			elseif ( $type == 'enable_header' ) {
				printControlTextToReport( $pdf, $title );
			}
			elseif ( $type == 'enable_footer' ) {
				printControlTextToReport( $pdf, $title );
			}
			elseif ( $type == 'disable_header' ) {
				printControlTextToReport( $pdf, $title );
			}
			elseif ( $type == 'disable_footer' ) {
				printControlTextToReport( $pdf, $title );
			}
			elseif ( $type == 'smokeping' ) {
				$file = $image_file;
				if ( file_exists( $file ) ) {
					$f         = fopen( $file, 'r' );
					$imageFile = fread( $f, filesize( $file ) );
					fclose( $f );
					if ( file_exists( $imageFile ) ) {
						addImage( $pdf, $title, $imageFile, $lgid );
						unlink( $imageFile );
					}
					unlink( $image_file );
				}
			}
		}
		fclose( $fh );
	}

	// Graph Report Generation -- performance improvement needed
	function doGraphPrint( $pdf, $lgID, $startTime, $endTime, $tier, $reportId )
	{
		global $isBoostEnabled, $isBoostCacheEnabled, $boost_png_cache_directory, $phpBinary, $config;

		// Get DB Instance
		$db = DBCxn::get();

		$stmt = $db->prepare( 'SELECT
            graph_templates_graph.id as id,
            graph_templates_graph.local_graph_id as lgid,
            graph_templates_graph.height as height,
            graph_templates_graph.width as width,
            graph_templates_graph.title_cache as title_cache,
            graph_templates.name as name,
            graph_local.host_id as hostid
            FROM (graph_local,graph_templates_graph)
            LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
            WHERE graph_local.id=graph_templates_graph.local_graph_id
            AND graph_templates_graph.local_graph_id  = :lgid' );
		$stmt->bindValue( ':lgid', $lgID );
		$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$stmt->execute();

		while ( $row = $stmt->fetch() ) {

			$customGraphWidth  = getDBValue( 'customGraphWidth', 'SELECT customGraphWidth FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
			$customGraphHeight = getDBValue( 'customGraphHeight', 'SELECT customGraphHeight FROM plugin_nmidCreatePDF_Reports WHERE ReportId=' . $reportId . ';' );
			if ( ( isNumber( $customGraphWidth ) == FALSE ) || ( strlen( $customGraphWidth ) < 1 ) ) {
				$customGraphWidth = $row[ 'width' ];
			}
			if ( ( isNumber( $customGraphHeight ) == FALSE ) || ( strlen( $customGraphHeight ) < 1 ) ) {
				$customGraphHeight = $row[ 'height' ];
			}
			$image_file = $pdf->nmidGetWorkerDir() . "/" . $row[ 'lgid' ] . '-' . $startTime . '-' . $endTime . '.png';
			$command = $phpBinary . " create_image.php " . $row[ 'lgid' ] . " 0 " . $startTime . " " . $endTime . " " . $customGraphHeight . " " . $customGraphWidth . " > " . $image_file;
			$title   = $row[ "title_cache" ];
			$lgid    = $row[ 'lgid' ];

			// Download file and store to tmp dir:
			$secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
			$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$customGraphHeight.'&width='.$customGraphWidth;
			curl_download($graph_image_url,$image_file);

			$content = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			CereusReporting_logger( 'Adding Graph ' . $lgid . '['.$graph_image_url.']', 'debug', 'system' );
			$pdf->nmidSetWorkerFileContent( $content );
		}
	}

	function multi_curl_download() {
        global $curl_nodes, $curl_destination;

        $total_graphs =  sizeof($curl_nodes);
        $total_graphs_downloaded = 0;
        $my_curl_nodes = $curl_nodes;
        $max_concurrent_connections = read_config_option( 'cr_max_http_connections');
        $mh = curl_multi_init();

        // Limit connections to max of 4:
        curl_multi_setopt( $mh, CURLMOPT_MAXCONNECTS, $max_concurrent_connections);
        curl_multi_setopt( $mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, $max_concurrent_connections );

        CereusReporting_logger('Downloading '.$total_graphs.' report graphs', 'debug', 'ReportEngine');

        foreach(array_chunk($my_curl_nodes, $max_concurrent_connections ) as $curl_nodes_chunk ) {
            $curl_array = array();
            $array_size = sizeof( $curl_array );
            $start_graphs = $total_graphs_downloaded;

            foreach ( $curl_nodes_chunk as $i => $url ) {
                $total_graphs_downloaded++;
                // CereusReporting_logger('Downloading #'.$total_graphs_downloaded.' report of '.$total_graphs.' ['.$url.']', 'debug', 'ReportEngine');
                CereusReporting_logger('Downloading chart URL ['.$url.']', 'debug', 'ReportEngine');

                $curl_array[ $i ] = curl_init( $url );
                curl_setopt( $curl_array[ $i ], CURLOPT_RETURNTRANSFER, TRUE );
                curl_setopt( $curl_array[ $i ], CURLOPT_SSL_VERIFYHOST, 0 );
                curl_setopt( $curl_array[ $i ], CURLOPT_SSL_VERIFYPEER, 0 );
                curl_setopt( $curl_array[ $i ], CURLOPT_FOLLOWLOCATION, 1 );
                if ( read_config_option( 'use_http_basic_auth' ) == "on" ) {
                    $username = read_config_option( 'use_http_basic_auth_username' );
                    $password = read_config_option( 'use_http_basic_auth_password' );
                    curl_setopt( $curl_array[ $i ], CURLOPT_USERPWD, $username . ":" . $password );
                }

                curl_multi_add_handle( $mh, $curl_array[ $i ] );
            }
            $end_graphs = $total_graphs_downloaded;

            $running = NULL;
            do {
                //usleep( 10 );
                curl_multi_exec( $mh, $running );
            } while ( $running > 0 );

            for( $i = 0; $i < sizeof($curl_array); $i++) {
                CereusReporting_logger('Saving report chart #'.($start_graphs + $i).' to  ['.$curl_destination[ ($start_graphs + $i ) ].']', 'debug', 'ReportEngine');
                $output = curl_multi_getcontent( $curl_array[ $i ] );
                $file   = fopen( $curl_destination[ ($start_graphs + $i) ], "w+" );
                fputs( $file, $output );
                fclose( $file );
            }

            foreach ( $curl_nodes_chunk as $i => $url ) {
                curl_multi_remove_handle( $mh, $curl_array[ $i ] );
            }
        }
        curl_multi_close( $mh );
        CereusReporting_logger('Downloaded '.$total_graphs_downloaded.' report graphs', 'debug', 'ReportEngine');
    }

	function curl_download($Url, $destination){
        global $curl_nodes, $curl_destination;

        $curl_nodes[] = $Url;
        $curl_destination[] = $destination;
        return;
	}


	// Default Graph Generation
	function doLeafGraphs( $pdf, $leafid, $startTime, $endTime )
	{
		if ( function_exists('top_header')) {
			include_once( __DIR__.'/functions_cacti_1.0.0.php' );
		} else {
			include_once( __DIR__.'/functions_cacti_0.8.php' );
		}
		CereusReporting_doLeafGraphs($pdf, $leafid, $startTime, $endTime );
	}


	// DSStats Report Generation
	function doDsstatsGraphs( $pdf, $reportId )
	{
		global $phpBinary, $config;

		// DSSTATS Report Types are only supported with the mPDF Engine
		if ( $pdf->nmidGetPdfType() > 0 ) {
			$pdf->Bookmark( $pdf->nmidGetHeaderTitle(), 0 );
		}

		// Get DB Instance
		$db = DBCxn::get();

		$myStmt = $db->prepare( 'SELECT
              `plugin_nmidCreatePDF_DSStatsReports`.`Id` as Id,
              `plugin_nmidCreatePDF_DSStatsReports`.`DSStatsGraph` as DSStatsGraph,
              `plugin_nmidCreatePDF_DSStatsReports`.`Description` as description
            FROM
              `plugin_nmidCreatePDF_DSStatsReports`
            WHERE
              `plugin_nmidCreatePDF_DSStatsReports`.`ReportId` = :reportId
            ORDER BY `order`' );
		$myStmt->bindValue( ':reportId', $reportId );
		$myStmt->execute();
		$a_reports = $myStmt->fetchAll();

		foreach ( $a_reports as $s_report ) {
			CereusReporting_logger( 'Adding ['. $s_report[1] .'] to DSSTats Report ['.$s_report[0].']', "debug", "DSSTATS" );
			$image_file = sys_get_temp_dir() . '/' . $s_report[ 1 ] . '-' . $reportId . '.png';
			$command    = $phpBinary . " dsstats_reports/" . $s_report[ 1 ] . " > " . $image_file;
			$title      = $s_report[ 'description' ];
			$lgid       = 0;
			$tier       = -1;

			// Download file and store to tmp dir:
			$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/dsstats_reports/'.$s_report[ 1 ];
			curl_download($graph_image_url, $image_file);

			addImage( $pdf, $title, $image_file, 0 );
			unlink( $image_file );
		}
	}

	function printTreeItemGraph( $pdf, $reportId, $data, $startTime, $endTime, $global_tier )
	{
		if ( function_exists('top_header')) {
			include_once( __DIR__.'/functions_cacti_1.0.0.php' );
		} else {
			include_once( __DIR__.'/functions_cacti_0.8.php' );
		}
		CereusReporting_printTreeItemGraph( $pdf, $reportId, $data, $startTime, $endTime, $global_tier );
	}

	function printHostItemGraph( $pdf, $host_id, $startTime, $endTime, $global_tier )
	{
		global $isBoostEnabled, $isSmokepingEnabled, $isBoostCacheEnabled, $boost_png_cache_directory, $graphPerPage, $config, $pdfType;

		list( $micro, $seconds ) = explode( " ", microtime() );
		$start = $seconds + $micro;

		// Get DB Instance
		$db = DBCxn::get();

		$tier = $global_tier;

		if ( $host_id > 0 ) {
			$local_stmt = $db->prepare( "select concat(concat( concat(description,' ( '),hostname),' ) ') from host where id = :hostid" );
			$local_stmt->bindValue( ':hostid', $host_id );
			$local_stmt->execute();
			$hostname = $local_stmt->fetchColumn();
			$local_stmt->closeCursor();
			CereusReporting_logger( 'Adding host item [' . $hostname . '] at Tier [' . $tier . ']', 'debug', 'system' );

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
			$local_stmt->bindValue( ':hostId', $host_id );
			$local_stmt->setFetchMode( PDO::FETCH_ASSOC );
			$local_stmt->execute();

			while ( $subRow = $local_stmt->fetch() ) {

				$image_file = $pdf->nmidGetWorkerDir() . "/" . $subRow[ 'lgid' ] . '.png';
				$command = '';
				$lgid    = $subRow[ 'lgid' ];
				$title   = $subRow[ "title_cache" ];
				$gtier   = $tier + 1;
				if ( $pdfType == TCPDF_ENGINE ) {
					$gtier       = $tier + 1;
				}
				CereusReporting_logger( 'Adding graph ['.$subRow[ 'lgid' ].'] for [' . $hostname . '] at Tier [' . $gtier . ']', 'debug', 'system' );

				// Download file and store to tmp dir:
				$secure_key = sha1( $lgid . '0' . $startTime . $endTime . SECURE_URL_KEY);
                $graph_width = readConfigOption( 'nmid_cr_default_graph_width' );
                $graph_height = readConfigOption( 'nmid_cr_default_graph_height' );
                $graph_theme = readConfigOption("selected_theme");
				curl_download(readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/create_image.php?key='.$secure_key.'&lgid='.$lgid.'&rraid=0&start='.$startTime.'&end='.$endTime.'&height='.$graph_height.'&width='.$graph_width.'&theme='.$graph_theme,$image_file);

				$content     = $pdf->nmidGetWorkerFileContent() . 'graph' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
				$pdf->nmidSetWorkerFileContent( $content );
			}
			$local_stmt->closeCursor();

			if ( $isSmokepingEnabled ) {

				$local_stmt = $db->prepare( 'select nwmgmt_settings from host where id = :hostId' );
				$local_stmt->bindValue( ':hostId', $host_id );
				$local_stmt->execute();
				$isHostEnabled = $local_stmt->fetchColumn();
				$local_stmt->closeCursor();

				if ( preg_match( "/^s1/", $isHostEnabled ) == 1 ) {
					$local_stmt = $db->prepare( 'select description from host where id = :hostId' );
					$local_stmt->bindValue( ':hostId', $host_id );
					$local_stmt->execute();
					$title = $local_stmt->fetchColumn() . ' - Smokeping Graph';
					$local_stmt->closeCursor();
					CereusReporting_logger( 'Smokeping found [' . $title. ']', 'debug', 'system' );

					$image_file = $pdf->nmidGetWorkerDir() . "/" . $host_id . '-' . $startTime . '-' . $endTime . '.name';
					$command    = '';
					$gtier   = $tier + 1;
					$lgid       = '-1';
					$content = $pdf->nmidGetWorkerFileContent() . 'smokeping' . '@' . $command . '@' . $title . '@' . $gtier . '@' . $image_file . '@' . $lgid . "\n";
					$pdf->nmidSetWorkerFileContent( $content );

					// Download file and store to tmp dir:
					$secure_key = sha1( $host_id . $startTime . $endTime . SECURE_URL_KEY);
					$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/cereusReporting_getSmokePingImage.php?key='.$secure_key.'&hostId='. $host_id.'&start='.$startTime.'&end='.$endTime;
					curl_download($graph_image_url,$image_file);
				}
			}
		}
		list( $micro, $seconds ) = explode( " ", microtime() );
		$end = $seconds + $micro;
		$cacti_stats = sprintf( "Time:%01.4f ", round( $end - $start, 4 ) );
		CereusReporting_logger( 'Item generation time: [' . $cacti_stats. ']', 'debug', 'system' );

	}

	function printRegExpItemGraph( $pdf, $reportId, $data, $startTime, $endTime, $global_tier )
	{
		if ( function_exists('top_header')) {
			include_once( __DIR__.'/functions_cacti_1.0.0.php' );
		} else {
			include_once( __DIR__.'/functions_cacti_0.8.php' );
		}
		CereusReporting_printRegExpItemGraph( $pdf, $reportId, $data, $startTime, $endTime, $global_tier );
	}

	/* MULTI REPORT FUNCTIONS */
	// DSStats Report Generation - Multi Report
	function printDsstatsGraph( $pdf, $reportId, $dsstats_graph, $dsstats_description, $tier )
	{
		global $phpBinary, $config;


		$image_file = $pdf->nmidGetWorkerDir() . '/' . $dsstats_graph . '-' . $reportId . '.png';
		$command    = $phpBinary . " dsstats_reports/" . $dsstats_graph . " > " . $image_file;
		$title      = $dsstats_description;
		$lgid       = 0;
		#$tier = -1;

		// Download file and store to tmp dir:
		$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/dsstats_reports/'.$dsstats_graph;
		curl_download($graph_image_url, $image_file);

		CereusReporting_logger( 'Adding DSSTats ' . $dsstats_graph . ' -> ' . $command, 'debug', 'system' );
		$content = $pdf->nmidGetWorkerFileContent() . 'dsstats' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
		$pdf->nmidSetWorkerFileContent( $content );
	}


	function printSQLStatementTable( $pdf, $reportId, $sqlStatement, $startTime, $endTime, $tier )
	{
		global $phpBinary;

		$db = DBCxn::get();


		$sqlOutputString = "";

		$sqlArray = preg_split( "/:/", $sqlStatement );
		//$sqlStatement = $sqlStatement;
		$title        = end( $sqlArray );
		$sqlStatement = preg_replace( "/:$title$/", "", $sqlStatement );

		if ( preg_match( "/DELETE/i",  $sqlStatement ) ) {
			// DELETE Statements not allowed
		}
		elseif ( preg_match( "/DROP/i",  $sqlStatement ) ) {
			// DELETE Statements not allowed
		}
		elseif ( preg_match( "/UPDATE/i",  $sqlStatement ) ) {
			// UPDATE Statements not allowed
		}
		else {
			// Ok
			$sql = $sqlStatement;
			if ( get_magic_quotes_gpc() ) {
				$sql = stripslashes( $sql );
			}

			$keyList = array();
			$sqlOutputString .=
				' <br><div class="nmidImageGraph"><center><table autosize=\"0\" repeat_header=\"1\" width="100%"><tr>' .
				'<td width="100%" class="nmidImageText" style="border-bottom:2px solid #000";>' . $title . '<bookmark content="' . $title . '" level="' . $tier . '" />' .
				'</td></tr></table>' .
				'<table repeat_header="1" width="100%">';
			$dataCount = 0;

            $stmt = $db->prepare( $sql);
            $stmt->execute();

			while ( $row = $stmt->fetch() ) {
				$dataCount++;
				if ( sizeof( $keyList ) > 0 ) {
					if ( strlen( $keyList[ 0 ] ) > 0 ) {
						// Ok, got the keylist already ...
						$sqlOutputString .= '<tr>';
						foreach ( $row as $key => $value ) {
							$sqlOutputString .= '<td style="border-bottom:1px solid #000;border-right:1px solid #000;">'.$value.'</td>';
						}
						$sqlOutputString .= '</tr>';
					}
				}
				else {
					$sqlOutputString .= '<<tr>';
					foreach ( $row as $key => $value ) {
						// going to add the keylist
						$keyList[ ] = $key;
						$sqlOutputString .= '<th style="border-bottom:1px solid #000;border-right:1px solid #000;" align=left>'.$key.'</th>';
					}
					$sqlOutputString .= '</tr>';
					$sqlOutputString .= '<tr>';
					foreach ( $row as $key => $value ) {
						$sqlOutputString .= '<td style="border-bottom:1px solid #000;border-right:1px solid #000;">'.$value.'</td>';
					}
					$sqlOutputString .= "</tr>";
				}
			}
			$stmt->closeCursor();
			if ( $dataCount == 0 ) {
				$sqlOutputString .= "<tr><td align=center>No Data.</td></tr>";
			}
			$sqlOutputString .= "</table></center></div>";

			$text       = preg_replace( "/\n/", "<br>", $sqlOutputString );
			$html       = $text;
			$command    = '';
			$image_file = '';
			$lgid       = '';
			$html       = preg_replace( "/@/", "&#64;", $html );
			$content    = $pdf->nmidGetWorkerFileContent() . 'sqlstatement' . '@' . $command . '@' . $html . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			$pdf->nmidSetWorkerFileContent( $content );
		}

	}

	// Availability Report Generation - Multi Report
	function printWeathermapGraph( $pdf, $reportId, $wm_id, $startTime, $endTime, $tier )
	{
		global $phpBinary;


		$dir     = dirname( __FILE__ );
		$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

		$filehash        = getDBValue( 'filehash', 'select filehash from weathermap_maps where id=' . $wm_id . ';' );
		$weathermap_output_directory = readConfigOption('nmid_cr_weathermap_output_dir');
		$orig_image_file = $weathermap_output_directory . '/' . $filehash . '.png';
		$image_file      = $pdf->nmidGetWorkerDir() . '/' . $wm_id . '-' . $reportId . '_weathermap.png';
		#$command = $phpBinary." CereusReporting_AvailabilityChartCLI.php $host_id $startTime $endTime > ".$image_file;
		copy( $orig_image_file, $image_file );
		chmod( $image_file, 0755 );
		$command = '';

		$title = getDBValue( 'titlecache', 'select titlecache from weathermap_maps where id=' . $wm_id . ';' );
		$lgid  = 0;

		CereusReporting_logger( 'Adding Weathermap ' . $title, 'debug', 'system' );
		$content = $pdf->nmidGetWorkerFileContent() . 'weathermap' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
		$pdf->nmidSetWorkerFileContent( $content );
	}

	function addPDFFileToReport( $pdf, $reportId, $pdf_file, $tier ) {
	    global $pdfType;
		if ( file_exists( $pdf_file ) && ( is_dir( $pdf_file ) == FALSE ) ) {
			CereusReporting_logger( 'Adding PDF File [' . $pdf_file .'] to Report' , 'debug', 'system' );
            //$pdf->nmidDisableTemplate();
            $pdf->setPrintHeader(false);


            $pagecount = $pdf->SetSourceFile( $pdf_file );
            CereusReporting_logger( 'PDF File contains [' . $pagecount .'] pages' , 'debug', 'system' );
            // Import all pages of the source PDF file
            for ( $page = 1; $page <= $pagecount; $page++ ) {
                CereusReporting_logger( 'Importing page [' . $page .'] of [ ' . $pagecount . ']' , 'debug', 'system' );
                $pagecount = $pdf->SetSourceFile( $pdf_file );
                $templateId = $pdf->ImportPage($page);
                // get the size of the imported page
                $size = $pdf->getTemplateSize($templateId);

                // create a page (landscape or portrait depending on the imported page size)
                if ($size['w'] > $size['h']) {
                    //$pdf->AddPage('L', array($size['w'], $size['h']));
                    $pdf->AddPage('L');
                } else {
                    //$pdf->AddPage('P', array($size['w'], $size['h']));
                    $pdf->AddPage('P');
                }
                $pdf->setPrintFooter(false);
                $pdf->UseTemplate( $templateId );
            }

            $pdf->setPrintHeader(true);
            $pdf->AddPage();
            $pdf->setPrintFooter(true);
		} else {
			CereusReporting_logger( 'ERROR: PDF File does not exist [' . $pdf_file .'] to Report' , 'info', 'system' );
		}
	}

	function printReportItReport( $pdf, $reportId, $reportit_id, $tier )
	{
		global $phpBinary;
		if ( !( ( EDITION == "CORPORATE" ) || ( isSMBServer() ) ) ) {
			return;
		}
		// Get DB Instance
		$db = DBCxn::get();

		// Get ReportIt Name:
		$stmt = $db->prepare( 'SELECT description FROM reportit_reports WHERE id= :reportitId' );
		$stmt->bindValue( ':reportitId', $reportit_id );
		$stmt->execute();
		$reportit_report_name = $stmt->fetchColumn();
		$stmt->closeCursor();

		// Get ReportIt Data: TODO
		$stmt = $db->prepare( 'SELECT a.*, b.*, c.name_cache FROM reportit_results_ AS a
		INNER JOIN reportit_data_items AS b
		ON (b.id = a.id AND b.report_id = :reportitId)
		INNER JOIN data_template_data AS c
		ON c.local_data_id = a.id' );
		$stmt->bindValue( ':reportitId', $reportit_id );
		$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$stmt->execute();
		# Get number of data columns:
		$reportit_column_count = $stmt->columnCount();

		// Cycle through data and get :
		while ( $row = $stmt->fetch() ) {

		}
		$stmt->closeCursor();
	}

    // Smokeping Report Generation - Multi Report
	function printSmokepingGraph( $pdf, $reportId, $host_id, $startTime, $endTime, $tier )
	{
		global $phpBinary, $config;


		$isHostEnabled = getDBValue( 'nwmgmt_settings', "select nwmgmt_settings from host where id=" . $host_id );
		if ( preg_match( "/^s1/", $isHostEnabled ) == 1 ) {
			$title      = getDBValue( 'description', "select description from host where id=" . $host_id ) . ' - Smokeping Graph';
			$image_file = $pdf->nmidGetWorkerDir() . "/" . $host_id . '-' . $startTime . '-' . $endTime . '.name';
			$command    = $phpBinary . " cereusReporting_getSmokePingImage.php " . $host_id . " $startTime $endTime > " . $image_file;
			$lgid       = '-1';
			$content    = $pdf->nmidGetWorkerFileContent() . 'smokeping' . '@' . $command . '@' . $title . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
			$pdf->nmidSetWorkerFileContent( $content );

			// Download file and store to tmp dir:
			$secure_key = sha1( $host_id . $startTime . $endTime . SECURE_URL_KEY);
			$graph_image_url = readConfigOption( 'nmid_pdfCactiServerUrl' ) . $config['url_path'] . 'plugins/CereusReporting/cereusReporting_getSmokePingImage.php?key='.$secure_key.'&hostId='. $host_id.'&start='.$startTime.'&end='.$endTime;
			curl_download($graph_image_url,$image_file);

		}
	}

	function printControlText( $pdf, $reportId, $text, $tier, $type )
	{
        $command    = '';
        $image_file = '';
        $lgid       = '';
        $content    = $pdf->nmidGetWorkerFileContent() . $type . '@' . $command . '@' . $text . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
        $pdf->nmidSetWorkerFileContent( $content );
	}

	// Add text to report data - Multi Report
	function printText( $pdf, $reportId, $text, $tier )
	{
        // $html       = preg_replace( "/\n/", "<br>", $text );
        $html_text  = '<div class="nmidNormalText">' . $text . '</div>';

        $command    = '';
        $image_file = '';
        $lgid       = '';
        $content    = $pdf->nmidGetWorkerFileContent() . 'text' . '@' . $command . '@' . $html_text . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
        $pdf->nmidSetWorkerFileContent( $content );

	}

	// Print the text to the report - Multi Report
	function printControlTextToReport( $pdf, $text )
	{
        if ( $pdf->nmidGetPdfType() == TCPDF_ENGINE ) {
            //check_remaining_height($pdf, $text);
            if ($text == '<tcpdf method="AddPage" />') {
                check_remaining_height($pdf, $text);
                printEndColumnHTML($pdf);
            }
            $pdf->writeHTML($text, TRUE, FALSE, TRUE, FALSE, '');
        } elseif ( $pdf->nmidGetPdfType() == MPDF_ENGINE ) {
            $pdf->writeHTML($text);
        }
	}

	// Print the text to the report - Multi Report
	function printTextToReport( $pdf, $text )
	{
        $html = '';
        if ( preg_match( '/\[nrnrnr\]/', $text ) > 0 ) {
            $html = preg_replace( '/\[nrnrnr\]/', '', $text );
        }
        else {
            $html = $text;
        }
        check_remaining_height($pdf, $html);
        printGenericTextHTML($pdf, $html);
	}

	// Print the text to the report - Multi Report
	function printSQLDataToReport( $pdf, $text )
	{

        if ( $pdf->nmidGetPdfType() == TCPDF_ENGINE ) {
            $pdf->writeHTML( $text . "\n", TRUE, FALSE, TRUE, FALSE, '' );
        } elseif ( $pdf->nmidGetPdfType() == MPDF_ENGINE ) {
            $pdf->writeHTML($text . "\n");
        }

	}

	// Add title to report data - Multi Report
	function printTitle( $pdf, $reportId, $text, $tier )
	{
        $title_text = '';
        if ( read_config_option('nmid_pdf_new_bookmark_style') ) {
            $title_text  = '<h2 class="nmidTitleText">' . $text . '</h2>';
        } else{
            $title_text  = '<div class="nmidTitleText">' . $text . '</div><bookmark content="' . $text . '" level="2" /><br />';
        }

        $command    = '';
        $image_file = '';
        $lgid       = '';
        $content    = $pdf->nmidGetWorkerFileContent() . 'title' . '@' . $command . '@' . $title_text . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
        $pdf->nmidSetWorkerFileContent( $content );

		return 1;
	}

	// Print the title to the report - Multi Report
	function printTitleToReport( $pdf, $html )
	{
        check_remaining_height($pdf, $html);
        printGenericTextHTML($pdf, $html);
		return 1;
	}

	// Add Chapter to report data - Multi Report
	function printPDFFile( $pdf, $reportId, $pdf_file, $tier )
	{

		$command    = '';
		$image_file = '';
		$lgid       = '';
		$content    = $pdf->nmidGetWorkerFileContent() . 'pdf_file' . '@' . $command . '@' . $pdf_file . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
		$pdf->nmidSetWorkerFileContent( $content );

		return 0;
	}

	// Add Chapter to report data - Multi Report
	function printChapter( $pdf, $reportId, $text, $tier )
	{
        // Set Chapter Title and Bookmark
        if ( $pdf->nmidGetPdfType() == TCPDF_ENGINE ) {
            $params = $pdf->serializeTCPDFtagParameters(array($text, 0, -1, '', '', array(0,0,0)));
            $html = '<div class="nmidChapterText">' . $text . '</div><tcpdf method="Bookmark" params="'.$params.'" />';
        } elseif ( $pdf->nmidGetPdfType() == MPDF_ENGINE ) {
            $html = '<div class="nmidChapterText">' . $text . '</div><bookmark content="'.$text.'" level="'.$tier.'"/>';
        }
        $command    = '';
        $image_file = '';
        $lgid       = '';
        $content    = $pdf->nmidGetWorkerFileContent() . 'chapter' . '@' . $command . '@' . $html . '@' . $tier . '@' . $image_file . '@' . $lgid . "\n";
        $pdf->nmidSetWorkerFileContent( $content );

		return 0;
	}

	// Print the chapter to the report - Multi Report
	function printChapterToReport( $pdf, $html )
	{
        check_remaining_height($pdf, $html);
        printChapterHTML($pdf, $html);
		return 0;
	}


	/* GENERIC FUNCTIONS */
	// General Image Creation
	function addImage( $pdf, $title, $imageFile, $lgid, $tier = 1 )
	{
		global $graphPerPage, $setLinks, $config, $headerFontSize, $font, $dir, $showGraphHeader;
		list( $imWidth, $imHeigth ) = convertPng( $imageFile );
		if ( file_exists( $imageFile ) == FALSE ) {
			return;
		}
		CereusReporting_logger( 'Adding image file [' . $imageFile . '][w:'.$imWidth.'/h:'.$imHeigth.'] to report.', "debug", "PDFCreation" );
		$pageWidth  = getWidthLeft( $pdf );
		$imageWidth = $pageWidth;
		if ( $pdf->nmidGetPdfType() == TCPDF_ENGINE ) {
			$cellWidth = $pdf->getPageWidth() / 1.7;
		}
		else {
			$cellWidth = $pdf->w / 1.7;
		}
		$imageX = NULL;

		if ( ( $setLinks == 'on' ) && ( $lgid > 0 ) ) {
			$cactiServerUrl = readConfigOption( 'nmid_pdfCactiServerUrl' );
			if ( strlen( $cactiServerUrl ) == 0 ) {
				$cactiServerUrl = 'http://' . $_SERVER[ 'SERVER_NAME' ] . ':' .
					$_SERVER[ 'SERVER_PORT' ];
			}
			$link = $cactiServerUrl . $config[ 'url_path' ] .
				"graph.php?action=view&local_graph_id=" . $lgid . "&rra_id=all";
		}
		else {
			$link = NULL;
		}

        if ( readConfigOption('nmid_pdf_new_bookmark_style') ) {
            $bookmarkString = '';
        } else {
            if ( strlen($title) > 0 ) {
                if ( $pdf->nmidGetPdfType() == TCPDF_ENGINE ) {
                    if (function_exists('top_header')) {
                        $params = $pdf->serializeTCPDFtagParameters(array($title, $tier, -1, '', '', array(0, 0, 0)));
                    } else {
                        $params = $pdf->serializeTCPDFtagParameters(array($title, $tier - 1, -1, '', '', array(0, 0, 0)));
                    }
                    $bookmarkString = '<tcpdf method="Bookmark" params="' . $params . '" />';
                } elseif ( $pdf->nmidGetPdfType() == MPDF_ENGINE ) {
                    $bookmarkString = '<bookmark content="'.$title.'" level="'.$tier.'"/>';
                }
            }
        }
        $html = '';
        $graphHeaderString = '';
        if ( $showGraphHeader ) {
            if ( strlen($title) > 0 ) {
                if ( readConfigOption('nmid_pdf_new_bookmark_style') ) {
                    $graphHeaderString = '<h'.$tier.' class="nmidImageText">' . $title . '</h'.$tier.'>';
                }
                else {
                    $graphHeaderString = '
                                <span class="nmidImageText">' . $title . '</span>';
                }
            }
        }

        $html .= '<table  width="100%" border="0" align="center" style="page-break-inside:avoid">
                        <thead style="display:none;">
                            <tr height="0px">
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>';
        if (strlen($graphHeaderString ) > 0 ) {
            $html .= '
                <tr nobr="true" align="center" class="nmidImageText">
                    <td width="100%">
                        ' . $graphHeaderString . $bookmarkString . '
                    </td>
                </tr>';
            if ( $pdf->nmidGetPdfType() == TCPDF_ENGINE ) {
                $bookmarkString = '';
            } elseif ( $pdf->nmidGetPdfType() == MPDF_ENGINE ) {
                $bookmarkString = '';
            }
        }

        if ( $imageWidth < 10 ) {
            $imageWidth = $imWidth;
            $html .= '
                    <tr nobr="true" align="center">
                        <td class="nmidImageGraph">' . $bookmarkString . '
                            <a href="' . $link . ' " style="text-decoration: none">
                                <img width="' . $imageWidth . '" src="' . $imageFile . '"/>
                            </a>
                        </td>
                    </tr>
               </tbody>
           </table>';
        }
        else {
            $html .= '
                    <tr nobr="true" align="center">
                        <td class="nmidImageGraph">' . $bookmarkString . '
                            <a href="' . $link . '" style="text-decoration: none">
                            <img width="' . $imWidth . '" src="' . $imageFile . '"/>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>';
        }
        printGenericTextHTML($pdf,$html);

    }

	// Unicode Support for FPDF
	function drawUnicodeText( $pdf, $font, $text, $width, $height, $fontSize )
	{
		mb_language( 'uni' );
		mb_internal_encoding( 'UTF-8' );

		$dir = dirname( __FILE__ ) . '/';
		//$unicodeFont = './font/'.lc($font) .'.ttf';
		$unicodeFont = $dir . 'font/simhei.ttf';
		//$width = 800;
		//$height = 100;
		$im = imagecreatetruecolor( $width, $height );

		$white = imagecolorallocate( $im, 255, 255, 255 );
		$black = imagecolorallocate( $im, 0, 0, 0 );

		// Create some colors
		imagefilledrectangle( $im, 0, 0, $width - 1, $height - 1, $white );

		// Add the text
		imagettftext( $im, $fontSize * 2, 0, 1, $height - 10, $black, $unicodeFont, $text );
		$imageFile = tempnam( sys_get_temp_dir() . '/', 'unicodeIMAGE' );
		unlink( $imageFile );
		$imageFile = $imageFile . '.jpg';
		imagejpeg( $im, $imageFile );
		imagedestroy( $im );
		return $imageFile;
	}
