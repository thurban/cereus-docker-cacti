<?php
	/*******************************************************************************

	 File:         $Id: CereusReporting_support.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
	 Modified_On:  $Date: 2017/07/18 06:44:34 $
	 Modified_By:  $Author: thurban $ 
	 Language:     Perl
	 Encoding:     UTF-8
	 Status:       -
	 License:      Commercial
	 Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 
	*******************************************************************************/
	

$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
chdir ($mainDir);
include_once("./include/auth.php");
include_once("./lib/data_query.php");
chdir ($dir);
require_once('cereusReporting_constants.php');
require_once('functions.php');  // Support functions
require_once('modules/update/update.php');
include_once( './include/functions_compat.php' );
	cr_top_header();

/* Support/Patch/Ticket/Update Section */


/* Patch/Version List */

// Version Info
$currentVersion = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='CereusReporting'");
$latestVersion = CR_checkVersion();
$supportContractStatus[0] = '<font color=darkred><b>expired</b></font>';
$supportContractStatus[1] = '<font color=darkgreen><b>active</b></font>';

// get minor and major version number
preg_match("@(\d+)\.(\d+).(\d+)@", $currentVersion, $version_match );
$version_major = $version_match[1];
$version_minor = $version_match[2];
$version_build = $version_match[3];
echo "<h2>CereusReporting Support Page</h2><br>\n<hr>\n";
echo "<table>";
echo " <tr>";
echo "   <td colspan=2><b>Current Version:</b></td>";
echo "   <td>". $currentVersion ."</td>";
echo " </tr>";
echo " <tr>";
echo "   <td colspan=2><b>Latest available Version:</b></td>";
echo "   <td>". $latestVersion ."</td>";
echo " </tr>";
echo " <tr>";
echo "   <td colspan=2><b>Support Contract Status:</b></td>";
echo "   <td>". $supportContractStatus[ CR_isValidCustomer() ] ."</td>";
echo " </tr>";
echo "</table>";


// Patch Info
if (version_compare($currentVersion, $latestVersion, '<')) {
    echo "<table width=600>";
    echo "<tr><td colspan=3><hr></td></tr>\n";
    echo "<tr><td colspan=3><h3><i><b>Available Hotfixes/Patches:</b></i></h3></td></tr>\n";

    // Get list of patches available
    $patchList = CR_getPatchList();
    
    if ( is_array( $patchList ) ) {
        $patchFound = FALSE;
        foreach( $patchList as $version => $description ) {
            // get minor and major version number
            preg_match("@(\d+)\.(\d+).(\d+)@", $version, $lversion_match );
            $lversion_major = $lversion_match[1];
            $lversion_minor = $lversion_match[2];
            $lversion_build = $lversion_match[3];
            
            if ( $lversion_major == $version_major ) {
                if ( $lversion_minor == $version_minor ) {
                    if ( $lversion_build > $version_build ) {
                        $patchFound = TRUE;
                        echo " <tr>";
                        echo "   <td align=left width=20%><b>".$version."</b></td>";
                        echo "   <td align=left width=60%><font color=darkorange>". $description ."</font></td>";
                        echo "   <td align=right><a href=''>Download/Apply HotFix</a></td>";
                        echo " </tr>";
                    }
                }
            }
        }
        if ( $patchFound == FALSE ) {
            echo "<tr><td colspan=3><i>You already have the latest patch level for this version.</i></td></tr>\n";            
        }
        
        echo "<tr><td colspan=3><h3><i><b>Available Updates:</b></i></h3></td></tr>\n";
        $upgradeFound = FALSE;
        foreach( $patchList as $version => $description ) {
            // get minor and major version number
            preg_match("@(\d+)\.(\d+).(\d+)@", $version, $lversion_match );
            $lversion_major = $lversion_match[1];
            $lversion_minor = $lversion_match[2];
            $lversion_build = $lversion_match[3];
            
            if ( $lversion_major >= $version_major ) {
                if ( $lversion_minor > $version_minor ) {
                    $upgradeFound = TRUE;
                    echo " <tr>";
                    echo "   <td><b>".$version."</b></td>";
                    echo "   <td><font color=darkgreen><b>". $description ."</b></font></td>";
                    echo "   <td><a href=''>Upgrade to this version</a></td>";
                    echo " </tr>";
                }
            }
        }
        if ( $upgradeFound == FALSE ) {
            echo "<tr><td colspan=3><i>You already have the latest version.</i></td></tr>\n";            
        }

    } else {
        if ( $patchList == 'INVALIDCUST' ) {
            print "<tr><td><b><i>You do not have a valid support contract.</i></b></td></tr>";
        }
    }
    echo "</table>";
}


chdir ($mainDir);
	cr_bottom_footer();
chdir ($dir);


?>
