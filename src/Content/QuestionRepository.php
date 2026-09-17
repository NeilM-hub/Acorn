<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Content;use Acorn\SafetyHealthcheck\Database\Schema;
final class QuestionRepository{public function forVersion(int $v):array{global $wpdb;return $wpdb->get_results($wpdb->prepare('SELECT * FROM '.Schema::table('questions').' WHERE content_version_id=%d AND is_active=1 ORDER BY sort_order',$v),ARRAY_A);}public function get(int $v,string $k):array{global $wpdb;return(array)$wpdb->get_row($wpdb->prepare('SELECT * FROM '.Schema::table('questions').' WHERE content_version_id=%d AND question_key=%s',$v,$k),ARRAY_A);}}
