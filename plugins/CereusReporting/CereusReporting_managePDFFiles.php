<?php
	/*******************************************************************************
 * Copyright (c) 2017. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Urban <ThomasUrban@urban-software.de>, September 1943
 *
 * File:         $Id: CereusReporting_managePDFFiles.php,v 40a17197e8c9 2017/07/18 06:44:34 thurban $
 * Modified_On:  $Date: 2017/07/18 06:44:34 $
 * Modified_By:  $Author: thurban $
 ******************************************************************************/
	include_once( 'functions.php' );
	include_once( './include/functions_compat.php' );

	$dir     = dirname( __FILE__ );
	$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

	chdir( $mainDir );
	include_once( "./include/auth.php" );
	// include_once( "./lib/tree.php" );
	include_once( "./lib/data_query.php" );
	$_SESSION[ 'custom' ] = FALSE;


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
	if ( !isset( $_REQUEST[ "ReportId" ] ) ) {
		$_REQUEST[ "ReportId" ] = "";
	}

	// Sanitize strings
	$_REQUEST[ "drp_action" ]     = filter_var( $_REQUEST[ "drp_action" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_column" ]    = filter_var( $_REQUEST[ "sort_column" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$_REQUEST[ "sort_direction" ] = filter_var( $_REQUEST[ "sort_direction" ], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH );
	$reportId                     = filter_var( $_REQUEST[ "ReportId" ], FILTER_SANITIZE_NUMBER_INT );

	input_validate_input_number( $reportId );

	cr_top_header();
    form_display( $reportId );
	form_jscript_footer();
	cr_bottom_footer();

	function form_display( $reportId )
	{
		global $colors, $hash_type_names, $dir, $config;
		print "<font size=+1>CereusReporting - PDF File Management</font><br>\n";
		print "<hr>\n";
        ?>
        <p>Here you can upload external PDF files to be included in your reports.</p>
        <?php

		// html_start_box( "<strong>PDF File Management Selection</strong>", "100%", $colors[ "header" ], "3", "center", "" );

		?>
        <style>
            /* Adjust the jQuery UI widget font-size: */
            .ui-widget {
                font-size: 0.95em;
            }
        </style>
        <!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
        <link rel="stylesheet" href="libs/css/jquery.fileupload.css">
        <link rel="stylesheet" href="libs/css/jquery.fileupload-ui.css">
        <!-- CSS adjustments for browsers with JavaScript disabled -->
        <noscript><link rel="stylesheet" href="libs/css/jquery.fileupload-noscript.css"></noscript>
        <noscript><link rel="stylesheet" href="libs/css/jquery.fileupload-ui-noscript.css"></noscript>

        <!-- The file upload form used as target for the file upload widget -->
        <form id="fileupload" action="server/php/index.php" method="POST" enctype="multipart/form-data">
            <!-- Redirect browsers with JavaScript disabled to the origin page -->
            <noscript><input type="hidden" name="redirect" value="cacti_url"></noscript>
            <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
            <div class="fileupload-buttonbar">
                <div class="fileupload-buttons">
                    <!-- The fileinput-button span is used to style the file input field as button -->
                    <span class="fileinput-button">
                <span>Add files...</span>
                <input type="file" name="files[]" multiple>
            </span>
                    <button type="submit" class="start">Start upload</button>
                    <button type="reset" class="cancel">Cancel upload</button>
                    <button type="button" class="delete">Delete</button>
                    <input type="checkbox" class="toggle">
                    <!-- The global file processing state -->
                    <span class="fileupload-process"></span>
                </div>
                <!-- The global progress state -->
                <div class="fileupload-progress fade" style="display:none">
                    <!-- The global progress bar -->
                    <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
                    <!-- The extended global progress state -->
                    <div class="progress-extended">&nbsp;</div>
                </div>
            </div>
            <!-- The table listing the files available for upload/download -->
            <table role="presentation"><tbody class="files"></tbody></table>
        </form>

        <!-- The template to display files available for upload -->
        <script id="template-upload" type="text/x-tmpl">
        {% for (var i=0, file; file=o.files[i]; i++) { %}
            <tr class="template-upload fade">
                <td>
                    <span class="preview"></span>
                </td>
                <td>
                    <p class="name">{%=file.name%}</p>
                    <strong class="error"></strong>
                </td>
                <td>
                    <p class="size">Processing...</p>
                    <div class="progress"></div>
                </td>
                <td>
                    {% if (!i && !o.options.autoUpload) { %}
                        <button class="start" disabled>Start</button>
                    {% } %}
                    {% if (!i) { %}
                        <button class="cancel">Cancel</button>
                    {% } %}
                </td>
            </tr>
        {% } %}
        </script>
        <!-- The template to display files available for download -->
        <script id="template-download" type="text/x-tmpl">
        {% for (var i=0, file; file=o.files[i]; i++) { %}
            <tr class="template-download fade">
                <td>
                    <span class="preview">
                        {% if (file.thumbnailUrl) { %}
                            <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
                        {% } %}
                    </span>
                </td>
                <td>
                    <p class="name">
                        <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                    </p>
                    {% if (file.error) { %}
                        <div><span class="error">Error</span> {%=file.error%}</div>
                    {% } %}
                </td>
                <td>
                    <span class="size">{%=o.formatFileSize(file.size)%}</span>
                </td>
                <td>
                    <button class="delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>Delete</button>
                    <input type="checkbox" name="delete" value="1" class="toggle">
                </td>
            </tr>
        {% } %}
        </script>

     		<?php
			html_end_box( FALSE );

		print "
		<table align='center' width='100%' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>
			<tr>
				<td bgcolor='#f5f5f5' align='right'>
					<a href='CereusReporting_addReport.php?ReportId=" . $reportId . "'>Go back to report</a>
				</td>
	
			</tr>
		</table>
	";

	}

	function form_jscript_footer() {
	    global $config;
		if ( function_exists('top_header')) {
			// New Cacti 1.x
		} else {
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
		}
		?>

        <!-- The Templates plugin is included to render the upload/download listings -->
        <script src="libs/js/tmpl.min.js"></script>
        <!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
        <script src="libs/js/jquery.iframe-transport.js"></script>
        <!-- The Load Image plugin is included for the preview images and image resizing functionality -->
        <script src="libs/js/load-image.all.min.js"></script>
        <!-- The basic File Upload plugin -->
        <script src="libs/js/jquery.fileupload.js"></script>
        <!-- The File Upload processing plugin -->
        <script src="libs/js/jquery.fileupload-process.js"></script>
        <!-- The File Upload image preview & resize plugin -->
        <script src="libs/js/jquery.fileupload-image.js"></script>
        <!-- The File Upload audio preview plugin -->
        <script src="libs/js/jquery.fileupload-audio.js"></script>
        <!-- The File Upload video preview plugin -->
        <script src="libs/js/jquery.fileupload-video.js"></script>
        <!-- The File Upload validation plugin -->
        <script src="libs/js/jquery.fileupload-validate.js"></script>
        <!-- The File Upload user interface plugin -->
        <script src="libs/js/jquery.fileupload-ui.js"></script>
        <!-- The File Upload jQuery UI plugin -->
        <script src="libs/js/jquery.fileupload-jquery-ui.js"></script>
        <!-- The main application script -->
        <script src="libs/js/main.js"></script>
        <!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
        <!--[if (gte IE 8)&(lt IE 10)]>
        <script src="js/cors/jquery.xdr-transport.js"></script>
        <![endif]-->
		<?php
    }

