<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Reports;use Acorn\SafetyHealthcheck\Database\Schema;final class ReportAccess{public function findAssessmentId(string $raw):?int{global $wpdb;$id=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.Schema::table('assessments').' WHERE report_token_hash=%s AND status=%s',hash('sha256',$raw),'completed'));return$id?(int)$id:null;}}
