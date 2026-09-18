<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Content\{ContentPublisher, ContentVersionRepository};
use Acorn\SafetyHealthcheck\Database\Schema;

final class ContentVersioningTest extends IntegrationTestCase
{
    public function test_draft_clones_questions_and_all_recommendations(): void
    {
        global $wpdb;
        $live = (new ContentVersionRepository())->getPublished();
        $questions = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Schema::table('questions').' WHERE content_version_id=%d',$live['id']));
        $recommendations = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Schema::table('recommendations').' WHERE content_version_id=%d',$live['id']));
        $draft = (new ContentPublisher())->createDraftFromPublished(1);
        self::assertSame($questions,(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Schema::table('questions').' WHERE content_version_id=%d',$draft)));
        self::assertSame($recommendations,(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Schema::table('recommendations').' WHERE content_version_id=%d',$draft)));
    }

    public function test_publish_rejects_unsupported_applicability_operator(): void
    {
        global $wpdb;
        $draft=(new ContentPublisher())->createDraftFromPublished(1);
        $wpdb->update(Schema::table('questions'),['reviewed_by'=>'Reviewer','last_reviewed'=>'2026-09-18','next_review'=>'2027-09-18'],['content_version_id'=>$draft]);
        $wpdb->update(Schema::table('questions'),['applicability_json'=>'{"eval":"danger"}'],['content_version_id'=>$draft,'question_key'=>'M01_COMPETENT_PERSON']);
        $this->expectException(RuntimeException::class);
        (new ContentPublisher())->publish($draft,1);
    }

    public function test_reviewed_complete_draft_can_be_published_and_live_content_is_retired(): void
    {
        global $wpdb;
        $publisher=new ContentPublisher();$draft=$publisher->createDraftFromPublished(1);
        $wpdb->update(Schema::table('questions'),['reviewed_by'=>'Named test reviewer','last_reviewed'=>'2026-09-18','next_review'=>'2027-09-18'],['content_version_id'=>$draft]);
        self::assertSame($draft,$publisher->publish($draft,1));
        self::assertSame((string)$draft,(string)(new ContentVersionRepository())->getPublished()['id']);
        self::assertSame('1',(string)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Schema::table('content_versions').' WHERE status=%s','retired')));
    }
}
