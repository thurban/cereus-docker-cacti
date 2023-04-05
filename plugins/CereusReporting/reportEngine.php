<?php
	/*******************************************************************************
	 *
	 * File:         $Id: reportEngine.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	* Encoding:     UTF-8
	* Status:       -
	* License:      Commercial
	* Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/
	error_reporting( 0 );

    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;

	/* Initialize the ReportEngine */
	function nmid_report_initialize( $pdfType, $pageSize = 'A4', $subTitle, $edition, $title, $font, $reportId, $logoMode, $footerText, $headerText )
	{
		global $orientation, $graphPerPage;
		$dir     = dirname( __FILE__ );
		$mainDir = preg_replace( "@plugins.CereusReporting@", "", $dir );

		CereusReporting_logger( 'DEBUG: My Directory [' . $dir.'] ' , 'debug', 'ReportEngine' );
		CereusReporting_logger( 'DEBUG: Main Directory [' . $mainDir.'] ' , 'debug', 'ReportEngine' );

		if ( ( $orientation == "L" ) or ( $orientation == "P" ) ) {
			// good
		}
		else {
			// Default orientation
			$orientation = "P";
		}
		if ( $pdfType == FPDF_ENGINE ) { // FPDF Engine
			chdir( $dir );
			require_once( 'ReportEngines/fpdf/fpdf.php' ); // actual PDF Creation Class
			require_once( 'ReportEngines/fpdf/fpdi.php' ); // Include other PDF files
			require_once( 'ReportEngines/pdf_fpdf.php' ); // PDF Class

			$report = new PDF( $orientation, 'mm', $pageSize );
			$report->SetCompression( TRUE );
			$report->SetSubject( $subTitle );
		}
		elseif ( $pdfType == MPDF_ENGINE ) { // mPDF Engine
			chdir( $dir );
            require_once __DIR__ . '/vendor/autoload.php';
			require_once( 'ReportEngines/pdf_mpdf.php' ); // PDF Class
			$origPageSize = $pageSize;
			if ( $orientation == 'L' ) {
				$pageSize = $pageSize . '-L';
			} else {
                $orientation = 'P';
            }
			$nmid_pdfUseUnicode           = readConfigOption( "nmid_pdfUseUnicode" );
			$nmid_debug_option            = readConfigOption( "nmid_pdf_debug" );
			$nmid_progress_bar            = readConfigOption( "nmid_pdfProgressBar" );
			$nmid_nmid_pdf_margin_left    = readConfigOption( "nmid_pdf_margin_left" );
			$nmid_nmid_pdf_margin_right   = readConfigOption( "nmid_pdf_margin_right" );
			$nmid_nmid_pdf_margin_top     = readConfigOption( "nmid_pdf_margin_top" );
			$nmid_nmid_pdf_margin_bottom  = readConfigOption( "nmid_pdf_margin_bottom" );
			$nmid_pdfUnicodeFontSet       = readConfigOption( "nmid_pdfUnicodeFontSet" );
			$template_file = "unknown";

            $report_template_id = -1;
			if ( $logoMode == "Report" ) {
                // CreatePDFReport defined/scheduled
                $report_template_id = getPreparedDBValue( 'select CoverPage from plugin_nmidCreatePDF_Reports where ReportId=?;', array( $reportId ) );
                if ($report_template_id > 0) {
                    // great
                } else {
                    $report_template_id = -1;
                }
            } else {
                $report_template_id = read_config_option( 'nmid_global_default_report_template' );
            }

            if ( ( $report_template_id > 0 ) || ( $report_template_id == -1 ) ) {
                $template_file = getPreparedDBValue( 'SELECT template_file FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_left   = getPreparedDBValue(  'SELECT page_margin_left FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_right  = getPreparedDBValue( 'SELECT page_margin_right FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_top    = getPreparedDBValue( 'SELECT page_margin_top FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_bottom = getPreparedDBValue( 'SELECT page_margin_bottom FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
            }

			if ( $nmid_pdfUseUnicode ) {
				$report = new PDF([
                    'mode' => 'utf-8',
				    'tempDir' => __DIR__ . '/tmp',
                    'orientation' => $orientation,
                    'useAdobeCJK' => true,
                    'format' => $origPageSize,
                    'autoScriptToLang' => true,
                    'simpleTables' => true,
                    'packTableData' => true,
                    'margin_left' => $nmid_nmid_pdf_margin_left,
                    'margin_right' => $nmid_nmid_pdf_margin_right,
                    'margin_top' => $nmid_nmid_pdf_margin_top,
                    'margin_bottom' => $nmid_nmid_pdf_margin_bottom,
                    'margin_header' => 0,
                    'margin_footer' => 0
                ]);
			}
			else {
                $report = new PDF([
                    'mode' => 'c',
                    'tempDir' => __DIR__ . '/tmp',
                    'orientation' => $orientation,
                    'useAdobeCJK' => false,
                    'format' => $origPageSize,
                    'simpleTables' => true,
                    'packTableData' => true,
                    'useSubstitutions' => false,
                    'margin_left' => $nmid_nmid_pdf_margin_left,
                    'margin_right' => $nmid_nmid_pdf_margin_right,
                    'margin_top' => $nmid_nmid_pdf_margin_top,
                    'margin_bottom' => $nmid_nmid_pdf_margin_bottom,
                    'margin_header' => 0,
                    'margin_footer' => 0
                ]);
			}
            $report->SetCompression( TRUE );
            $report->nmidSetReportId($reportId);
            $report->nmidSetReportMode($logoMode);

            //set margins
            //$nmid_pdf_footer_margin_bottom = $nmid_nmid_pdf_margin_bottom;
            //$report->SetMargins( $nmid_nmid_pdf_margin_left, $nmid_nmid_pdf_margin_right, $nmid_nmid_pdf_margin_top );
            //$report->SetFooterMargin( $nmid_pdf_footer_margin_bottom );

            if ( $nmid_debug_option ) {
				$report->debug           = TRUE;
				$report->showImageErrors = TRUE; // false/true;
                $report->allow_output_buffering = true;

                if (( PHP_MAJOR_VERSION >= 7) AND (PHP_MINOR_VERSION > 0 ) ) {
                    // create a log channel
                    $logger = new Logger( 'name' );
                    $logger->pushHandler( new StreamHandler( '/tmp/mpdf_log.log', Logger::DEBUG ) );
                    $report->setLogger( $logger );
                }
			}

			$report->SetDisplayMode( 'fullpage' );

			$base_dir = dirname( __FILE__ );
			$stylesheet = file_get_contents( $base_dir.'/'.'CereusReporting.css' );
			if ( ( $edition == "CORPORATE" ) ||  ( isSMBServer() ) ) {
				$plainCoverPageFile = $base_dir.'/templates/'.$template_file.'.css';
				if ( file_exists( $plainCoverPageFile ) ) {
					$stylesheet = file_get_contents( $plainCoverPageFile );
					CereusReporting_logger( 'Adding Stylesheet [' . $plainCoverPageFile .'] to Report' , 'debug', 'ReportEngine' );
				} else {
					CereusReporting_logger( 'WARNING: Stylesheet does not exist [' . $plainCoverPageFile .'] ' , 'debug', 'ReportEngine' );
					CereusReporting_logger( 'Adding Stylesheet [' . $base_dir.'/'.'CereusReporting.css' .'] to Report' , 'debug', 'ReportEngine' );
				}
			}
			$report->h2bookmarks = array('H1'=>0, 'H2'=>1, 'H3'=>2, 'H4'=>3, 'H5'=>4, 'H6'=>5);
			$report->WriteHTML( $stylesheet, 1);
		}
		elseif ( $pdfType == TCPDF_ENGINE ) { // TCPDF Engine
			chdir( $dir );
			require_once( 'ReportEngines/tcpdf/tcpdf.php' );
			//require_once( 'ReportEngines/fpdi/fpdi.php' ); // For Cover Pages
			require_once( 'ReportEngines/pdf_tcpdf.php' ); // PDF Class

			$nmid_pdfUseUnicode     = readConfigOption( "nmid_pdfUseUnicode" );
			$nmid_pdfUnicodeFontSet = readConfigOption( "nmid_pdfUnicodeFontSet" );
			$nmid_nmid_pdf_margin_left    = readConfigOption( "nmid_pdf_margin_left" );
			$nmid_nmid_pdf_margin_right   = readConfigOption( "nmid_pdf_margin_right" );
			$nmid_nmid_pdf_margin_top     = readConfigOption( "nmid_pdf_margin_top" );
			$nmid_nmid_pdf_margin_bottom = readConfigOption( "nmid_pdf_margin_bottom" );
			$nmid_pdf_footer_margin_bottom = $nmid_nmid_pdf_margin_bottom;
			$template_file = "unknown";

			if ( $logoMode == "Report" ) {
				CereusReporting_logger( 'DEBUG: We are in defined/schedule mode [' . $logoMode.'] ' , 'debug', 'ReportEngine' );
				$report_template_id = getPreparedDBValue( 'select CoverPage from plugin_nmidCreatePDF_Reports where ReportId=?;', array( $reportId ) );
                if ($report_template_id > 0) {
                    // great
                } else {
                    $report_template_id = -1;
                }

            } else {
                CereusReporting_logger( 'DEBUG: We are in on-demand mode [' . $logoMode . '] ', 'debug', 'ReportEngine' );
                $report_template_id = read_config_option( 'nmid_global_default_report_template' );
            }

            if ( ( $report_template_id > 0 ) || ( $report_template_id == -1 ) ) {
                $template_file = getPreparedDBValue( 'SELECT template_file FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_left   = getPreparedDBValue(  'SELECT page_margin_left FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_right  = getPreparedDBValue( 'SELECT page_margin_right FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_top    = getPreparedDBValue( 'SELECT page_margin_top FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_nmid_pdf_margin_bottom = getPreparedDBValue( 'SELECT page_margin_bottom FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
                $nmid_pdf_footer_margin_bottom = getPreparedDBValue( 'SELECT page_footer_margin_bottom FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
            }

			if ($nmid_pdfUseUnicode) {
				$report = new PDF( $orientation, 'mm', $pageSize, TRUE, 'UTF-8', FALSE );
				$report->setFontSubsetting(false);
			} else {
				$report = new PDF( $orientation, 'mm', $pageSize, TRUE, 'ISO-8859-1', FALSE );
				$report->setFontSubsetting(true);
			}
			$report->nmidSetFooterText( $footerText );
			$report->nmidSetHeaderText( $headerText );
            $report->nmidSetPageOrientation( $orientation );
            $report->nmidSetPageSize( $pageSize );

            $report->set_pdf_margin_top($nmid_nmid_pdf_margin_top);
            $report->set_pdf_margin_bottom($nmid_nmid_pdf_margin_bottom);
            $report->set_pdf_margin_left($nmid_nmid_pdf_margin_left);
            $report->set_pdf_margin_right($nmid_nmid_pdf_margin_right);


			if ( $logoMode == "Report" ) {
				$skipHFCoverPage = getPreparedDBValue( 'select skipHFCoverPage from plugin_nmidCreatePDF_Reports where ReportId=?;', array( $reportId ) );
				$report->nmidSetSkipCoverPage($skipHFCoverPage);
				if ( $skipHFCoverPage ) {
					$report->nmidDisableTemplate();
				} else {
					$report->setPrintHeader( TRUE );
					$report->setPrintFooter( TRUE );
				}
			} else {
				if ( readConfigOption('nmid_pdfPrintHeaderFooter') ) {
					$report->setPrintHeader( TRUE );
					$report->setPrintFooter( TRUE );
				} else {
					$report->setPrintHeader( TRUE );
					$report->setPrintFooter( FALSE );
				}
			}

			CereusReporting_logger( 'DEBUG: Setting some variables ...' , 'debug', 'ReportEngine' );

			$report->nmidSetReportId( $reportId );
			$report->nmidSetReportMode( $logoMode );
			$report->nmidSetPageOrientation( $orientation );
			$report->nmidSetPageSize( $pageSize );

			//$pdf->SetProtection(array('print'));
			$report->SetDisplayMode( 'fullpage' );

			// set document information
			$report->SetCreator( PDF_CREATOR );
			$report->SetAuthor( PDF_AUTHOR );
			$report->SetTitle( 'CereusReporting Report' );
			$report->SetSubject( 'CereusReporting Report' );
			$report->SetKeywords( 'CereusReporting, PDF, cacti' );

			// set default header data
			$report->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, '..', PDF_HEADER_STRING );

			// set header and footer fonts
			$report->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
			$report->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

			// set default monospaced font
			$report->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

			//set margins
			$report->SetMargins( $nmid_nmid_pdf_margin_left, $nmid_nmid_pdf_margin_top, $nmid_nmid_pdf_margin_right, true );
			$report->SetHeaderMargin( PDF_MARGIN_HEADER );
			$report->SetFooterMargin( $nmid_pdf_footer_margin_bottom );

			//set auto page breaks
			$report->SetAutoPageBreak( TRUE, $nmid_nmid_pdf_margin_bottom );

			//set image scale factor
			$report->setImageScale( PDF_IMAGE_SCALE_RATIO );
			CereusReporting_logger( 'DEBUG: Setting some variables ... Finished' , 'debug', 'ReportEngine' );

			$base_dir = dirname( __FILE__ );
			$stylesheet = file_get_contents( $base_dir.'/'.'CereusReporting.css' );
			if ( ( $edition == "CORPORATE" ) ||  ( isSMBServer() ) ) {
				$plainCoverPageFile = $base_dir.'/templates/'.$template_file.'.css';
				if ( file_exists( $plainCoverPageFile ) ) {
					$stylesheet = file_get_contents( $plainCoverPageFile );
					CereusReporting_logger( 'Adding Stylesheet [' . $plainCoverPageFile .'] to Report' , 'debug', 'ReportEngine' );
				} else {
					CereusReporting_logger( 'WARNING: Stylesheet does not exist [' . $plainCoverPageFile .'] ' , 'debug', 'ReportEngine' );
					CereusReporting_logger( 'Adding Stylesheet [' . $base_dir.'/'.'CereusReporting.css' .'] to Report' , 'debug', 'ReportEngine' );
				}
			}
			// set some language-dependent strings (optional)
			if (@file_exists(dirname(__FILE__).'/ReportEngines/tcpdf/lang/eng.php')) {
				require_once(dirname(__FILE__).'/ReportEngines/tcpdf/lang/eng.php');
				$report->setLanguageArray($l);
			}

			$report->nmidSetStylesheet($stylesheet);

			// ---------------------------------------------------------

			$font = 'dejavusans';
			// set font
			if ($nmid_pdfUseUnicode) {
				// set font
				$report->SetFont('dejavusans', '', 12, false);
			} else {
				$report->SetFont('dejavusans', '', 12, false);
			}
			$report->nmidSetMyFont( 'dejavusans' );


            // add a page
            CereusReporting_logger( 'DEBUG: Creating initial page' , 'debug', 'ReportEngine' );
            $a_column_data = preg_split( "/,/", $graphPerPage );

            $report->AddPage();
            //$report->resetColumns();
            //$report->setEqualColumns($a_column_data[ 0 ], $a_column_data[ 1 ] );

			CereusReporting_logger( 'DEBUG: Adding the stylesheet to the report.' , 'debug', 'ReportEngine' );
			$report->writeHTML( '<head><style>'. $stylesheet . '</style></head>', TRUE, FALSE, TRUE, FALSE, '' );
		}

		$report->nmidSetPdfType( $pdfType );
		$report->SetTitle( $title );

		if ( $edition == "EXPRESS" ) {
			$report->SetAuthor( 'Urban-Software.com' );
			$report->SetCreator( 'CereusReporting STANDARD - https://www.urban-software.com' );
		}
		else {
			$report->SetAuthor( 'Urban-Software.com' );
			$report->SetCreator( 'CereusReporting - https://www.urban-software.com' );
		}
		$report->nmidSetMyFont( $font );
		return $report;
	}

	function nmid_report_initialize_header_data( $report, $subTitle, $footerText, $headerText, $reportDate )
	{
		$report->nmidSetHeaderSubTitle( $subTitle );
		$report->nmidSetFooterText( $footerText );
		$report->nmidSetHeaderText( $headerText );
		$report->nmidSetReportDate( $reportDate );
    }

	function nmid_report_initializes_headerfooter( $report, $graphPerPage, $mode="defined" )
	{
		$html                      = '';
        $report->nmidSetHtml( $html );
        $report->nmidHeader();
        $report->nmidFooter();
		$report->nmidSetCurCol( 1 );
		$report->SetFont( $report->nmidGetMyFont(), '', 10 );
        if ( $report->nmidGetPdfType() == TCPDF_ENGINE ) {
            $report->AddPage();
        } elseif ( $report->nmidGetPdfType() == MPDF_ENGINE ) {
            $a_column_data = preg_split( "/,/", $graphPerPage );
            $maxColumn = $a_column_data[0];
            if ($report->nmidGetSkipFirstPage() == true) {
                $report->SetHTMLHeaderByName('myheader');
                //$report->WriteHTML('<pagebreak resetpagenum=1>');
                $report->SetHTMLFooterByName('myfooter');
                $report->SetColumns($maxColumn);
            } else {
                $report->SetHTMLHeaderByName('myheader', 'O', true);
                $report->SetHTMLFooterByName('myfooter');
                if ( $mode == 'defined' ) {
                    //$report->AddPage();
                }
                $report->SetColumns($maxColumn);
            }
        }
    }

	function nmid_report_finalize( $report )
	{
		if ( $report->nmidGetPdfType() == MPDF_ENGINE ) {
			// $report->WriteHTML( '' );
		}
		if ( $report->nmidGetPdfType() == TCPDF_ENGINE ) {
			$report->writeHTML( '', FALSE, TRUE, TRUE, FALSE, '' );
		}
	}

	function nmid_report_add_reportDescription( $report, $reportDescription, $headerFontSize )
	{
		if ( $report->nmidGetPdfType() == FPDF_ENGINE ) {
			$report->MultiCell( 0, $headerFontSize - 2, $reportDescription, 'B', 'L' );
			$report->Ln( $headerFontSize );
		}
		if ( $report->nmidGetPdfType() == MPDF_ENGINE ) {
			$html = '<div class="nmidReportDescription">
                    ' . $reportDescription . '
                </div>';
			$report->WriteHTML( $html);
		}
		if ( $report->nmidGetPdfType() == TCPDF_ENGINE ) {
			$html = '<div class="nmidReportDescription">
                    ' . $reportDescription . '
                </div>';
			$report->writeHTML( $html, FALSE, FALSE, TRUE, FALSE, '' );
		}
	}
