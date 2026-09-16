<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;
use App\Services\NotificationService;
use RuntimeException;

final class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        $page = max(1, (int) $request->input('page', 1));
        $listing = $this->service()->paginate((int) $user['id'], $page);

        $this->app->view()->render('notifications/index', $this->layoutData() + [
            'pageTitle' => 'Notifications',
            'listing' => $listing,
        ]);
    }

    public function markRead(Request $request): never
    {
        $this->ensureCsrf($request);
        $id = filter_var($request->route('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new HttpException(404, 'Notification not found.');
        }

        $this->service()->markAsRead((int) $id, (int) $this->currentUser()['id']);
        Response::redirect('/notifications');
    }

    public function markAllRead(Request $request): never
    {
        $this->ensureCsrf($request);
        $this->service()->markAllAsRead((int) $this->currentUser()['id']);
        Response::redirect('/notifications');
    }

    private function service(): NotificationService
    {
        return new NotificationService(new Notification($this->app->database()));
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        return [
            'activeNavigation' => 'notifications',
            'currentUser' => $this->currentUser(),
            'csrfToken' => $this->app->csrf()->token(),
        ];
    }

    private function ensureCsrf(Request $request): void
    {
        try {
            $this->app->csrf()->validate((string) $request->input('_token'));
        } catch (RuntimeException) {
            throw new HttpException(419, 'Your session has expired. Please try again.');
        }
    }
}
