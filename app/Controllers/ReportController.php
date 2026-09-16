<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Models\Ticket;
use App\Services\ReportPolicy;
use App\Services\ReportService;

final class ReportController extends Controller
{
    public function index(Request $request): void
    {
        $this->ensureAdmin();

        $service = $this->service();
        $ticketModel = new Ticket($this->app->database());

        $filters = $service->sanitizeFilters($request->query());

        $summary = $service->summary($filters);
        $trend = $service->trend($filters);
        $statusDistribution = $service->statusDistribution($filters);
        $priorityDistribution = $service->priorityDistribution($filters);
        $categoryDistribution = $service->categoryDistribution($filters);
        $technicianWorkload = $service->technicianWorkload($filters);

        $this->app->view()->render('reports/index', [
            'pageTitle' => 'Reports & Analytics',
            'activeNavigation' => 'reports',
            'currentUser' => $this->currentUser(),
            'csrfToken' => $this->app->csrf()->token(),
            'filters' => $filters,
            'summary' => $summary,
            'trend' => $trend,
            'statusDistribution' => $statusDistribution,
            'priorityDistribution' => $priorityDistribution,
            'categoryDistribution' => $categoryDistribution,
            'technicianWorkload' => $technicianWorkload,
            'statusOptions' => $ticketModel->options('statuses'),
            'priorityOptions' => $ticketModel->options('priorities'),
            'categoryOptions' => $ticketModel->options('categories'),
            'technicianOptions' => $ticketModel->activeTechnicians(),
        ]);
    }

    private function ensureAdmin(): void
    {
        $policy = new ReportPolicy();
        if (!$policy->canView($this->currentUser())) {
            throw new HttpException(403, 'You do not have permission to access reports.');
        }
    }

    private function service(): ReportService
    {
        return new ReportService($this->app->database());
    }
}
