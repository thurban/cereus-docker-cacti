<?php
/*******************************************************************************

 File:         $Id: CereusReporting_ArchiveUserGroups_AddUser.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 Modified_On:  $Date: 2020/12/10 07:06:31 $
 Modified_By:  $Author: thurban $ 
 Language:     Perl
 Encoding:     UTF-8
 Status:       -
 License:      Commercial
 Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
 
*******************************************************************************/

	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
$dir = dirname(__FILE__);
$mainDir = preg_replace("@plugins.CereusReporting@","",$dir);
chdir($mainDir);
include_once("./include/auth.php");
include_once("./lib/data_query.php");
$_SESSION['custom']=false;

    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "alternate" ] = '';
    $colors[ "light" ] = '';
    $colors[ "header" ] = '';

/* set default action */
if (!isset($_REQUEST["GroupId"])) { $_REQUEST["GroupId"] = ""; }
if (!isset($_REQUEST["itemId"])) { $_REQUEST["itemId"] = ""; }
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }
if (!isset($_REQUEST["sort_column"])) { $_REQUEST["sort_column"] = ""; }
if (!isset($_REQUEST["sort_direction"])) { $_REQUEST["sort_direction"] = ""; }

	// Sanitize strings
	$_REQUEST[ "drp_action" ]     = filter_var( $_REQUEST[ "drp_action" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_column" ]    = filter_var( $_REQUEST[ "sort_column" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_direction" ] = filter_var( $_REQUEST[ "sort_direction" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "itemId" ]        = filter_var( $_REQUEST[ "itemId" ], FILTER_SANITIZE_NUMBER_INT );
	$_REQUEST[ "GroupId" ]        = filter_var( $_REQUEST[ "GroupId" ], FILTER_SANITIZE_NUMBER_INT );

input_validate_input_number( $_REQUEST["GroupId"] );
input_validate_input_number( $_REQUEST["itemId"] );


switch ($_REQUEST["action"]) {
    case 'save':
		form_save($_REQUEST["GroupId"] );
		break;
	default:
		cr_top_header();
		form_display( $_REQUEST["GroupId"] );
		cr_bottom_footer();
		break;
}

function form_save( $groupId ) {
    global $colors, $hash_type_names;
    $db = DBCxn::get();
    if ( isset ($_POST['save_component_import']) ) {
        if (isset ($_POST['userId'])) { $s_userId = $_POST['userId']; }
        $sql = "
			INSERT INTO `plugin_nmidCreatePDF_UserGroupList`
				( `UserId`, `UserGroupId` )
			VALUES
				( :s_userId, :groupId )
			";
        $stmt = $db->prepare( $sql);
        $stmt->bindValue( ':s_userId', $s_userId, PDO::PARAM_STR );
        $stmt->bindValue( ':groupId', $groupId, PDO::PARAM_STR );
        $stmt->execute();
    }

    header("Location: CereusReporting_ArchiveUserGroups_Add.php?action=update&GroupId=".$groupId);

}

function form_display( $groupId ) {
    global $colors, $hash_type_names, $config;
	if (!( ( EDITION == "CORPORATE" ) || ( isPluginLicensed( 'ARCHIVING' ) ) || ( isSMBServer() ) )) {
		// Multi Repots are only supported for PROFESSIONAL and CORPORATE editions
		print "<p>Archiving is not supported for this Edition. Your edition is :<b>". EDITION."</b><br>\n";
		return;
	}	
	$a_users = db_fetch_assoc("
		SELECT DISTINCT
		  `user_auth`.`id`,
		  `user_auth`.`username`,
		  `user_auth`.`full_name`
		FROM
		  `user_auth`
		WHERE
			`user_auth`.`id` NOT IN (SELECT `plugin_nmidCreatePDF_UserGroupList`.`UserId` FROM `plugin_nmidCreatePDF_UserGroupList` WHERE `plugin_nmidCreatePDF_UserGroupList`.`UserGroupId` = ".$groupId." )		  
		ORDER BY `username`;
	");

		//	`plugin_nmidCreatePDF_UserGroupList`.`UserGroupId` = ".$groupId."
		//AND `user_auth`.`id` NOT IN (SELECT `plugin_nmidCreatePDF_UserGroupList`.`UserId` FROM `plugin_nmidCreatePDF_UserGroupList`)
	$count = 0;
	foreach ($a_users as $s_user)
	{
		$a_userName[$count]['name'] = $s_user['username'] . '['.$s_user['full_name'].']';
		$a_userName[$count]['id'] = $s_user['id'];
		$count++;
	}
	
	print "<font size=+1>CereusReporting - Add User to Group</font><br>\n";
    print "<hr>\n";

	?>
	<form method="post" action="CereusReporting_ArchiveUserGroups_AddUser.php" enctype="multipart/form-data">
	<?php

	html_start_box("<strong>User</strong> [new]", "100%", $colors["header"], "3", "center", "");

	form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td valign=top width="30%">
			<font class="textEditTitle">User</font><br>
            The user to add to the group.<br>
		</td>
		<td>
			<?php
				form_dropdown("userId",$a_userName, "name", "id", '', "" ,'' ,"","");
			?>
		</td>
	</tr>

	<?php
	form_hidden_box("save_component_import","1","");
	form_hidden_box("GroupId",$groupId,"");
	html_end_box();
    form_save_button("CereusReporting_ArchiveUserGroups_Add.php?action=update&GroupId=".$groupId, "save");

}



include_once("./include/bottom_footer.php");

?>
