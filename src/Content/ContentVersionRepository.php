<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Content;use Acorn\SafetyHealthcheck\Database\Schema;
final class ContentVersionRepository{public function getPublished():array{global $wpdb;return (array)$wpdb->get_row("SELECT * FROM ".Schema::table('content_versions')." WHERE status='published' ORDER BY id DESC LIMIT 1",ARRAY_A);}}
