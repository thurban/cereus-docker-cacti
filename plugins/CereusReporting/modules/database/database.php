<?php

	/*******************************************************************************
	 *
	 * File:         $Id: database.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2016 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

    //include_once(__DIR__.'/../logger/logger.php');

	class DBCxn
	{
		// What DSN to connect to?
		public static $dsn = 'sqlite:c:/data/zodiac.db';
		public static $user = null;
		public static $pass = null;
		public static $driverOpts = null;

		// Internal variable to hold the connection
		private static $db;
		// No cloning or instantiating allowed
		private function __construct() { }
		private function __clone() { }

		public static function get() {
			$dir = dirname( __FILE__ );
			$mainDir = preg_replace( "@plugins.CereusReporting.modules.database@", "", $dir );
			include( $mainDir."/include/config.php" );
			self::$dsn  = "mysql:host=" . $database_hostname . ";port=$database_port;dbname=" . $database_default;
			self::$user = $database_username;
			self::$pass = $database_password;
			//self::$driverOpts =  array(PDO::ATTR_PERSISTENT => true);

			try {
    			// Connect if not already connected
	    		if (is_null(self::$db)) {
                    // CereusReporting_logger( 'Creating new database connection.', 'debug', 'system' );
                    self::$db = new PDO(self::$dsn, self::$user, self::$pass,
                                        self::$driverOpts);
                }
                // Return the connection
                return self::$db;
            } catch (PDOException $e) {
                print "Error! : " . $e->getMessage() . "<br/>";
                die();
            }
		}
	}
