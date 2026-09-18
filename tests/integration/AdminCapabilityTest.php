<?php
declare(strict_types=1);use Acorn\SafetyHealthcheck\Activation;
final class AdminCapabilityTest extends IntegrationTestCase{public function test_activation_assigns_custom_capability_only_to_administrators():void{Activation::activate();self::assertTrue(get_role('administrator')->has_cap('manage_acorn_healthcheck'));self::assertFalse(get_role('subscriber')->has_cap('manage_acorn_healthcheck'));}}
