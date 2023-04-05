<?php
	/*******************************************************************************
	 * Copyright (c) 2016. - All Rights Reserved
	 * Unauthorized copying of this file, via any medium is strictly prohibited
	 * Proprietary and confidential
	 * Written by Thomas Urban <ThomasUrban@urban-software.de>, September 1943
	 *
	 * File:         $Id: CereusReporting_ReportTemplates.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 ******************************************************************************/

	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );

	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );
	chdir( $mainDir );
	include_once( "./include/auth.php" );
	include_once( "./lib/data_query.php" );
	$_SESSION[ 'custom' ] = FALSE;


    $colors = array();
    $colors[ "form_alternate1" ] = '';
    $colors[ "form_alternate2" ] = '';
    $colors[ "alternate" ] = '';
    $colors[ "light" ] = '';
    $colors[ "header" ] = '';

	/* set default action */
	if ( !isset( $_REQUEST[ "drp_action" ] ) ) {
		$_REQUEST[ "drp_action" ] = "";
	}
	if ( !isset( $_REQUEST[ "sort_column" ] ) ) {
		$_REQUEST[ "sort_column" ] = "";
	}
	if ( !isset( $_REQUEST[ "sort_direction" ] ) ) {
		$_REQUEST[ "sort_direction" ] = "";
	}
	if ( !isset( $_REQUEST[ "Duplicate" ] ) ) {
		$_REQUEST[ "Duplicate" ] = "";
	}

	// CRC-7 - No Copy function at report page nor report schedule
	if ( $_REQUEST[ "Duplicate" ] == "Duplicate" ) {
		form_duplicate_execute();
		exit;
	}

	switch ( $_REQUEST[ "drp_action" ] ) {
		case '2':
			form_delete();
			break;
		case '6':
			form_duplicate();
			break;
		default:
			cr_top_header();
			form_display();
			cr_bottom_footer();
			break;
	}

	// CRC-7 - No Copy function at report page nor report schedule
	function form_duplicate_execute()
	{
		// Get DB Instance
		$db = DBCxn::get();

		$template_id = filter_var( $_REQUEST[ "templateId" ], FILTER_SANITIZE_NUMBER_INT );
		$template_name = filter_var( $_REQUEST[ "templateName" ], FILTER_SANITIZE_STRING );

		$sql = "Insert Into
		  plugin_CereusReporting_Reports_templates 
	      (name,description,template_file,page_size,page_orientation,custom_graph_width,custom_graph_height,
		  page_margin_top,page_margin_bottom,page_margin_left,page_margin_right,header_template,footer_template,
      	  report_title,report_subtitle)
      	SELECT
      	  :templateName,description,template_file,page_size,page_orientation,custom_graph_width,custom_graph_height,
		  page_margin_top,page_margin_bottom,page_margin_left,page_margin_right,header_template,footer_template,
      	  report_title,report_subtitle
	    From
		  plugin_CereusReporting_Reports_templates
	    Where
		  templateid=:templateid;
	    ";
		$stmt = $db->prepare( $sql );
		$stmt->bindValue( ':templateName', $template_name );
		$stmt->bindValue( ':templateid', $template_id );
		$stmt->execute();
		$stmt->closeCursor();

		header( "Location: CereusReporting_ReportTemplates.php" );
	}

	function form_duplicate()
	{
		global $config, $colors;

		include( $config[ "include_path" ] . "/top_header.php" );

		$template_id = '';
		/* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				$template_id = $matches[ 1 ];
			}
		}

		$old_template_name = getPreparedDBValue( "select name from plugin_CereusReporting_Reports_templates where templateId=?",array($template_id) );

		$fields_cereusreporting_report_template_duplicate = array(
			"templateName" => array(
				"method"        => "textbox",
				"friendly_name" => "New Report Template Name",
				"description"   => "A useful name for this Report Template.",
				"value"         => $old_template_name . '_new',
				"max_length"    => "255",
				"size"          => "60"
			),
			"mode"         => array(
				"method" => "hidden",
				"value"  => "duplicate"
			),
			"templateId"   => array(
				"method" => "hidden",
				"value"  => $template_id
			)
		);

		$type = "Duplicate";

		print "<table align='center' width='80%'><tr><td>\n";
		html_start_box( "<strong>CereusReporting - " . $type . " Report Templates</strong>", "100%", $colors[ "header" ], "3", "center", "" );
		print "<tr><td bgcolor='#FFFFFF'>\n";

		print "<p>When you click 'Continue', the following Report Template will be duplicated. You can optionally change the title format for the new Report Template.</p>
		   <p>Press <b>'Duplicate'</b> to proceed with the duplication, or <b>'Cancel'</b> to return to the Report Template menu.</p>
			</td></tr>";

		html_end_box();
		print "<form action='CereusReporting_ReportTemplates.php' method='post'>\n";
		html_start_box( "<strong>Schedule " . $type . " Settings</strong>", "100%", $colors[ "header" ], "3", "center", "" );
		draw_edit_form( array(
			                "config" => array(),
			                "fields" => inject_form_variables( $fields_cereusreporting_report_template_duplicate, array() ) )
		);
		html_end_box();
		cereusReporting_confirm_button( "Duplicate", "CereusReporting_ReportTemplates.php" );
		print "</td></tr></table>\n";
		exit;
	}

	function cereusReporting_confirm_button( $action, $cancel_url )
	{
		?>
        <table align='center' width='100%' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>
            <tr>
                <td bgcolor="#f5f5f5" align="right">
                    <input name='<?php print 'return' ?>' type='submit' value='Cancel'>
                    <input name='Duplicate' type='submit' value='Duplicate'>
                </td>
            </tr>
        </table>
        </form>
		<?php
	}

	function form_delete()
	{
		global $colors, $hash_type_names;

		/* loop through each of the selected tasks and delete them*/
        foreach ( $_POST as $var => $val) {
			if ( preg_match( "/^chk_([0-9]+)$/", $var, $matches ) ) {
				/* ================= input validation ================= */
				input_validate_input_number( $matches[ 1 ] );
				/* ==================================================== */
				// We do not allow the default template (-1) to be deleted
				if ( $matches[ 1 ] > 0 ) {
					db_execute( "DELETE FROM `plugin_CereusReporting_Reports_templates` WHERE `templateId`='" . $matches[ 1 ] . "'" );
				}
			}
		}
		header( "Location: CereusReporting_ReportTemplates.php" );
	}

	function form_edit()
	{

	}

	function form_display()
	{
		global $colors, $hash_type_names;
		print "<font size=+1>CereusReporting - Report Templates</font><br>\n";
		print "<hr>\n";

		$username = db_fetch_cell( "SELECT username FROM user_auth WHERE id=" . $_SESSION[ "sess_user_id" ] );

		$where_clause = '';
		if ( isset( $_REQUEST[ "sort_column" ] ) ) {
			if (
				( $_REQUEST[ "sort_column" ] == 'name' )
				|| ( $_REQUEST[ "sort_column" ] == 'templateId' )
				|| ( $_REQUEST[ "sort_column" ] == 'description' )
				|| ( $_REQUEST[ "sort_column" ] == 'template_file' )
			) {
				if (
					( $_REQUEST[ "sort_direction" ] == 'ASC' )
					|| ( $_REQUEST[ "sort_direction" ] == 'DESC' )
				) {
					$where_clause .= ' ORDER BY ' .
						$_REQUEST[ "sort_column" ] .
						' ' . $_REQUEST[ "sort_direction" ];
				}
			}
		}
		$report_templates_array = db_fetch_assoc( "
    SELECT
      *
    FROM
      `plugin_CereusReporting_Reports_templates`
      $where_clause;
    " );

		print "<form name=chk method=POST action=CereusReporting_ReportTemplates.php>\n";

		html_start_box( "<strong>Report Templates</strong>", "100%", $colors[ "header" ], "3", "center", "CereusReporting_addReportTemplate.php?action=add" );

		form_hidden_box( "save_component_import", "1", "" );

		if ( sizeof( $report_templates_array ) > 0 ) {
			$menu_text = array(
				"templateId"       => array( "Id", "ASC" ),
				"name"             => array( "Name", "ASC" ),
				"template_file"    => array( "PDF File", "ASC" ),
				"page_size"        => array( "Page Size", "ASC" ),
				"page_orientation" => array( "Page Orientation", "ASC" ),
				"description"      => array( "description", "ASC" )
			);

			html_header_sort_checkbox( $menu_text, $_REQUEST[ "sort_column" ], $_REQUEST[ "sort_direction" ] );

			$i = 0;

			foreach ( $report_templates_array as $report_template ) {
				form_alternate_row_color( $colors[ "alternate" ], $colors[ "light" ], $i, 'line' . $report_template[ 'templateId' ] );
				$i++;
				form_selectable_cell( $report_template[ 'templateId' ], $report_template[ "templateId" ] );
				form_selectable_cell( "<a href='CereusReporting_addReportTemplate.php?action=update&templateId=" . $report_template[ "templateId" ] . "'><b>" . $report_template[ 'name' ] . "</b></a>", $report_template[ 'templateId' ], 250 );
				form_selectable_cell( $report_template[ 'template_file' ], $report_template[ "templateId" ] );
				form_selectable_cell( $report_template[ 'page_size' ], $report_template[ "templateId" ] );
				form_selectable_cell( $report_template[ 'page_orientation' ], $report_template[ "templateId" ] );
				form_selectable_cell( $report_template[ 'description' ], $report_template[ "templateId" ] );
				form_checkbox_cell( 'selected_items', $report_template[ "templateId" ] );
				form_end_row();
			}
			html_end_box( FALSE );

			$task_actions = array(
				1 => "Please select an action",
				2 => "Delete",
				6 => "Duplicate"
			);
			draw_actions_dropdown( $task_actions );
		}
		else {
			print "<tr><td><em>No Report Templates exist</em></td></tr>";
			html_end_box( FALSE );
		}
		print "</form>";
	}


?>
