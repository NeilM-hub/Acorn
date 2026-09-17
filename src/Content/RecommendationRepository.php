<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Content;use Acorn\SafetyHealthcheck\Database\Schema;
final class RecommendationRepository{public function resolve(int $v,string $k,string $a,string $j):?array{global $wpdb;$r=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.Schema::table('recommendations').' WHERE content_version_id=%d AND question_key=%s AND answer_value=%s AND jurisdiction IN (%s,%s) ORDER BY jurisdiction=%s DESC LIMIT 1',$v,$k,$a,$j,'*',$j),ARRAY_A);return $r?:null;}}
