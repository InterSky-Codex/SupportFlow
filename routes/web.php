<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\TicketController;
use App\Controllers\CommentController;
use App\Controllers\AttachmentController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Controllers\ReportController;
use App\Controllers\AuditLogController;
use App\Controllers\NotificationController;
use App\Controllers\KnowledgeBaseController;
use App\Middleware\Authenticate;

$router->get('/', [DashboardController::class, 'index'], [Authenticate::class]);
$router->get('/dashboard', [DashboardController::class, 'index'], [Authenticate::class]);
$router->get('/tickets', [TicketController::class, 'index'], [Authenticate::class]);
$router->get('/tickets/create', [TicketController::class, 'create'], [Authenticate::class]);
$router->post('/tickets', [TicketController::class, 'store'], [Authenticate::class]);
$router->get('/tickets/{id}', [TicketController::class, 'show'], [Authenticate::class]);
$router->get('/tickets/{id}/edit', [TicketController::class, 'edit'], [Authenticate::class]);
$router->post('/tickets/{id}', [TicketController::class, 'update'], [Authenticate::class]);
$router->post('/tickets/{id}/assign', [TicketController::class, 'assign'], [Authenticate::class]);
$router->post('/tickets/{id}/status', [TicketController::class, 'transition'], [Authenticate::class]);
$router->post('/tickets/{id}/comments', [CommentController::class, 'store'], [Authenticate::class]);
$router->post('/comments/{id}/delete', [CommentController::class, 'destroy'], [Authenticate::class]);
$router->get('/tickets/{ticket_id}/attachments/{id}', [AttachmentController::class, 'download'], [Authenticate::class]);
$router->get('/attachments/{id}', [AttachmentController::class, 'download'], [Authenticate::class]);

$router->get('/reports', [ReportController::class, 'index'], [Authenticate::class]);
$router->get('/audit-logs', [AuditLogController::class, 'index'], [Authenticate::class]);
$router->get('/knowledge-base', [KnowledgeBaseController::class, 'index'], [Authenticate::class]);
$router->get('/knowledge-base/create', [KnowledgeBaseController::class, 'create'], [Authenticate::class]);
$router->post('/knowledge-base', [KnowledgeBaseController::class, 'store'], [Authenticate::class]);
$router->get('/knowledge-base/{id}', [KnowledgeBaseController::class, 'show'], [Authenticate::class]);
$router->get('/knowledge-base/{id}/edit', [KnowledgeBaseController::class, 'edit'], [Authenticate::class]);
$router->post('/knowledge-base/{id}', [KnowledgeBaseController::class, 'update'], [Authenticate::class]);
$router->get('/notifications', [NotificationController::class, 'index'], [Authenticate::class]);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead'], [Authenticate::class]);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead'], [Authenticate::class]);


$router->get('/users', [UserController::class, 'index'], [Authenticate::class]);
$router->get('/users/create', [UserController::class, 'create'], [Authenticate::class]);
$router->post('/users', [UserController::class, 'store'], [Authenticate::class]);
$router->get('/users/{id}', [UserController::class, 'show'], [Authenticate::class]);
$router->get('/users/{id}/edit', [UserController::class, 'edit'], [Authenticate::class]);
$router->post('/users/{id}', [UserController::class, 'update'], [Authenticate::class]);
$router->post('/users/{id}/activate', [UserController::class, 'activate'], [Authenticate::class]);
$router->post('/users/{id}/deactivate', [UserController::class, 'deactivate'], [Authenticate::class]);
$router->get('/users/{id}/password', [UserController::class, 'showPasswordReset'], [Authenticate::class]);
$router->post('/users/{id}/password', [UserController::class, 'resetPassword'], [Authenticate::class]);

$router->get('/settings', [SettingsController::class, 'index'], [Authenticate::class]);
$router->post('/settings', [SettingsController::class, 'update'], [Authenticate::class]);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout'], [Authenticate::class]);
