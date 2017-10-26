<?php

class CRM_Airmail_Utils_Ses extends CRM_Airmail_Utils {

  /**
   * Process Events from Amazon SNS on behalf of Amazon SES
   * @param  object $events json decoded object sent from SES
   */
  public function getNotifications($events) {
    //  If the message is to confirm subscription to SNS
    if ($events->Type == 'SubscriptionConfirmation' && !empty($events->SubscribeURL)) {
      // Go to the subscribe URL to confirm end point
      // TODO parse the xml and save the info to civi just in case
      $snsResponse == file_get_contents($events->SubscribeURL);
    }
    CRM_Core_Error::debug_log_message('getNotifcations ses', FALSE, 'AirmailWebhook');
    // If the message is a notification of a mailing event
    if ($events->Type == 'Notification') {
      $responseMessage = json_decode($events->Message);
      self::processNotification($responseMessage->mail->source, $responseMessage->notificationType, $responseMessage);
    }
  }

  public function processNotification($source = NULL, $type = NULL, $extra = NULL) {
    if ($source) {
      $mailingJobInfo = self::parseSourceString($source);
      if (!empty($type) && !empty($mailingJobInfo) && !empty($mailingJobInfo['job_id'])) {
        switch ($type) {
          // NOTE there are other Event Types including "Reject", "Send", "Delivery", "Click", "Open", and "Rendering Failure" which we are not currently addressing
          case 'Bounce':
            $body = "Bounce Description: {$extra->bounce->bounceType} {$extra->bounce->bounceSubType}";
            self::bounce($mailingJobInfo['job_id'], $mailingJobInfo['event_queue_id'], $mailingJobInfo['hash'], $body);
            break;

          case 'Complaint':
            // TODO opt out contact entirely
            self::spamreport($mailingJobInfo['job_id'], $mailingJobInfo['event_queue_id'], $mailingJobInfo['hash']);
            break;

          default:
            # code...
            break;
        }
      }
    }
  }

}