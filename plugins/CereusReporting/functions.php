<?php
	/*******************************************************************************
	 * Copyright (c) 2016. - All Rights Reserved
	 * Unauthorized copying of this file, via any medium is strictly prohibited
	 * Proprietary and confidential
	 * Written by Thomas Urban <ThomasUrban@urban-software.de>, September 1943
	 *
	 * File:         $Id: functions.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
	 * Modified_On:  $Date: 2017/11/01 15:05:58 $
	 * Modified_By:  $Author: thurban $
	 ******************************************************************************/

	// ini_set( "soap.wsdl_cache_enabled", "0" );

	define ("SUPPORT_URL", 'https://check.urban-software.com/check_support.php');

	// PDF Engines
	define( "FPDF_ENGINE", 0 );
	define( "MPDF_ENGINE", 1 );
	define( "TCPDF_ENGINE", 2 );

	// Report Types
	define( "GRAPH_REPORT", 0 );
	define( "MULTI_REPORT", 1 );
	define( "DSSTATS_REPORT", 3 );

	// Encryption Key for image retrieval
	define( 'SECURE_URL_KEY', '&Q6nofak!Lvi^5J7zlJG4gVq8ziiQUJM1i1Mozh^e$tqNjcLXDcB%rRncn@i' );

	// Output Types
	define( "HTML_OUTPUT", 1 );
	define( "PDF_OUTPUT", 0 );

	$key = array(
		'EDITION'    => array( 'value' => 'CORPORATE' ),
		'DSLIMITSMB' => array( 'value' => '200' ),
		'CUSTOMER'   => array( 'value' => 'CORPORATE Customer' )
	);

	define( "EDITION", $key[ 'EDITION' ][ 'value' ] );
	define( "CUSTOMER", $key[ 'CUSTOMER' ][ 'value' ] );

	define( "CEREUSREPORTING_DSCOUNT", 200 );

    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "alternate" ] = '';
    $colors[ "light" ] = '';
    $colors[ "header" ] = '';

	$functionsDir = __DIR__;
	$modulePath   = __DIR__ . '/modules';
	$includePath  = __DIR__. '/include';

	set_include_path( get_include_path() . PATH_SEPARATOR . $modulePath );

	// include ezComponents
	// set_include_path( get_include_path() . PATH_SEPARATOR . $modulePath . '/ezc' );
	// require_once 'Base/src/ezc_bootstrap.php';

	// include new DB functionality
	include_once( $modulePath . '/database/database.php' );

	// include mail functionality
	include_once( $modulePath . '/mail/nmid_mail.php' );

	// include logging functionality
	include_once( $modulePath . '/logger/logger.php' );

	// Include maintenance tasks
	include_once( $modulePath . '/maintenance/db_maintenance.php' );

	// Include Availability functions
	include_once( $includePath . '/availability_functions.php' );

	// Include Report functions
	include_once( $includePath . '/report_functions.php' );

	// Include Compatibility functions
	include_once( __DIR__.'/include/functions_compat.php' );
	/*
		if ( function_exists( 'csrf_get_tokens' ) ) {
			// CSRF installed
		}
		else {
			$cacti_version = getCactiVersion();
			// get minor and major version number
			preg_match( "@(\d+)\.(\d+)\.(\d+)(\w*)@", $cacti_version, $version_match );
			$version_major        = $version_match[ 1 ];
			$version_minor        = $version_match[ 2 ];
			$version_build        = $version_match[ 3 ];
			$version_build_minor  = $version_match[ 4 ];
			if ( $version_major < 1 ) {  // 0.
				if ( $version_minor < 9 ) {  // 0.8
					if ( $version_build < 8 ) { // 0.8.7
						function csrf_get_tokens()
						{
							return '';
						}
					}
					else {  // 0.8.8
						if ( ord( $version_build_minor ) < 99 ) { // 0.8.8a 0.8.8b
							function csrf_get_tokens()
							{
								return '';
							}
						}
					}
				}
			}
		}
	*/
	/*
	Support Functions
	*/
	if ( !function_exists( '_' ) ) {
		function _( $text )
		{
			return $text;
		}
	}

	if ( readConfigOption('nmid_pdf_debug') == 5 ) {
		error_reporting( -1 );
	} else {
		error_reporting( 0 );
	}

	function getWidthLeft( $pdf )
	{
		$x = $pdf->GetX();
		if ( $pdf->nmid_pdf_type == TCPDF_ENGINE ) {
			$myMargins = $pdf->getMargins();
			$pdf->SetX( -1 * $myMargins[ 'right' ] );
		}
		else {
			$pdf->SetX( -1 * $pdf->rMargin );
		}
		$leftWidth = $pdf->GetX() - $x;
		$pdf->SetX( $x );
		return $leftWidth;
	}

	function getHeightLeft( $pdf )
	{
		$y = $pdf->GetY();
		if ( $pdf->nmid_pdf_type == TCPDF_ENGINE ) {
			$myMargins = $pdf->getMargins();
			$pdf->SetX( -1 * $myMargins[ 'left' ] );
		}
		else {
			$pdf->SetY( -1 * $pdf->bMargin );
		}
		$leftHeight = $pdf->GetY() - $y;
		$pdf->SetY( $y );
		return $leftHeight;
	}

	/**
	 * Checks the given password with the web-service password stored in the cacti DB
	 *
	 * @param string $option
	 * @param int    $userId
	 * @param string $type
	 *
	 * @return string
	 */
	function readConfigOption( $option, $userId = -1, $type = 'default' )
	{
		// Get DB Instance
		$db = DBCxn::get();
		// $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$db->setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE );

		$config_option = '';
		if ( $type == 'graph' ) {
			if ( isNumber( $userId ) ) {
				// $query = "select value from settings_graphs where name='" . $option . "' and userId=" . $userId;
				$local_stmt = $db->prepare( 'SELECT `value` FROM `settings_graphs` WHERE `name` = :option AND user_id = :userId' );
				$local_stmt->bindValue( ':option', $option );
				$local_stmt->bindValue( ':userId', $userId );
				$local_stmt->execute();
				$config_option = $local_stmt->fetchColumn();
				$local_stmt    = NULL;
			}
			else {
				return -1;
			}
		}
		else {
			//$query = "select value from settings where name='" . $option . "'";//
			$my_local_stmt = $db->prepare( 'SELECT `value` FROM `settings` WHERE `name` = :option' );
			$my_local_stmt->bindValue( ':option', $option );
			$my_local_stmt->execute();
			$config_option = $my_local_stmt->fetchColumn();
			$my_local_stmt = NULL;

		}
		return $config_option;
	}

	// Check Plugin status / support
	function readPluginStatus( $plugin )
	{
		// Get DB Instance
		$db            = DBCxn::get();
		$plugin_status = FALSE;

		// $plugin_status = db_fetch_cell( "SELECT status FROM plugin_config WHERE directory='" . $plugin . "' AND status=1" );
		$local_stmt = $db->prepare( 'SELECT status FROM plugin_config WHERE directory = :plugin AND status=1' );
		$local_stmt->bindValue( ':plugin', $plugin );
		$local_stmt->execute();
		if ( $local_stmt ) {
			$plugin_status = $local_stmt->fetchColumn();
		}
		return $plugin_status;
	}

	// Check Plugin status / support
	function getCactiVersion( )
	{
		global $config;
		// Get DB Instance
		$db            = DBCxn::get();
		$cacti_version = '0.8.7';

		// $plugin_status = db_fetch_cell( "SELECT status FROM plugin_config WHERE directory='" . $plugin . "' AND status=1" );
		$local_stmt = $db->prepare( 'select cacti from version;' );
		$local_stmt->execute();
		if ( $local_stmt ) {
			$cacti_version = $local_stmt->fetchColumn();
		}
		return $cacti_version;
	}

	// retrieve one specific value from a table
	function getDBValue( $name, $sql )
	{
		global $config;
		// Get DB Instance
		$db   = DBCxn::get();
		$data = '';

		$stmt = $db->prepare( $sql );
		$stmt->execute();
		if ( $stmt ) {
			$data = $stmt->fetchColumn();
		}
		$stmt->closeCursor();
		return $data;
	}

	// retrieve one specific value from a table
	function cr_db_execute( $sql )
	{
		// Get DB Instance
		$db   = DBCxn::get();

		$stmt = $db->prepare( $sql );
		$stmt->execute();
		$stmt->closeCursor();
	}

	// retrieve one specific value from a table
	function getPreparedDBValue( $sql, $params )
	{
		global $config;
		// Get DB Instance
		$db   = DBCxn::get();
		$data = '';

		$stmt = $db->prepare( $sql );
		if ( is_array($params) ) {
			$stmt->execute( $params );
		} else {
			$stmt->execute( );
		}
		if ( $stmt ) {
			$data = $stmt->fetchColumn();
		}
		$stmt->closeCursor();

		return $data;
	}

	function cr_db_fetch_assoc_prepared( $sql, $params )
	{
		global $config;
		// Get DB Instance
		$db   = DBCxn::get();
		$data = array();

		$sth = $db->prepare( $sql );
		$sth->execute( $params );
		if ( $sth ) {
			$data = $sth->fetchAll( PDO::FETCH_ASSOC );
		}
		$sth->closeCursor();
		return $data;
	}

	function get_support_site_data($key){

		// is cURL installed yet?
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}

		// Curl is installed so proceed
		$ch_supp = curl_init();
		curl_setopt($ch_supp, CURLOPT_URL, SUPPORT_URL);
		curl_setopt($ch_supp, CURLOPT_REFERER, "http://www.urban-software.com/");
		curl_setopt($ch_supp, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
		curl_setopt($ch_supp, CURLOPT_HEADER, 0);
		curl_setopt($ch_supp, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch_supp, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch_supp);
		curl_close($ch_supp);
		return $output;
	}

	function replaceTextFields( $text, $replaceArray )
	{
		if ( array_key_exists( 'START', $replaceArray ) ) {
			$text = preg_replace( '/\[START\]/', $replaceArray[ 'START' ], $text );
		}
		if ( array_key_exists( 'END', $replaceArray ) ) {
			$text = preg_replace( '/\[END\]/', $replaceArray[ 'END' ], $text );
		}
		if ( array_key_exists( 'REPORTTITLE', $replaceArray ) ) {
			$text = preg_replace( '/\[REPORTTITLE\]/', $replaceArray[ 'REPORTTITLE' ], $text );
		}
		if ( array_key_exists( 'REPORTSUBTITLE', $replaceArray ) ) {
			$text = preg_replace( '/\[REPORTSUBTITLE\]/', $replaceArray[ 'REPORTSUBTITLE' ], $text );
		}
		if ( array_key_exists( 'DEVICENAME', $replaceArray ) ) {
			$text = preg_replace( '/\[DEVICENAME\]/', $replaceArray[ 'DEVICENAME' ], $text );
		}
		if ( array_key_exists( 'DEVICEDESCRIPTION', $replaceArray ) ) {
			$text = preg_replace( '/\[DEVICEDESCRIPTION\]/', $replaceArray[ 'DEVICEDESCRIPTION' ], $text );
		}
		if ( array_key_exists( 'DEVICEIP', $replaceArray ) ) {
			$text = preg_replace( '/\[DEVICEIP\]/', $replaceArray[ 'DEVICEIP' ], $text );
		}
		if ( array_key_exists( 'CURRENTDATE', $replaceArray ) ) {
			$text = preg_replace( '/\[CURRENTDATE\]/', $replaceArray[ 'CURRENTDATE' ], $text );
		}
		if ( array_key_exists( 'CUSTOMER', $replaceArray ) ) {
			$text = preg_replace( '/\[CUSTOMER\]/', $replaceArray[ 'CUSTOMER' ], $text );
		}
		if ( array_key_exists( 'TREENAME', $replaceArray ) ) {
			$text = preg_replace( '/\[TREENAME\]/', $replaceArray[ 'TREENAME' ], $text );
		}
		if ( array_key_exists( 'AUTHOR', $replaceArray ) ) {
			$text = preg_replace( '/\[AUTHOR\]/', $replaceArray[ 'AUTHOR' ], $text );
		}
		if ( array_key_exists( 'EMAIL', $replaceArray ) ) {
			$text = preg_replace( '/\[EMAIL\]/', $replaceArray[ 'EMAIL' ], $text );
		}
		if ( array_key_exists( 'REPORTDATE', $replaceArray ) ) {
			$text = preg_replace( '/\[REPORTDATE\]/', $replaceArray[ 'REPORTDATE' ], $text );
		}

		return $text;
	}

	function convertPng( $imgname )
	{
        $im = false;
		if ( file_exists( $imgname ) ) {
			if ( is_dir( $imgname ) ) {
				$imgname = 'images/unknow.png';
			}
			// thats fine then !
		}
		else {
			$imgname = 'images/unknow.png';
		}
		if ( file_exists($imgname) ) {
            try {
                if (getimagesize($imgname) > 1) {
                    $im = ImageCreateFromPNG($imgname); /* Versuch, Datei zu �ffnen */
                } else {
                    $im = false;
                }
            } catch (Exception $e) {
                $im = false;
            }
        }

		if ( !$im ) { /* Pr�fen, ob fehlgeschlagen */
			$im  = ImageCreate( 500, 100 ); /* Erzeugen eines leeren Bildes */
			$bgc = ImageColorAllocate( $im, 255, 255, 255 );
			$tc  = ImageColorAllocate( $im, 0, 0, 0 );
			ImageFilledRectangle( $im, 0, 0, 150, 30, $bgc );
			/* Ausgabe einer Fehlermeldung */
			ImageString( $im, 1, 5, 5, "Error opening the file : $imgname", $tc );
		}
		imagealphablending( $im, FALSE );
		if ( ( file_exists( $imgname ) ) && ( filesize($imgname) > 0 ) ) {
			// skip
		}
		if ( ( filesize($imgname) >= 0 ) && ( strstr($imgname, sys_get_temp_dir() ) ) ) {
			imagepng( $im, $imgname );
		}

		$imWidth  = imagesx( $im );
		$imHeight = imagesy( $im );
		return array( $imWidth, $imHeight );
	}

	//conversion pixel -> millimeter in 72 dpi
	function px2mm( $px )
	{
		return $px * 25.4 / 72;
	}

	function mm2px( $mm )
	{
		return ( $mm / 25.4 ) * 72;
	}

	// validation functions

	/**
	 * Check if given parameter is an integer
	 *
	 * @param int $var
	 *
	 * @return bool
	 */
	function isNumber( $var )
	{
		if ( preg_match( "/^([0-9]+)$/", $var ) ) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	function makeXMLClean( $strin )
	{
		$strout = NULL;

		for ( $i = 0; $i < strlen( $strin ); $i++ ) {
			$ord = ord( $strin[ $i ] );

			if ( ( $ord > 0 && $ord < 32 ) || ( $ord >= 127 ) ) {
				$strout .= "&amp;#{$ord};";
			}
			else {
				switch ( $strin[ $i ] ) {
					case '<':
						$strout .= '&lt;';
						break;
					case '>':
						$strout .= '&gt;';
						break;
					case '&':
						$strout .= '&amp;';
						break;
					case '"':
						$strout .= '&quot;';
						break;
					default:
						$strout .= $strin[ $i ];
				}
			}
		}

		return $strout;
	}

	function makeSafe( $var )
	{
		return escapeshellcmd( $var );
	}

	function isServerLicensed()
	{
        return TRUE;
	}

    /**
     * @return bool
     */
    function isLicenseExpired()
	{
        return false;
	}

	function isSMBServer()
	{
		return true;

	}

	function getLicenseExpiry()
	{
        return 7265938030;
	}

	function isPluginLicensed( $pluginName )
	{
        return true;
	}


	function CereusReporting_process_schedule( $mode )
	{
		$db = DBCxn::get();

		$logType = 'POLLER';
		if ( readConfigOption( 'nmid_pdfscheduletype' ) == 'cron' ) {
			$logType = 'CRON';
		}
		if ( $mode != $logType ) {
			// return;
		}

		CereusReporting_logger( 'Retrieving active report schedules.', "debug", "functions" );
		$sql                = "
            SELECT
              `plugin_nmidCreatePDF_Reports_scheduler`.`ScheduleId`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`Name`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`Date`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`lastRunDate`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`isRecurring`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`frequency`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`Status`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`Creator`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`ReportID`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`Recipients`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`RecipientsBcc`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`Description`,
              `plugin_nmidCreatePDF_Reports_scheduler`.`runNow`
            FROM
              `plugin_nmidCreatePDF_Reports_scheduler`
            WHERE
              `plugin_nmidCreatePDF_Reports_scheduler`.`Status` = 1
            OR
              `plugin_nmidCreatePDF_Reports_scheduler`.`runNow` = 1  ;
            ";
		$stmt = $db->prepare( $sql );
		$stmt->execute();

		$a_scheduledReports = $stmt->fetchAll( PDO::FETCH_ASSOC );
		$stmt->closeCursor();



		if (is_array($a_scheduledReports)) {
			foreach ( $a_scheduledReports as $a_scheduled ) {
				CereusReporting_logger( 'Checking schedule [' . $a_scheduled[ 'Name' ] . ']', "debug", "functions" );
				if ( $a_scheduled[ 'isRecurring' ] == 1 ) {
					CereusReporting_logger( 'Report [' . $a_scheduled[ 'Name' ] . '] is recurring', "debug", "functions" );
					if ( ( time() >= $a_scheduled[ 'Date' ] ) || ( $a_scheduled[ 'runNow' ] == '1' ) ) {
						CereusReporting_logger( 'NMID CereusReporting - Sending report', "info", "mail" );
						CereusReporting_sendReport( $a_scheduled[ 'ReportID' ], $a_scheduled[ 'Name' ], $a_scheduled[ 'Description' ], $a_scheduled[ 'Recipients' ], $a_scheduled[ 'RecipientsBcc' ], FALSE, $a_scheduled[ 'ScheduleId' ] );
						while ( time() >= $a_scheduled[ 'Date' ] ) {
							if ( $a_scheduled[ 'frequency' ] == 'h' ) {
								$a_scheduled[ 'Date' ] = strtotime( '1 hour', $a_scheduled[ 'Date' ] );
							}
							elseif ( $a_scheduled[ 'frequency' ] == 'd' ) {
								$a_scheduled[ 'Date' ] = strtotime( '1 day', $a_scheduled[ 'Date' ] );
							}
							elseif ( $a_scheduled[ 'frequency' ] == 'w' ) {
								$a_scheduled[ 'Date' ] = strtotime( '1 week', $a_scheduled[ 'Date' ] );
							}
							elseif ( $a_scheduled[ 'frequency' ] == 'm' ) {
								$a_scheduled[ 'Date' ] = strtotime( '1 month', $a_scheduled[ 'Date' ] );
							}
							elseif ( $a_scheduled[ 'frequency' ] == 'y' ) {
								$a_scheduled[ 'Date' ] = strtotime( '1 year', $a_scheduled[ 'Date' ] );
							}
						}
						// Jira Ticket: CRC-20
						$sql = 'UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET Date=' . $a_scheduled[ 'Date' ] . ' WHERE ScheduleId=' . $a_scheduled[ 'ScheduleId' ];
						$stmt = $db->prepare( $sql );
						$stmt->execute();
						$stmt->closeCursor();

						$sql = 'UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET runNow=0 WHERE ScheduleId=' . $a_scheduled[ 'ScheduleId' ];
						$stmt = $db->prepare( $sql );
						$stmt->execute();
						$stmt->closeCursor();
					} // end if
				} // end if
				else {
					CereusReporting_logger( 'Report [' . $a_scheduled[ 'Name' ] . '] is a one-time report', "debug", "functions" );
					if ( ( time() >= $a_scheduled[ 'Date' ] ) || ( $a_scheduled[ 'runNow' ] == '1' ) ) {

						CereusReporting_logger( 'NMID CereusReporting - Sending scheduled one-time report', "info", "mail" );
						CereusReporting_sendReport( $a_scheduled[ 'ReportID' ], $a_scheduled[ 'Name' ], $a_scheduled[ 'Description' ], $a_scheduled[ 'Recipients' ], $a_scheduled[ 'RecipientsBcc' ], TRUE, $a_scheduled[ 'ScheduleId' ] );

						$sql = 'UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET runNow=0 WHERE ScheduleId=' . $a_scheduled[ 'ScheduleId' ];
						$stmt = $db->prepare( $sql );
						$stmt->execute();
						$stmt->closeCursor();
					} // end if
				} // end else
			} // end foreach
		} // end if
	} // end function

	/*
	Check if the directory is empty
	*/
	function is_emtpy_dir( $dirname )
	{
		$isEmpty = FALSE;
		if ( is_dir( $dirname ) ) {
			$isEmpty = TRUE;
			$handle  = opendir( $dirname );
			while ( ( $name = readdir( $handle ) ) !== FALSE ) {
				if ( $name != "." && $name != ".." ) {
					$isEmpty = FALSE;
					break;
				}
			}
			closedir( $handle );
		}
		return $isEmpty;
	}


	function CereusReporting_getHostCountFromLeaf( $leaf_id, $tree_id )
	{
		// Get DB Instance
		$db          = DBCxn::get();

		if ( function_exists('get_allowed_trees')) {
			$devices = cr_get_hosts($tree_id,$leaf_id);
			return sizeof($devices);
		} else {

			$stmt        = NULL;
			$serverCount = 0;
			$orderKey    = getPreparedDBValue( 'SELECT order_key FROM graph_tree_items WHERE id=?;', array( $leaf_id ) );
			$hostId      = getPreparedDBValue( 'SELECT host_id FROM graph_tree_items WHERE id=?;', array( $leaf_id ) );
			$orderKey    = preg_replace( "/0{3,3}/", "", $orderKey );

			if ( $hostId > 0 ) {
				$stmt = $db->prepare( 'SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE id=:leafid;' );
				$stmt->bindValue( ':leafid', $leaf_id );
			}
			else {
				$stmt = $db->prepare( 'SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE graph_tree_id=:treeid AND order_key LIKE :orderkey;' );
				$stmt->bindValue( ':treeid', $tree_id );
				$stmt->bindValue( ':orderkey', $orderKey . '%' );
			}

			$stmt->setFetchMode( PDO::FETCH_ASSOC );
			$stmt->execute();

			while ( $row = $stmt->fetch() ) {
				if ( $row[ 'host_id' ] > 0 ) {
					$serverCount++;
				}
			}

			if ( $stmt ) {
				$stmt->closeCursor();
			}
		}
		return $serverCount;
	}


	function CereusReporting_cleanup_files($report, $debugModeOn = 0, $reportType= '') {
		if ( $debugModeOn < 5 ) {
			if ( $report->nmidGetWorkerFile() ) {
				CereusReporting_logger( 'NMID CereusReporting - Retrieving file for removal from [' . $report->nmidGetWorkerFile() . ']', "info", "cleanup" );
				//Remove images
				$fh = fopen( $report->nmidGetWorkerFile(), "r" );
				while ( $line = fgets( $fh ) ) {
					$a_data       = preg_split( "/@/", $line );
					$s_image_file = $a_data[ 4 ];
					$s_type       = $a_data[ 0 ];
					if ( $s_type == 'graph' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}
					elseif ( $s_type == 'smokeping' ) {
						if ( file_exists( $s_image_file ) ) {
							$file = $s_image_file;
							if ( filesize( $file ) > 0 ) {
								$f         = fopen( $file, 'r' );
								$imageFile = fread( $f, filesize( $file ) );
								fclose( $f );
								if ( file_exists( $imageFile ) ) {
									unlink( $imageFile );
								}
								if ( file_exists( $s_image_file ) ) {
									unlink( $s_image_file );
								}
							}
						}
					}
					elseif ( $s_type == 'dsstats' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}
					elseif ( $s_type == 'availability' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}
					elseif ( $s_type == 'availability_combined' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}
					elseif ( $s_type == 'availability_winservice' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}
					elseif ( $s_type == 'availability_thold' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}
					elseif ( $s_type == 'availability_thold_tree_sum' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}
					elseif ( $s_type == 'availability_tree_sum' ) {
						if ( file_exists( $s_image_file ) ) {
							unlink( $s_image_file );
						}
					}

				}
				fclose( $fh );

				CereusReporting_logger( 'NMID CereusReporting - Removing workerfile [' . $report->nmidGetWorkerFile() . ']', "info", "cleanup" );
				// Remove workerfile
				if ( file_exists( $report->nmidGetWorkerFile() ) ) {
					unlink( $report->nmidGetWorkerFile() );
				}
				else {
					CereusReporting_logger( 'WARNING: NMID CereusReporting - Cannote remove worker file [' . $report->nmidGetWorkerDir() . ']', "fatal", "cleanup" );
				}

				CereusReporting_logger( 'NMID CereusReporting - Removing worker directory[' . $report->nmidGetWorkerDir() . ']', "info", "cleanup" );
				// Remove worker dir if empty
				if ( is_emtpy_dir( $report->nmidGetWorkerDir() ) ) {
					rmdir( $report->nmidGetWorkerDir() );
				}
				else {
					CereusReporting_logger( 'WARNING: NMID CereusReporting - Cannote remove worker directory [' . $report->nmidGetWorkerDir() . ']', "fatal", "cleanup" );
				}
			}
		}
		else {
            CereusReporting_logger( 'Skipping Cleanup for debug mode.', "debug", "PDFCreation" );
            CereusReporting_logger( 'Worker file still exists : ['.$report->nmidGetWorkerFile().']', "debug", "PDFCreation" );
        }
	}