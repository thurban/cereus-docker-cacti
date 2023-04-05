<?php
	/*******************************************************************************
	 *
	 * File:         $Id: pdf_tcpdf.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	require_once( 'functions.php' );
	require_once('ReportEngines/tcpdf/tcpdf.php');
	require_once('ReportEngines/fpdi/fpdi.php');

	//Instanciation of inherited class
	class PDF extends FPDI
	{
		var $nmid_logoImage;
		var $nmid_title;
		var $nmid_subTitle;
		var $nmid_headerTxt;
		var $nmid_footerTxt;
		var $nmid_curCol;
		var $nmid_columnY;
		var $nmid_reportDate;
		var $nmid_skipFirstPage = FALSE;
		var $nmid_font;
		var $nmid_useUnicode;
		var $show_detailed_failed_table = FALSE;
		var $show_detailed_table = FALSE;
		var $nmid_pdf_type; // 0 = FPDF, 1 = mPDF, 2 = TCPDF
		var $nmid_worker_file;
		var $nmid_worker_file_content;
		var $nmid_worker_dir;
		var $nmid_skip_coverpage;
		var $nmid_print_header;
		var $nmid_print_footer;
		var $nmid_report_id;
		var $nmid_report_stylesheet;
		var $nmid_report_mode;
		var $nmid_page_size;
		var $nmid_page_orientation;
		var $printPageNumbers = TRUE;
		var $parser;
		var $objcopy;
		var $hide_template = FALSE;
		var $nmid_nmid_pdf_margin_left;
		var $nmid_nmid_pdf_margin_top;
		var $nmid_nmid_pdf_margin_right;
		var $nmid_pdf_footer_margin_bottom;
		protected $_tplIdx;


		public function Header()
		{
            global $config;

            $nmid_nmid_pdf_margin_left = $this->get_pdf_margin_left();
            $nmid_nmid_pdf_margin_right = $this->get_pdf_margin_right();
            $nmid_nmid_pdf_margin_top = $this->get_pdf_margin_top();
            $nmid_pdf_footer_margin_bottom = $this->get_pdf_margin_bottom();

            //set margins
            $this->SetMargins( $nmid_nmid_pdf_margin_left, $nmid_nmid_pdf_margin_top, $nmid_nmid_pdf_margin_right, true );
            $this->SetHeaderMargin( PDF_MARGIN_HEADER );
            $this->SetFooterMargin( $nmid_pdf_footer_margin_bottom );
/*
            $this->SetMargins(0, 0, 0);
            $this->SetHeaderMargin(0);
            $this->SetFooterMargin(0);
*/
			if ( ( $this->nmidGetSkipFirstPage() == TRUE ) && ( $this->getAliasNumPage()==1 ) ) {
				$params       = $this->serializeTCPDFtagParameters( array( 'false' ) );
				$control_text = '<tcpdf method="setPrintHeader" params="' . $params . '" />';
				$control_text .= '<tcpdf method="setPrintFooter" params="' . $params . '" />';
				$this->writeHTML( $control_text, TRUE, FALSE, TRUE, FALSE, '' );
				return;
			}

			$dir     = dirname( __FILE__ ).'/../';



			CereusReporting_logger( 'DEBUG: My Directory [' . $dir.'] ' , 'debug', 'ReportEngine' );
			if ( ( EDITION == "CORPORATE" ) || ( isSMBServer() ) ) {

				if  ( $this->hide_template == FALSE ) {
					// Get Template
					if ( is_null( $this->_tplIdx ) ) {
						if ( $this->nmid_report_mode == 'Report' ) {
							$report_template_id = getPreparedDBValue( 'SELECT CoverPage FROM plugin_nmidCreatePDF_Reports WHERE ReportId=?', array($this->nmid_report_id ));
                            CereusReporting_logger('Retrieving '.$this->nmid_report_mode.' Template ID [' . $report_template_id . '] for this Report ['. $this->nmid_report_id .']', 'debug', 'ReportEngine');
							if ( $report_template_id > 0 ) {
								// great
							} else {
								$report_template_id = -1;
							}
							$template_file = $dir.'templates/'.getPreparedDBValue( 'SELECT template_file FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array($report_template_id) );
						}
						else {
                            $report_template_id = read_config_option( 'nmid_global_default_report_template' );
                            CereusReporting_logger('Retrieving '.$this->nmid_report_mode.' Template ID [' . $report_template_id . '] for this Report ['. $this->nmid_report_id .']', 'debug', 'ReportEngine');
                            if ($report_template_id != 0) {
                                $template_file = $dir . 'templates/' . getPreparedDBValue( 'SELECT template_file FROM plugin_CereusReporting_Reports_templates WHERE templateId=?;', array( $report_template_id ) );
                            }
						}
                        CereusReporting_logger('Checking Report Template [' . $template_file . '] for this Report', 'debug', 'ReportEngine');

						if ( ( file_exists( $template_file ) ) && ( is_dir($template_file) == false ) ) {
							CereusReporting_logger( 'Using Report Template [' . $template_file .'] for this Report' , 'debug', 'ReportEngine' );
							$this->setSourceFile( $template_file );
							$this->_tplIdx = $this->importPage( 1 );
							$this->useTemplate( $this->_tplIdx );
							CereusReporting_logger( 'DEBUG: Report Template has been added.' , 'debug', 'ReportEngine' );
						} else {
							CereusReporting_logger( 'WARNING: Report Template does not exist [' .$template_file .'] ' , 'debug', 'ReportEngine' );

						}
					}
					else {
						$this->useTemplate( $this->_tplIdx );
					}

					$img = $this->nmidGetLogoImage();
					list( $imWidth, $imHeigth ) = convertPng( $img );

					if ( $imWidth > 200 ) {
						$imWidth = 200;
					}
					$imHeigth = ( $imWidth * px2mm( $imHeigth ) ) / px2mm( $imWidth );

					if ( $img == "images/transparent_logo.png" ) {
						$imageWidth = 1;
					}
					// Create Header HTML Template:
                if ( strlen($this->getNmidHeaderTxt() ) > 0 ) {
                    CereusReporting_logger('Setting CUSTOM Header Text for this Report', 'debug', 'ReportEngine');
						$html = $this->getNmidHeaderTxt();
					}
					else {
                        CereusReporting_logger('Setting DEFAULT Header Text for this Report', 'debug', 'ReportEngine');

                        $html = '
						<table width="99%">
						    <thead>
						        <tr height="1px">
						            <th></th>
						            <th></th>
						        </tr>
						    </thead>
						    <tbody>
                                <tr>
                                    <td class="nmidHeaderLogo" align="left"><img src="' . $img . '" height="' . $imHeigth . '"/></td>
                                    <td align="right">
                                        <table>
                                                <tr>
                                                    <td class="nmidHeaderTitle">' . $this->nmidGetHeaderTitle() . '</td>
                                                </tr>
                                                <tr>
                                                    <td class="nmidSubHeaderTitle">' . $this->nmidGetHeaderSubTitle() . '</td>
                                                </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
						</table>';
					}

					if ( $this->nmid_report_mode == 'Report' ) {
                        CereusReporting_logger('Report Type - Print Header for this Report', 'debug', 'ReportEngine');
						$this->writeHTML( $html, TRUE, FALSE, TRUE, FALSE, '' );
					} else {
						if ( readConfigOption('nmid_pdfPrintHeaderFooter')  ) {
                            CereusReporting_logger('Print Header for this Report', 'debug', 'ReportEngine');
                            $this->writeHTML( $html, TRUE, FALSE, TRUE, FALSE, '' );
						} else {
                            CereusReporting_logger('Skipping Header for this Report', 'debug', 'ReportEngine');
                            // skipping header
						}
					}

                    $this->nmidSetCurCol(1);
                    $this->nmidSetHtml('');
                }
			}
		}

		// Page footer
		public function Footer()
		{
            printEndColumnHTML($this);

            if ( ( $this->nmidGetSkipFirstPage() == true ) && ( $this->getAliasNumPage()==1 ) ) {
				$params       = $this->serializeTCPDFtagParameters( array( 'false' ) );
				$control_text = '<tcpdf method="setPrintHeader" params="' . $params . '" />';
				$control_text .= '<tcpdf method="setPrintFooter" params="' . $params . '" />';
				$this->writeHTML( $control_text, TRUE, FALSE, TRUE, FALSE, '' );
				return;
			}
			if ( $this->hide_template == FALSE ) {
				// Create Footer HTML Template:
				if ( $this->nmidGetFooterText() ) {
					if ( EDITION == "EXPRESS" ) {
						$footerText = $this->nmidGetFooterText() . ' - ' . 'Created with CereusReporting plugin. (c) '.date('Y').' by Urban-Software.de. Standard Edition';
						$html = $this->getFooterHTMLText( $footerText );
					}
					else {
						$this->showWatermarkText = FALSE;
						$footerText = $this->nmidGetFooterText();
						$html = $this->getFooterHTMLText( $footerText );
					}
				}
				else {
					if ( EDITION == "EXPRESS" ) {
						$footerText = 'Created with CereusReporting plugin. (c) '.date('Y').' by Urban-Software.de. Standard Edition';
						$html = $this->getFooterHTMLText( $footerText );
					}
					else {
						$footerText = $this->nmidGetFooterText();
						$html = $this->getFooterHTMLText( $footerText );
						$this->showWatermarkText = FALSE;
					}
				}
				$this->writeHTML( $html, TRUE, FALSE, TRUE, FALSE, '' );
			}
		}

		function getFooterHTMLText( $footerText ) {
			$page_width = '100%';
            $page_data = '<td width="20%"></td>';

			if ($this->nmidGet_PrintPageNumbers() == TRUE) {
			    $page_data = '<td width="20%" style="text-align:right" class="nmidFooterPageData">Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . '</td>';
            }

			return '<table width="'.$page_width.'" cellspacing="0" cellpadding="0" border="0">
                        <thead>
                            <tr height="1px">
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                           <tr>
                                <td width="80%" style="text-align:left" class="nmidFooterText">' . $footerText . '</td>
                                '.$page_data.'
                            </tr>
					    </tbody>
					</table>';
		}

		function writeHTML( $html, $bool = TRUE, $bool2 = TRUE, $bool3 = TRUE, $bool4 = TRUE, $string='' )
		{
		    $this->setNmidCompleteHTML($html);
            // $html = '<style>' . $this->nmid_report_stylesheet . '</style>' . $html;
            parent::writeHTML( $html, $bool, $bool2, $bool3, $bool4, $string );
		}

		function generateReport()
		{
			return;
		}

		function nmidGet_PrintPageNumbers() {
		    return $this->printPageNumbers;
        }

        function nmidSet_PrintPageNumbers( $status = TRUE) {
            $this->printPageNumbers = $status;
        }

		//Page header
		function nmidHeader()
		{
			// Using TCPDF Header method
		}

		//Page footer
		function nmidFooter()
		{
			// Using TCPDF Footer method
		}

		public function SetSourceFile( $file )
		{
			return parent::setSourceFile( $file );
		}

		public function nmidSetHeaderText( $nmid_headerTxt )
		{
			$this->nmid_headerTxt = $nmid_headerTxt;
		}

		public function nmidSetFooterText( $nmid_footerTxt )
		{
			$this->nmid_footerTxt = $nmid_footerTxt;
		}

		public function SetDocTemplate()
		{
			return;
		}

		public function SetHTMLHeader()
		{
			return;
		}

		public function SetHTMLFooter()
		{
			return;
		}

		/**
		 * @return mixed
		 */
		public function getNmidHeaderTxt()
		{
			return $this->nmid_headerTxt;
		}

		public function getNmidFooterTxt()
		{
			return $this->nmidfooterTxt;
		}

		function nmidSetShowDetailedTable( $boolean )
		{
			$this->show_detailed_table = $boolean;
		}

		function nmidGetShowDetailedTable()
		{
			return $this->show_detailed_table;
		}

		function nmidSetShowDetailedFailedTable( $boolean )
		{
			$this->show_detailed_failed_table = $boolean;
		}

		function nmidGetShowDetailedFailedTable()
		{
			return $this->show_detailed_failed_table;
		}

		function nmidSetLogoImage( $image )
		{
			$this->nmid_logoImage = $image;
		}

		function nmidGetLogoImage()
		{
			return $this->nmid_logoImage;
		}

		function nmidSetSkipFirstPage( $boolean )
		{
			$this->nmid_skipFirstPage = $boolean;
		}

		function nmidGetSkipFirstPage()
		{
			return $this->nmid_skipFirstPage;
		}

		function nmidSetSkipCoverPage( $boolean )
		{
			$this->nmid_skip_coverpage = $boolean;
		}

		function nmidGetSkipCoverPage()
		{
			return $this->nmid_skip_coverpage;
		}

		function nmidSetPrintFooter( $boolean )
		{
			$this->nmid_print_footer = $boolean;
		}

		function nmidGetPrintFooter()
		{
			return $this->nmid_print_footer;
		}

		function nmidSetPrintHeader( $boolean )
		{
			$this->nmid_print_header = $boolean;
		}

		function nmidGetPrintHeader()
		{
			return $this->nmid_print_header;
		}

		function nmidSetHeaderTitle( $title )
		{
			$this->nmid_title = $title;
		}

		function nmidGetHeaderTitle()
		{
			return $this->nmid_title;
		}

		function nmidSetHeaderSubTitle( $title )
		{
			$this->nmid_subTitle = $title;
		}

		function nmidGetHeaderSubTitle()
		{
			return $this->nmid_subTitle;
		}

		function nmidSetCurCol( $column )
		{
			$this->nmid_curCol = $column;
		}

		function nmidGetCurCol()
		{
			return $this->nmid_curCol;
		}

		function nmidSetColumnY( $y )
		{
			$this->nmid_columnY = $y;
		}

		function nmidGetColumnY()
		{
			return $this->nmid_columnY;
		}

		function nmidSetReportDate( $reportDate )
		{
			$this->nmid_reportDate = $reportDate;
		}

		function nmidGetReportDate()
		{
			return $this->nmid_reportDate;
		}

		function nmidGetFooterText()
		{
			return $this->nmid_footerTxt;
		}

		function nmidSetMyFont( $font )
		{
			$this->nmid_font = $font;
		}

		function nmidGetMyFont()
		{
			return $this->nmid_font;
		}

		function nmidSetUseUnicode( $useUnicode )
		{
			$this->nmid_useUnicode = $useUnicode;
		}

		function nmidGetUseUnicode()
		{
			return $this->nmid_useUnicode;
		}

		function nmidSetHtml( $text )
		{
			$this->nmid_html = $text;
		}

		function nmidGetHtml()
		{
			return $this->nmid_html;
		}

		function nmidSetPdfType( $type )
		{
			$this->nmid_pdf_type = $type;
		}

		function nmidGetPdfType()
		{
			return $this->nmid_pdf_type;
		}

		function nmidSetWorkerFile( $file )
		{
			$this->nmid_worker_file = $file;
		}

		function nmidGetWorkerFile()
		{
			return $this->nmid_worker_file;
		}

		function nmidSetWorkerFileContent( $content )
		{
			$this->nmid_worker_file_content = $content;
		}

		function nmidGetWorkerFileContent()
		{
			return $this->nmid_worker_file_content;
		}

		function nmidSetWorkerDir( $dir )
		{
			$this->nmid_worker_dir = $dir;
		}

		function nmidGetWorkerDir()
		{
			return $this->nmid_worker_dir;
		}

		function nmidGetReportId()
		{
			return $this->nmid_report_id;
		}

		function nmidSetReportId( $reportId )
		{
			$this->nmid_report_id = $reportId;
		}

		function nmidGetStylesheet( )
		{
			return $this->nmid_report_stylesheet;
		}

		function nmidSetStylesheet( $stylesheet )
		{
			$this->nmid_report_stylesheet = $stylesheet;
		}

		function nmidGetReportMode( )
		{
			return $this->nmid_report_mode;
		}

		function nmidSetReportMode( $mode )
		{
			$this->nmid_report_mode = $mode;
		}

		function nmidGetPageSize( )
		{
			return $this->nmid_page_size;
		}

		function nmidSetPageSize( $page_size )
		{
			$this->nmid_page_size = $page_size;
		}

		function nmidEnableTemplate( )
		{
			$this->hide_template = false;
		}

		function nmidDisableTemplate( )
		{
			$this->hide_template = true;
		}


		function nmidGetPageOrientation( )
		{
			return $this->nmid_page_orientation;
		}

		function nmidSetPageOrientation( $orientation )
		{
			$this->nmid_page_orientation = $orientation;
		}

        public function set_pdf_margin_top( $margin ) {
            $this->nmid_nmid_pdf_margin_top = $margin;
        }

        public function set_pdf_margin_bottom( $margin ) {
            $this->nmid_pdf_footer_margin_bottom = $margin;
        }

        public function set_pdf_margin_left( $margin ) {
            $this->nmid_nmid_pdf_margin_left = $margin;
        }

        public function set_pdf_margin_right( $margin ) {
            $this->nmid_nmid_pdf_margin_right = $margin;
        }

        public function get_pdf_margin_top(  ) {
            return $this->nmid_nmid_pdf_margin_top;
        }

        public function get_pdf_margin_bottom(  ) {
            return $this->nmid_pdf_footer_margin_bottom;
        }

        public function get_pdf_margin_left(  ) {
            return $this->nmid_nmid_pdf_margin_left;
        }

        public function get_pdf_margin_right(  ) {
            return $this->nmid_nmid_pdf_margin_right;
        }

        public function getPageRemainingHeight()
        {
            return $this->getPageHeight() - $this->GetY() - $this->getBreakMargin();
        }

        public function getNmidCompleteHTML() {
		    return $this->nmid_complete_html;
        }

        public function setNmidCompleteHTML( $html ) {
            $this->nmid_complete_html = $this->nmid_complete_html . $html;
        }
	}

