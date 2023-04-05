<?php
/*******************************************************************************

 File:         update.php
 License:      Commercial
 Copyright:    Copyright 2011 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/

$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting.modules.update@","",$dir);
$pluginDir = preg_replace("@modules.update@","",$dir);

chdir($pluginDir);
require_once('functions.php');
require_once('cereusReporting_constants.php');

chdir($mainDir);
//include("./include/auth.php");
include_once("./include/auth.php");
include_once("./lib/data_query.php");

chdir($dir);

include("Zend/Soap/Client.php");

$key = ioncube_license_properties();
$purchaseId = $key['PurchaseId']['value'];
$customerId = $key['Customer']['value'];

$client = new Zend_Soap_Client( CR_SUPPORT_SERVER );
    
//$writer = new Zend_Log_Writer_Stream('/tmp/logfile');
//$logger = new Zend_Log($writer);
//$logger->info('Loading update functions');

function CR_checkVersion() {
    global $client;
    $latestVersion = $client->getLatestVersion();
    return $latestVersion;
}

function CR_updateVersion( $patchId ) {
    global $client;
    if (  CR_isValidCustomer() ) {
        $updateDir = readConfigOption('nmid_UpdateDir');
        $patchFileContent = $client->getPatchFileContent( $patchId ); // Base64 encoded zip
        $patchFileName = $patchId.'_hotfix.zip'; //$client->getPatchFileName( $patchId ); // Base64 encoded zip
        if ( length($patchFileContent) > 0 ) {
            $fileContent = base64_decode( $patchFileContent );
            $ifp = fopen( $updateDir.'/'.$patchFileName, "wb" );
            fwrite( $ifp, $fileContent );
            fclose( $ifp );
            return "Ok";
        } else {
            // No file with that patch id available
            return "MISSINGFILE";
        }
    } else {
        return "INVALIDCUST";
    }
}

function CR_getPatchList() {
    global $client, $purchaseId, $customerId;
    if (  CR_isValidCustomer() ) {
        $patchInfoArray = array();
        
        $patchList = $client->getPatchList( $purchaseId, $customerId );
        
        $patchListLines = preg_split("/\n/", $patchList);
        
        foreach ( $patchListLines as $patchListLine ) {
            $patchData = preg_split("/;/", $patchListLine );
            $patchInfoArray[ $patchData[0] ] = $patchData[1];
        }
        
        return $patchInfoArray;
    } else {
        return "INVALIDCUST";
    }
}

function CR_isValidCustomer() {
    global $client, $purchaseId, $customerId;
    $isValid = $client->validate( $purchaseId, $customerId );
    if ( $isValid == 'TRUE' ) {
        return 1;
    } else {
        return 0;
    }
}

?>