<?php

namespace App\Services;

use Aws\S3\S3Client;
use Psr\Http\Message\UploadedFileInterface;

class FileStorageService
{
    private string $driver;

    private ?S3Client $s3 = null;

    private string $bucket;

    private string $localRoot;

    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv',
        'application/zip',
    ];

    private const EXTENSION_MIME_MAP = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
        'png' => ['image/png'], 'gif' => ['image/gif'], 'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt' => ['text/plain'], 'csv' => ['text/csv'],
        'zip' => ['application/zip'],
    ];

    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB

    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'sh', 'exe', 'bat', 'cmd', 'pl', 'py', 'rb', 'jsp', 'asp', 'aspx', 'htaccess'];

    public static function validateUpload(UploadedFileInterface $file): ?string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return 'Upload failed with error code: '.$file->getError();
        }

        $size = $file->getSize();
        if ($size === 0) {
            return 'Uploaded file is empty';
        }
        if ($size > self::MAX_FILE_SIZE) {
            return 'File exceeds maximum size of '.(self::MAX_FILE_SIZE / 1024 / 1024).'MB';
        }

        // Reject blocked extensions
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            return 'File extension "'.$ext.'" is not allowed';
        }

        // Verify extension matches expected MIME types
        $clientMime = $file->getClientMediaType();
        if (! in_array($clientMime, self::ALLOWED_MIMES, true)) {
            return 'File type "'.$clientMime.'" is not allowed';
        }
        $expectedMimes = self::EXTENSION_MIME_MAP[$ext] ?? null;
        if ($expectedMimes && ! in_array($clientMime, $expectedMimes, true)) {
            return 'MIME type "'.$clientMime.'" does not match extension "'.$ext.'"';
        }

        // Server-side MIME verification using file content (finfo)
        $stream = $file->getStream();
        if ($stream && $stream->isReadable()) {
            $content = $stream->read(8192);
            $stream->rewind();
            if (function_exists('finfo_buffer')) {
                $detected = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);
                if ($detected && $detected !== 'application/octet-stream') {
                    $allowedByMime = in_array($detected, self::ALLOWED_MIMES, true);
                    $allowedByType = $expectedMimes && in_array($detected, $expectedMimes, true);
                    if (! $allowedByMime || ! $allowedByType) {
                        return 'File content appears to be "'.$detected.'", which is not allowed';
                    }
                }
            }
        }

        return null;
    }

    public function __construct()
    {
        $this->driver = $_ENV['STORAGE_DRIVER'] ?? 'local';
        $this->bucket = $_ENV['R2_BUCKET'] ?? 'avsb-erp';
        $this->localRoot = __DIR__.'/../../uploads';

        if ($this->driver === 'r2') {
            $accountId = $_ENV['R2_ACCOUNT_ID'] ?? '';
            $defaultEndpoint = $accountId
                ? "https://{$accountId}.r2.cloudflarestorage.com"
                : 'http://localhost:9000';

            $hasCustomEndpoint = ! empty($_ENV['R2_ENDPOINT']);
            $this->s3 = new S3Client([
                'version' => 'latest',
                'region' => $_ENV['R2_REGION'] ?? ($hasCustomEndpoint ? 'us-east-1' : 'auto'),
                'endpoint' => $hasCustomEndpoint ? $_ENV['R2_ENDPOINT'] : $defaultEndpoint,
                'use_path_style_endpoint' => ($_ENV['R2_USE_PATH_STYLE'] ?? '') === 'true',
                'credentials' => [
                    'key' => $_ENV['R2_ACCESS_KEY_ID'] ?? '',
                    'secret' => $_ENV['R2_SECRET_ACCESS_KEY'] ?? '',
                ],
            ]);
        }
    }

    private function resolvePath(string $path): string
    {
        $path = str_replace("\0", '', $path);
        $fullPath = $this->localRoot.'/'.ltrim(str_replace('\\', '/', $path), '/');
        $parts = array_filter(explode('/', $fullPath), fn ($p) => $p !== '' && $p !== '.');
        $resolved = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }
        $clean = '/'.implode('/', $resolved);
        $root = rtrim($this->localRoot, '/');
        if ($clean !== $root && ! str_starts_with($clean, $root.'/')) {
            throw new \RuntimeException('Path traversal blocked');
        }

        return $clean;
    }

    public function put(string $path, $body, ?string $mime = null): void
    {
        if ($this->driver === 'r2') {
            $params = [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $body,
            ];
            if ($mime) {
                $params['ContentType'] = $mime;
            }
            $params['CacheControl'] = 'public, max-age=3600';
            $this->s3->putObject($params);
        } else {
            $fullPath = $this->resolvePath($path);
            $dir = dirname($fullPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents($fullPath, $body);
        }
    }

    public function get(string $path): string
    {
        if ($this->driver === 'r2') {
            $result = $this->s3->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return (string) $result['Body'];
        }

        return file_get_contents($this->resolvePath($path));
    }

    public function delete(string $path): void
    {
        if ($this->driver === 'r2') {
            $this->s3->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
        } else {
            $fullPath = $this->resolvePath($path);
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function exists(string $path): bool
    {
        if ($this->driver === 'r2') {
            return $this->s3->doesObjectExist($this->bucket, $path);
        }

        return is_file($this->resolvePath($path));
    }

    public function size(string $path): int
    {
        if ($this->driver === 'r2') {
            $result = $this->s3->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return (int) $result['ContentLength'];
        }

        return filesize($this->resolvePath($path));
    }

    public function putFromFile(string $path, string $sourceFile, ?string $mime = null): void
    {
        if ($this->driver === 'r2') {
            $params = [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'SourceFile' => $sourceFile,
            ];
            if ($mime) {
                $params['ContentType'] = $mime;
            } elseif (function_exists('mime_content_type')) {
                $detected = mime_content_type($sourceFile);
                if ($detected) {
                    $params['ContentType'] = $detected;
                }
            }
            $params['CacheControl'] = 'public, max-age=3600';
            $this->s3->putObject($params);
        } else {
            $fullPath = $this->resolvePath($path);
            $dir = dirname($fullPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            copy($sourceFile, $fullPath);
        }
    }

    public function getPresignedUrl(string $path, int $expiryMinutes = 5, ?string $filename = null): ?string
    {
        if ($this->driver !== 'r2' || ! $this->s3) {
            return null;
        }
        $params = [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ];
        if ($filename) {
            $params['ResponseContentDisposition'] = 'attachment; filename="'.addslashes($filename).'"';
        }
        try {
            $cmd = $this->s3->getCommand('GetObject', $params);
            $request = $this->s3->createPresignedRequest($cmd, "+{$expiryMinutes} minutes");

            return (string) $request->getUri();
        } catch (\Throwable) {
            return null;
        }
    }
}
