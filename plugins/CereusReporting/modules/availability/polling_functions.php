<?php
    /*******************************************************************************
    
     File:         $Id: CereusReporting_addReport.php 87 2010-12-07 17:34:16Z thurban $
     Modified_On:  $Date: 2010-12-07 18:34:16 +0100 (Di, 07 Dez 2010) $
     Modified_By:  $Author: thurban $ 
     Language:     Perl
     Encoding:     UTF-8
     Status:       -
     License:      Commercial
     Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
     
    *******************************************************************************/
    
    define('THOLD_STATUS_NORMAL', '5');
    define('THOLD_STATUS_ALERT', '4');
    define('THOLD_STATUS_WARNING', '3');
    define('THOLD_STATUS_RETRIGGER', '2');
    define('THOLD_STATUS_TRIGGER', '1');
    define('THOLD_STATUS_RESTORED', '0');
 
    function getTholdHost( $tholdId ) {
        $tholdHost = db_fetch_cell("
            SELECT
                host_id 
            FROM
                thold_data
            WHERE 
                thold_data.id = ".$tholdId);
        return $tholdHost;        
    }
 
 
    // Calculate Thold Availability 
    function getTholdAvailability( $tholdId, $startTime, $endTime ) {
        $totalPolls = getTholdTotalPolls($tholdId, $startTime, $endTime);
        $failedPolls = getTholdFailedPolls($tholdId, $startTime, $endTime);
        $availability = 100;
        if ( $totalPolls > 0 ) {
            $availability = 100 - ( ( $failedPolls * 100 ) / $totalPolls );
        }
        return $availability;
    }
    
    // Retrieve Thold Total Polls
    function getTholdTotalPolls( $tholdId, $startTime, $endTime ) {
        // Get total polling time
        $totalTime = $endTime - $startTime;
        
        // Check for scheduled maintenance
        //$totalTime = $totalTime - getTholdMaintenance( $tholdId, $startTime, $endTime );
        
        // Calculate the total polls
        //$totalPolls = $totalTime / getPollerInterval( $tholdId );
        return $totalTime;
    }
    
    // Get Maintenance Definitions for this thold data
    function getTholdMaintenance($tholdId, $startTime, $endTime) {
        $totalMaintenanceTime = 0;
        
        // Check if the "maint" plugin is installed.
        if ( readPluginStatus('maint') ) {
            $a_maintenance =  db_fetch_assoc("
                SELECT
                    stime,
                    etime,
                    host
                FROM
                    plugin_maint_hosts,plugin_maint_schedules,thold_data
                WHERE 
                    plugin_maint_hosts.`schedule` = plugin_maint_schedules.id
                AND
                    thold_data.host_id = plugin_maint_hosts.host
                AND
                   plugin_maint_schedules.enabled = 'on'
                AND
                    stime >= ".$startTime."
                AND
                    etime <= ".$endTime."
                AND
                    thold_data.id = ".$tholdId);
            
            // Calculate total maintenance time
            foreach ($a_maintenance as $s_maintenance) {
                $totalMaintenanceTime = $s_maintenance['etime'] - $s_maintenance['stime'];
            }
        }        
        return $totalMaintenanceTime;
    }
    
    // Get Maintenance Definitions for this thold data
    function getHostMaintenance($hostid, $startTime, $endTime) {
        $totalMaintenanceTime = 0;
        
        // Check if the "maint" plugin is installed.
        if ( readPluginStatus('maint') ) {
            $a_maintenance =  db_fetch_assoc("
                SELECT
                    stime,
                    etime,
                    host
                FROM
                    plugin_maint_hosts,plugin_maint_schedules
                WHERE 
                    plugin_maint_hosts.`schedule` = plugin_maint_schedules.id
                AND
                   plugin_maint_schedules.enabled = 'on'
                AND
                    stime >= ".$startTime."
                AND
                    etime <= ".$endTime."
                AND
                    plugin_maint_hosts.host = ".$hostid);
            
            // Calculate total maintenance time
            foreach ($a_maintenance as $s_maintenance) {
                $totalMaintenanceTime = $s_maintenance['etime'] - $s_maintenance['stime'];
            }
        }        
        return $totalMaintenanceTime;
    }
    
    // Retrieve Thold Failed Polls
    function getTholdFailedPolls( $tholdId, $startTime, $endTime ) {
        $totalFailedTholdPolls = 0;
        $totalFailedTholdTime = 0;
        $startTimeTholdBreach = 0;
        $endTimeTholdBreach = 0;
        $isInBreach = FALSE;
        
        $a_breaches = db_fetch_assoc("
            SELECT
                time,
                status
            FROM
                plugin_thold_log
            WHERE
                `threshold_id` = ".$tholdId."
            AND
                time <= ".$endTime."
            AND
                time >= ".$startTime."
            ORDER BY
                `time`");

    
        // Calculate total failed polls time
        foreach ($a_breaches as $s_breach) {
            if ( ($s_breach['status'] == THOLD_STATUS_ALERT ) || ($s_breach['status'] == THOLD_STATUS_TRIGGER ) ) {
                $isInBreach = TRUE;
                $startTimeTholdBreach = $s_breach['time'];
            }
            elseif ($s_breach['status'] == THOLD_STATUS_RETRIGGER ) {
                if ( $isInBreach ) {
                    $endTimeTholdBreach = $s_breach['time'];
                }
                else {
                    $isInBreach = TRUE;
                    $startTimeTholdBreach = $s_breach['time'];
                }
            }
            elseif ( ($s_breach['status'] == THOLD_STATUS_NORMAL ) || ($s_breach['status'] == THOLD_STATUS_RESTORED ) ) {
                if ( $isInBreach ) {
                    $isInBreach = FALSE;
                    $endTimeTholdBreach = $s_breach['time'];
                    
                    $failedTime = ($endTimeTholdBreach - $startTimeTholdBreach) - getTholdMaintenance($tholdId,$startTimeTholdBreach,$endTimeTholdBreach);
                    if ( $failedTime > 0 ){
                        $totalFailedTholdTime += $failedTime;                        
                    }
                } 
            }
	}
        
        if ( $isInBreach ) {
            $failedTime = time() - $startTimeTholdBreach;
            $totalFailedTholdTime += $failedTime;     
        }

        
        // Calculate the total failed polls
        //$totalFailedTholdPolls = $totalFailedTholdTime / getPollerInterval( $tholdId );
        return $totalFailedTholdTime;
    }
    
    function getPollerInterval( $tholdId ) {
        $rraId = db_fetch_cell("SELECT rra_id FROM thold_data where id = ".$tholdId);
        $pollerInterval = db_fetch_cell("SELECT 
                Min(data_template_data.rrd_step*rra.steps) AS poller_interval
                FROM data_template
                INNER JOIN (data_local
                INNER JOIN ((data_template_data_rra
                INNER JOIN data_template_data ON data_template_data_rra.data_template_data_id=data_template_data.id)
                INNER JOIN rra ON data_template_data_rra.rra_id = rra.id) ON data_local.id = data_template_data.local_data_id) ON data_template.id = data_template_data.data_template_id
                where data_template_data.local_data_id=".$rraId);
        return $pollerInterval;
    }
    
    function CereusReporting_getGraphPollerInterval( $ldid ) {
        $pollerInterval = db_fetch_cell("SELECT 
                Min(data_template_data.rrd_step*rra.steps) AS poller_interval
                FROM data_template
                INNER JOIN (data_local
                INNER JOIN ((data_template_data_rra
                INNER JOIN data_template_data ON data_template_data_rra.data_template_data_id=data_template_data.id)
                INNER JOIN rra ON data_template_data_rra.rra_id = rra.id) ON data_local.id = data_template_data.local_data_id) ON data_template.id = data_template_data.data_template_id
                where data_template_data.local_data_id=".$ldid);
        return $pollerInterval;
    }    
 
     // Retrieve Total Polls for Services
    function CereusReporting_getGraphTotalPolls( $ldid, $startTime, $endTime, $slaTimeFrame ) {
        $nonFailedServicePolls = 0;
        $hostId = db_fetch_cell("SELECT host_id from data_local where id=".$ldid);
        // Calculate the total polls
        //$pollerInterval = CereusReporting_getGraphPollerInterval ( $ldid );
        $pollerInterval = readConfigOption('poller_interval');
    
        // Get total polling time
        $totalPolls = CereusReporting_getGraphSLAtime( $ldid, $hostId, $startTime, $endTime, $slaTimeFrame );        
            
        // Check for scheduled maintenance
        //$totalPolls = $totalPolls - ( getHostMaintenance( $hostId, $startTime, $endTime ) / $pollerInterval );
        
        return ($totalPolls);
    }
    
    function CereusReporting_getGraphSLAtime($ldid, $hostId, $startTime, $endTime, $slaTimeFrame ) {
        // Get the SLA timeframe for this host
        //$pollerInterval = CereusReporting_getGraphPollerInterval ( $ldid );
        $pollerInterval = readConfigOption('poller_interval');

        // Check for SLA TimeFrame
        $s_timeframes_sql = "
            SELECT
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultDays`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultStartTime`,
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`defaultEndTime`
            FROM
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`
            WHERE
              `plugin_nmidCreatePDF_Availability_SLATimeFrame_Table`.`Id`=".$slaTimeFrame."
        ";
        $tfRows = db_fetch_assoc( $s_timeframes_sql );
        
        $polls = 0;
        $timeStamp = $startTime;
        while ( $timeStamp <= $endTime ) {
            $dayString = date("D", $timeStamp);
            $skipData = TRUE;
            foreach ( $tfRows as $tfRow ) {
                if ( preg_match("/$dayString/i",$tfRow['defaultDays'] ) ) {
                    $a_defaultStartTimeItemsList = preg_split("/,/",$tfRow['defaultStartTime']);
                    $a_defaultEndTimeItemsList = preg_split("/,/",$tfRow['defaultEndTime']);
                    for ( $listCount = 0; $listCount < sizeof( $a_defaultStartTimeItemsList ); $listCount++ ) {
                        $a_defaultStartTimeItems = preg_split("/:/",$a_defaultStartTimeItemsList[ $listCount ] );
                        $s_defaultStartTime = mktime($a_defaultStartTimeItems[0], $a_defaultStartTimeItems[1], 0, date("m",$timeStamp), date("j",$timeStamp), date("Y",$timeStamp) );
                        
                        $a_defaultEndTimeItems = preg_split("/:/",$a_defaultEndTimeItemsList[ $listCount ]);
                        $s_defaultEndTime = mktime($a_defaultEndTimeItems[0], $a_defaultEndTimeItems[1], 0, date("m",$timeStamp), date("j",$timeStamp), date("Y",$timeStamp) );

                        if ( ( $timeStamp >= $s_defaultStartTime ) AND ( $timeStamp  <= $s_defaultEndTime ) ){
                            // TimeStamp Falls Into the SLA time
                           $skipData = FALSE;
                        }
                    }
                }
            }
            if ( $skipData == FALSE ) {
                $polls++;
            }
            $timeStamp = $timeStamp + $pollerInterval;
        }
        return $polls;// $polls;
        
    }
?>