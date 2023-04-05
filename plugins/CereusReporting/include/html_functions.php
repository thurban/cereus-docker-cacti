<?php
/*******************************************************************************
 * Copyright (c) 2018. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Thomas Urban <ThomasUrban@urban-software.de>, 2018.
 *
 * File:         $Id: html_functions.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
 * Filename:     html_functions.php
 * LastModified: 24/12/18 14:21
 * Modified_On:  $Date: 2020/12/10 07:06:31 $
 * Modified_By:  $Author: thurban $
 *
 ******************************************************************************/

/**
 * Created by PhpStorm.
 * User: da_ph
 * Date: 24/12/2018
 * Time: 14:21
 */

if ( readConfigOption('nmid_pdf_debug') == 5 ) {
    error_reporting( -1 );
} else {
    error_reporting( 0 );
}

function endTable() {
    echo '
            </tr>
        </table>';
}


function startTable() {
    echo '
            </tr>
        </table>';
}

function printEndColumnHTML($pdf) {
    global $graphPerPage;
    $a_column_data = preg_split( "/,/", $graphPerPage );
    $maxColumn = $a_column_data[0];
    return;

    $currentColumn = $pdf->nmidGetCurCol();
    $html = '';
    if ($currentColumn == 1) {
        if ( $maxColumn == 1 ) {
            $html = '<td width="100%"></td></tr></tbody></table>';
        }
        if ( $maxColumn == 2 ) {
            $html = '<td width="50%"></td><td width="50%"></td></tr></tbody></table>';
        }
        if ( $maxColumn == 3 ) {
            $html = '<td width="33%"></td><td width="33%"></td><td width="33%"></td></tr></tbody></table>';
        }
    }  else if ($currentColumn == 2) {
        if ( $maxColumn == 2 ) {
            $html = '<td width="50%"></td></tr></tbody></table>';
        }
        if ( $maxColumn == 3 ) {
            $html = '<td width="33%"></td><td width="33%"></td></tr></tbody></table>';
        }
    }  else if ($currentColumn == 3) {
        if ( $maxColumn == 3 ) {
            $html = '<td width="33%"></td></tr></tbody></table>';
        }
    }

    // Set current column back to the first column
    $pdf->nmidSetCurCol(1);
    $html .= $pdf->nmidGetHtml();
    $pdf->nmidSetHtml('');
    $pdf->writeHTML(  $html, TRUE, FALSE, FALSE, FALSE, '' );
}

function printChapterHTML($pdf, $chapter_text) {
    global $graphPerPage;
    $a_column_data = preg_split( "/,/", $graphPerPage );
    $maxColumn = $a_column_data[0];

    $pdf->writeHTML( $chapter_text );
    return;
    // Close any open Tables
    printEndColumnHTML($pdf);

    $currentColumn = $pdf->nmidGetCurCol();
    $html = '';
    if ($currentColumn == 1) {
        if ( $maxColumn == 1 ) {
            $html  = '<table style="page-break-inside:avoid; autosize:1;" width="100%" border="0">
                            <thead class="myheader">
                                <tr>
                                    <th height="1px"></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr><td width="100%">'.$chapter_text.'</td></tr></tbody></table>';
        }
        if ( $maxColumn == 2 ) {
            $html  .= '<table style="page-break-inside:avoid; autosize:1;" width="100%" border="0">
                            <thead class="myheader">
                                <tr>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr><td colspan="2">'.$chapter_text.'</td></tr></tbody></table>';
        }
        if ( $maxColumn == 3 ) {
            $html  = '<table style="page-break-inside:avoid; autosize:1;" width="100%" border="0">
                            <thead class="myheader">
                                <tr>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr><td colspan="3">'.$chapter_text.'</td></tr></tbody></table>';
        }
    }  else if ($currentColumn == 2) {
        if ( $maxColumn == 2 ) {
            $html  = '<td width="50%"></td></tr></tbody></table>';
            $html .= '<table style="page-break-inside:avoid; autosize:1;" width="100%" border="0">
                            <thead class="myheader">
                                <tr>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr><td colspan="2" width="100%">'.$chapter_text.'</td></tr></tbody></table>';
        }
        if ( $maxColumn == 3 ) {
            $html  = '<td  width="33%"></td><td  width="33%"></td></tr></table>';
            $html .= '<table style="page-break-inside:avoid; autosize:1;" width="100%" border="0">
                            <thead class="myheader">
                                <tr>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr><td colspan="3">'.$chapter_text.'</td></tr></tbody></table>';
        }
    }  else if ($currentColumn == 3) {
        if ( $maxColumn == 3 ) {
            $html  = '<td width="33%"></td></tr></tbody></table>';
            $html .= '<table style="page-break-inside:avoid; autosize:1;" width="100%" border="0">
                            <thead class="myheader">
                                <tr>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                    <th height="1px"></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr><td colspan="3">'.$chapter_text.'</td></tr></tbody></table>';
        }
    }


    // Set current column back to the first column
    $pdf->writeHTML( $pdf->nmidGetHtml() . $html, TRUE, FALSE, FALSE, FALSE, '' );
    $pdf->nmidSetHtml('');
    $pdf->nmidSetCurCol(1);
}

function printGenericTextHTML($pdf, $html_text)
{
    global $graphPerPage, $pdfType;
    $a_column_data = preg_split("/,/", $graphPerPage);
    $maxColumn = $a_column_data[0];

    if ( strlen($html_text) == 0 ) {
        return;
    }
    if ( $pdfType == TCPDF_ENGINE ) {
        $pdf->writeHTML( $html_text, FALSE, FALSE, FALSE, FALSE );
    } else {
        $pdf->writeHTML( $html_text);
    }

    return;

    $currentColumn = $pdf->nmidGetCurCol();
    $html = '';
    CereusReporting_logger( 'DEBUG: Current Column: [' . $currentColumn. '] Max Column: ['.$maxColumn.']', 'debug', 'ReportEngine' );

    if ($currentColumn == 1) {
        if ($maxColumn == 1) {
            $html =  '<table class="print-friendly" cellspacing="2" cellpadding="1" width="100%" border="0" nobr="true">
                            <thead>
                                <tr height="1px">
                                    <th></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr nobr="true"><td width="100%" valing="top" nobr="true" align="center">' . $html_text . '</td></tr></tbody></table>';
            //CereusReporting_logger($html,"debug","PDFCreation");
            $pdf->nmidSetCurCol(1);
            $html = $pdf->nmidGetHtml() . $html;
            $pdf->writeHTML( $html, TRUE, FALSE, FALSE, FALSE, '' );
            $pdf->nmidSetHtml('');
        } else if ($maxColumn == 2) {
            $html =  '<table  class="print-friendly" cellspacing="2" cellpadding="1" width="100%" border="0" nobr="true">
                            <thead>
                                <tr height="1px">
                                    <th></th>
                                    <th></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr nobr="true"><td width="50%" valing="top" align="center">' . $html_text . '</td>';
            $pdf->nmidSetCurCol(2);
            $pdf->nmidSetHtml( $pdf->nmidGetHtml() . $html);
        } else if ($maxColumn == 3) {
            $html =  '<table class="print-friendly"  cellspacing="2" cellpadding="1" width="100%" border="0" nobr="true">
                            <thead>
                                <tr height="1px">
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>                            
                            </thead>
                            <tbody>';
            $html .= '<tr nobr="true"><td width="33%" valing="top" align="center">' . $html_text . '</td>';
            $pdf->nmidSetCurCol(2);
            $pdf->nmidSetHtml( $pdf->nmidGetHtml() . $html);
        }
    }  else if ($currentColumn == 2) {
        if ( $maxColumn == 2 ) {
            $html = '<td width="50%" valing="top">'.$html_text.'</td></tr></tbody></table>';
            $pdf->nmidSetCurCol(1);
            $html = $pdf->nmidGetHtml() . $html;
            $pdf->nmidSetHtml('');
            $pdf->writeHTML(  $html, TRUE, FALSE, FALSE, FALSE, '' );
        }
        else if ($maxColumn == 3) {
            $html = '<td width="33%" valing="top">'.$html_text.'</td>';
            $pdf->nmidSetCurCol( 3 );
            $pdf->nmidSetHtml( $pdf->nmidGetHtml() . $html);
        }
    }  else if ($currentColumn == 3) {
        if ( $maxColumn == 3 ) {
            $html = '<td width="33%" valing="top">'.$html_text.'</td></tr></tbody></table>';
            $pdf->nmidSetCurCol(1);
            $html = $pdf->nmidGetHtml() . $html;
            $pdf->nmidSetHtml('');
            $pdf->writeHTML( $html, TRUE, FALSE, FALSE, FALSE, '' );
        }
    }
}

function check_remaining_height($pdf, $html) {

    global $graphPerPage;

    return;

    $a_column_data = preg_split("/,/", $graphPerPage);
    $maxColumn = $a_column_data[0];
    $currentColumn = $pdf->nmidGetCurCol();

    $nmid_nmid_pdf_margin_left = $pdf->get_pdf_margin_left();
    $nmid_nmid_pdf_margin_right = $pdf->get_pdf_margin_right();
    $orientation = $pdf->nmidGetPageOrientation();
    $pageSize = $pdf->nmidGetPageSize();

    $pdf2 = new PDF( $orientation, 'mm', $pageSize, TRUE, 'ISO-8859-1', FALSE );
    $pdf2->setFontSubsetting(true);
    //set margins
    $pdf2->SetMargins( $nmid_nmid_pdf_margin_left, 0, $nmid_nmid_pdf_margin_right, true );
    $pdf2->SetHeaderMargin( 0 );
    $pdf2->SetFooterMargin( 0 );
    $pdf2->setImageScale( PDF_IMAGE_SCALE_RATIO );
    $pdf2->nmidSetStylesheet( $pdf->nmidGetStylesheet() );
    $pdf2->AddPage();
    $html_plain = preg_replace('/tcpdf/','temp',$html);
    $pdf2->writeHTML( $html_plain, TRUE, FALSE, FALSE, FALSE, '' );
    $cell_height = $pdf2->getY();
    $pdf2->deletePage($pdf2->getPage());

    if ( $cell_height > $pdf->getPageRemainingHeight() ) {
       // if ($currentColumn == 1) {
            printEndColumnHTML($pdf);
            $pdf->AddPage();
       // }
    }
}