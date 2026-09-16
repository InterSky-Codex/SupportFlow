<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Comment;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Services\CommentService;
use App\Services\AttachmentService;
use App\Services\TicketPolicy;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Models\Notification;
use App\Services\NotificationService;
use RuntimeException;

final class CommentController extends Controller
{
    public function store(Request $request): never
    {
        $this->ensureCsrf($request);
        $ticket = $this->findTicket($request);

        try {
            $message = (string) $request->input('message');
            $file = $request->file('attachment');
            $this->service()->create($ticket, $this->currentUser(), $message, $file);
            $this->app->session()->flash('success', 'Comment added successfully.');
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
        }

        Response::redirect('/tickets/' . $ticket['id']);
    }

    public function destroy(Request $request): never
    {
        $this->ensureCsrf($request);
        $ticket = $this->findTicket($request);
        
        $commentId = filter_var($request->route('id', $request->input('comment_id')), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        
        if ($commentId === false) {
            $this->app->session()->flash('error', 'Invalid comment ID.');
            Response::redirect('/tickets/' . $ticket['id']);
        }

        try {
            $this->service()->delete($commentId, $ticket, $this->currentUser());
            $this->app->session()->flash('success', 'Comment deleted successfully.');
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
        }

        Response::redirect('/tickets/' . $ticket['id']);
    }

    private function service(): CommentService
    {
        $attachmentModel = new Attachment($this->app->database());
        $attachmentService = new AttachmentService($attachmentModel);
        $auditModel = new AuditLog($this->app->database());
        $auditService = new AuditService($auditModel);
        $notificationService = new NotificationService(new Notification($this->app->database()));

        return new CommentService(
            new Comment($this->app->database()),
            new TicketPolicy(),
            $this->app->database(),
            $attachmentService,
            $auditService,
            $notificationService
        );
    }


    /** @return array<string, mixed> */
    private function findTicket(Request $request): array
    {
        // For /tickets/{id}/comments, ticket ID is in the route.
        // For /comments/{id}/delete, ticket ID should be passed in the POST body.
        $ticketId = filter_var($request->route('id'), FILTER_VALIDATE_INT);
        if (str_starts_with($request->path(), '/comments/')) {
            $ticketId = filter_var($request->input('ticket_id'), FILTER_VALIDATE_INT);
        }

        if ($ticketId === false || $ticketId === null) {
            throw new HttpException(404, 'Ticket not found');
        }

        $ticket = (new Ticket($this->app->database()))->findById((int) $ticketId);

        if ($ticket === null) {
            throw new HttpException(404, 'Ticket not found');
        }

        return $ticket;
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
