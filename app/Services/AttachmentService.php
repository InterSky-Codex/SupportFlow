<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use RuntimeException;
use ZipArchive;

final class AttachmentService
{
    public const MAX_SIZE_BYTES = 10485760; // 10 MB

    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'pdf',
        'docx',
        'xlsx',
    ];

    private const MIME_MAP = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip',
        ],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip',
        ],
    ];

    /** @var list<string> */
    private array $trackedFiles = [];

    private readonly string $storagePath;

    public function __construct(
        private readonly Attachment $attachments,
        ?string $storagePath = null,
    ) {
        $this->storagePath = $storagePath ?? (defined('BASE_PATH') ? BASE_PATH . '/storage/uploads/tickets' : dirname(__DIR__, 2) . '/storage/uploads/tickets');
    }

    public function storageDirectory(): string
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }

        return $this->storagePath;
    }

    /** @param array<string, mixed> $file @return array<string, string> */
    public function validateUpload(?array $file): array
    {
        $errors = [];

        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return $errors;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['attachment'] = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the 10 MB limit.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
                default => 'Failed to upload the file. Please try again.',
            };
            return $errors;
        }

        $originalName = (string) ($file['name'] ?? '');
        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);

        if ($originalName === '' || $tmpName === '' || (!is_uploaded_file($tmpName) && !file_exists($tmpName))) {
            $errors['attachment'] = 'Invalid upload file received.';
            return $errors;
        }

        if ($size <= 0) {
            $errors['attachment'] = 'The uploaded file is empty.';
            return $errors;
        }

        if ($size > self::MAX_SIZE_BYTES || filesize($tmpName) > self::MAX_SIZE_BYTES) {
            $errors['attachment'] = 'The attachment must not exceed 10 MB.';
            return $errors;
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $errors['attachment'] = 'Only JPG, JPEG, PNG, PDF, DOCX, and XLSX files are allowed.';
            return $errors;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpName);

        if ($detectedMime === false || !is_string($detectedMime)) {
            $errors['attachment'] = 'Could not determine the file type.';
            return $errors;
        }

        $allowedMimes = self::MIME_MAP[$extension] ?? [];
        if (!in_array($detectedMime, $allowedMimes, true)) {
            $errors['attachment'] = 'The file contents do not match the expected file type.';
            return $errors;
        }

        if ($extension === 'docx' || $extension === 'xlsx') {
            if (!$this->validateOoxmlContainer($tmpName, $extension)) {
                $errors['attachment'] = "The uploaded {$extension} file is malformed or corrupted.";
                return $errors;
            }
        }

        return $errors;
    }

    /**
     * Stores the uploaded file to disk and persists database record.
     * 
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public function storeAndCreate(int $ticketId, ?int $commentId, int $userId, array $file): array
    {
        $validationErrors = $this->validateUpload($file);
        if ($validationErrors !== []) {
            throw new RuntimeException($validationErrors['attachment'] ?? 'File validation failed.');
        }

        $originalName = basename(str_replace(["\0", "\r", "\n"], '', (string) $file['name']));
        $tmpName = (string) $file['tmp_name'];
        $size = (int) $file['size'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) ($finfo->file($tmpName) ?: 'application/octet-stream');

        // Generate a cryptographically secure, unique storage filename
        $storedFilename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
        $destinationPath = $this->storageDirectory() . '/' . $storedFilename;


        $moved = is_uploaded_file($tmpName)
            ? move_uploaded_file($tmpName, $destinationPath)
            : copy($tmpName, $destinationPath);

        if (!$moved) {
            throw new RuntimeException('Failed to save the attachment to disk.');
        }


        $this->trackedFiles[] = $destinationPath;

        $attachmentId = $this->attachments->create([
            'ticket_id' => $ticketId,
            'comment_id' => $commentId,
            'user_id' => $userId,
            'original_filename' => $originalName,
            'storage_path' => $storedFilename,
            'file_size' => $size,
            'mime_type' => $mimeType,
        ]);

        $created = $this->attachments->findById($attachmentId);
        if ($created === null) {
            throw new RuntimeException('Failed to load created attachment record.');
        }

        return $created;
    }

    public function resolveFilePath(string $storagePath): string
    {
        $safeStorageFilename = basename(str_replace(["\0", "\r", "\n", '..', '/'], '', $storagePath));
        $fullPath = $this->storageDirectory() . '/' . $safeStorageFilename;

        if (!is_file($fullPath)) {
            throw new RuntimeException('The attachment file could not be found on disk.');
        }

        return $fullPath;
    }

    public function deleteAttachmentFile(string $storagePath): void
    {
        try {
            $path = $this->resolveFilePath($storagePath);
            if (file_exists($path)) {
                unlink($path);
            }
        } catch (\Throwable) {
            // Ignore if file doesn't exist
        }
    }

    public function cleanupTrackedFiles(): void
    {
        foreach ($this->trackedFiles as $filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        $this->trackedFiles = [];
    }

    /** @return list<array<string, mixed>> */
    public function listForTicket(int $ticketId): array
    {
        return $this->attachments->findByTicketId($ticketId);
    }

    private function validateOoxmlContainer(string $tmpPath, string $extension): bool
    {
        if (!class_exists(ZipArchive::class)) {
            return false;
        }

        $zip = new ZipArchive();
        $status = $zip->open($tmpPath);
        if ($status !== true) {
            return false;
        }

        $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;
        $hasModuleDir = false;

        $targetPrefix = $extension === 'docx' ? 'word/' : 'xl/';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename !== false && str_starts_with($filename, $targetPrefix)) {
                $hasModuleDir = true;
                break;
            }
        }

        $zip->close();

        return $hasContentTypes && $hasModuleDir;
    }
}
