<?php
	/*******************************************************************************
	 *
	 * File:         $Id: logger.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
	 * Modified_On:  $Date: 2017/07/18 06:44:34 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2013 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	function CereusReporting_logger( $message, $mode = "notice", $category = "default" )
	{
		if ( function_exists('cacti_log') ) {
			// Creating Logger
			$filterMode = readConfigOption( "nmid_pdf_debug" );
			$message    = $category . ' - ' . $message;
			switch ( strtolower( $mode ) ) {
				case 'info':
					if ( $filterMode > 3 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				case 'notice':
					if ( $filterMode > 3 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				case 'warning':
					if ( $filterMode > 2 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				case 'error':
					if ( $filterMode > 1 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				case 'fatal':
					if ( $filterMode > 0 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				case 'debug':
					if ( $filterMode > 4 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				case 'audit_success':
					if ( $filterMode > -2 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				case 'audit_failed':
					if ( $filterMode > -2 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
				default:
					if ( $filterMode > -2 ) {
						cacti_log( $message, FALSE, 'CereusReporting' );
					}
					break;
			}
		}
	}
