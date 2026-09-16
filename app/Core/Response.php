<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    public static function download(string $filePath, string $filename, string $mimeType): never
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new HttpException(404, 'File not found');
        }

        $fileSize = filesize($filePath);
        $safeFilename = str_replace(['"', "\r", "\n"], '', basename($filename));

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        if ($fileSize !== false) {
            header('Content-Length: ' . (string) $fileSize);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        readfile($filePath);
        exit;
    }
}

