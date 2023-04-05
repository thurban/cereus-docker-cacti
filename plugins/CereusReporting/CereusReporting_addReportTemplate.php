<?php
	/*******************************************************************************
	 * Copyright (c) 2017. - All Rights Reserved
	 * Unauthorized copying of this file, via any medium is strictly prohibited
	 * Proprietary and confidential
	 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2017.
	 *
	 * File:         $Id: CereusReporting_addReportTemplate.php,v ea43511c66ce 2018/11/11 17:22:55 thurban $
	 * Filename:     CereusReporting_addReportTemplate.php
	 * LastModified: 24.03.17 07:25
	 * Modified_On:  $Date: 2018/11/11 17:22:55 $
	 * Modified_By:  $Author: thurban $
	 *
	 ******************************************************************************/

	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );
	$my_dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $my_dir );
	chdir( $mainDir );
	include_once( "./include/auth.php" );
	include_once( "./include/global.php" );
	include_once( "./lib/data_query.php" );
	$_SESSION[ 'custom' ] = FALSE;

    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "header" ] = '';

	/* set default action */
	if ( !isset( $_REQUEST[ "templateId" ] ) ) {
		$_REQUEST[ "templateId" ] = "";
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

	// Sanitize strings
	$_REQUEST[ "sort_column" ]    = filter_var( $_REQUEST[ "sort_column" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_direction" ] = filter_var( $_REQUEST[ "sort_direction" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "templateId" ]     = filter_var( $_REQUEST[ "templateId" ], FILTER_SANITIZE_NUMBER_INT );

	input_validate_input_number( $_REQUEST[ "templateId" ] );


	switch ( $_REQUEST[ "action" ] ) {
		case 'save':
			form_save( $_REQUEST[ "templateId" ] );
			break;
		default:
            cr_top_header();
            form_display( $_REQUEST[ "templateId" ] );
			cr_bottom_footer();
			break;
	}

	function form_save( $templateId )
	{
		if ( isset ( $_POST[ 'template_name' ] ) ) {
			$s_reportName = filter_input( INPUT_POST, 'template_name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'template_file' ] ) ) {
			$s_reportTemplateFile = filter_input( INPUT_POST, 'template_file', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'template_description' ] ) ) {
			$s_reportDescription = filter_input( INPUT_POST, 'template_description', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'page_size' ] ) ) {
			$s_reportPageSize = filter_input( INPUT_POST, 'page_size', FILTER_SANITIZE_STRING );
		}
		if ( isset ( $_POST[ 'page_orientation' ] ) ) {
			$s_reportPageOrientation = filter_input( INPUT_POST, 'page_orientation', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'custom_graph_width' ] ) ) {
			$s_defaultGraphWidth = filter_input( INPUT_POST, 'custom_graph_width', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'custom_graph_height' ] ) ) {
			$s_defaultGraphHeight = filter_input( INPUT_POST, 'custom_graph_height', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'page_margin_top' ] ) ) {
			$s_defaultPageMarginTop = filter_input( INPUT_POST, 'page_margin_top', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'page_margin_bottom' ] ) ) {
			$s_defaultPageMarginBottom = filter_input( INPUT_POST, 'page_margin_bottom', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'page_margin_left' ] ) ) {
			$s_defaultPageMarginLeft = filter_input( INPUT_POST, 'page_margin_left', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'page_margin_right' ] ) ) {
			$s_defaultPageMarginRight = filter_input( INPUT_POST, 'page_margin_right', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}
		if ( isset ( $_POST[ 'page_footer_margin_bottom' ] ) ) {
			$s_defaultPageFooterMarginBottom = filter_input( INPUT_POST, 'page_footer_margin_bottom', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
		}

		if ( isset ( $_POST[ 'header_template' ] ) ) {
			$s_header_template = filter_input( INPUT_POST, 'header_template', FILTER_UNSAFE_RAW );
		}
		if ( isset ( $_POST[ 'footer_template' ] ) ) {
			$s_footer_template = filter_input( INPUT_POST, 'footer_template', FILTER_UNSAFE_RAW );
		}
		if ( isset ( $_POST[ 'report_title' ] ) ) {
			$s_report_title = filter_input( INPUT_POST, 'report_title', FILTER_UNSAFE_RAW );
		}
		if ( isset ( $_POST[ 'report_subtitle' ] ) ) {
			$s_report_subtitle = filter_input( INPUT_POST, 'report_subtitle', FILTER_UNSAFE_RAW );
		}


		if ( ( isset ( $_POST[ 'template_name' ] ) ) && ( isset ( $_POST[ 'save_component_import' ] ) ) ) {
			db_execute( "
			INSERT INTO `plugin_CereusReporting_Reports_templates`
				(
				 `name`, `description`,`page_size`, `page_orientation`, `custom_graph_width`,
				 `custom_graph_height`,`template_file`,`page_margin_top`, `page_margin_bottom`, `page_margin_left`,
				 `page_margin_right`,`header_template`,`footer_template`,`report_title`,`report_subtitle`,`page_footer_margin_bottom`
				)
			VALUES
				(
				 '$s_reportName', '$s_reportDescription', '$s_reportPageSize', '$s_reportPageOrientation', 
				 '$s_defaultGraphWidth','$s_defaultGraphHeight', '$s_reportTemplateFile', $s_defaultPageMarginTop, $s_defaultPageMarginBottom,
				 $s_defaultPageMarginLeft, $s_defaultPageMarginRight,'$s_header_template','$s_footer_template','$s_report_title','$s_report_subtitle',
				 $s_defaultPageFooterMarginBottom
				)
			" );
			header( "Location: CereusReporting_ReportTemplates.php" );
		}
		if ( ( isset ( $_POST[ 'template_name' ] ) ) && ( isset ( $_POST[ 'update_component_import' ] ) ) ) {
			if ( isset ( $_POST[ 'Description' ] ) ) {
				$s_reportDescription = filter_input( INPUT_POST, 'Description', FILTER_UNSAFE_RAW );
			}

			db_execute( "
			UPDATE `plugin_CereusReporting_Reports_templates`
			Set
				name='$s_reportName',
				description='$s_reportDescription',
				page_size='$s_reportPageSize',
				template_file='$s_reportTemplateFile',
				page_orientation='$s_reportPageOrientation',
			    custom_graph_width='$s_defaultGraphWidth',
				custom_graph_height='$s_defaultGraphHeight',
				page_margin_top=$s_defaultPageMarginTop,
				page_margin_bottom=$s_defaultPageMarginBottom,
				page_margin_left=$s_defaultPageMarginLeft,
				page_margin_right=$s_defaultPageMarginRight,
				header_template='$s_header_template',
				footer_template='$s_footer_template',
				report_title='$s_report_title',
				report_subtitle='$s_report_subtitle',
				page_footer_margin_bottom=$s_defaultPageFooterMarginBottom
			WHERE
				templateId='$templateId'
			" );
			header( "Location: CereusReporting_addReportTemplate.php?templateId=" . $templateId );
		}

	}

	function form_display( $templateId )
	{
		global $colors, $config, $my_dir;


		if ( readConfigOption( "nmid_use_css" ) == "1" ) {
			if ( CereusReporting_isNewCactiUI() ) {
				// 0.8.8c and greater
			}
			else {
				echo '<link href="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/css/ui-lightness/jquery-ui-1.9.2.custom.min.css" type="text/css" rel="stylesheet">';
				echo '<script src="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/js/jquery-1.8.3.js"></script>';
				echo '<script src="' . $config[ 'url_path' ] . 'plugins/CereusReporting/libs/js/jquery-ui-1.9.2.custom.min.js"></script>';
			}
		}

		$s_defaultName             = '';
		$s_defaultDescription      = '';
		$i_defaultPageOrientation  = 'P';
		$i_defaultPageSize         = 'A4';
		$s_defaultGraphWidth       = '';
		$s_defaultGraphHeight      = '';
		$i_defaultPageMarginTop    = 0;
		$i_defaultPageMarginBottom = 0;
		$i_defaultPageMarginLeft   = 0;
		$i_defaultPageMarginRight  = 0;
		$s_defaultTemplateFile     = '';
		$s_default_header_template = '';
		$s_default_footer_template = '';
		$s_default_report_title    = '';
		$s_default_report_subtitle = '';
		$i_defaultPageFooterMarginBottom = 5;

		if ( ( $templateId > 0 ) || ( $templateId == -1 ) )  {
			$a_templates = db_fetch_assoc( "
			SELECT
			  *
		    FROM
			  `plugin_CereusReporting_Reports_templates` 		
			WHERE templateId='$templateId'
		" );
			foreach ( $a_templates as $s_templates ) {
				$s_defaultName             = $s_templates[ 'name' ];
				$s_defaultDescription      = $s_templates[ 'description' ];
				$i_defaultPageOrientation  = $s_templates[ 'page_orientation' ];
				$i_defaultPageSize         = $s_templates[ 'page_size' ];
				$s_defaultGraphWidth       = $s_templates[ 'custom_graph_width' ];
				$s_defaultGraphHeight      = $s_templates[ 'custom_graph_height' ];
				$i_defaultPageMarginTop    = $s_templates[ 'page_margin_top' ];
				$i_defaultPageMarginBottom = $s_templates[ 'page_margin_bottom' ];
				$i_defaultPageMarginLeft   = $s_templates[ 'page_margin_left' ];
				$i_defaultPageMarginRight  = $s_templates[ 'page_margin_right' ];
				$s_defaultTemplateFile     = $s_templates[ 'template_file' ];
				$s_default_header_template = $s_templates[ 'header_template' ];
				$s_default_footer_template = $s_templates[ 'footer_template' ];
				$s_default_report_title    = $s_templates[ 'report_title' ];
				$s_default_report_subtitle = $s_templates[ 'report_subtitle' ];
				$i_defaultPageFooterMarginBottom = $s_templates[ 'page_footer_margin_bottom' ];
			}
		}
		if ( ( $templateId > 0 ) || ( $templateId == -1 ) ) {
			print "<font size=+1>CereusReporting - Update Template</font><br>\n";
		}
		else {
			print "<font size=+1>CereusReporting - Add Template</font><br>\n";
		}

		print "<hr>\n";

		?>
        <form name="ReportData" method="post" action="CereusReporting_addReportTemplate.php"
              enctype="multipart/form-data">
        <?php if (function_exists('csrf_get_tokens' )) { ?>
            <input type=hidden id='__csrf_magic' name='__csrf_magic' value='<?php echo csrf_get_tokens(); ?>'>
        <?php } ?>

		<?php

			if ( ( $templateId > 0 ) || ( $templateId == -1 ) ) {
				html_start_box( "<strong>Report</strong> [update]", "100%", $colors[ "header" ], "3", "center", "" );
			}
			else {
				html_start_box( "<strong>Report</strong> [new]", "100%", $colors[ "header" ], "3", "center", "" );
			}

			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Template Name</font><br>
            The name of the report.
        </td>
        <td>
			<?php form_text_box( "template_name", "", $s_defaultName, 255 ); ?>
        </td>
        </tr>

		<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Template Description</font><br>
            The detailed describtion of this report. This will be also be displayed in the report.
        </td>
        <td>
			<?php form_text_area( "template_description", $s_defaultDescription, 5, 50, "" ); ?>
        </td>
        </tr>

		<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Report Template</font><br>
            The PDF Template to use for this report. Uses default if left empty.
        </td>
        <td>
			<?php
				$fileCount                      = 1;
				$a_PageCoverPage                = array();
				$a_PageCoverPage[ 0 ][ 'name' ] = 'None';
				$a_PageCoverPage[ 0 ][ 'id' ]   = '';
				$a_templates                    = array();
				$dirFiles                       = array();

				if ( is_dir( $my_dir . '/templates/' ) ) {
					if ( $dh = opendir( $my_dir . '/templates/' ) ) {
						while ( ( $file = readdir( $dh ) ) !== FALSE ) {
							if ( !( is_dir( $file ) ) ) {
								if ( file_exists( $my_dir . '/templates/' . $file ) ) {
									$dirFiles[] = $file;
								}
							}
						}
						closedir( $dh );
					}
				}
				sort( $dirFiles );
				foreach ( $dirFiles as $file ) {
					if ( preg_match( "/([^.]+).*\.pdf$/i", $file, $matchme ) ) {
						if ( in_array( $file, $a_templates ) == FALSE ) {
							$a_templates[ $file ]                    = $file;
							$a_PageCoverPage[ $fileCount ][ 'name' ] = $file;
							$a_PageCoverPage[ $fileCount ][ 'id' ]   = $file;
							$fileCount++;
						}
					}
				}
				form_dropdown( "template_file", $a_PageCoverPage, "name", "id", $s_defaultTemplateFile, "", $s_defaultTemplateFile, "", "" );
			?>
        </td>
        </tr>


		<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Report Page Size</font><br>
            The default report timespane of this report.
        </td>
        <td>
			<?php
				$a_PageSize                = array();
				$a_PageSize[ 0 ][ 'name' ] = 'A3';
				$a_PageSize[ 0 ][ 'id' ]   = 'A3';
				$a_PageSize[ 1 ][ 'name' ] = 'A4';
				$a_PageSize[ 1 ][ 'id' ]   = 'A4';
				$a_PageSize[ 2 ][ 'name' ] = 'A5';
				$a_PageSize[ 2 ][ 'id' ]   = 'A5';
				$a_PageSize[ 3 ][ 'name' ] = 'Letter';
				$a_PageSize[ 3 ][ 'id' ]   = 'Letter';
				$a_PageSize[ 4 ][ 'name' ] = 'Legal';
				$a_PageSize[ 4 ][ 'id' ]   = 'Legal';
				form_dropdown( "page_size", $a_PageSize, "name", "id", $i_defaultPageSize, "", $i_defaultPageSize, "", "" );
			?>
        </td>
        </tr>

		<?php form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Report Page Orientation</font><br>
            The default report timespane of this report.
        </td>
        <td>
			<?php
				$a_PageOrientation                = array();
				$a_PageOrientation[ 0 ][ 'name' ] = 'Portrait';
				$a_PageOrientation[ 0 ][ 'id' ]   = 'P';
				$a_PageOrientation[ 1 ][ 'name' ] = 'Landscape';
				$a_PageOrientation[ 1 ][ 'id' ]   = 'L';
				form_dropdown( "page_orientation", $a_PageOrientation, "name", "id", $i_defaultPageOrientation, "", $i_defaultPageOrientation, "", "" );
			?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Custom Graph Width</font><br>
            You can change the default graph width to this custom size, overwriting the graph size defined in the graph
            templates.
            Leave empty to use the size from the graph template.
        </td>
        <td>
			<?php form_text_box( "custom_graph_width", "", $s_defaultGraphWidth, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Custom Graph Height</font><br>
            You can change the default graph heigth to this custom size, overwriting the graph size defined in the graph
            templates.
            Leave empty to use the size from the graph template.
        </td>
        <td>
			<?php form_text_box( "custom_graph_height", "", $s_defaultGraphHeight, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Top Page Margin</font><br>
            The margin to the top of the page.
        </td>
        <td>
			<?php form_text_box( "page_margin_top", "", $i_defaultPageMarginTop, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Bottom Page AutoBreak Margin</font><br>
            The margin to the bottom of the page where a new page will automatically be added.
        </td>
        <td>
			<?php form_text_box( "page_margin_bottom", "", $i_defaultPageMarginBottom, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Left Page Margin</font><br>
            The margin to the left of the page.
        </td>
        <td>
			<?php form_text_box( "page_margin_left", "", $i_defaultPageMarginLeft, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Right Page Margin</font><br>
            The margin to the right of the page.
        </td>
        <td>
			<?php form_text_box( "page_margin_right", "", $i_defaultPageMarginRight, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Bottom Footer Page Margin</font><br>
            The margin to the bottom of the page for the footer.
        </td>
        <td>
			<?php form_text_box( "page_footer_margin_bottom", "", $i_defaultPageFooterMarginBottom, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Header Template</font><br>
            A custom header. If emtpy defaults to the global header.<br>
            Supported Tags: <code>[START][END][AUTHOR][REPORTTITLE][REPORTSUBTITLE][REPORTDATE]</code>
        </td>
        <td>
	        <?php form_text_area( "header_template", $s_default_header_template, 5, 50, "" ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Footer Template</font><br>
            A custom footer. If emtpy defaults to the global header.<br>
            Supported Tags: <code>[START][END][AUTHOR][REPORTTITLE][REPORTSUBTITLE][REPORTDATE]</code>
        </td>
        <td>
	        <?php form_text_area( "footer_template", $s_default_footer_template, 5, 50, "" ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 0 ); ?>
        <td width="50%">
            <font class="textEditTitle">Default Report Title</font><br>
            A default report title
        </td>
        <td>
			<?php form_text_box( "report_title", "", $s_default_report_title, 255 ); ?>
        </td>
        </tr>

		<?php
			form_alternate_row_color( $colors[ "form_alternate1" ], $colors[ "form_alternate2" ], 1 ); ?>
        <td width="50%">
            <font class="textEditTitle">Default Report SubTitle</font><br>
            A default report subtitle
        </td>
        <td>
			<?php form_text_box( "report_subtitle", "", $s_default_report_subtitle, 255 ); ?>
        </td>
        </tr>

		<?php

		if ( ( $templateId > 0 ) || ( $templateId == -1 ) ) {
			form_hidden_box( "update_component_import", "1", "" );
			form_hidden_box( "templateId", $templateId, "" );
		}
		else {
			form_hidden_box( "save_component_import", "1", "" );
		}
		html_end_box();
		form_save_button( "CereusReporting_ReportTemplates.php", "save" );

	}