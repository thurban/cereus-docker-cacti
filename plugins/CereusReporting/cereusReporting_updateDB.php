<?php
	/*******************************************************************************
	 *
	 * File:         $Id: cereusReporting_updateDB.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	* Encoding:     UTF-8
	* Status:       -
	* License:      Commercial
	* Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	include_once( 'functions.php' );

	$mainDir = preg_replace( "@plugins.CereusReporting@", "", __DIR__ );
	chdir( $mainDir );
	//include("./include/auth.php");
	include_once( "./include/global.php" );
	include_once( './include/config.php' );
	chdir( __DIR__ );

	define("ORDER",0);
	define("ID",1);

	/* Create Connection to the DB */
	$db = DBCxn::get();
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE );

	if ( readPluginStatus( 'nmidskin' ) ) {
		/* set default action */
		if ( !isset( $_GET[ "ReportId" ] ) ) {
			$_GET[ "ReportId" ] = "";
		}
		$reportId   = $_GET[ "ReportId" ];
		$item       = $_GET[ "id" ];
		$itemId     = preg_replace( "/line/", "", $item );
		$from_place = $_GET[ "fromPosition" ];
		$to_place   = $_GET[ "toPosition" ];

		$stmt = $db->prepare( 'SELECT
            `order`,id
            from plugin_nmidCreatePDF_MultiGraphReports
			where ReportId= :ReportId
			order by `order`'
		);
		$stmt->bindValue( ':ReportId', $reportId );
		//$stmt->setFetchMode( PDO::FETCH_ASSOC );
		$stmt->execute();

		$order_row_number = 0;
		$rows = $stmt->fetchAll();
		foreach ( $rows as $row )
		{
			//CereusReporting_logger( "Changing order of [$itemId] from Report [$reportId]. Current row: [".$row[ ORDER ]."]. From:[$from_place] To:[$to_place].", 'debug', 'updatedb' );
			$order_row_number++;
			if ( $order_row_number == $to_place ) {
				CereusReporting_logger( "Found Row: [$itemId] from Report [$reportId]. Current row: [".$row[ ORDER ]."]. From:[$from_place] To:[$to_place].", 'debug', 'updatedb' );
				$ustmt = $db->prepare('UPDATE `plugin_nmidCreatePDF_MultiGraphReports` SET `order`= :order WHERE `id`= :itemId AND `ReportId`= :ReportId');
				$ustmt->bindValue( ':order', $order_row_number );
				$ustmt->bindValue( ':itemId', $itemId );
				$ustmt->bindValue( ':ReportId', $reportId );
				$return = $ustmt->execute();
				$ustmt->closeCursor();
				if ($return) {
					CereusReporting_logger( "Updated Row [$itemId] : ", 'debug', 'updatedb' );
					$order_row_number++;
					$ustmt = $db->prepare('UPDATE `plugin_nmidCreatePDF_MultiGraphReports` SET `order`= :order WHERE `id`= :itemId AND `ReportId`= :ReportId');
					$ustmt->bindValue( ':order', $order_row_number );
					$ustmt->bindValue( ':itemId', $row[ 'id' ]);
					$ustmt->bindValue( ':ReportId', $reportId );
					$return = $ustmt->execute();
					$ustmt->closeCursor();
					if ( $return ) {
						CereusReporting_logger( "Updated Row [ " . $row[ 'id' ] . " ]", 'debug', 'updatedb' );
					}
					else {
						CereusReporting_logger( "Failed to update Row [ " . $row[ 'id' ] . " ].", 'debug', 'updatedb' );
					}
				} else {
					CereusReporting_logger( "Failed to update Row [$itemId].", 'debug', 'updatedb' );
				}
			}
			else {
				if ( $row[ 'id' ] == $itemId ) {
					CereusReporting_logger( "Skipping  Row [$itemId] at [$order_row_number]", 'debug', 'updatedb' );
					$order_row_number = $order_row_number - 1;
				}
				else {
					$ustmt = $db->prepare('UPDATE `plugin_nmidCreatePDF_MultiGraphReports` SET `order`= :order WHERE `id`= :itemId AND `ReportId`= :ReportId');
					$ustmt->bindValue( ':order', $order_row_number );
					$ustmt->bindValue( ':itemId', $row[ 'id' ]);
					$ustmt->bindValue( ':ReportId', $reportId );
					$return = $ustmt->execute();
					$ustmt->closeCursor();
					if ( $return ) {
						CereusReporting_logger( "Updated Row [ " . $row[ 'id' ] . " ]", 'debug', 'updatedb' );
					}
					else {
						CereusReporting_logger( "Failed to update Row [ " . $row[ 'id' ] . " ].", 'debug', 'updatedb' );
					}
				}

			}
		}
	}
	else {
		if ( !isset( $_REQUEST[ "ReportId" ] ) ) {
			$_REQUEST[ "ReportId" ] = "";
		}
		$reportId = $_REQUEST[ "ReportId" ];
		$items    = $_REQUEST[ "reportItems" ];
		$orderNr  = 1;
		foreach ( $items as $item ) {
			$itemId = preg_replace( "/line/", "", $item );
			if ( ( $itemId > 0 ) && ( isNumber( $itemId ) ) ) {
				if ( isNumber( $reportId ) ) {

					//CereusReporting_logger( "Reordering report items", 'debug', 'gui' );
					$ustmt = $db->prepare('UPDATE `plugin_nmidCreatePDF_MultiGraphReports` SET `order`= :order WHERE `id`= :itemId AND `ReportId`= :ReportId');
					$ustmt->bindValue( ':order', $orderNr);
					$ustmt->bindValue( ':itemId', $itemId);
					$ustmt->bindValue( ':ReportId', $reportId );
					$ustmt->execute();
					$ustmt->closeCursor();
					$orderNr++;
				}
			}
		}
	}

	return TRUE;
?>
