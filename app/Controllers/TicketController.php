<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Services\TicketPolicy;
use App\Services\TicketWorkflowService;
use App\Services\CommentService;
use App\Models\Comment;
use App\Models\Attachment;
use App\Services\AttachmentService;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Models\Notification;
use App\Services\NotificationService;

final class TicketController extends Controller
{
    public function index(Request $request): void
    {
        $service = $this->service();
        $listing = $service->list($request->query(), $this->currentUser());

        $this->app->view()->render('tickets/index', $this->layoutData() + [
            'listing' => $listing,
            'filters' => $request->query(),
            'options' => $service->formOptions(),
            'successMessage' => $this->app->session()->consumeFlash('success'),
        ]);
    }

    public function create(Request $request): void
    {
        $this->renderForm('Create ticket', '/tickets', [], [], false);
    }

    public function store(Request $request): never
    {
        $this->ensureCsrf($request);
        $input = $request->post();
        $file = $request->file('attachment');
        $errors = $this->service()->validate($input, $file);

        if ($errors !== []) {
            $this->renderForm('Create ticket', '/tickets', $input, $errors, false);
            exit;
        }

        try {
            $ticket = $this->service()->create($input, $this->currentUser(), $file);
            $this->app->session()->flash('success', "{$ticket['ticket_number']} was created successfully.");
            Response::redirect('/tickets/' . $ticket['id']);
        } catch (\RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
            $this->renderForm('Create ticket', '/tickets', $input, [], false);
            exit;
        }
    }

    public function show(Request $request): void
    {
        $ticket = $this->findTicket($request);
        $user = $this->currentUser();

        if (!$this->policy()->canView($user, $ticket)) {
            throw new HttpException(403, 'You do not have permission to view this ticket');
        }

        $attachmentModel = new Attachment($this->app->database());
        $attachmentService = new AttachmentService($attachmentModel);
        $auditModel = new AuditLog($this->app->database());
        $auditService = new AuditService($auditModel);
        $notificationService = new NotificationService(new Notification($this->app->database()));

        $commentService = new CommentService(
            new Comment($this->app->database()),
            $this->policy(),
            $this->app->database(),
            $attachmentService,
            $auditService,
            $notificationService
        );
        $comments = $commentService->listForTicket($ticket, $user);
        $attachments = $attachmentService->listForTicket((int) $ticket['id']);
        $timeline = $auditService->getTimelineForTicket((int) $ticket['id']);

        $this->renderDetail($ticket, [], $comments, $attachments, $timeline);
    }


    public function edit(Request $request): void
    {
        $ticket = $this->findTicket($request);
        $this->ensureCanEdit($ticket);
        $this->renderForm('Edit ticket', '/tickets/update', $ticket, [], true);
    }

    public function update(Request $request): never
    {
        $this->ensureCsrf($request);
        $ticket = $this->findTicket($request);
        $this->ensureCanEdit($ticket);
        $input = $request->post();
        $errors = $this->service()->validate($input);

        if ($errors !== []) {
            $input['id'] = $ticket['id'];
            $this->renderForm('Edit ticket', '/tickets/update', $input, $errors, true);
            exit;
        }

        $this->service()->update((int) $ticket['id'], $input, $this->currentUser());
        $this->app->session()->flash('success', 'Ticket updated successfully.');
        Response::redirect('/tickets');
    }

    public function assign(Request $request): never
    {
        $this->ensureCsrf($request);
        $ticket = $this->findTicket($request);

        try {
            $technicianId = $this->positiveId($request->input('assigned_to'));
            $this->workflow()->assign($ticket, $this->currentUser(), $technicianId);
            $this->app->session()->flash('success', 'Ticket assignment updated.');
        } catch (\RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
        }

        Response::redirect('/tickets/' . $ticket['id']);
    }

    public function transition(Request $request): never
    {
        $this->ensureCsrf($request);
        $ticket = $this->findTicket($request);

        try {
            $statusId = $this->positiveId($request->input('status_id'));
            $this->workflow()->transition($ticket, $this->currentUser(), $statusId);
            $this->app->session()->flash('success', 'Ticket status updated.');
        } catch (\RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
        }

        Response::redirect('/tickets/' . $ticket['id']);
    }

    private function service(): TicketService
    {
        $attachmentModel = new Attachment($this->app->database());
        $attachmentService = new AttachmentService($attachmentModel);
        $auditModel = new AuditLog($this->app->database());
        $auditService = new AuditService($auditModel);
        $notificationService = new NotificationService(new Notification($this->app->database()));

        return new TicketService(
            new Ticket($this->app->database()),
            $this->app->database(),
            $this->policy(),
            $attachmentService,
            $auditService,
            $notificationService
        );
    }

    private function policy(): TicketPolicy
    {
        return new TicketPolicy();
    }

    private function workflow(): TicketWorkflowService
    {
        $auditModel = new AuditLog($this->app->database());
        $auditService = new AuditService($auditModel);
        $notificationService = new NotificationService(new Notification($this->app->database()));

        return new TicketWorkflowService(
            new Ticket($this->app->database()),
            $this->policy(),
            $auditService,
            $notificationService
        );
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        return [
            'activeNavigation' => 'tickets',
            'currentUser' => $this->currentUser(),
            'csrfToken' => $this->app->csrf()->token(),
        ];
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function renderForm(string $title, string $action, array $values, array $errors, bool $editing): void
    {
        $this->app->view()->render('tickets/form', $this->layoutData() + [
            'pageTitle' => $title,
            'action' => $action,
            'values' => $values,
            'errors' => $errors,
            'editing' => $editing,
            'options' => $this->service()->formOptions(),
        ]);
    }

    /** @return array<string, mixed> */
    private function findTicket(Request $request): array
    {
        $ticketId = filter_var($request->route('id', $request->input('id')), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($ticketId === false) {
            throw new HttpException(404, 'Ticket not found');
        }

        $ticket = (new Ticket($this->app->database()))->findById($ticketId);

        if ($ticket === null) {
            throw new HttpException(404, 'Ticket not found');
        }

        return $ticket;
    }

    /** @param array<string, mixed> $ticket */
    private function ensureCanEdit(array $ticket): void
    {
        $user = $this->currentUser();

        if (!$this->policy()->canEdit($user, $ticket)) {
            throw new HttpException(403, 'You do not have permission to edit this ticket');
        }
    }

    /** @param array<string, mixed> $ticket @param array<string, string> $errors @param list<array<string, mixed>> $comments @param list<array<string, mixed>> $attachments @param list<array<string, mixed>> $timeline */
    private function renderDetail(array $ticket, array $errors, array $comments = [], array $attachments = [], array $timeline = []): void
    {
        $user = $this->currentUser();
        $this->app->view()->render('tickets/show', $this->layoutData() + [
            'pageTitle' => $ticket['ticket_number'],
            'ticket' => $ticket,
            'comments' => $comments,
            'attachments' => $attachments,
            'timeline' => $timeline,
            'canEdit' => $this->policy()->canEdit($user, $ticket),
            'canAssign' => $this->policy()->canAssign($user),
            'canComment' => (new CommentService(new Comment($this->app->database()), $this->policy()))->canComment($user, $ticket),
            'transitionStatuses' => $this->workflow()->allowedStatuses($ticket, $user),
            'technicians' => $this->policy()->canAssign($user) ? (new Ticket($this->app->database()))->activeTechnicians() : [],
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
            'errors' => $errors,
        ]);
    }


    private function positiveId(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new \RuntimeException('Select a valid value.');
        }

        return $id;
    }

    private function ensureCsrf(Request $request): void
    {
        try {
            $this->app->csrf()->validate((string) $request->input('_token'));
        } catch (\RuntimeException) {
            throw new HttpException(419, 'Your session has expired. Please try again.');
        }
    }
}
