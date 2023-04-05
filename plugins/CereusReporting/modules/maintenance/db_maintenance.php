<?php
	/*******************************************************************************
	 *
	 * File:         $Id: db_maintenance.php,v baddefb05461 2016/05/20 06:21:48 thurban $
	 * Modified_On:  $Date: 2016/05/20 06:21:48 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2013 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	function fix_availability_table_index()
	{
		CereusReporting_logger( "Fixing Index status on `plugin_nmidCreatePDF_Availability_Table`", 'notice', 'maintenance' );

		// Create and configure default cacti connection
		$db = DBCxn::get();
		
		// Dropping Index
		$stmt = $db->prepare( 'ALTER TABLE `plugin_nmidCreatePDF_Availability_Table` DROP `id`;' );
		$stmt->execute();

		// Fixing start index number
		$stmt = $db->prepare( 'ALTER TABLE `plugin_nmidCreatePDF_Availability_Table` AUTO_INCREMENT = 1;' );
		$stmt->execute();

		// Adding new index
		$stmt = $db->prepare( 'ALTER TABLE `plugin_nmidCreatePDF_Availability_Table` ADD `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;' );
		$stmt->execute();

		return TRUE;
	}

?>