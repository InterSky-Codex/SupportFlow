<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Services\AuditService;
use App\Services\DashboardService;
use App\Services\TicketPolicy;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $currentUser = $this->currentUser();
        $service = $this->service();
        $recentTicketsResult = $service->recentTickets($currentUser, 5);
        $recentActivities = $service->recentActivities($currentUser, 5);

        $this->app->view()->render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'activeNavigation' => 'dashboard',
            'currentUser' => $currentUser,
            'csrfToken' => $this->app->csrf()->token(),
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'statistics' => $service->statistics($currentUser),
            'recentTickets' => $recentTicketsResult['items'],
            'totalTickets' => $recentTicketsResult['total'],
            'recentActivities' => $recentActivities,
        ]);
    }

    private function service(): DashboardService
    {
        $auditService = new AuditService(new AuditLog($this->app->database()));
        $ticketModel = new Ticket($this->app->database());

        return new DashboardService(
            $this->app->database(),
            new TicketPolicy(),
            $auditService,
            $ticketModel
        );
    }
}
