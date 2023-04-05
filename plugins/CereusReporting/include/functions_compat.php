<?php
	/*******************************************************************************
	 * Copyright (c) 2017. - All Rights Reserved
	 * Unauthorized copying of this file, via any medium is strictly prohibited
	 * Proprietary and confidential
	 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2017.
	 *
	 * File:         $Id: functions_compat.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
	 * Filename:     functions_compat.php
	 * LastModified: 13.04.17 12:07
	 * Modified_On:  $Date: 2018/11/11 17:22:55 $
	 * Modified_By:  $Author: thurban $
	 *
	 ******************************************************************************/


	function cr_top_header() {
		global $config;
		if ( function_exists('top_header')) {
			top_header();
		} else {
			include_once( $config[ 'base_path' ] . "/include/top_header.php" );
		}
	}

	function cr_draw_actions_dropdown( $actions_array, $delete_action = 1 ) {
		if ( function_exists('top_graph_header')) {
			draw_actions_dropdown( $actions_array, $delete_action );
		} else {
			draw_actions_dropdown( $actions_array );
		}
	}

	/**
	 *
	 */
	function cr_top_graph_header() {
		global $config;
		if ( function_exists('top_graph_header')) {
			top_graph_header();
		} else {
			include_once( $config[ 'base_path' ] . "/include/top_graph_header.php" );
		}
	}

	/**
	 *
	 */
	function cr_bottom_footer() {
		global $config;
		if ( function_exists('bottom_footer')) {
			bottom_footer();
		} else {
			include_once( $config[ 'base_path' ] . "/include/bottom_footer.php" );
		}
	}

	/**
	 *
	 */
	function cr_get_trees( $sql_where = '' ) {
		if ( function_exists('get_allowed_trees')) {
			$a_tree_items = db_fetch_assoc('
			SELECT
				graph_tree.id AS treeid,
				graph_tree.name AS name,
				graph_tree_items.title AS title,
				graph_tree_items.id AS leafid,
				graph_tree_items.parent AS parent
			FROM
			  	graph_tree_items
			INNER JOIN
				graph_tree
			ON
			  	graph_tree.id = graph_tree_items.graph_tree_id
			WHERE
			  ( 
			  		local_graph_id=0
			    AND
			    	host_id=0
			    AND
			    	title <> ""
			    AND 
			    	enabled="on"
			    '.$sql_where.'
			  )
			UNION SELECT
				graph_tree.id AS treeid,
				graph_tree.name AS name,
				"" AS title,
				"-1" AS leafid,
				"" AS parent
			FROM
				graph_tree            
			ORDER BY 
				treeid, parent,title');
			return $a_tree_items;
		} else {
			// old 0.8.8x stuff
			return db_fetch_assoc( '
                        SELECT
                            graph_tree.id AS treeid,
                            graph_tree.name AS name,
                            graph_tree_items.title AS title,
                            graph_tree_items.id AS leafid,
                            graph_tree_items.order_key AS level
                        FROM
                            graph_tree_items
                        INNER JOIN
                            graph_tree
                        ON
                            graph_tree.id = graph_tree_items.graph_tree_id
                        WHERE
                            ( rra_id=0
                            AND
                            host_id=0 
                            AND
                            title <> ""
                            )
                        UNION SELECT
                         graph_tree.id AS treeid,
                         graph_tree.name AS name,
                         "" AS title,
                         "-1" AS leafid,
                         "" AS level
                        FROM
                            graph_tree
                        ORDER BY treeid, level'
			);
		}
	}

	function cr_get_sites() {
	}

	function cr_get_hosts($tree_id = -1, $leaf_id = -1) {
		// Get DB Instance
		$db = DBCxn::get();
		if ( function_exists('get_allowed_trees')) {
			// Get all items for this tree
			$a_item_array = array();
			$a_item_array  = cr_get_graph_items( $a_item_array, $tree_id, $leaf_id, 0,  1 );
			return $a_item_array;
		} else {
			$sql       = "select host_id,local_graph_id,rra_id from graph_tree_items where graph_tree_id=?;";
			$params = array($tree_id);
			$stmt = $db->prepare($sql);
			$stmt->setFetchMode( PDO::FETCH_ASSOC );
			$stmt->execute($params);
			$devices = $stmt->fetchAll();
			$stmt->closeCursor();
			return $devices;
		}
	}

	function cr_get_leaf_items($tree_id = -1, $leaf_id = -1, $isSorted = false, $sortedFrom = '', $sortedTo = '') {
		$sortedChar = $sortedFrom;
		// Get DB Instance
		$db = DBCxn::get();
		$hostId   = getPreparedDBValue( 'SELECT host_id FROM graph_tree_items WHERE id=?;', array($leaf_id) );
		if ( function_exists('get_allowed_trees')) {
			// Get all items for this tree
			$a_item_array = array();
			$a_item_array = cr_get_graph_items( $a_item_array, $tree_id, $leaf_id, 0, 1 );
			if ( $isSorted == 1 ) { // Number sorting
				CereusReporting_logger( 'Searching for [' . $sortedTo . '] Number sorted devices from [' . $sortedFrom . ']', 'debug', 'availability' );
				$device_array = array();
				$device_count = 0;
				foreach ( $a_item_array as $device ) {
					if ( $device[ 'host_id' ] > 0 ) {
						$device_count++;
						if ( ( $device_count >= $sortedFrom ) && ( $device_count < ( $sortedFrom + $sortedTo ) ) ) {
							$local_stmt = $db->prepare( "SELECT hostname FROM host WHERE id = :hostid" );
							$local_stmt->bindValue( ':hostid', $device[ 'host_id' ] );
							$local_stmt->execute();
							$hostname = $local_stmt->fetchColumn();
							$local_stmt->closeCursor();
							$device_array[ $hostname ] = $device;
						}
					}
				}

				return $device_array;
			}
			elseif ( $isSorted == 2 ) { // ABC sorting
				CereusReporting_logger( 'Searching for ABC sorted devices for letter [' . $sortedFrom . ']', 'debug', 'availability' );
				$device_array = array();
				$count        = 0;
				foreach ( $a_item_array as $device ) {
					if ( $device[ 'host_id' ] > 0 ) {
						$local_stmt = $db->prepare( "SELECT description FROM host WHERE id = :hostid" );
						$local_stmt->bindValue( ':hostid', $device[ 'host_id' ] );
						$local_stmt->execute();
						$hostname = strtoupper($local_stmt->fetchColumn());
						$local_stmt->closeCursor();
						if ( substr($hostname,0,1) == strtoupper( $sortedChar ) ) {
							$device_array[ $hostname ] = $device;
						}
					}
				}

				return $device_array;
			} else {
				$device_array = array();
				foreach ( $a_item_array as $device ) {
					if ( $device[ 'host_id' ] > 0 ) {
						$device_array[] = $device;
					}
				}
				return $device_array;
			}
		} else {
			$orderKey = getPreparedDBValue( 'SELECT order_key FROM graph_tree_items WHERE id=?;', array($leaf_id) );
			$orderKey = preg_replace( "/0{3,3}/", "", $orderKey ) . '%';
			CereusReporting_logger( 'Checking Order Key : ['.$orderKey.']', "debug", "availability_server" );
			CereusReporting_logger( 'Checking host id : ['.$hostId.']', "debug", "availability_server" );
			CereusReporting_logger( 'Checking tree id : ['.$tree_id.']', "debug", "availability_server" );
			$params = array();
			$sql      = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE graph_tree_id=:treeid AND host_id>0 AND order_key LIKE :order_key;";
			$params['order'] = true;
			$params['treeid'] = true;
			if ( $isSorted == 1 ) {
				CereusReporting_logger( 'Searching for ['.$sortedTo.'] Number sorted devices from ['.$sortedFrom.']', 'debug', 'availability_server_compat' );
				$sql = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE graph_tree_id=:treeid AND host_id>0 AND order_key LIKE :order_key LIMIT :sorted_from,:sorted_to;";
				$params = array();
				$params['sorted'] = true;
				$params['treeid'] = true;
				$params['order'] = true;
			} else if ( $isSorted == 2 ) {
				CereusReporting_logger( 'Searching for ABC sorted devices for letter ['.$sortedFrom.']', 'debug', 'availability_server_compat' );
				$sql = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items,host WHERE graph_tree_id=:treeid AND host_id>0 AND order_key LIKE :order_key AND graph_tree_items.host_id = host.id  AND UPPER(host.description) LIKE '" . strtoupper( $sortedChar ) . "%';";
				$params = array();
				$params['treeid'] = true;
				$params['order'] = true;
			}
			if ( $hostId > 0 ) {
				$sql = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE id=:leafid;";
				$params = array();
				$params['leafid'] = true;
				if ( $isSorted ) {
					$sql = "SELECT host_id,local_graph_id,rra_id FROM graph_tree_items WHERE id=:leafid AND host_id>0 AND order_key LIKE :order_key LIMIT :sorted_from,:sorted_to;";
					$params = array();
					$params['leafid'] = true;
					$params['sorted'] = true;
					$params['order'] = true;
				}
			}
			CereusReporting_logger( 'Checking SQL Statement : ['.$sql.']', "debug", "availability_server_compat" );
			$stmt = $db->prepare($sql);
			if ( array_key_exists('leafid', $params) ) {
				CereusReporting_logger( 'SQL Statement - Setting Leafid: ['.$leaf_id.']', "debug", "availability_server_compat" );
				$stmt->bindParam(':leafid', $leaf_id, PDO::PARAM_INT);
			}
			if ( array_key_exists('treeid', $params) ) {
				CereusReporting_logger( 'SQL Statement - Setting treeid: ['.$tree_id.']', "debug", "availability_server_compat" );
				$stmt->bindParam(':treeid', $tree_id, PDO::PARAM_INT);
			}
			if ( array_key_exists('order', $params) ) {
				CereusReporting_logger( 'SQL Statement - Setting order_key: ['.$orderKey.']', "debug", "availability_server_compat" );
				$stmt->bindParam(':order_key', $orderKey , PDO::PARAM_STR);
			}
			if ( array_key_exists('sorted', $params) ) {
				CereusReporting_logger( 'SQL Statement - Setting LIMIT: ['.$sortedFrom.','.$sortedTo.']', "debug", "availability_server_compat" );
				error_reporting(0);
				$stmt->bindParam(':sorted_from', intval($sortedFrom), PDO::PARAM_INT);
				$stmt->bindParam(':sorted_to', intval($sortedTo), PDO::PARAM_INT);
				error_reporting(1);
			}
			$stmt->setFetchMode( PDO::FETCH_ASSOC );
			$stmt->execute();
			$devices = $stmt->fetchAll();
			CereusReporting_logger( 'Retrieved ['.count($devices).'] devices', "debug", "availability_server_compat" );

			$stmt->closeCursor();
			return $devices;
		}
	}

	function cr_get_site_items() {

	}

	/**
	 * @param $tree_id
	 */
//	function cr_get_tree_items( $tree_id ) {
//		if ( function_exists('get_allowed_graphs')) {
//			//cr_get_tree_item();
//		} else {
//			// old 0.8.8x stuff
//		}
//	}