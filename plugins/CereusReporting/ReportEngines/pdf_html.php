<?php
	/*******************************************************************************

	File:         $Id: pdf_html.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	Modified_On:  $Date: 2020/12/10 07:06:31 $
	Modified_By:  $Author: thurban $
	Language:     Perl
	Encoding:     UTF-8
	Status:       -
	License:      Commercial
	Copyright:    Copyright 2009/2010 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

	$dir = dirname( __FILE__ );
	chdir( $dir );
	chdir( '..' );

	require_once( 'functions.php' );

	//Instanciation of inherited class
	class HTMLReport
	{
		var $nmid_logoImage;
		var $nmid_title;
		var $nmid_subTitle;
		var $nmid_footerTxt;
		var $nmid_curCol;
		var $nmid_columnY;
		var $nmid_reportDate;
		var $nmid_skipFirstPage;
		var $nmid_font;
		var $nmid_useUnicode;
		var $nmid_html = "";
		var $nmid_pdf_type; // 0 = FPDF, 1 = mPDF
		var $nmid_worker_file;
		var $nmid_worker_file_content;
		var $nmid_worker_dir;
		var $rMargin;
		var $bMargin;
		var $stylesheet;
		var $footerSend = FALSE;
		var $nmid_skip_coverpage;
		var $nmid_print_header;
		var $nmid_print_footer;
		var $nmid_headerTxt;
		var $show_detailed_failed_table = FALSE;
		var $show_detailed_table = FALSE;
		var $nmid_report_id;

		function SetAuthor( $text )
		{
		}

		function SetCreator( $text )
		{
		}

		function AliasNbPages( $text )
		{
		}

		function AddPage( $text )
		{
		}

		function SetTitle( $text )
		{
		}

		function SetSubject( $text )
		{
		}

		function SetFont( $text )
		{
		}

		function Bookmark( $text )
		{
		}


		function PageNo( $text )
		{
		}

		function GetX()
		{
			return 1;
		}

		function SetX( $text )
		{
		}

		function GetY()
		{
			return 1;
		}

		function SetY( $text )
		{
		}


		function Output( $filename, $mode )
		{
			$this->nmidFooter( TRUE );

			$zip = new ZipArchive();

			if ( $mode == 'F' ) {
				$filename = $filename . '.zip';
			}
			else {
				$filename = $this->nmidGetWorkerDir() . '/' . $filename . '.zip';
			}

			if ( $zip->open( $filename, ZIPARCHIVE::CREATE ) !== TRUE ) {
				exit( "cannot open <$filename>\n" );
			}
			$reportHtml = $this->nmidGetHtml();


			$bookmarkHTML  = '<br><h4>Bookmark</h4>';
			$a_reportLines = preg_split( "/\n/", $reportHtml );
			$nbSpaces      = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			foreach ( $a_reportLines as $line ) {
				if ( preg_match( "/<bookmark content=\"(.*)\" level=\"(\d+)\" \/><br \/>/", $line, $entry ) ) {
					$tierSpace = '';
					for ( $i = 1; $i <= $entry[ 2 ]; $i++ ) {
						$tierSpace .= $nbSpaces;
					}
					$bookmarkHTML .= $tierSpace . '<a href="#' . $entry[ 1 ] . '">' . $entry[ 1 ] . '</a><br>' . "\n";
				}
			}
			$bookmarkHTML .= '<h4>Report</h4>';

			$reportHtml   = preg_replace( "/<bookmark content=\"/", "<a name=\"", $reportHtml );
			$reportHtml   = preg_replace( "/###BOOKMARK###/", $bookmarkHTML, $reportHtml );
			$logoFileName = $this->nmidGetLogoImage();
			$stylesheet   = $this->nmidGetStylesheet();
			$logoFileName = preg_replace( "/^\w\:/", '.', $logoFileName );
			$origLogo     = $this->nmidGetLogoImage();
			$reportHtml   = str_replace( $origLogo, $logoFileName, $reportHtml );

			$zip->addFromString( 'index.html', $reportHtml );
			$zip->addFile( $this->nmidGetLogoImage(), $logoFileName );
			$zip->addFile( $stylesheet, $stylesheet );

			if ( EDITION != "EXPRESS" ) {
				//Remove images
				$fh = fopen( $this->nmidGetWorkerFile(), "r" );
				while ( $line = fgets( $fh ) ) {
					$a_data       = preg_split( "/@/", $line );
					$s_image_file = $a_data[ 4 ];
					if ( preg_match( "/WIN/", PHP_OS ) > 0 ) {
						// Windows
						$s_image_file = preg_replace( "/\//", "\\", $s_image_file );
					}
					else {
						// *Nix
						//$s_image_file = preg_replace("/\\/","/",$s_image_file);
					}
					$s_type = $a_data[ 0 ];
					if ( $s_type == 'graph' ) {
						if ( file_exists( $s_image_file ) ) {
							$zip->addFile( $s_image_file, $s_image_file );
						}
					}
					elseif ( $s_type == 'smokeping' ) {
						if ( file_exists( $s_image_file ) ) {
							$file      = $s_image_file;
							$f         = fopen( $file, 'r' );
							$imageFile = fread( $f, filesize( $file ) );
							fclose( $f );
							if ( file_exists( $imageFile ) ) {
								$zip->addFile( $imageFile, $imageFile );
							}
							if ( file_exists( $s_image_file ) ) {
								$zip->addFile( $s_image_file, $s_image_file );
							}
						}
					}
					elseif ( $s_type == 'dsstats' ) {
						if ( file_exists( $s_image_file ) ) {
							$zip->addFile( $s_image_file, $s_image_file );
						}
					}
					elseif ( $s_type == 'availability_combined' ) {
						if ( file_exists( $s_image_file ) ) {
							$zip->addFile( $s_image_file, $s_image_file );
						}
					}
					elseif ( $s_type == 'availability' ) {
						if ( file_exists( $s_image_file ) ) {
							$zip->addFile( $s_image_file, $s_image_file );
						}
					}
				}
				fclose( $fh );
			}

			$zip->close();

			if ( $mode == 'D' ) {
				readfile( $filename );
			}
		}


		function WriteHTML( $html, $start, $finish )
		{
			$this->nmidSetHtml( $html );
		}

		//Page header
		function nmidHeader()
		{
			$img = $this->nmidGetLogoImage();
			list( $imWidth, $imHeigth ) = convertPng( $img );
			$stylesheet = $this->nmidGetStylesheet();

			$html = '
		<html><body>
		<link href="' . $stylesheet . '" rel="stylesheet">
        <div class=nmidHeaderDesign  width=100%>
        <table width="100%">
         <tr>
          <td valign=middle width="20%" style="color:#0000BB;">
           <img src="' . $img . '" height="40"/>
          </td>
          <td valign=middle width="80%" style="text-align: left;">
           <div class=nmidHeaderTitle>' . $this->nmidGetHeaderTitle() . '</div><br />
           <div class=nmidSubHeaderTitle>' . $this->nmidGetHeaderSubTitle() . '</div><br />
          </td>
         </tr>
        </table>
        </div>
		
		###BOOKMARK###
        ';

			$this->WriteHTML( $html, FALSE, FALSE );
		}

		//Page footer
		function nmidFooter( $printMe = FALSE )
		{
			if ( $this->footerSend == FALSE ) {
				if ( $printMe ) {
					$this->footerSend = TRUE;
					$footerText       = '';

					if ( $this->nmidGetFooterText() ) {
						if ( EDITION == "EXPRESS" ) {
							$footerText = $this->nmidGetFooterText() . ' - ' . 'Created with CereusReporting plugin. (c) '.date('Y').' by Urban-Software.de.';
						}
						else {
							$footerText = $this->nmidGetFooterText();
						}
					}
					else {
						if ( EDITION == "EXPRESS" ) {
							$footerText = 'Created with CereusReporting plugin. (c) '.date('Y').' by Urban-Software.de.';
						}
						else {
						}

					}

					// Add footer :
					$html = '
					<div class=nmidFooterDesign  width=100%>
					<table width="100%">
					 <tr>
					  <td align=left width="70%"><div class=nmidFooterText>' . $this->nmidGetReportDate() . '<br/>' . $footerText . '</div></td>
					  <td align=right width="30%"></td>
					 </tr>
					</table>
					</div>
					</body></html>
				';

					$this->WriteHTML( $html, FALSE, FALSE );
				}
			}
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

		function nmidSetHtml( $text )
		{
			$this->nmid_html .= $text;
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

		function nmidSetStylesheet( $stylesheet )
		{
			$this->stylesheet = $stylesheet;
		}

		function nmidGetStylesheet()
		{
			return $this->stylesheet;
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

		function nmidGetReportId()
		{
			return $this->nmid_report_id;
		}

		function nmidSetReportId( $reportId )
		{
			$this->nmid_report_id = $reportId;
		}
	}
