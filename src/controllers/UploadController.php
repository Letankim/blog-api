<?php
namespace App\Controllers;

use App\config\settings;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Cloudinary\Cloudinary;

class UploadController
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => settings::get('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => settings::get('CLOUDINARY_API_KEY'),
                'api_secret' => settings::get('CLOUDINARY_API_SECRET'),
            ],
        ]);
    }

public function upload(Request $request, Response $response): Response
{
    try {
        $uploadedFiles = $request->getUploadedFiles();
        $files = $uploadedFiles['files'] ?? $uploadedFiles['file'] ?? [];

        if (empty($files)) {
            throw new HttpBadRequestException($request, 'No files provided.');
        }

        if (!is_array($files)) {
            $files = [$files];
        }

        $results = [];
        $now = new \DateTime();

        foreach ($files as $file) {
            $uploadResult = $this->handleUploadCloudinary($file);

            $results[] = [
                'image_url'   => $uploadResult['url'] ?? null,
                'filename'    => $file->getClientFilename(),
                'size'        => $file->getSize(),
                'mime_type'   => $file->getClientMediaType(),
                'uploaded_at' => $now->format('c')
            ];
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'count' => count($results),
            'data' => $results
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);

    } catch (\Exception $e) {
        $this->logError("General upload error: " . $e->getMessage());

        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
}

   private function handleUploadCloudinary($file): array
{
    try {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \Exception("File upload error: " . $file->getError());
        }

        $tmpFilePath = $file->getFilePath();

        $uploadResult = $this->cloudinary->uploadApi()->upload($tmpFilePath, [
            'folder' => 'products',
            'format' => 'webp',
            'quality' => 'auto'
        ]);

        return [
            'originalName' => $file->getClientFilename(),
            'url' => $uploadResult['secure_url'],
            'public_id' => $uploadResult['public_id']
        ];

    } catch (\Exception $e) {
        $this->logError("Error uploading file {$file->getClientFilename()}: " . $e->getMessage());

        throw new \Exception("Upload failed for file {$file->getClientFilename()}: " . $e->getMessage());
    }
}

private function logError(string $message): void
{
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $file = $logDir . '/upload.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($file, "[$time] $message" . PHP_EOL, FILE_APPEND);
}


    // public function serveImage(Request $request, Response $response, array $args): Response
    // {
    //     $filename = $args['filename'] ?? null;

    //     if (!$filename) {
    //         throw new HttpBadRequestException($request, 'Missing filename.');
    //     }

    //     $filepath = $this->uploadDir . '/' . basename($filename);

    //     if (!file_exists($filepath)) {
    //         throw new HttpNotFoundException($request, 'Image not found.');
    //     }

    //     $finfo = finfo_open(FILEINFO_MIME_TYPE);
    //     $mimeType = finfo_file($finfo, $filepath);
    //     finfo_close($finfo);

    //     if (!preg_match('/^image\/(jpeg|png|webp|gif)$/', $mimeType)) {
    //         throw new HttpNotFoundException($request, 'Invalid image type.');
    //     }

    //     $stream = new Stream(fopen($filepath, 'rb'));

    //     return $response
    //         ->withHeader('Content-Type', $mimeType)
    //         ->withHeader('Content-Length', filesize($filepath))
    //         ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
    //         ->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000))
    //         ->withBody($stream);
    // }

    // private function handleUpload($file): array
    // {
    //     if ($file->getError() !== UPLOAD_ERR_OK) {
    //         return [
    //             'error' => $this->getErrorMessage($file->getError()),
    //             'image_url' => null
    //         ];
    //     }

    //     $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    //     $mime = $file->getClientMediaType();
    //     if (!in_array($mime, $allowed)) {
    //         return [
    //             'error' => 'Only JPEG, PNG, WebP, GIF allowed.',
    //             'image_url' => null
    //         ];
    //     }

    //     $now = new \DateTime();
    //     $timestamp = $now->format('Ymd_His'); 
    //     $random = bin2hex(random_bytes(8)); 
    //     $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
    //     $ext = strtolower($ext);

    //     $filename = "{$timestamp}_{$random}.{$ext}";
    //     $filepath = $this->uploadDir . '/' . $filename;

    //     $file->moveTo($filepath);

    //     $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    //     $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    //     $imageUrl = "{$protocol}://{$host}/api/v1/images/{$filename}";

    //     return [
    //         'image_url' => $imageUrl,
    //         'filename' => $filename,
    //         'size' => $file->getSize(),
    //         'mime_type' => $mime,
    //         'uploaded_at' => $now->format('c')
    //     ];
    // }

    // private function ensureDirectory(string $dir): void
    // {
    //     if (!is_dir($dir)) {
    //         mkdir($dir, 0755, true);
    //     }
    // }

    // private function getErrorMessage(int $code): string
    // {
    //     return match ($code) {
    //         UPLOAD_ERR_INI_SIZE   => 'File too large (upload_max_filesize).',
    //         UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE.',
    //         UPLOAD_ERR_PARTIAL    => 'Partial upload.',
    //         UPLOAD_ERR_NO_FILE    => 'No file sent.',
    //         UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
    //         UPLOAD_ERR_CANT_WRITE => 'Cannot write file.',
    //         default               => 'Upload failed.'
    //     };
    // }

    // private function jsonResponse(Response $response, array $data, int $status = 200): Response
    // {
    //     $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    //     $response->getBody()->write($payload);
    //     return $response
    //         ->withHeader('Content-Type', 'application/json; charset=utf-8')
    //         ->withStatus($status);
    // }
}