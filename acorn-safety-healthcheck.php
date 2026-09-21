<?php
/**
 * Plugin Name: Acorn Safety Healthcheck
 * Description: Client-first Health & Safety Healthcheck for Acorn Safety Services.
 * Version: 1.2.3
 * Requires at least: 6.5
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('ACORN_HC_VERSION', '1.2.3');
define('ACORN_HC_FILE', __FILE__);
define('ACORN_HC_DIR', plugin_dir_path(__FILE__));

require ACORN_HC_DIR . 'vendor/autoload.php';

register_activation_hook(ACORN_HC_FILE, [Acorn\SafetyHealthcheck\Activation::class, 'activate']);

add_action('plugins_loaded', static function (): void {
    (new Acorn\SafetyHealthcheck\Plugin())->boot();
});
