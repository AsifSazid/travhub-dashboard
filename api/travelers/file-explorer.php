<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../server/db_connection.php';

class FileExplorerAPI
{
    private PDO    $pdo;
    private string $travelerId;
    private string $basePath;
    private string $travelerFolder;
    private string $serverCusPath; // ✅ FIX: store as class property

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        if (!isset($_GET['traveler_id'])) {
            $this->sendError('Invalid traveler_id', 400);
        }

        $this->travelerId = (string) $_GET['traveler_id'];
        $this->inittravelerDirectory();
    }

    private function inittravelerDirectory(): void
    {
        // ✅ FIX: Store in class property so other methods can use it
        $this->serverCusPath = trim(file_get_contents('../../server-name.txt'));

        $stmt = $this->pdo->prepare(
            "SELECT name, sys_id 
             FROM travelers 
             WHERE sys_id = ? LIMIT 1"
        );
        $stmt->execute([$this->travelerId]);
        $traveler = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$traveler) {
            $this->sendError('traveler not found', 404);
        }

        $root = realpath(__DIR__ . "/../../storage/travelers");
        // $root = realpath(__DIR__ . "/../storage/{$this->serverCusPath}-travelers");

        if (!$root) {
            $this->sendError('Storage root missing', 500);
        }

        // Traveler folder: "C001_AcmeCorp"
        $this->travelerFolder =
            preg_replace('/\s+/', '', $traveler['sys_id']) . '_' .
            preg_replace('/\s+/', '', $traveler['name']);

        $this->basePath = $root . '/' . $this->travelerFolder;

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    /* ================= REQUEST ROUTER ================= */

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        // $action = $_GET['action'] ?? '';
        $action = $this->resolveAction($method); // আগে ছিল: $_GET['action'] ?? ''


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

        match ($action) {
            'list'         => $this->listContents($path),
            'file_info'    => $this->getFileInfo($path, $_GET['name'] ?? ''),
            'list_folders' => $this->listAllFolders(),
            default        => $this->sendError('Invalid action', 400),
        };
    }

    private function handlePost(string $action): void
    {
        if ($action === 'upload') {
            $this->handleUpload();
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        match ($action) {
            'create_folder' => $this->createFolder($data),
            'rename'        => $this->renameItem($data),
            'move'          => $this->moveItem($data),
            'copy'          => $this->copyItem($data),
            'duplicate'     => $this->duplicateItem($data),
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

    /* ================= FILE UPLOAD ================= */

    private function handleUpload(): void
    {
        $path       = $_POST['path'] ?? '';
        $uploadPath = $this->safePath($path);

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $uploadedFiles = [];
        $errors        = [];

        foreach ($_FILES as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Failed to upload {$file['name']}";
                continue;
            }

            $filename   = $this->sanitizeFilename($file['name']);
            $targetFile = $uploadPath . '/' . $filename;

            // If file exists, rename with timestamp
            if (file_exists($targetFile)) {
                $info       = pathinfo($filename);
                $filename   = $info['filename'] . '_' . time() . '.' . ($info['extension'] ?? '');
                $targetFile = $uploadPath . '/' . $filename;
            }

            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $uploadedFiles[] = $filename;
            } else {
                $errors[] = "Failed to save {$file['name']}";
            }
        }

        if (empty($errors)) {
            $this->sendResponse([
                'success'  => true,
                'message'  => count($uploadedFiles) . ' file(s) uploaded successfully',
                'uploaded' => $uploadedFiles,
            ]);
        } else {
            $this->sendResponse([
                'success'  => false,
                'error'    => implode(', ', $errors),
                'uploaded' => $uploadedFiles,
            ]);
        }
    }

    /* ================= CORE OPERATIONS ================= */

    private function listContents(string $relativePath): void
    {
        $dir = $this->safePath($relativePath);

        if (!is_dir($dir)) {
            $this->sendError('Directory not found: ' . $relativePath, 404);
        }

        $items = [];
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;

            $full    = $dir . '/' . $file;
            $isDir   = is_dir($full);
            $items[] = [
                'name'         => $file,
                'type'         => $isDir ? 'folder' : 'file',
                'size'         => $isDir ? '-' : $this->formatSize(filesize($full)),
                'lastModified' => date('Y-m-d H:i:s', filemtime($full)),
                'path'         => trim($relativePath . '/' . $file, '/'),
                'icon'         => $this->getFileIcon($file),
                'extension'    => $isDir ? '' : strtolower(pathinfo($file, PATHINFO_EXTENSION)),
            ];
        }

        // Sort: folders first, then files alphabetically
        usort($items, function ($a, $b) {
            if ($a['type'] === $b['type']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['type'] === 'folder' ? -1 : 1;
        });

        // ✅ FIX: Use $this->serverCusPath in displayPath
        $this->sendResponse([
            'success'     => true,
            'path'        => $relativePath,
            'currentPath' => $relativePath,
            'contents'    => $items,
            'totalItems'  => count($items),
            'travelerFolder'   => $this->travelerFolder,
            'displayPath'  => "\\storage\\{$this->serverCusPath}-travelers\\" .
                              $this->travelerFolder .
                              ($relativePath ? '\\' . str_replace('/', '\\', $relativePath) : ''),
        ]);
    }

    private function getFileInfo(string $relativePath, string $filename): void
    {
        $baseDir  = $this->safePath($relativePath);
        $filePath = $baseDir . '/' . $filename;

        if (!file_exists($filePath)) {
            $this->sendError('File not found', 404);
        }

        $isDir = is_dir($filePath);

        $this->sendResponse([
            'success' => true,
            'info'    => [
                'name'          => $filename,
                'type'          => $isDir ? 'folder' : 'file',
                'size'          => filesize($filePath),
                'sizeFormatted' => $isDir ? '-' : $this->formatSize(filesize($filePath)),
                'lastModified'  => date('Y-m-d H:i:s', filemtime($filePath)),
                'path'          => trim($relativePath . '/' . $filename, '/'),
                'icon'          => $this->getFileIcon($filename),
                'extension'     => $isDir ? '' : strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                'permissions'   => substr(sprintf('%o', fileperms($filePath)), -4),
                'created'       => date('Y-m-d H:i:s', filectime($filePath)),
            ],
        ]);
    }

    private function listAllFolders(): void
    {
        $folders = [];
        $this->scanFolders($this->basePath, '', $folders);

        usort($folders, fn($a, $b) => strcasecmp($a['path'], $b['path']));

        $this->sendResponse([
            'success' => true,
            'folders' => $folders,
        ]);
    }

    private function scanFolders(string $dir, string $relativePath, array &$folders): void
    {
        if (!is_dir($dir)) return;

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;

            $fullPath = $dir . '/' . $item;
            if (is_dir($fullPath)) {
                $rel       = trim($relativePath . '/' . $item, '/');
                $folders[] = [
                    'name'     => $item,
                    'path'     => $rel,
                    'fullPath' => $fullPath,
                ];
                $this->scanFolders($fullPath, $rel, $folders);
            }
        }
    }

    private function createFolder(array $data): void
    {
        $name = $this->sanitizeFilename($data['name'] ?? '');
        $path = $data['path'] ?? '';

        if (empty($name)) {
            $this->sendError('Folder name required', 400);
        }

        $dir = $this->safePath($path) . '/' . $name;

        if (file_exists($dir)) {
            $this->sendError('Folder already exists', 409);
        }

        if (!mkdir($dir, 0755, true)) {
            $this->sendError('Failed to create folder', 500);
        }

        $this->sendResponse(['success' => true, 'message' => 'Folder created successfully']);
    }

    private function renameItem(array $data): void
    {
        $path    = $data['path'] ?? '';
        $oldName = $data['oldName'] ?? '';
        $newName = $this->sanitizeFilename($data['newName'] ?? '');

        if (empty($oldName) || empty($newName)) {
            $this->sendError('Both old and new names are required', 400);
        }

        $baseDir = $this->safePath($path);
        $oldPath = $baseDir . '/' . $oldName;
        $newPath = $baseDir . '/' . $newName;

        if (!file_exists($oldPath)) {
            $this->sendError('Source not found', 404);
        }

        if (file_exists($newPath)) {
            $this->sendError('Target already exists', 409);
        }

        if (!rename($oldPath, $newPath)) {
            $this->sendError('Failed to rename', 500);
        }

        $this->sendResponse(['success' => true, 'message' => 'Item renamed successfully']);
    }

    private function moveItem(array $data): void
    {
        $sourcePath = $data['sourcePath'] ?? '';
        $sourceName = $data['sourceName'] ?? '';
        $targetPath = $data['targetPath'] ?? '';
        $targetName = $this->sanitizeFilename($data['targetName'] ?? $sourceName);

        if (empty($sourceName)) {
            $this->sendError('Source name is required', 400);
        }

        $sourceFull = $this->safePath($sourcePath) . '/' . $sourceName;
        $targetFull = $this->safePath($targetPath) . '/' . $targetName;

        if (!file_exists($sourceFull)) {
            $this->sendError('Source not found', 404);
        }

        if (file_exists($targetFull)) {
            $this->sendError('Target already exists', 409);
        }

        // Prevent moving a folder into itself
        if (is_dir($sourceFull) && strpos($targetFull . '/', $sourceFull . '/') === 0) {
            $this->sendError('Cannot move a folder into itself', 400);
        }

        if (!rename($sourceFull, $targetFull)) {
            $this->sendError('Failed to move item', 500);
        }

        $this->sendResponse(['success' => true, 'message' => 'Item moved successfully']);
    }

    private function copyItem(array $data): void
    {
        $sourcePath = $data['sourcePath'] ?? '';
        $sourceName = $data['sourceName'] ?? '';
        $targetPath = $data['targetPath'] ?? '';
        $targetName = $this->sanitizeFilename($data['targetName'] ?? $sourceName);

        $sourceFull = $this->safePath($sourcePath) . '/' . $sourceName;
        $targetFull = $this->safePath($targetPath) . '/' . $targetName;

        if (!file_exists($sourceFull)) {
            $this->sendError('Source not found', 404);
        }

        if (file_exists($targetFull)) {
            $this->sendError('Target already exists', 409);
        }

        $success = is_dir($sourceFull)
            ? $this->copyDirectory($sourceFull, $targetFull)
            : copy($sourceFull, $targetFull);

        if (!$success) {
            $this->sendError('Failed to copy item', 500);
        }

        $this->sendResponse(['success' => true, 'message' => 'Item copied successfully']);
    }

    private function duplicateItem(array $data): void
    {
        $sourcePath = $data['sourcePath'] ?? '';
        $sourceName = $data['sourceName'] ?? '';
        $targetName = $this->sanitizeFilename($data['targetName'] ?? '');

        $sourceFull = $this->safePath($sourcePath) . '/' . $sourceName;

        if (!file_exists($sourceFull)) {
            $this->sendError('Source not found', 404);
        }

        // Auto-generate duplicate name if not provided
        if (empty($targetName)) {
            $ext           = pathinfo($sourceName, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($sourceName, PATHINFO_FILENAME);
            $targetName    = $nameWithoutExt . ' - Copy' . ($ext ? '.' . $ext : '');

            $counter = 1;
            while (file_exists($this->safePath($sourcePath) . '/' . $targetName)) {
                $targetName = $nameWithoutExt . ' - Copy (' . $counter . ')' . ($ext ? '.' . $ext : '');
                $counter++;
            }
        }

        $targetFull = $this->safePath($sourcePath) . '/' . $targetName;

        if (file_exists($targetFull)) {
            $this->sendError('Target already exists', 409);
        }

        $success = is_dir($sourceFull)
            ? $this->copyDirectory($sourceFull, $targetFull)
            : copy($sourceFull, $targetFull);

        if (!$success) {
            $this->sendError('Failed to duplicate item', 500);
        }

        $this->sendResponse(['success' => true, 'message' => 'Item duplicated successfully']);
    }

    private function deleteItem(array $data): void
    {
        $path = $data['path'] ?? '';
        $name = $data['name'] ?? '';

        if (empty($name)) {
            $this->sendError('Name is required', 400);
        }

        $target = $this->safePath($path) . '/' . $name;

        if (!file_exists($target)) {
            $this->sendError('Item not found', 404);
        }

        $success = is_dir($target) ? $this->deleteDirectory($target) : unlink($target);

        if (!$success) {
            $this->sendError('Failed to delete item', 500);
        }

        $this->sendResponse(['success' => true, 'message' => 'Item deleted successfully']);
    }

    /* ================= HELPER FUNCTIONS ================= */

    private function copyDirectory(string $source, string $dest): bool
    {
        if (!is_dir($source)) return false;

        if (!is_dir($dest)) {
            if (!mkdir($dest, 0755, true)) return false;
        }

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;

            $srcFile  = $source . '/' . $file;
            $destFile = $dest . '/' . $file;

            $ok = is_dir($srcFile)
                ? $this->copyDirectory($srcFile, $destFile)
                : copy($srcFile, $destFile);

            if (!$ok) {
                closedir($dir);
                return false;
            }
        }
        closedir($dir);

        return true;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) return false;

        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    private function safePath(string $relative): string
    {
        $relative        = trim($relative, '/\\');
        $requestedPath   = $this->basePath . ($relative ? DIRECTORY_SEPARATOR . $relative : '');
        $realRequestedPath = realpath($requestedPath);

        if ($realRequestedPath === false) {
            $realRequestedPath = $this->normalizePathManual($requestedPath);
        }

        $realBasePath = realpath($this->basePath);

        if (stripos($realRequestedPath, $realBasePath) !== 0) {
            $this->sendError('Access denied: Path traversal attempt', 403);
        }

        return $realRequestedPath;
    }

    private function normalizePathManual(string $path): string
    {
        $path  = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $safe  = [];

        foreach ($parts as $part) {
            if ($part === '.' || $part === '') continue;
            if ($part === '..') { array_pop($safe); continue; }
            $safe[] = $part;
        }

        return (str_starts_with($path, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '')
            . implode(DIRECTORY_SEPARATOR, $safe);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^\p{L}\p{N}\s\.\-_]/u', '', $filename);
        $filename = preg_replace('/\s+/', ' ', $filename);
        return trim($filename);
    }

    private function getFileIcon(string $file): string
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']) => 'image',
            in_array($ext, ['pdf'])                                              => 'pdf',
            in_array($ext, ['zip', 'rar', 'tar', 'gz', '7z'])                  => 'archive',
            in_array($ext, ['doc', 'docx', 'txt', 'rtf', 'odt'])               => 'document',
            in_array($ext, ['xls', 'xlsx', 'csv'])                              => 'spreadsheet',
            in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'])                       => 'audio',
            in_array($ext, ['mp4', 'avi', 'mov', 'wmv', 'flv'])                => 'video',
            $ext === ''                                                          => 'folder',
            default                                                              => 'file',
        };
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $i     = floor(log($bytes, 1024));
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
        echo json_encode(['success' => false, 'error' => $msg, 'code' => $code]);
        exit;
    }
    
    private function resolveAction(string $method): string
    {
        // সবসময় URL param আগে চেক করো
        if (!empty($_GET['action'])) {
            return $_GET['action'];
        }
    
        // POST/DELETE এ JSON body থেকেও action নাও
        if (in_array($method, ['POST', 'DELETE'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!empty($data['action'])) {
                return $data['action'];
            }
        }
    
        return '';
    }

}

/* ================= RUN ================= */

$api = new FileExplorerAPI($pdo);
$api->handleRequest();