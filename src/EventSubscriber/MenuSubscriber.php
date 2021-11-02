<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Timesheet\TrackingModeService;
use App\Utils\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Menu event subscriber is creating the Kimai default menu structure.
 */
final class MenuSubscriber implements EventSubscriberInterface
{
    private $security;
    private $trackingModeService;

    public function __construct(AuthorizationCheckerInterface $security, TrackingModeService $trackingModeService)
    {
        $this->security = $security;
        $this->trackingModeService = $trackingModeService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMainMenuConfigure', 100],
        ];
    }

    public function onMainMenuConfigure(ConfigureMainMenuEvent $event)
    {
        $auth = $this->security;

        if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        // ------------------- main menu -------------------
        $menu = $event->getMenu();

        $times = new MenuItemModel('timesheet', 'menu.admin_timesheet', 'timesheet', [], 'timesheet');

        if ($auth->isGranted('view_own_timesheet')) {
            $timesheets = new MenuItemModel('timesheet', 'menu.timesheet', 'timesheet', [], 'timesheet');
            $timesheets->setChildRoutes(['timesheet_export', 'timesheet_edit', 'timesheet_create', 'timesheet_multi_update']);
            $times->addChild($timesheets);

            // FIXME which permission?
            //if ($auth->isGranted('create_own_timesheet')) {
            if ($auth->isGranted('edit_own_timesheet')) {
                $mode = $this->trackingModeService->getActiveMode();
                if ($mode->canEditDuration() || $mode->canEditEnd()) {
                    $times->addChild(
                        new MenuItemModel('quick_entry', 'quick_entry.title', 'quick_entry', [], $icons->icon('weekly-times'))
                    );
                }
            }

            $times->addChild(
                new MenuItemModel('calendar', 'calendar.title', 'calendar', [], 'calendar')
            );
        }

        $times->addChild(MenuItemModel::createDivider());

        if ($auth->isGranted('create_export')) {
            $times->addChild(
                new MenuItemModel('export', 'menu.export', 'export', [], 'export')
            );
        }

        if ($auth->isGranted('view_other_timesheet')) {
            $timesheets = new MenuItemModel('timesheet_admin', 'menu.admin_timesheet', 'admin_timesheet', [], 'timesheet-team');
            $timesheets->setChildRoutes(['admin_timesheet_export', 'admin_timesheet_edit', 'admin_timesheet_create', 'admin_timesheet_multi_update']);
            $times->addChild($timesheets);
        }

        if ($times->hasChildren()) {
            $menu->addChild($times);
        }

        $invoice = new MenuItemModel('invoice', 'invoice.title', null, [], 'invoice');
        $invoice->setChildRoutes(['admin_invoice_template', 'admin_invoice_template_edit', 'admin_invoice_template_create', 'admin_invoice_template_copy', 'admin_invoice_list', 'admin_invoice_document_upload']);

        if ($auth->isGranted('view_invoice')) {
            $invoice->addChild(new MenuItemModel('invoice', 'invoice_form.title', 'invoice', [], 'invoice'));
            $invoice->addChild(new MenuItemModel('invoice-listing', 'invoice.title', 'admin_invoice_list', [], 'list'));

            if ($auth->isGranted('manage_invoice_template')) {
                $invoice->addChild(new MenuItemModel('invoice-template', 'admin_invoice_template.title', 'admin_invoice_template', [], 'invoice-template'));
            }

            if ($invoice->hasChildren()) {
                $invoice->addChild(MenuItemModel::createDivider());
            }
        }

        $menu->addChild($invoice);

        if ($auth->isGranted('view_reporting')) {
            $reporting = new MenuItemModel('reporting', 'menu.reporting', 'reporting', [], 'reporting');
            $reporting->setChildRoutes(['report_user_week', 'report_user_month', 'report_weekly_users', 'report_monthly_users', 'report_project_view']);
            $menu->addChild($reporting);
        }

        // ------------------- admin menu -------------------
        $menu = $event->getAdminMenu();

        if ($auth->isGranted('view_customer') || $auth->isGranted('view_teamlead_customer') || $auth->isGranted('view_team_customer')) {
            $customers = new MenuItemModel('customer_admin', 'menu.admin_customer', 'admin_customer', [], 'customer');
            $customers->setChildRoutes(['admin_customer_create', 'admin_customer_permissions', 'customer_details', 'admin_customer_edit', 'admin_customer_delete']);
            $menu->addChild($customers);
        }

        if ($auth->isGranted('view_project') || $auth->isGranted('view_teamlead_project') || $auth->isGranted('view_team_project')) {
            $projects = new MenuItemModel('project_admin', 'menu.admin_project', 'admin_project', [], 'project');
            $projects->setChildRoutes(['admin_project_permissions', 'admin_project_create', 'project_details', 'admin_project_edit', 'admin_project_delete']);
            $menu->addChild($projects);
        }

        if ($auth->isGranted('view_activity') || $auth->isGranted('view_teamlead_activity') || $auth->isGranted('view_team_activity')) {
            $activities = new MenuItemModel('activity_admin', 'menu.admin_activity', 'admin_activity', [], 'activity');
            $activities->setChildRoutes(['admin_activity_create', 'activity_details', 'admin_activity_edit', 'admin_activity_delete']);
            $menu->addChild($activities);
        }

        if ($auth->isGranted('view_tag')) {
            $menu->addChild(
                new MenuItemModel('tags', 'menu.tags', 'tags', [], 'fas fa-tags')
            );
        }

        $menu->addChild(MenuItemModel::createDivider());

        // ------------------- system menu -------------------
        $menu = $event->getSystemMenu();

        if ($auth->isGranted('view_user')) {
            $users = new MenuItemModel('user_admin', 'menu.admin_user', 'admin_user', [], 'users');
            $users->setChildRoutes(['admin_user_create', 'admin_user_delete',  'user_profile', 'user_profile_edit', 'user_profile_password', 'user_profile_api_token', 'user_profile_roles', 'user_profile_teams', 'user_profile_preferences']);
            $menu->addChild($users);
        }

        if ($auth->isGranted('role_permissions')) {
            $users = new MenuItemModel('admin_user_permissions', 'profile.roles', 'admin_user_permissions', [], 'permissions');
            $menu->addChild($users);
        }

        if ($auth->isGranted('view_team')) {
            $teams = new MenuItemModel('user_team', 'menu.admin_team', 'admin_team', [], 'team');
            $teams->setChildRoutes(['admin_team_create', 'admin_team_edit']);
            $menu->addChild($teams);
        }

        if ($auth->isGranted('plugins')) {
            $menu->addChild(
                new MenuItemModel('plugins', 'menu.plugin', 'plugins', [], 'plugin')
            );
        }

        if ($auth->isGranted('system_configuration')) {
            $menu->addChild(
                new MenuItemModel('system_configuration', 'menu.system_configuration', 'system_configuration', [], 'configuration')
            );
        }

        if ($auth->isGranted('system_information')) {
            $menu->addChild(
                new MenuItemModel('doctor', 'menu.doctor', 'doctor', [], 'doctor')
            );
        }

        $menu->addChild(MenuItemModel::createDivider());
    }
}
