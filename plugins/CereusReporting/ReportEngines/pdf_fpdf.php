<?php
	/*******************************************************************************
	 *
	 * File:         $Id: pdf_fpdf.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * Language:     Perl
	 * Encoding:     UTF-8
	 * Status:       -
	 * License:      Commercial
	 * Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	require_once( 'functions.php' );

	ini_set( 'auto_detect_line_endings', 1 );

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
		var $nmid_skipFirstPage;
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

		//Page header
		function Header()
		{
			if ( ( $this->PageNo() == 1 ) AND ( $this->nmidGetSkipFirstPage() == TRUE ) ) {
				return;
			}

			$img = $this->nmidGetLogoImage();
			list( $imWidth, $imHeigth ) = convertPng( $img );
			$imageWidth = 30;
			$imHeigth   = ( $imageWidth * px2mm( $imHeigth ) ) / px2mm( $imageWidth );
			if ( $imHeigth > 10 ) {
				$imHeigth   = 10;
				$imageWidth = ( $imHeigth * px2mm( $imageWidth ) ) / px2mm( $imHeigth );
			}
			else {
				$imHeigth   = 10;
				$imageWidth = ( $imHeigth * px2mm( $imageWidth ) ) / px2mm( $imHeigth );
			}
			//Logo
			$this->Image( $img, 10, 8, $imageWidth, $imHeigth );
			//Arial bold 15
			$this->SetFont( $this->nmidGetMyFont(), 'B', 15 );
			//Move to the right
			$this->Cell( $imageWidth + 5 );
			//Title
			if ( $this->nmidGetUseUnicode() == "on" ) {
				// required: php-mbstring
				$imageFontFile = drawUnicodeText( $this, $this->nmidGetMyFont(), $this->nmidGetHeaderTitle(), 800, 50, 15 );
				$this->Image( $imageFontFile, $this->GetX(), 4, getWidthLeft( $this ), 8, NULL, NULL );
			}
			else {
				$this->Cell( 0, 8, $this->nmidGetHeaderTitle(), 0, 0, 'L' );
			}
			$this->Ln();
			//Default Arial bold 10, Font can be changed via the web-interface
			$this->SetFont( $this->nmidGetMyFont(), 'B', 10 );
			$this->SetLineWidth( 0.6 );
			$this->Cell( $imageWidth + 5, 6, '', 'B' );
			$this->Cell( 0, 6, $this->nmidGetHeaderSubTitle(), 'B', 0, 'L' );
			$this->SetLineWidth( 0.2 );
			//$this->Line()
			//Line break
			$this->Ln( 10 );
		}

		//Page footer
		function Footer()
		{
			if ( ( $this->PageNo() == 1 ) AND ( $this->nmidGetSkipFirstPage() == TRUE ) ) {
				return;
			}

			//Position at 1.5 cm from bottom
			$this->SetY( -20 );
			//Arial italic 8
			$this->SetFont( $this->nmidGetMyFont(), 'I', 8 );
			//Page number
			$this->SetLineWidth( 0.6 );
			$this->Cell( 80, 10, $this->nmid_reportDate, 'T', 0, 'L' );
			$this->Cell( 0, 10, 'Page ' . $this->PageNo() . '/{nb}', 'T', 0, 'R' );
			$this->SetLineWidth( 0.2 );
			$this->Ln( 5 );
			if ( $this->nmidGetFooterText() ) {
				if ( EDITION == "EXPRESS" ) {
					$this->Cell( 0, 10, $this->nmidGetFooterText() . ' - ' . 'Created with CereusReporting plugin. (c) '.date('Y').' by Urban-Software.de. Standard Edition', 0, 0, 'L', FALSE, 'http://www.urban-software.de' );
				}
				else {
					$this->Cell( 0, 10, $this->nmidGetFooterText(), 0, 0, 'L', FALSE );
				}
			}
			else {
				if ( EDITION == "EXPRESS" ) {
					$this->Cell( 0, 10, 'Created with CereusReporting plugin. (c) '.date('Y').' by Urban-Software.de. Standard Edition', 0, 0, 'L', FALSE, 'http://www.urban-software.de' );
				}
			}
			#$this->Ln(5);
			#$this->Cell(0,10,'Created with CereusReporting plugin. (c) 2009 by Urban-Software.de. Free Version',0,0,'L');
		}


		/**
		 * @param mixed $nmid_headerTxt
		 */
		public function setNmidHeaderTxt( $nmid_headerTxt )
		{
			$this->nmid_headerTxt = $nmid_headerTxt;
		}

		/**
		 * @return mixed
		 */
		public function getNmidHeaderTxt()
		{
			return $this->nmid_headerTxt;
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

		function nmidSetFooterText( $footerText )
		{
			$this->nmid_footerTxt = $footerText;
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
	}