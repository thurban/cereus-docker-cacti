<?php
	/*******************************************************************************
 * Copyright (c) 2017. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Urban <ThomasUrban@urban-software.de>, September 1943
 *
 * File:         $Id: init.php,v 732f49817120 2017/03/21 11:36:25 thurban $
 * Modified_On:  $Date: 2017/03/21 11:36:25 $
 * Modified_By:  $Author: thurban $
 ******************************************************************************/

	$dir = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting.queueScripts@", "", $dir );

	chdir( $dir );
	include_once( '../functions.php' ); // Support functions
	include_once( '../modules/queueManager/queueManager.php' ); // Report Queue Engine

	chdir( $mainDir );
	include_once( "./include/global.php" );
	include_once( "./lib/rrd.php" );
	//include_once( './include/config.php' );
	chdir( $dir );

	$Tasks = new Queue();
	$Tasks->run();

	/*
	 Queue::add($run_script, Array $params, 'GET' or 'POST');

	 Queue::add('email-customer', array('id' => 'Raivo', 'email' => 'raivo@php.net'), 'POST');
	 Queue::add('create-pdf-report', array('id' => 'Raivo', 'email' => 'raivo@php.net'), 'POST');
	 Queue::exists($run_script, Array $params);

	*/

	/*
	 * echo json_encode(array('status' => false));
	 * or
	 * echo json_encode(array('status' => true));
	 */
