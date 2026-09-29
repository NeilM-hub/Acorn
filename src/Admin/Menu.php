<?php

declare(strict_types=1);
namespace Acorn\SafetyHealthcheck\Admin;

final class Menu
{
    public function register(): void
    {
        add_menu_page('Healthcheck','Healthcheck','manage_acorn_healthcheck','acorn-healthcheck',[new DashboardPage(),'render'],'dashicons-shield');
        add_submenu_page('acorn-healthcheck','Assessments','Assessments','manage_acorn_healthcheck','acorn-healthcheck-assessments',[new AssessmentsPage(),'render']);
        add_submenu_page('acorn-healthcheck','Questions & Recommendations','Content','manage_acorn_healthcheck','acorn-healthcheck-content',[new ContentPage(),'render']);
        add_submenu_page('acorn-healthcheck','Content Review','Content Review','manage_acorn_healthcheck','acorn-healthcheck-review',[new ReviewPage(),'render']);
        add_submenu_page('acorn-healthcheck','Landing Page','Landing Page','manage_acorn_healthcheck','acorn-healthcheck-landing',[new LandingPage(),'render']);
        add_submenu_page('acorn-healthcheck','Settings','Settings','manage_acorn_healthcheck','acorn-healthcheck-settings',[new SettingsPage(),'render']);
    }
}
