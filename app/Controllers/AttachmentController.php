<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Services\AttachmentService;
use App\Services\TicketPolicy;
use RuntimeException;

final class AttachmentController extends Controller
{
    public function download(Request $request): never
    {
        $attachmentId = filter_var($request->route('id', $request->route('attachment_id')), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($attachmentId === false || $attachmentId === null) {
            throw new HttpException(404, 'Attachment not found');
        }

        $attachmentModel = new Attachment($this->app->database());
        $attachment = $attachmentModel->findById((int) $attachmentId);

        if ($attachment === null) {
            throw new HttpException(404, 'Attachment not found');
        }

        $ticketModel = new Ticket($this->app->database());
        $ticket = $ticketModel->findById((int) $attachment['ticket_id']);

        if ($ticket === null) {
            throw new HttpException(404, 'Ticket not found');
        }

        $policy = new TicketPolicy();
        if (!$policy->canView($this->currentUser(), $ticket)) {
            throw new HttpException(403, 'You do not have permission to download this attachment.');
        }

        $attachmentService = new AttachmentService($attachmentModel);

        try {
            $filePath = $attachmentService->resolveFilePath((string) $attachment['storage_path']);
        } catch (RuntimeException) {
            throw new HttpException(404, 'Attachment file not found on disk');
        }

        Response::download(
            $filePath,
            (string) $attachment['original_filename'],
            (string) $attachment['mime_type']
        );
    }
}
