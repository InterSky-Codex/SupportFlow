<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserPolicy;
use DateTimeImmutable;

final class AuditLogController extends Controller
{
    private const ACTIONS = [
        AuditService::ACTION_AUTH_LOGIN,
        AuditService::ACTION_AUTH_LOGOUT,
        AuditService::ACTION_AUTH_REGISTER,
        AuditService::ACTION_TICKET_CREATED,
        AuditService::ACTION_TICKET_UPDATED,
        AuditService::ACTION_TICKET_ASSIGNED,
        AuditService::ACTION_TICKET_STATUS_CHANGED,
        AuditService::ACTION_COMMENT_CREATED,
        AuditService::ACTION_COMMENT_DELETED,
        AuditService::ACTION_ATTACHMENT_UPLOADED,
        AuditService::ACTION_ATTACHMENT_DELETED,
    ];

    private const ENTITY_TYPES = ['user', 'ticket', 'comment', 'attachment'];

    public function index(Request $request): void
    {
        $currentUser = $this->currentUser();
        if (!(new UserPolicy())->canManage($currentUser)) {
            throw new HttpException(403, 'You do not have permission to access audit logs.');
        }

        [$filters, $errors] = $this->filters($request->query());
        $page = max(1, (int) ($request->input('page', 1)));
        $listing = $errors === []
            ? (new AuditService(new AuditLog($this->app->database())))->paginate($filters, $page, 15)
            : [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 15,
                'total_pages' => 1,
            ];

        $actors = (new User($this->app->database()))->paginate(
            ['is_active' => 1],
            1,
            100
        )['items'];

        $this->app->view()->render('audit_logs/index', [
            'pageTitle' => 'Audit Logs',
            'activeNavigation' => 'audit-logs',
            'currentUser' => $currentUser,
            'csrfToken' => $this->app->csrf()->token(),
            'filters' => $filters,
            'errors' => $errors,
            'listing' => $listing,
            'actionOptions' => self::ACTIONS,
            'entityOptions' => self::ENTITY_TYPES,
            'actorOptions' => $actors,
        ]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function filters(array $input): array
    {
        $errors = [];
        $from = trim((string) ($input['from'] ?? ''));
        $to = trim((string) ($input['to'] ?? ''));

        foreach (['from' => $from, 'to' => $to] as $field => $value) {
            if ($value !== '' && $this->parseDate($value) === null) {
                $errors[$field] = 'Use a valid date in YYYY-MM-DD format.';
            }
        }

        $action = trim((string) ($input['action'] ?? ''));
        if ($action !== '' && !in_array($action, self::ACTIONS, true)) {
            $errors['action'] = 'Select a valid audit action.';
            $action = '';
        }

        $actorId = trim((string) ($input['actor_id'] ?? ''));
        if ($actorId !== '' && filter_var($actorId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors['actor_id'] = 'Select a valid actor.';
            $actorId = '';
        }

        $entityType = trim((string) ($input['entity_type'] ?? ''));
        if ($entityType !== '' && !in_array($entityType, self::ENTITY_TYPES, true)) {
            $errors['entity_type'] = 'Select a valid entity type.';
            $entityType = '';
        }

        return [[
            'date_from' => $from,
            'date_to' => $to,
            'action' => $action,
            'actor_id' => $actorId,
            'entity_type' => $entityType,
        ], $errors];
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
