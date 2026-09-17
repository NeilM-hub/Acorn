<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Admin;final class ContentPage{public function render():void{if(!current_user_can('manage_acorn_healthcheck'))wp_die('Forbidden',403);echo'<div class="wrap"><h1>Healthcheck Content</h1></div>';}}
