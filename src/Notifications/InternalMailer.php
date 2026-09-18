<?php

declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Notifications;
use Acorn\SafetyHealthcheck\Assessment\{AssessmentRepository,ContactRepository};use Acorn\SafetyHealthcheck\Reports\ReportDataBuilder;
final class InternalMailer{public function send(int$id):bool{$a=(new AssessmentRepository)->find($id);$c=(new ContactRepository)->find((int)$a['contact_id']);$report=(new ReportDataBuilder)->build($id);$settings=get_option('acorn_hc_settings',[]);$to=$settings['internal_recipient']??get_option('admin_email');$subject='Healthcheck Lead | '.$c['company'].' | '.$a['priority_count'].' Priority Actions'.($c['audit_requested']?' | Audit Requested':'');$data=['assessment'=>$a,'contact'=>$c,'report'=>$report,'admin_url'=>admin_url('admin.php?page=acorn-healthcheck-assessments&assessment='.$id)];ob_start();require ACORN_HC_DIR.'templates/email-internal.php';$body=(string)ob_get_clean();return wp_mail($to,$subject,$body,['Content-Type: text/html; charset=UTF-8']);}}
