<?php
/*******************************************************************************
 *
 * File:         $Id: CereusReporting_addMultiReport.php,v 6de4bc63a72b 2017/11/01 15:05:58 thurban $
 * Modified_On:  $Date: 2017/11/01 15:05:58 $
 * Modified_By:  $Author: thurban $
 * Language:     Perl
* Encoding:     UTF-8
* Status:       -
* License:      Commercial
* Copyright:    Copyright 2009-2017 by Urban-Software.de / Thomas Urban
 *******************************************************************************/

	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );

$dir = dirname( __FILE__ );
$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
chdir( $mainDir );
include_once( "./include/auth.php" );
$_SESSION[ 'custom' ] = FALSE;

/* set default action */
if ( !isset( $_REQUEST[ "ReportId" ] ) ) {
	$_REQUEST[ "ReportId" ] = "";
}
if ( !isset( $_REQUEST[ "itemId" ] ) ) {
	$_REQUEST[ "itemId" ] = "";
}
if ( !isset( $_REQUEST[ "action" ] ) ) {
	$_REQUEST[ "action" ] = "";
}
if ( !isset( $_REQUEST[ "sort_column" ] ) ) {
	$_REQUEST[ "sort_column" ] = "";
}
if ( !isset( $_REQUEST[ "sort_direction" ] ) ) {
	$_REQUEST[ "sort_direction" ] = "";
}
if ( !isset( $_REQUEST[ "drp_action" ] ) ) {
	$_REQUEST[ "drp_action" ] = "";
}
if ( !isset( $_REQUEST[ "itemType" ] ) ) {
	$_REQUEST[ "itemType" ] = "";
}

	// Sanitize strings
	$_REQUEST[ "drp_action" ]     = filter_var( $_REQUEST[ "drp_action" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_column" ]    = filter_var( $_REQUEST[ "sort_column" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_direction" ] = filter_var( $_REQUEST[ "sort_direction" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$reportId                     = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );
	$itemId                       = filter_var( $_REQUEST[ "itemId" ], FILTER_SANITIZE_NUMBER_INT );
	$typeId                       = filter_var( $_REQUEST[ "itemType" ], FILTER_SANITIZE_NUMBER_INT );

input_validate_input_number( $reportId );
input_validate_input_number( $itemId );
input_validate_input_number( $typeId );


switch ( $_REQUEST[ "action" ] ) {
	case 'save':
		form_save( $reportId, $itemId );
		break;
	case 'refresh':
		cr_top_header();
		form_display( $reportId, $itemId, $typeId, $_REQUEST[ "Data" ] );
		cr_bottom_footer();
		break;
	default:
		cr_top_header();
		form_display( $reportId, $itemId);
		cr_bottom_footer();
		break;
}

function form_save( $reportId, $itemId )
{
	global $colors, $hash_type_names;

	// Get DB Instance
	$db = DBCxn::get();

	$a_itemTypes      = array();
	$a_itemTypes[ 0 ] = 'graph';
	$a_itemTypes[ 3 ] = 'text';
	$a_itemTypes[ 4 ] = 'title';
	$a_itemTypes[ 5 ] = 'chapter';
	$a_itemTypes[ 15 ]        = 'pagebreak';
	$a_itemTypes[ 25 ]        = 'host';
	if ( ( EDITION == "CORPORATE" ) || ( isSMBServer() ) ) {
		$a_itemTypes[ 1 ] = 'dsstats';
		$a_itemTypes[ 2 ] = 'smokeping';
		$a_itemTypes[ 6 ]  = 'availability';
		$a_itemTypes[ 7 ] = 'sqlstatement';
		$a_itemTypes[ 9 ]  = 'availability_combined';
		$a_itemTypes[ 10 ] = 'weathermap';
		$a_itemTypes[ 11 ] = 'availability_winservice';
		$a_itemTypes[ 12 ] = 'availability_thold';
		$a_itemTypes[ 13 ] = 'availability_thold_tree_sum';
		$a_itemTypes[ 14 ] = 'availability_tree_sum';
		$a_itemTypes[ 16 ]        = 'enable_header';
		$a_itemTypes[ 17 ]        = 'enable_footer';
		$a_itemTypes[ 18 ]        = 'disable_header';
		$a_itemTypes[ 19 ]        = 'disable_footer';
		$a_itemTypes[ 20 ]        = 'reportit_report';
		$a_itemTypes[ 21 ]        = 'bookmark';
		$a_itemTypes[ 22 ]        = 'tree_item';
		$a_itemTypes[ 23 ]        = 'pdf_file';
		$a_itemTypes[ 24 ]        = 'regexp';
		$a_itemTypes[ 26 ]        = 'graphtemplate';
	}

	$s_itemData = '';
	if ( isset ( $_REQUEST[ 'itemType' ] ) ) {
		$s_itemType = filter_var( $_REQUEST[ "itemType" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	}
	if ( isset ( $_REQUEST[ 'Data' ] ) ) {
		// $s_itemData = filter_var( $_REQUEST[ "Data" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$s_itemData = $_REQUEST[ "Data" ];
	}
    elseif ( isset ( $_REQUEST[ 'GraphData' ] ) ) {
        $s_itemData = filter_var( $_REQUEST[ "GraphData" ], FILTER_SANITIZE_NUMBER_INT);
    }
    elseif ( isset ( $_REQUEST[ 'HostData' ] ) ) {
        $s_itemData = filter_var( $_REQUEST[ "HostData" ], FILTER_SANITIZE_NUMBER_INT);
    }
    elseif ( isset ( $_REQUEST[ 'TreeData' ] ) ) {
        $s_itemData = filter_var( $_REQUEST[ "TreeData" ], FILTER_SANITIZE_NUMBER_INT);
    }
	if ( isset ( $_REQUEST[ 'DataRegExp' ] ) ) {
		// $s_itemData = filter_var( $_REQUEST[ "Data" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		$s_itemData = json_encode(array('data' => $s_itemData, 'dataRegExp' => $_REQUEST[ 'DataRegExp' ], 'dataRegExpFilter' => $_REQUEST[ 'DataRegExpItem'] ));

	}
	$s_itemType = $a_itemTypes[ $s_itemType ];
	if ( isset ( $_REQUEST[ 'save_component_import' ] ) ) {
		$s_itemOrder = db_fetch_cell( 'select MAX(`order`) from plugin_nmidCreatePDF_MultiGraphReports where ReportId=' . $reportId ) + 1;

		$stmt = $db->prepare( '
			INSERT INTO `plugin_nmidCreatePDF_MultiGraphReports`
				(
				 `data`, `type`,`order`, `ReportId`
				)
			VALUES
				(
				 :data, :type,:order,:ReportId
				)
			' );
		$stmt->bindValue( ':data', $s_itemData );
		$stmt->bindValue( ':type', $s_itemType, PDO::PARAM_STR );
		$stmt->bindValue( ':order', $s_itemOrder, PDO::PARAM_INT );
		$stmt->bindValue( ':ReportId', $reportId, PDO::PARAM_INT );
		$stmt->execute();
		$stmt->closeCursor();
	}
	if ( isset ( $_REQUEST[ 'update_component_import' ] ) ) {
		$stmt = $db->prepare( '
			UPDATE `plugin_nmidCreatePDF_MultiGraphReports`
			Set
				data=:data,
				type=:type
			WHERE
				Id=:Id
			' );
		$stmt->bindValue( ':data', $s_itemData );
		$stmt->bindValue( ':type', $s_itemType, PDO::PARAM_STR );
		$stmt->bindValue( ':Id', $itemId, PDO::PARAM_INT );
		$stmt->execute();
		$stmt->closeCursor();
	}
	header( 'Location: CereusReporting_addReport.php?action=update&ReportType=3&ReportId=' . $reportId );

}

function form_display( $reportId, $itemId, $itemType = -1, $itemData = '' )
{
    global $colors, $hash_type_names, $config;

    $a_defaultType[ 'graph' ] = 0;
    $a_defaultType[ 'dsstats' ] = 1;
    $a_defaultType[ 'smokeping' ] = 2;
    $a_defaultType[ 'text' ] = 3;
    $a_defaultType[ 'title' ] = 4;
    $a_defaultType[ 'chapter' ] = 5;
    $a_defaultType[ 'availability' ] = 6;
    $a_defaultType[ 'sqlstatement' ] = 7;
    $a_defaultType[ 'treeleaf' ] = 8;
    $a_defaultType[ 'availability_combined' ] = 9;
    $a_defaultType[ 'weathermap' ] = 10;
    $a_defaultType[ 'availability_winservice' ] = 11;
    $a_defaultType[ 'availability_thold' ] = 12;
    $a_defaultType[ 'availability_thold_tree_sum' ] = 13;
    $a_defaultType[ 'availability_tree_sum' ] = 14;
	$a_defaultType[ 'pagebreak' ] = 15;
	$a_defaultType[ 'enable_header' ] = 16;
	$a_defaultType[ 'enable_footer' ] = 17;
	$a_defaultType[ 'disable_header' ] = 18;
	$a_defaultType[ 'disable_footer' ] = 19;
	$a_defaultType[ 'reportit_report' ] = 20;
	$a_defaultType[ 'bookmark' ] = 21;
	$a_defaultType[ 'tree_item' ] = 22;
	$a_defaultType[ 'pdf_file' ] = 23;
	$a_defaultType[ 'regexp' ] = 24;
	$a_defaultType[ 'host' ] = 25;
	$a_defaultType[ 'graphtemplate' ] = 26;

	if ( function_exists('top_header')) {
	    // New Cacti 1.x
	} else {
        if ( readConfigOption( 'nmid_use_css' ) == "1" ) {
            echo '<link href="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/css/ui-lightness/jquery-ui-1.9.2.custom.min.css" type="text/css" rel="stylesheet">';
        }
        echo '
                <script src="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/js/jquery-1.8.3.js"></script>
                <script src="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/js/jquery-ui-1.9.2.custom.min.js"></script>
                ';
	}
    echo '
            <script>
    
                $(function() {
                    $( "#nmidTabs" ).tabs();
    
                    $( "button" )
                        .button()
                        .click(function( event ) {
                            event.preventDefault();
                            }';
    if ( $itemId > 0 ) {
        echo 'document.CereusReporting_itemtype_form.update_component_import.value = "0";';
    }
    else {
        echo 'document.CereusReporting_itemtype_form.save_component_import.value = "0";';
    }
    echo '          document.CereusReporting_itemtype_form.action.value = "refresh";
                            document.CereusReporting_itemtype_form.action = "' . $config[ 'url_path' ] . 'plugins/CereusReporting/CereusReporting_addMultiReport.php?action=refresh";
                            document.CereusReporting_itemtype_form.submit()
                     });
                });
            </script>
            ';
    $a_itemTypes = array();
    $a_itemTypes[ 0 ][ 'name' ] = 'graph';
    $a_itemTypes[ 0 ][ 'id' ] = '0';
    $a_itemTypes[ 3 ][ 'name' ] = 'text';
    $a_itemTypes[ 3 ][ 'id' ] = '3';
    $a_itemTypes[ 4 ][ 'name' ] = 'title';
    $a_itemTypes[ 4 ][ 'id' ] = '4';
    $a_itemTypes[ 5 ][ 'name' ] = 'chapter';
    $a_itemTypes[ 5 ][ 'id' ] = '5';
	$a_itemTypes[ 15 ][ 'name' ] = 'pagebreak';
	$a_itemTypes[ 15 ][ 'id' ]   = '15';
	$a_itemTypes[ 25 ][ 'name' ] = 'host';
	$a_itemTypes[ 25 ][ 'id' ]   = '25';

    if ( ( EDITION == "CORPORATE" ) || ( isSMBServer() ) ) {
	    $a_itemTypes[ 1 ][ 'name' ] = 'dsstats';
	    $a_itemTypes[ 1 ][ 'id' ]   = '1';
	    $a_itemTypes[ 2 ][ 'name' ] = 'smokeping';
	    $a_itemTypes[ 2 ][ 'id' ] = '2';
        $a_itemTypes[ 6 ][ 'name' ]  = 'availability';
        $a_itemTypes[ 6 ][ 'id' ]    = '6';
        $a_itemTypes[ 9 ][ 'name' ]  = 'availability_combined';
        $a_itemTypes[ 9 ][ 'id' ]    = '9';
        $a_itemTypes[ 11 ][ 'name' ] = 'availability_winservice';
        $a_itemTypes[ 11 ][ 'id' ]   = '11';
        $a_itemTypes[ 12 ][ 'name' ] = 'availability_thold';
        $a_itemTypes[ 12 ][ 'id' ]   = '12';
        $a_itemTypes[ 13 ][ 'name' ] = 'availability_thold_tree_sum';
        $a_itemTypes[ 13 ][ 'id' ]   = '13';
        $a_itemTypes[ 14 ][ 'name' ] = 'availability_tree_sum';
        $a_itemTypes[ 14 ][ 'id' ]   = '14';

        $a_itemTypes[ 16 ][ 'name' ] = 'enable_header';
        $a_itemTypes[ 16 ][ 'id' ]   = '16';
        $a_itemTypes[ 17 ][ 'name' ] = 'enable_footer';
        $a_itemTypes[ 17 ][ 'id' ]   = '17';
        $a_itemTypes[ 18 ][ 'name' ] = 'disable_header';
        $a_itemTypes[ 18 ][ 'id' ]   = '18';
        $a_itemTypes[ 19 ][ 'name' ] = 'disable_footer';
        $a_itemTypes[ 19 ][ 'id' ] = '19';
        $a_itemTypes[ 20 ][ 'name' ] = 'reportit_report';
        $a_itemTypes[ 20 ][ 'id' ] = '20';
        $a_itemTypes[ 21 ][ 'name' ] = 'bookmark';
        $a_itemTypes[ 21 ][ 'id' ]   = '21';
        $a_itemTypes[ 22 ][ 'name' ] = 'tree_item';
        $a_itemTypes[ 22 ][ 'id' ]   = '22';
	    $a_itemTypes[ 23 ][ 'name' ] = 'pdf_file';
	    $a_itemTypes[ 23 ][ 'id' ]   = '23';
	    $a_itemTypes[ 24 ][ 'name' ] = 'regexp';
	    $a_itemTypes[ 24 ][ 'id' ]   = '24';
        $a_itemTypes[ 10 ][ 'name' ] = 'weathermap';
        $a_itemTypes[ 10 ][ 'id' ] = '10';
	    //$a_itemTypes[ 26 ][ 'name' ] = 'graphtemplate';
	    //$a_itemTypes[ 26 ][ 'id' ]   = '26';
	    $a_itemTypes[ 7 ][ 'name' ] = 'sqlstatement';
	    $a_itemTypes[ 7 ][ 'id' ] = '7';
	    //$a_itemTypes[ 8 ][ 'name' ] = 'treeleaf';
	    //$a_itemTypes[ 8 ][ 'id' ] = '8';
    }

    $i_defaultType = 0;
    $s_defaultData = "";

    if ( $itemId > 0 ) {
        $a_reports = db_fetch_assoc( "
                SELECT
                  `plugin_nmidCreatePDF_MultiGraphReports`.`Id`,
                  `plugin_nmidCreatePDF_MultiGraphReports`.`type`,
                  `plugin_nmidCreatePDF_MultiGraphReports`.`data`
                FROM
                  `plugin_nmidCreatePDF_MultiGraphReports`
                WHERE
                  `plugin_nmidCreatePDF_MultiGraphReports`.`Id` = $itemId 
                ORDER BY `order`;
            " );
        foreach ( $a_reports as $s_report ) {
            $s_defaultData = $s_report[ 'data' ];
            $s_defaultType            = $s_report[ 'type' ];
            $i_defaultType            = 0;
            $i_defaultType            = $a_defaultType[ $s_defaultType ];
        }
    }
    if ( $reportId > 0 ) {
        print "<font size=+1>CereusReporting - Update Multi Report Item</font><br>\n";
    }
    else {
        print "<font size=+1>CereusReporting - Add Multi Report Item</font><br>\n";
    }
    print "<hr>\n";

    if ( $itemType > -1 ) {
        $i_defaultType = $itemType;
        $s_defaultData = $itemData;
    }

?>
<form name=CereusReporting_itemtype_form method="post" action="CereusReporting_addMultiReport.php"
      enctype="multipart/form-data">
	<?php

	if ( $reportId > 0 ) {
		html_start_box( "<strong>Report</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
	}
	else {
		html_start_box( "<strong>Report</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
	}

	form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
	<td>
		<div id="nmidTabs">
			<ul>
				<li><a href="#nmidTabs-3">Preview</a></li>
				<li><a href="#nmidTabs-2">Data</a></li>
				<li><a href="#nmidTabs-1">Help</a></li>
			</ul>
			<div id="nmidTabs-1">
				<font class="textEditTitle">Item Data</font><br>
				The data of the item.<br>
				<br>
				<b>graph</b> : "local graph id"<br>
				<b>text</b>: "some string"<br>
				<b>title</b>: "some string"<br>
				<b>Chapter</b>: "some string"<br>
                <b>pagebreak</b><br>
                <b>host</b>: "host id"<br>
                <?php 	if ( ( EDITION == "CORPORATE" ) || ( isSMBServer() ) ) { ?>
                    <b>dsstats</b>: "dsstats php file:title"<br>
                    <b>smokeping</b>: "host id"<br>
	                <b>availability</b>: "host id"<br>
                    <b>availability_combined</b>: "tree id;leaf id:sla timeframe"<br>
                    <b>availability_winservice</b>: "tree id;leaf id:sla timeframe"<br>
                    <b>sqlstatement</b>: "select hostname as Hostname, id as HostId from host:title"<br>
                    <!--<b>treeleaf</b>: "tree/leaf id" --->
                    <b>weathermap</b>: "weathermap id"<br>
                    <b>tholddata</b>: "thold id"<br>
                    <b>availability_thold_tree_sum</b>: "tree id"<br>
                    <b>availability_tree_sum</b>: "tree id"<br>
                    <b>enable_header</b><br>
                    <b>enable_footer</b><br>
                    <b>disable_header</b><br>
                    <b>disable_footer</b><br>
                    <b>reportit_report</b><br>
                    <b>bookmark</b><br>
                    <b>tree_item</b>: "tree id;leaf id"<br><br>
                    <b>pdf_file</b>: "/path/to/pdf/file.pdf"<br><br>
                <?php } ?>
			</div>
			<div id="nmidTabs-2">
				<font class="textEditTitle">Item Type:</font><br/>
				The item type.<br/>
				<?php
				form_dropdown( "itemType", $a_itemTypes, "name", "id", $i_defaultType, "", $i_defaultType, "", "" );
				?><br/>
				<?php
				form_text_area( "Data", $s_defaultData, 10, 80, "" );
				?>
				<button id="nmidRefresh">Preview</button>
			</div>
			<div id="nmidTabs-3">
				<button id="nmidRefresh">Refresh Data/Graph</button>
				<br/>
				<hr/>
				<?php
				if ( $i_defaultType == $a_defaultType[ 'graph' ] ) {
					echo "<img src='" . $config[ 'url_path' ] .
						'graph_image.php?local_graph_id=' . $s_defaultData .
						'&rra_id=0&view_type=tree&graph_start=' . ( time() - 3600 ) .
						'&graph_end=' . time() . "'/>";
				}
				elseif ( $i_defaultType == $a_defaultType[ 'text' ] ) {
					echo htmlspecialchars($s_defaultData);
				}
				elseif ( $i_defaultType == $a_defaultType[ 'chapter' ] ) {
					echo htmlspecialchars($s_defaultData);
				}
				elseif ( $i_defaultType == $a_defaultType[ 'title' ] ) {
					echo htmlspecialchars($s_defaultData);
				}
				else {
					echo "No Preview available yet.";
				}
				?>
			</div>
		</div>
	</td>

	</tr>

	<?php
	if ( $itemId > 0 ) {
		form_hidden_box( "update_component_import", "1", "" );
		form_hidden_box( "ReportId", $reportId, "" );
		form_hidden_box( "itemId", $itemId, "" );
	}
	else {
		form_hidden_box( "save_component_import", "1", "" );
		form_hidden_box( "ReportId", $reportId, "" );
	}
	html_end_box();
	form_save_button( "CereusReporting_addReport.php?action=update&ReportType=3&ReportId=" . $reportId, "save" );

	}


	cr_bottom_footer();

	?>
