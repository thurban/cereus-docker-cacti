<?php
	/*******************************************************************************
	 *
	 * File:         $Id: nmid_mail.php,v 412969a4f743 2020/12/10 07:06:31 thurban $
	 * Modified_On:  $Date: 2020/12/10 07:06:31 $
	 * Modified_By:  $Author: thurban $
	 * License:      Commercial
	 * Copyright:    Copyright 2009-2013 by Urban-Software.de / Thomas Urban
	 *******************************************************************************/

    require_once __DIR__ . '/../../vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

	function CereusReporting_sendReport( $reportId, $name, $description, $recipients, $recipientsBcc, $disable, $scheduleId )
	{
		error_reporting(-1);
		global $config;
		$logType = 'POLLER';
		if ( readConfigOption( 'nmid_pdfscheduletype' ) == 'cron' ) {
			$logType = 'CRON';
		}
		$phpBinary                = readConfigOption( "path_php_binary" );
		$s_defaultName            = '';
		$s_defaultDescription     = '';
		$i_defaultIncludeSubDirs  = 0;
		$i_defaultLeafId          = 'not defined';
		$i_defaultReportType      = 0;
		$i_defaultType            = 1;
		$i_defaultTimeInSeconds   = 3600;
		$s_defaultTypeDescription = 'On Demand';

		CereusReporting_logger( "Check for new report to be sent.", "debug", "nmid_mail" );


		$attachments = getDBValue( 'Attachments', 'select Attachments from plugin_nmidCreatePDF_Reports_scheduler where ScheduleId=' . $scheduleId );

		$a_reports =cr_db_fetch_assoc_prepared( "
            SELECT
              `plugin_nmidCreatePDF_Reports_Types`.`TypeId` as type,
              `plugin_nmidCreatePDF_Reports_Types`.`timeInSeconds` as timeInSeconds,
              `plugin_nmidCreatePDF_Reports_Types`.`Description` as typeDescription,
              `plugin_nmidCreatePDF_Reports`.`Description` as Description,
              `plugin_nmidCreatePDF_Reports`.`reportType`,
              `plugin_nmidCreatePDF_Reports`.`leafId`,
              `plugin_nmidCreatePDF_Reports`.`includeSubDirs`,
              `plugin_nmidCreatePDF_Reports`.`Name`
            FROM
              `plugin_nmidCreatePDF_Reports_Types` INNER JOIN
              `plugin_nmidCreatePDF_Reports` ON `plugin_nmidCreatePDF_Reports`.`type` =
              `plugin_nmidCreatePDF_Reports_Types`.`TypeId`
            WHERE ReportId=?",array($reportId) );
		foreach ( $a_reports as $s_report ) {
			$s_defaultName            = $s_report[ 'Name' ];
			$i_defaultTimeInSeconds   = $s_report[ 'timeInSeconds' ];
			$s_defaultDescription     = $s_report[ 'Description' ];
			$i_defaultIncludeSubDirs  = $s_report[ 'includeSubDirs' ]; // 1 = true
			$i_defaultLeafId          = $s_report[ 'leafId' ];
			$i_defaultReportType      = $s_report[ 'reportType' ];
			$i_defaultType            = $s_report[ 'type' ];
			$s_defaultTypeDescription = $s_report[ 'typeDescription' ];
		}

		// Calculate the times
		$s_endTime   = time();
		$s_startTime = time();
		if ( $s_defaultTypeDescription == "On Demand" ) {
			$s_startTime = strtotime( '-1 hour', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "Yesterday" ) {
			# Ticket ID: 7 - Yesterday calculated wrong
			$s_startTime = $s_startTime - 86400;
			$s_startTime = mktime( 0, 0, 0, date( 'm', $s_startTime ), date( 'd', $s_startTime ), date( 'Y', $s_startTime ) );
			$s_endTime   = mktime( 0, 0, 0, date( 'm', $s_endTime ), date( 'd', $s_endTime ), date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "Last Week" ) {
			$s_startTime = $s_startTime - ( ( date( 'N', $s_startTime ) - 1 ) * 86400 );
			$s_endTime   = $s_startTime;
			$s_startTime = $s_startTime - ( 7 * 86400 );
			$s_startTime = mktime( 0, 0, 0, date( 'm', $s_startTime ), date( 'd', $s_startTime ), date( 'Y', $s_startTime ) );
			$s_endTime   = mktime( 0, 0, 0, date( 'm', $s_endTime ), date( 'd', $s_endTime ), date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "Last Month" ) {
			$s_startTime = strtotime( 'last month', $s_startTime );
			$s_startTime = mktime( 0, 0, 0, date( 'm', $s_startTime ), 1, date( 'Y', $s_startTime ) );
			$s_endTime   = mktime( 0, 0, 0, date( 'm', $s_endTime ), 1, date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "Last Year" ) {
			$s_startTime = mktime( 0, 0, 0, 1, 1, date( 'Y', $s_startTime ) - 1 );
			$s_endTime   = mktime( 0, 0, 0, 1, 1, date( 'Y', $s_endTime ) );
		}
		elseif ( $s_defaultTypeDescription == "1 Hour" ) {
			$s_startTime = strtotime( '-1 hour', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Day" ) {
			$s_startTime = strtotime( '-1 day', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Week" ) {
			$s_startTime = strtotime( '-1 week', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Month" ) {
			$s_startTime = strtotime( '-1 month', $s_startTime );
		}
		elseif ( $s_defaultTypeDescription == "1 Year" ) {
			$s_startTime = strtotime( '-1 year', $s_startTime );
		}
		else {
			$minutesLeftToFullHour = ( 60 - date( "M", $s_startTime ) ) * 60;
			$s_endTime             = mktime( date( "H", $s_startTime + $minutesLeftToFullHour ), 0, 0, date( "m", $s_startTime + $minutesLeftToFullHour ), date( "d", $s_startTime + $minutesLeftToFullHour ), date( "Y", $s_startTime + $minutesLeftToFullHour ) );
			$s_startTime           = $s_endTime - $i_defaultTimeInSeconds;
			$s_startTime           = mktime( date( "H", $s_startTime ), 0, 0, date( "m", $s_startTime ), date( "d", $s_startTime ), date( "Y", $s_startTime ) );
		}
		$executeable = $config[ "base_path" ] . '/plugins/CereusReporting/createPDFReport_scheduler.php';

		$lastRunTimeStamp = time();
		if ( $disable ) {
			cr_db_execute( "UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET lastRunDate='$lastRunTimeStamp',Status=0 where `ScheduleId`='$scheduleId'" );
		}
		else {
			cr_db_execute( "UPDATE `plugin_nmidCreatePDF_Reports_scheduler` SET lastRunDate='$lastRunTimeStamp' where `ScheduleId`='$scheduleId'" );
		}

		CereusReporting_logger( 'NMID CereusReporting - CLI: ' . "$phpBinary $executeable $reportId $s_startTime $s_endTime $scheduleId", "info", "nmid_mail" );
		$filedat = sys_get_temp_dir() . '/filename_' . $reportId . "_" . $s_startTime . "_" . $s_endTime . "_" . $scheduleId . ".dat";
		CereusReporting_logger( 'NMID CereusReporting - ReportFile DATA: ' . $filedat, "info", "nmid_mail" );
		exec( "$phpBinary $executeable $reportId $s_startTime $s_endTime $scheduleId $filedat" );
		$reportFileName = preg_replace( "/\r|\n|\s+/", "", file_get_contents( $filedat ) );
		$reportFileName = str_replace( '\\', '\\\\', $reportFileName );
		$reportFileName = str_replace( '"', '', $reportFileName );
		if ( strlen($reportFileName) > 0 ) {
			CereusReporting_logger( 'NMID CereusReporting - ReportFile: [' . $reportFileName . ']', "info", "nmid_mail" );
		}
		else {
			CereusReporting_logger( 'NMID CereusReporting - Error: ReportFile name is empty !', "error", "nmid_mail" );
		}
		$dateFormat = readConfigOption( 'nmid_pdf_dateformat' );
		$subject    = $name . ' from ' . date( $dateFormat, time() );
		// TODO: Make email content customizable
		$body       = $description . "<br>\n<br>\n<hr>Please look at the attached file for the report.<br>\n";
		$mailError  = CereusReporting_send_pdfReport_mail( $recipients, $recipientsBcc, '', $subject, $body, $reportFileName, '', $attachments );
		if ( $mailError ) {
			CereusReporting_logger( 'NMID CereusReporting - Errors: ' . $mailError, "error", "nmid_mail" );
		}
		else {
			CereusReporting_logger( 'NMID CereusReporting - Report [' . $name . '] has been sent out', "notice", "nmid_mail" );
		}

		// Remove Report/Temp files
		unlink( $reportFileName );
		unlink( $filedat );

		return;
	}

	/**
	 * Report Mail function
	 *
	 * @param string $to
	 * @param string $from
	 * @param string $subject
	 * @param string $message
	 * @param string $filename
	 * @param string $headers
	 * @param string $attachments
	 *
	 * @return boolean success
	 */
	function CereusReporting_send_pdfReport_mail( $to, $toBcc, $from, $subject, $message, $filename = '', $headers = '', $attachments = '' )
	{
		global $config;

		$mail = new PHPMailer( TRUE ); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
		$mail->ClearAllRecipients();

		$message = str_replace( '<SUBJECT>', $subject, $message );
		$message = str_replace( '<TO>', $to, $message );
		$message = str_replace( '<FROM>', $from, $message );


		try {
			$how = readConfigOption( "settings_how" );
			if ( $how < 0 || $how > 2 ) {
				$how = 0;
			}
			if ( $how == 0 ) {
				// Default is using PHP internal mail() function
			}
			else if ( $how == 1 ) {
				$mail->IsSendmail();
			}
			else if ( $how == 2 ) {
				$mail->IsSMTP();
				//$mail->SMTPDebug = 3; // SMTP::DEBUG_CONNECTION
				$mail->Host    = readConfigOption( "settings_smtp_host" );
				$mail->Port    = readConfigOption( "settings_smtp_port" );
				if (readConfigOption( "settings_smtp_port" ) == 25) {
                    $mail->SMTPSecure = false;
                    $mail->SMTPAutoTLS = false;
                }
				$smtp_username = readConfigOption( "settings_smtp_username" );
				$smtp_password = readConfigOption( "settings_smtp_password" );
				//$mail->Debugoutput = function ($str, $level) {
                //    CereusReporting_logger( "NMID CereusReporting - Mail Debug: [$str]", "debug", "mail" );
                //};
				if ( $smtp_username ) {
					$mail->SMTPAuth = TRUE;
					$mail->Username = $smtp_username;
					$mail->Password = $smtp_password;
				}
			}

			if ( $from == '' ) {
				$from     = readConfigOption( 'settings_from_email' );
				$fromname = readConfigOption( 'settings_from_name' );
				if ( $from == "" ) {
					if ( isset( $_SERVER[ 'HOSTNAME' ] ) ) {
						$from = 'Cacti@' . $_SERVER[ 'HOSTNAME' ];
					}
					else {
						$from = 'cacti@nmid-plugins.de';
					}
				}
				if ( $fromname == "" ) {
					$fromname = "Cacti";
				}

				$mail->SetFrom( $from, $fromname );
			}
			else {
				$mail->SetFrom( $from, 'Cacti' );
			}

			if ( ( $to == '' ) && ( $toBcc == '' ) ) {
				return "Mailer Error: No <b>TO</b> address set!!<br>If using the <i>Test Mail</i> link, please set the <b>Alert e-mail</b> setting.";
			}

			if ( strlen( $to ) > 2 ) {
				$to = explode( ';', $to );

				foreach ( $to as $t ) {
					$mail->AddAddress( $t );
					CereusReporting_logger( "NMID CereusReporting - Adding TO address: [$t]", "debug", "mail" );
				}
			}


			if ( strlen( $toBcc ) > 2 ) {
				$toBcc = explode( ';', $toBcc );

				foreach ( $toBcc as $t ) {
					$mail->AddBCC( $t );
					CereusReporting_logger( "NMID CereusReporting - Adding BCC address: [$t ]", "debug", "mail" );
				}
			}

			$mail->Subject = $subject;
			clearstatcache();
			if ( isset( $filename ) && !empty( $filename ) && strstr( $message, '<GRAPH>' ) !== 0 ) {
				if ( file_exists( $filename ) ) {
					CereusReporting_logger( "NMID CereusReporting - Attaching: [$filename]", "debug", "mail" );
					$mail->AddAttachment( $filename );
				}
				else {
					CereusReporting_logger( "NMID CereusReporting - ERROR: Attachment not existing: [$filename]", "error", "mail" );
				}
			}

			$a_attachmentFiles = preg_split( "/\n/", $attachments );
			foreach ( $a_attachmentFiles as $s_attachmentFile ) {
				$s_attachmentFile = preg_replace( "/\s+/", "", $s_attachmentFile );
				if ( isset( $s_attachmentFile ) && !empty( $s_attachmentFile ) ) {
					if ( file_exists( $s_attachmentFile ) ) {
						$mail->AddAttachment( $s_attachmentFile );
						CereusReporting_logger( "NMID CereusReporting - Adding attachment: [$s_attachmentFile]", "debug", "mail" );
					}
					else {
						CereusReporting_logger( "NMID CereusReporting - ERROR: Attachment not existing: [$s_attachmentFile]", "error", "mail" );
					}
				}
			}

			$text = array( 'text' => '', 'html' => '' );
			if ( $filename == '' ) {
				$message        = str_replace( '<br>', "\n", $message );
				$message        = str_replace( '<BR>', "\n", $message );
				$message        = str_replace( '</BR>', "\n", $message );
				$text[ 'text' ] = strip_tags( $message );
			}
			else {
				$text[ 'html' ] = $message . '<br>';
				$text[ 'text' ] = strip_tags( str_replace( '<br>', "\n", $message ) );
			}
			$mail->AltBody = $text[ 'text' ]; // optional - MsgHTML will create an alternate automatically
			$mail->MsgHTML( $text[ 'html' ] ); // file_get_contents('contents.html')
			$mail->Send();



		}
		catch ( PHPMailer\PHPMailer\Exception $e ) {
			CereusReporting_logger( "NMID CereusReporting - ERROR: " . $e->errorMessage(), "error", "mail" );
		}
		catch ( \Exception $e ) {
			CereusReporting_logger( "NMID CereusReporting - ERROR: " . $e->getMessage(), "error", "mail" );
		}

		return '';
	}
