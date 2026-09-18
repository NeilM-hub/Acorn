<?php

declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Admin;
final class SettingsPage
{
 public function render():void{if(!current_user_can('manage_acorn_healthcheck'))wp_die('Forbidden',403);$defaults=require dirname(__DIR__,2).'/config/settings-defaults.php';if($_SERVER['REQUEST_METHOD']==='POST'){check_admin_referer('acorn_hc_settings');$settings=[];foreach($defaults as$key=>$default)$settings[$key]=is_int($default)?absint($_POST[$key]??$default):sanitize_text_field(wp_unslash($_POST[$key]??$default));update_option('acorn_hc_settings',$settings);echo'<div class="notice notice-success"><p>Settings saved.</p></div>';}$settings=array_merge($defaults,get_option('acorn_hc_settings',[]));echo'<div class="wrap"><h1>Healthcheck Settings</h1><form method="post">';wp_nonce_field('acorn_hc_settings');foreach($settings as$key=>$value){echo'<p><label><strong>'.esc_html(ucwords(str_replace('_',' ',$key))).'</strong><br><input class="regular-text" name="'.esc_attr($key).'" value="'.esc_attr((string)$value).'"></label></p>';}submit_button();echo'</form></div>';}
}
