<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Admin;final class SettingsPage{public function render():void{if(!current_user_can('manage_acorn_healthcheck'))wp_die('Forbidden',403);echo'<div class="wrap"><h1>Healthcheck Settings</h1></div>';}}
