<?php
	/*******************************************************************************

	 File:         $Id: cereusReporting_checkRequirements.php,v 732f49817120 2017/03/21 11:36:25 thurban $
	 Modified_On:  $Date: 2017/03/21 11:36:25 $
	 Modified_By:  $Author: thurban $ 
	 Language:     Perl
	 Encoding:     UTF-8
	 Status:       -
	 License:      Commercial
	 Copyright:    Copyright 2009-2012 by Urban-Software.de / Thomas Urban
	 
	*******************************************************************************/
	

    //
    // Check System Requirements
    //
    
    echo "Checking for PHP Extensions ...\n";
    if ( extension_loaded  ( 'mbstring' ) ) {
        echo "\tmbstring ok\n";
    }
    else {
        echo "\tmbstring missing\n";
    }
    
    if ( extension_loaded  ( 'curl' ) ) {
        echo "\tcurl ok\n";
    }
    else {
        echo "\tcurl missing\n";
    }

    if ( extension_loaded  ( 'gd' ) ) {
        echo "\tgd ok\n";
    }
    else {
        echo "\tgd missing\n";
    }

    if ( extension_loaded  ( 'ionCube loader' ) ) {
        echo "\tionCube loader ok\n";
    }
    else {
        echo "\tionCube loader missing\n";
    }

    if ( extension_loaded  ( 'zip' ) ) {
        echo "\tzip ok\n";
    }
    else {
        echo "\tzip missing\n";
    }
    
    if ( extension_loaded  ( 'soap' ) ) {
        echo "\tsoap ok\n";
    }
    else {
        echo "\tsoap missing\n";
    }
    
    if ( extension_loaded  ( 'ChartDirector PHP API' ) ) {
        echo "\tChartDirector PHP API ok\n";
    }
    else {
        echo "\tChartDirector PHP API missing\n";
    }
    
    echo "\n";
    
    $extension_dir =  ini_get("extension_dir");
    echo "Extension dir: $extension_dir\n";
    
    $system_version = php_uname("m");
    if ( $system_version == 'x86_64' ) {
        echo "You are running a 64bit system\n";
    }
    else {
        echo "You are running a 32bit syetm\n";
    }
    
    $php_version = phpversion();
    echo "Your are running PHP version $php_version\n";
    
    echo "\n\nChecking File/Directory Settings\n";
    echo "\tOwner/Group of this file: ";
    $a_owner = posix_getpwuid(fileowner('cereusReporting_checkRequirements.php'));
    $a_group = posix_getgrgid(filegroup('cereusReporting_checkRequirements.php'));
    $permissions = substr(sprintf('%o', fileperms('cereusReporting_checkRequirements.php')), -4);
    echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";

    echo "\tOwner/Group/Permissions of CereusReporting dir: ";
    $a_owner = posix_getpwuid(fileowner('.'));
    $a_group = posix_getgrgid(filegroup('.'));
    $permissions = substr(sprintf('%o', fileperms('.')), -4);
    echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";

    echo "\tOwner/Group/Permissions of temporary dir: ";
    //$a_owner = posix_getpwuid(fileowner('tmp'));
    //$a_group = posix_getgrgid(filegroup('tmp'));
    //$permissions = substr(sprintf('%o', fileperms('tmp')), -4);
    // echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";

    echo "\tOwner/Group/Permissions of ReportEngines/mpdf/tmp dir: ";
    $a_owner = posix_getpwuid(fileowner('ReportEngines/mpdf/tmp'));
    $a_group = posix_getgrgid(filegroup('ReportEngines/mpdf/tmp'));
    $permissions = substr(sprintf('%o', fileperms('ReportEngines/mpdf/tmp')), -4);
    echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";

    echo "\tOwner/Group/Permissions of archive dir: ";
    $a_owner = posix_getpwuid(fileowner('archive'));
    $a_group = posix_getgrgid(filegroup('archive'));
    $permissions = substr(sprintf('%o', fileperms('archive')), -4);
    echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";
    
    echo "\tOwner/Group/Permissions of images dir: ";
    $a_owner = posix_getpwuid(fileowner('images'));
    $a_group = posix_getgrgid(filegroup('images'));
    $permissions = substr(sprintf('%o', fileperms('images')), -4);
    echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";

    echo "\tOwner/Group/Permissions of backup dir: ";
    $a_owner = posix_getpwuid(fileowner('backup'));
    $a_group = posix_getgrgid(filegroup('backup'));
    $permissions = substr(sprintf('%o', fileperms('backup')), -4);
    echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";
    
    echo "\tOwner/Group/Permissions of rra dir: ";
    $a_owner = posix_getpwuid(fileowner('../../rra'));
    $a_group = posix_getgrgid(filegroup('../../rra'));
    $permissions = substr(sprintf('%o', fileperms('../../rra')), -4);
    echo "\t".$a_owner['name'].':'.$a_group['name'].' '.$permissions."\n";
    
    echo "\n\nChecking SELinux status\n";
    echo `sestatus`;
?>