<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../server/db_connection.php';

class FileExplorerAPI
{
    private PDO $pdo;
    private string $workId;
    private string $basePath;
    private string $clientFolder;
    private string $workFolder;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        if (!isset($_GET['work_id'])) {
            $this->sendError('Invalid work_id', 400);
        }

        $this->workId = (string) $_GET['work_id'];
        $this->initWorkDirectory();
    }

    private function initWorkDirectory(): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT title, sys_id, client_sys_id, client_name 
             FROM works 
             WHERE sys_id = ? LIMIT 1"
        );
        $stmt->execute([$this->workId]);
        $work = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$work) {
            $this->sendError('Work not found', 404);
        }

        $root = realpath(__DIR__ . '/../storage/clients');
        if (!$root) {
            $this->sendError('Storage root missing', 500);
        }

        $this->clientFolder = 
            preg_replace('/\s+/', '', $work['client_sys_id']) . '_' .
            preg_replace('/\s+/', '', $work['client_name']);

        // $this->workFolder = str_replace(' ', '_', $work['sys_id']) . '+' . str_replace(' ', '_', $work['title']);
        $this->workFolder = str_replace(' ', '_', $work['sys_id']);

        $this->basePath = $root . '/' . $this->clientFolder . '/' . $this->workFolder;
        
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        match ($method) {
            'GET'    => $this->handleGet($action),
            'POST'   => $this->handlePost($action),
            'DELETE' => $this->handleDelete($action),
            default  => $this->sendError('Method not allowed', 405),
        };
    }

    private function handleGet(string $action): void
    {
        $path = $_GET['path'] ?? '';

        if ($action === 'list') {
            $this->listContents($path);
        } else {
            $this->sendError('Invalid action', 400);
        }
    }

    private function handlePost(string $action): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        match ($action) {
            'create_folder' => $this->createFolder($data),
            'rename'        => $this->renameItem($data),
            default         => $this->sendError('Invalid action', 400),
        };
    }

    private function handleDelete(string $action): void
    {
        if ($action !== 'delete') {
            $this->sendError('Invalid action', 400);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $this->deleteItem($data);
    }

    /* ================= FILE OPERATIONS ================= */

    private function listContents(string $relativePath): void
    {
        $dir = $this->safePath($relativePath);

        if (!is_dir($dir)) {
            $this->sendError('Directory not found: ' . $dir, 404);
        }

        $items = [];
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $full = $dir . '/' . $file;
            $items[] = [
                'name' => $file,
                'type' => is_dir($full) ? 'folder' : 'file',
                'size' => is_dir($full) ? '-' : $this->formatSize(filesize($full)),
                'lastModified' => date('Y-m-d H:i:s', filemtime($full)),
                'path' => trim($relativePath . '/' . $file, '/'),
                'icon' => $this->getFileIcon($file),
            ];
        }

        // Sort: folders first, then files
        usort(
            $items,
            function($a, $b) {
                if ($a['type'] === $b['type']) {
                    return strcasecmp($a['name'], $b['name']);
                }
                return $a['type'] === 'folder' ? -1 : 1;
            }
        );

        $this->sendResponse([
            'success' => true,
            'path' => $relativePath,
            'currentPath' => $relativePath,
            'contents' => $items,
            'totalItems' => count($items),
            'clientFolder' => $this->clientFolder,
            'workFolder' => $this->workFolder,
            'displayPath' => '\\storage\\clients\\' . $this->clientFolder . '\\' . $this->workFolder . 
                            ($relativePath ? '\\' . str_replace('/', '\\', $relativePath) : '')
        ]);
    }

    private function createFolder(array $data): void
    {
        $name = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $data['name'] ?? '');
        $path = $data['path'] ?? '';

        if ($name === '') {
            $this->sendError('Folder name required', 400);
        }

        $dir = $this->safePath($path) . '/' . $name;

        if (file_exists($dir)) {
            $this->sendError('Folder already exists', 409);
        }

        if (!mkdir($dir, 0755, true)) {
            $this->sendError('Failed to create folder', 500);
        }

        $this->sendResponse(['success' => true]);
    }

    private function renameItem(array $data): void
    {
        $path = $data['path'] ?? '';
        $old  = $data['oldName'] ?? '';
        $new  = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $data['newName'] ?? '');

        if (!$old || !$new) {
            $this->sendError('Invalid rename data', 400);
        }

        $base = $this->safePath($path);
        $oldPath = $base . '/' . $old;
        $newPath = $base . '/' . $new;

        if (!file_exists($oldPath)) {
            $this->sendError('Source not found', 404);
        }

        if (file_exists($newPath)) {
            $this->sendError('Target already exists', 409);
        }

        if (!rename($oldPath, $newPath)) {
            $this->sendError('Failed to rename', 500);
        }

        $this->sendResponse(['success' => true]);
    }

    private function deleteItem(array $data): void
    {
        $path = $data['path'] ?? '';
        $name = $data['name'] ?? '';

        if (!$name) {
            $this->sendError('Name required', 400);
        }

        $target = $this->safePath($path) . '/' . $name;

        if (!file_exists($target)) {
            $this->sendError('Not found', 404);
        }

        if (is_dir($target)) {
            $this->deleteDirectory($target);
        } else {
            if (!unlink($target)) {
                $this->sendError('Failed to delete file', 500);
            }
        }

        $this->sendResponse(['success' => true]);
    }

    /* ================= SECURITY ================= */
private function safePath(string $relative): string
{
    // 1. Clean up the relative path
    $relative = trim($relative, '/\\');
    
    // 2. Create the absolute path
    $requestedPath = $this->basePath . ($relative ? DIRECTORY_SEPARATOR . $relative : '');
    
    // 3. Resolve the real path (this removes ../ and ./ automatically)
    $realRequestedPath = realpath($requestedPath);
    $realBasePath = realpath($this->basePath);

    // 4. Check if the path actually exists (realpath returns false if not)
    if ($realRequestedPath === false) {
        // If it's a new folder creation, realpath might fail. 
        // In that case, we normalize manually but still check the prefix.
        $realRequestedPath = $this->normalizePathManual($requestedPath);
    }

    // 5. Critical Security Check: Does the requested path start with the base path?
    // We use case-insensitive comparison for Windows compatibility
    if (stripos($realRequestedPath, $realBasePath) !== 0) {
        $this->sendError('Access denied: Path traversal attempt', 403);
    }
    
    return $realRequestedPath;
}

private function normalizePathManual(string $path): string 
{
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $safe = [];
    foreach ($parts as $part) {
        if ($part === '.' || $part === '') continue;
        if ($part === '..') {
            array_pop($safe);
            continue;
        }
        $safe[] = $part;
    }
    return (strpos($path, DIRECTORY_SEPARATOR) === 0 ? DIRECTORY_SEPARATOR : '') . implode(DIRECTORY_SEPARATOR, $safe);
}
    /* ================= HELPERS ================= */

    private function deleteDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    private function getFileIcon(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $pdfExtensions = ['pdf'];
        $archiveExtensions = ['zip', 'rar', 'tar', 'gz', '7z'];
        $documentExtensions = ['doc', 'docx', 'txt', 'rtf', 'odt'];
        $spreadsheetExtensions = ['xls', 'xlsx', 'csv'];
        
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $pdfExtensions)) {
            return 'pdf';
        } elseif (in_array($extension, $archiveExtensions)) {
            return 'archive';
        } elseif (in_array($extension, $documentExtensions)) {
            return 'document';
        } elseif (in_array($extension, $spreadsheetExtensions)) {
            return 'spreadsheet';
        } else {
            return 'file';
        }
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $i = floor(log($bytes, 1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    }

    private function sendResponse(array $data): void
    {
        http_response_code(200);
        echo json_encode($data);
        exit;
    }

    private function sendError(string $msg, int $code): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => false, 
            'error' => $msg,
            'code' => $code
        ]);
        exit;
    }
}

/* ================= RUN ================= */

$api = new FileExplorerAPI($pdo);
$api->handleRequest();