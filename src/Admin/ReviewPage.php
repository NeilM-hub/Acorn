<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Admin;

use Acorn\SafetyHealthcheck\Content\{ContentPublisher, QuestionRepository};
use Acorn\SafetyHealthcheck\Database\Schema;

final class ReviewPage
{
    public function render(): void
    {
        if(!current_user_can('manage_acorn_healthcheck')) wp_die('Forbidden',403);
        global $wpdb;
        $draft=$wpdb->get_row("SELECT * FROM ".Schema::table('content_versions')." WHERE status='draft' ORDER BY id DESC LIMIT 1",ARRAY_A);
        if($_SERVER['REQUEST_METHOD']==='POST'){
            check_admin_referer('acorn_hc_review');
            if(!$draft) wp_die('Create a draft before recording review approval.',409);
            (new ContentPublisher())->updateReviewMetadata((int)$draft['id'],sanitize_key($_POST['question_key']??''),sanitize_text_field(wp_unslash($_POST['reviewed_by']??'')),sanitize_text_field(wp_unslash($_POST['last_reviewed']??'')),sanitize_text_field(wp_unslash($_POST['next_review']??'')));
            echo'<div class="notice notice-success"><p>Draft review metadata saved. This does not publish or imply approval of other content.</p></div>';
        }
        echo'<div class="wrap"><h1>Content Review</h1><p><strong>Launch gate:</strong> content is not technically approved until named Acorn reviewers complete these fields and publish the reviewed draft.</p>';
        if(!$draft){echo'<p>No draft exists. Create one on the Content page.</p></div>';return;}
        $questions=(new QuestionRepository())->forVersion((int)$draft['id']);
        echo'<form method="post">';wp_nonce_field('acorn_hc_review');echo'<select name="question_key" required><option value="">Select question…</option>';foreach($questions as$q)echo'<option value="'.esc_attr($q['question_key']).'">'.esc_html($q['question_key'].' — '.$q['question_text']).'</option>';echo'</select><input name="reviewed_by" required placeholder="Named reviewer"><input type="date" name="last_reviewed" required><input type="date" name="next_review" required>';submit_button('Save review metadata','primary','',false);echo'</form><table class="widefat"><thead><tr><th>Question</th><th>Reviewer</th><th>Last reviewed</th><th>Next review</th><th>Status</th></tr></thead><tbody>';
        foreach($questions as$q){$due=empty($q['reviewed_by'])||$q['last_reviewed']==='1970-01-01'||$q['next_review']<=gmdate('Y-m-d');echo'<tr><td>'.esc_html($q['question_key']).'</td><td>'.esc_html($q['reviewed_by']?:'Outstanding').'</td><td>'.esc_html($q['last_reviewed']).'</td><td>'.esc_html($q['next_review']).'</td><td>'.($due?'Review required':'Current').'</td></tr>';}
        echo'</tbody></table></div>';
    }
}
