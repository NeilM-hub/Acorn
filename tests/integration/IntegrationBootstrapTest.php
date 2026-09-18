<?php

declare(strict_types=1);

final class IntegrationBootstrapTest extends IntegrationTestCase
{
    public function test_shared_base_class_is_loaded_after_wordpress_test_bootstrap(): void
    {
        self::assertTrue(class_exists(WP_UnitTestCase::class, false));
        self::assertTrue(class_exists(IntegrationTestCase::class, false));
        self::assertTrue(is_subclass_of(IntegrationTestCase::class, WP_UnitTestCase::class));
    }
}
