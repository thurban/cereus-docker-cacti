<?php
	/*******************************************************************************
	 *
	 * File:         $Id: CereusReporting_debug.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
	 * Modified_On:  $Date: 2018/11/11 17:22:55 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	* Encoding:     UTF-8
	* Status:       -
	* License:      Commercial
	* Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	include_once( './include/functions_compat.php' );
	$main_dir = preg_replace( "@plugins.CereusReporting@", "", __DIR__ );
	chdir('../../' );
	include_once( "./include/auth.php" );
	cr_top_header();
	include_once( "./lib/data_query.php" );
	require_once( __DIR__ . '/functions.php' ); // Support functions
    chdir($main_dir);
	// include_once( './modules/update/update.php' );
	//require_once('reportEngine.php');  // Report Engine
	
	$licenseExpiry = "never";

	$licensedServer = '<font color=green>' . 'TRUE' . '</font>';

	if ( isset ( $_REQUEST[ 'doMaintenance' ] ) ) {
		fix_availability_table_index();
	}

		if (isset($plugin_architecture) ) {
		$piaVersion = $plugin_architecture[ 'version' ];
	} else {
	    $piaVersion = 'integrated';
    }
	$pluginVersion = db_fetch_cell( "SELECT version FROM plugin_config WHERE directory='CereusReporting'" );
	$mainUrl = $_SERVER[ 'PHP_SELF' ];
	$mainUrl = preg_replace( "@plugins/CereusReporting/CereusReporting_debug.php@", "", $mainUrl );


	$d_tmpexists = "<font color=red>" . 'not existing' . "</font>";
	$d_tmpwriteable = "<font color=red>" . 'not writeable' . "</font>";
	$d_tmpmdok = "<font color=red>" . _( 'not able to create directory "test". (chmod 775) ?' ) . "</font>";
	if ( is_dir( sys_get_temp_dir() ) ) {
		$d_tmpexists = "<font color=darkgreen>" . 'yes' . "</font>";
		if ( is_writeable( sys_get_temp_dir() ) ) {
			$d_tmpwriteable = "<font color=darkgreen>" . 'yes' . "</font>";
			if ( mkdir( sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test' ) ) {
				$d_tmpmdok = "<font color=darkgreen>" . 'yes' . "</font>";
				rmdir( sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test' );
			}
		}
	}

	$cr_req_plugins = array();
	$cr_req_plugins[ 'settings' ] = "<font color=yellow>" . 'missing' . "</font>";
	if ( readPluginStatus( 'settings' ) ) {
		$cr_req_plugins[ 'settings' ] = "<font color=darkgreen>" . 'installed' . "</font>";
	}
	$cr_req_plugins[ 'storeLastPoll' ] = "<font color=yellow>" . 'missing' . "</font>";
	if ( readPluginStatus( 'storeLastPoll' ) ) {
		$cr_req_plugins[ 'storeLastPoll' ] = "<font color=darkgreen>" . 'installed' . "</font>";
	}
	$cr_req_plugins[ 'nmidskin' ] = "<font color=yellow>" . 'missing' . "</font>";
	if ( readPluginStatus( 'nmidskin' ) ) {
		$cr_req_plugins[ 'nmidskin' ] = "<font color=darkgreen>" . 'installed' . "</font>";
	}

	$cr_plugins = array();
	$cr_plugins[ 'AVAILABILITY' ] = "<font color=red>" . 'disabled' . "</font>";
    $cr_plugins[ 'AVAILABILITY' ] = "<font color=darkgreen>" . 'licensed' . "</font>";
    $cr_plugins[ 'SCHEDULER' ] = "<font color=darkgreen>" . 'licensed' . "</font>";
    $cr_plugins[ 'DSSTATS' ] = "<font color=darkgreen>" . 'licensed' . "</font>";
    $cr_plugins[ 'MULTIREPORTS' ] = "<font color=darkgreen>" . 'licensed' . "</font>";
    $cr_plugins[ 'ARCHIVING' ] = "<font color=darkgreen>" . 'licensed' . "</font>";
    $cr_plugins[ 'TEMPLATING' ] = "<font color=darkgreen>" . 'licensed' . "</font>";



	$m_mbstring = "<font color=red>" . 'disabled' . "</font>";
	if ( extension_loaded( 'mbstring' ) ) {
		$m_mbstring = "<font color=darkgreen>" . 'enabled' . "</font>";
	}

	$m_gettext = "<font color=red>" . 'disabled' . "</font>";
	if ( extension_loaded( 'gettext' ) ) {
		$m_gettext = "<font color=darkgreen>" . 'enabled' . "</font>";
	}


	$m_curl = "<font color=red>" . 'disabled' . "</font>";
	if ( extension_loaded( 'curl' ) ) {
		$m_curl = "<font color=darkgreen>" . 'enabled' . "</font>";
	}


	$m_gd = "<font color=red>" . 'disabled' . "</font>";
	if ( extension_loaded( 'gd' ) ) {
		$m_gd = "<font color=darkgreen>" . 'enabled' . "</font>";
	}

	$m_sg = "<font color=darkgreen>" . 'disabled' . "</font>";
	if ( extension_loaded( 'SourceGuardian' ) ) {
		$m_sg = "<font color=darkgreen>" . 'enabled' . "</font>";
	}

	$m_ionCube = "<font color=darkgreen>" . 'disabled' . "</font>";
	if ( extension_loaded( 'ionCube Loader' ) ) {
		$m_ionCube = "<font color=darkgreen>" . 'enabled' . "</font>";
	}

	$m_zip = "<font color=red>" . 'disabled' . "</font>";
	if ( extension_loaded( 'zip' ) ) {
		$m_zip = "<font color=darkgreen>" . 'enabled' . "</font>";
	}

	$m_soap = "<font color=red>" . 'disabled' . "</font>";
	if ( extension_loaded( 'soap' ) ) {
		$m_soap = "<font color=darkgreen>" . 'enabled' . "</font>";
	}

	$m_chartDir = "<font color=red>disabled</font>";
	if ( extension_loaded( 'ChartDirector PHP API' ) ) {
		$m_chartDir = "<font color=darkgreen>" . 'enabled' . "</font>";
	}

	$s_phpPath = readConfigOption( 'path_php_binary' );
	$s_pdfEngine = readConfigOption( 'nmid_pdf_type' );
	$a_pdfEngines[ 0 ] = 'NOT SET';
	$a_pdfEngines[ 1 ] = 'mPDF';
	$a_pdfEngines[ 2 ] = 'TCPDF';
	$s_phpVersion = `$s_phpPath -v`;

	$s_cr_Edition = '';

	$sql = 'SELECT count(id) as DSCount  FROM data_template_data WHERE data_source_path!="NULL" AND data_source_path!="" AND active="on";';
	$dsCount = getDBValue( "DSCount", $sql );

    $s_cr_Edition = 'CORPORATE Edition - Licensed</font> [' . $dsCount . ' Active Data Sources ]';

	echo "<h2>CereusReporting " . EDITION . "</h2><br>\n<hr>\n";
	echo "<table width=1024>\n";
	echo "<tr><td width=30%>" . 'Dir' . ":</td><td><b>" . __DIR__ . "</b></td></tr>\n";
	echo "<tr><td>" . 'Main Dir' . ": </td><td><b>" . $main_dir . "</b></td></tr>\n";
	echo "<tr><td>PHP_SELF: </td><td><b>" . $_SERVER[ 'PHP_SELF' ] . "</b></td></tr>\n";
	echo "<tr><td>Cacti URL: </td><td><b>" . $mainUrl . "</b></td></tr>\n";
	echo "<tr><td>PIA Version: </td><td><b>" . $piaVersion . "</b></td></tr>\n";
	echo "<tr><td>" . 'Edition' . ": </td><td><b>" . $s_cr_Edition . "</b></td></tr>\n";
	echo "<tr><td>" . 'License Expiry' . ": </td><td><b>" . $licenseExpiry . "</b></td></tr>\n";
	echo "<tr><td>" . 'Licensed Server' . ": </td><td><b>" . $licensedServer . "</b></td></tr>\n";
	echo "<tr><td>" . 'Operating System' . ": </td><td><b>" . php_uname() . "</b></td></tr>\n";
	$code_revision = '$Id: CereusReporting_debug.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $';
	echo "<tr><td>File Version</td><td><b>$code_revision</b></td><tr></tr>";

	echo "<tr><td colspan=2><hr></td></tr>\n";
	echo "<tr><td colspan=2><i>" . 'Plugin Settings' . ":</i></td></tr>\n";
	echo "<tr><td>PHP " . 'Path' . "</td><td>" . $s_phpPath . "</td></tr>\n";
	echo "<tr><td>PHP Version</td><td>" . $s_phpVersion . "</td></tr>\n";
	echo "<tr><td>PDF Engine</td><td>" . $a_pdfEngines[ $s_pdfEngine ] . "</td></tr>\n";
	echo "<tr><td>Plugin Version</td><td>" . $pluginVersion . "</td></tr>\n";

	echo "<tr><td colspan=2><hr></td></tr>\n";
	echo "<tr><td colspan=2><i>" . 'Licensed Modules' . ":</i></td></tr>\n";
	echo "<tr><td>Availability " . 'module' . "</td><td>" . $cr_plugins[ 'AVAILABILITY' ] . "</td></tr>\n";
	echo "<tr><td>Report Scheduling " . 'module' . "</td><td>" . $cr_plugins[ 'SCHEDULER' ] . "</td></tr>\n";
	echo "<tr><td>DSStats " . 'module' . "</td><td>" . $cr_plugins[ 'DSSTATS' ] . "</td></tr>\n";
	echo "<tr><td>MultiReports " . 'module' . "</td><td>" . $cr_plugins[ 'MULTIREPORTS' ] . "</td></tr>\n";
	echo "<tr><td>Archiving " . 'module' . "</td><td>" . $cr_plugins[ 'ARCHIVING' ] . "</td></tr>\n";
	echo "<tr><td>Templating" . 'module' . "</td><td>" . $cr_plugins[ 'TEMPLATING' ] . "</td></tr>\n";

	echo "<tr><td colspan=2><hr></td></tr>\n";
	echo "<tr><td colspan=2><i>" . _( 'File/Directory Settings' ) . ":</i></td></tr>\n";
	echo "<tr><td>" . 'tmp directory' . "</td><td>" . sys_get_temp_dir()  . "</td></tr>\n";
	echo "<tr><td>" . 'tmp directory exists' . "</td><td>" . $d_tmpexists . "</td></tr>\n";
	echo "<tr><td>" . 'tmp directory writeable' . "</td><td>" . $d_tmpwriteable . "</td></tr>\n";
	echo "<tr><td>" . _( 'tmp directory - can create dirs' ) . "</td><td>" . $d_tmpmdok . "</td></tr>\n";
	echo "<tr><td colspan=2><hr></td></tr>\n";
	echo "<tr><td colspan=2><i>" . 'Loaded PHP Modules' . ":</i></td></tr>\n";
	echo "<tr><td>mbstring</td><td><b>" . $m_mbstring . "</b></td></tr>\n";
	echo "<tr><td>gd</td><td><b>" . $m_gd . "</b></td></tr>\n";
	echo "<tr><td>SourceGuardian</td><td><b>" . $m_sg . "</b></td></tr>\n";
	echo "<tr><td>IonCube Loader</td><td><b>" . $m_ionCube . "</b></td></tr>\n";
	echo "<tr><td>zip</td><td><b>" . $m_zip . "</b></td></tr>\n";
	//echo "<tr><td>soap</td><td><b>" . $m_soap . "</b></td></tr>\n";
	//echo "<tr><td>gettext</td><td><b>" . $m_gettext . "</b></td></tr>\n";
	echo "<tr><td>curl</td><td><b>" . $m_curl . "</b></td></tr>\n";

	echo "<tr><td colspan=2><hr></td></tr>\n";
	echo "<tr><td colspan=2><i>" . 'Required Cacti Plugins' . ":</i></td></tr>\n";
	echo "<tr><td>settings</td><td><b>" . $cr_req_plugins[ 'settings' ] . "</b></td></tr>\n";

	echo "<tr><td colspan=2><hr></td></tr>\n";
	echo "<tr><td colspan=2><i>" . 'Optional PHP Modules' . ":</i></td></tr>\n";
	echo "<tr><td>ChartDirector PHP API</td><td><b>" . $m_chartDir . "</b></td></tr>\n";


	echo "</table>\n";
	?>
<hr>
<h2>Licenses</h2>

<h3>jQuery File Upload Plugin</h3>
<br/>
<code>
	The MIT License (MIT)<br/>
	<br/>
	Copyright (c) 2017 jQuery-File-Upload Authors<br/>
	<br/>
	Permission is hereby granted, free of charge, to any person obtaining a copy of<br/>
	this software and associated documentation files (the "Software"), to deal in<br/>
	the Software without restriction, including without limitation the rights to<br/>
	use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of<br/>
	the Software, and to permit persons to whom the Software is furnished to do so,<br/>
	subject to the following conditions:<br/>
	<br/>
	The above copyright notice and this permission notice shall be included in all<br/>
	copies or substantial portions of the Software.<br/>
	<br/>
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR<br/>
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS<br/>
	FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR<br/>
	COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER<br/>
	IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN<br/>
	CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.<br/>
</code>
<hr>

<?php
	cr_bottom_footer();