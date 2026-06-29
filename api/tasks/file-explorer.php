<?php
/**
 * FILE PATH: /api/tasks/file-explorer.php
 * Task Mind Board File Explorer
 * Adapted from /api/travelers/file-explorer.php
 *
 * Folder structure: /storage/tasks/{work_sys_id}/{task_sys_id}/
 * SMB:              {SERVER_CUS_PATH}_tasks/{work_sys_id}/{task_sys_id}/
 *
 * GET  ?task_id=THR-A26-TK-0001&action=list&path=
 * GET  ?task_id=...&action=list_folders
 * POST action=create_folder | upload | rename | move | copy | duplicate
 * DELETE action=delete
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/safe_folder_name.php';

class TaskFileExplorerAPI
{
    private PDO    $pdo;
    private string $taskId;
    private string $basePath;
    private string $baseSMBPath;
    private string $taskFolder;
    private string $workFolder;
    private OMV_SMB_Manager $omv;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->omv = new OMV_SMB_Manager();

        if (!isset($_GET['task_id'])) $this->sendError('task_id is required', 400);
        $this->taskId = (string)$_GET['task_id'];
        $this->initTaskDirectory();
    }

    private function initTaskDirectory(): void
    {
        $serverCusPath = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');

        $stmt = $this->pdo->prepare("SELECT sys_id, work_sys_id, workname FROM tasks WHERE sys_id = ? LIMIT 1");
        $stmt->execute([$this->taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$task) $this->sendError('Task not found', 404);

        $this->taskFolder = safeFolderName($task['sys_id']);
        $this->workFolder = safeFolderName($task['work_sys_id']);

        $root = realpath(__DIR__ . '/../../storage/tasks');
        if (!$root) {
            mkdir(__DIR__ . '/../../storage/tasks', 0755, true);
            $root = realpath(__DIR__ . '/../../storage/tasks');
        }

        $this->basePath    = "{$root}/{$this->workFolder}/{$this->taskFolder}";
        $this->baseSMBPath = "{$serverCusPath}_tasks/{$this->workFolder}/{$this->taskFolder}";

        if (!is_dir($this->basePath)) mkdir($this->basePath, 0755, true);
        // Create SMB folder if needed
        $this->omv->create_folder("{$serverCusPath}_tasks/{$this->workFolder}");
        $this->omv->create_folder($this->baseSMBPath);
    }

    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        match ($method) {
            'GET'    => $this->handleGet(),
            'POST'   => $this->handlePost(),
            'DELETE' => $this->handleDelete(),
            default  => $this->sendError('Method not allowed', 405),
        };
    }

    // ── GET ──────────────────────────────────────────────────
    private function handleGet(): void
    {
        $action = $_GET['action'] ?? 'list';
        $path   = $this->sanitizePath($_GET['path'] ?? '');

        match ($action) {
            'list'         => $this->listContents($path),
            'list_folders' => $this->listAllFolders(),
            default        => $this->sendError('Invalid action', 400),
        };
    }

    private function listContents(string $path): void
    {
        $fullPath = $this->getFullPath($path);
        if (!is_dir($fullPath)) {
            echo json_encode(['success' => true, 'currentPath' => $path, 'taskFolder' => $this->taskFolder, 'contents' => []]);
            return;
        }

        $items = [];
        foreach (new DirectoryIterator($fullPath) as $item) {
            if ($item->isDot()) continue;
            $name = $item->getFilename();
            $relPath = $path ? "{$path}/{$name}" : $name;
            $items[] = [
                'name'         => $name,
                'type'         => $item->isDir() ? 'folder' : 'file',
                'size'         => $item->isFile() ? $this->formatBytes($item->getSize()) : '—',
                'sizeBytes'    => $item->isFile() ? $item->getSize() : 0,
                'lastModified' => date('d M Y, H:i', $item->getMTime()),
                'path'         => $relPath,
            ];
        }

        usort($items, fn($a, $b) => $a['type'] === $b['type'] ? strcmp($a['name'], $b['name']) : ($a['type'] === 'folder' ? -1 : 1));

        echo json_encode([
            'success'     => true,
            'currentPath' => $path,
            'taskFolder'  => $this->taskFolder,
            'contents'    => $items,
        ]);
    }

    private function listAllFolders(string $basePath = '', string $relativePath = ''): void
    {
        $folders = [];
        $this->collectFolders($this->basePath, '', $folders);
        echo json_encode(['success' => true, 'folders' => $folders]);
    }

    private function collectFolders(string $base, string $rel, array &$result): void
    {
        foreach (new DirectoryIterator($base) as $item) {
            if ($item->isDot() || !$item->isDir()) continue;
            $name    = $item->getFilename();
            $relPath = $rel ? "{$rel}/{$name}" : $name;
            $result[] = ['name' => $name, 'path' => $relPath];
            $this->collectFolders($item->getPathname(), $relPath, $result);
        }
    }

    // ── POST ─────────────────────────────────────────────────
    private function handlePost(): void
    {
        $isMultipart = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart');
        $action      = $isMultipart ? ($_POST['action'] ?? '') : '';

        if ($action === 'upload' || $isMultipart) { $this->handleUpload(); return; }

        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';

        match ($action) {
            'create_folder' => $this->createFolder($data),
            'rename'        => $this->renameItem($data),
            'move'          => $this->moveItem($data),
            'copy'          => $this->copyItem($data),
            'duplicate'     => $this->duplicateItem($data),
            default         => $this->sendError("Unknown action: {$action}", 400),
        };
    }

    private function handleUpload(): void
    {
        $path       = $this->sanitizePath($_POST['path'] ?? $_POST['target_folder'] ?? '');
        $targetDir  = $this->getFullPath($path);
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $uploaded = [];
        $files    = $_FILES['files'] ?? [];

        // Normalize single vs multiple
        if (!is_array($files['name'])) {
            foreach ($files as $k => $v) $files[$k] = [$v];
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $origName = basename($files['name'][$i]);
            $safeName = $this->uniqueName($targetDir, $origName);
            $dest     = $targetDir . '/' . $safeName;
            if (!move_uploaded_file($files['tmp_name'][$i], $dest)) continue;
            $uploaded[] = $safeName;

            // SMB
            $smbDest = $this->baseSMBPath . ($path ? "/{$path}" : '') . "/{$safeName}";
            $this->omv->paste_file($dest, $smbDest);
        }

        echo json_encode(['success' => true, 'message' => count($uploaded) . ' file(s) uploaded', 'files' => $uploaded]);
    }

    private function createFolder(array $data): void
    {
        $path    = $this->sanitizePath($data['path'] ?? '');
        $name    = safeFolderName($data['name'] ?? '');
        if (!$name) { echo json_encode(['success' => false, 'error' => 'Folder name required']); return; }
        $fullDir = $this->getFullPath($path) . '/' . $name;
        if (!is_dir($fullDir)) mkdir($fullDir, 0755, true);
        $smbDir  = $this->baseSMBPath . ($path ? "/{$path}" : '') . "/{$name}";
        $this->omv->create_folder($smbDir);
        echo json_encode(['success' => true, 'message' => 'Folder created']);
    }

    private function renameItem(array $data): void
    {
        $path    = $this->sanitizePath($data['path'] ?? '');
        $oldName = basename($data['oldName'] ?? '');
        $newName = basename($data['newName'] ?? '');
        if (!$oldName || !$newName) { echo json_encode(['success' => false, 'error' => 'Names required']); return; }
        $dir     = $this->getFullPath($path);
        rename("{$dir}/{$oldName}", "{$dir}/{$newName}");
        $smbDir  = $this->baseSMBPath . ($path ? "/{$path}" : '');
        $this->omv->rename_item("{$smbDir}/{$oldName}", "{$smbDir}/{$newName}");
        echo json_encode(['success' => true]);
    }

    private function moveItem(array $data): void
    {
        $srcPath  = $this->sanitizePath($data['sourcePath'] ?? '');
        $srcName  = basename($data['sourceName'] ?? '');
        $dstPath  = $this->sanitizePath($data['targetPath'] ?? '');
        $dstName  = basename($data['targetName'] ?? $srcName);
        $srcFull  = $this->getFullPath($srcPath) . '/' . $srcName;
        $dstDir   = $this->getFullPath($dstPath);
        if (!is_dir($dstDir)) mkdir($dstDir, 0755, true);
        rename($srcFull, "{$dstDir}/{$dstName}");
        $smbSrc   = $this->baseSMBPath . ($srcPath ? "/{$srcPath}" : '') . "/{$srcName}";
        $smbDst   = $this->baseSMBPath . ($dstPath ? "/{$dstPath}" : '') . "/{$dstName}";
        $this->omv->move_item($smbSrc, $smbDst);
        echo json_encode(['success' => true]);
    }

    private function copyItem(array $data): void
    {
        $srcPath  = $this->sanitizePath($data['sourcePath'] ?? '');
        $srcName  = basename($data['sourceName'] ?? '');
        $dstPath  = $this->sanitizePath($data['targetPath'] ?? '');
        $dstName  = basename($data['targetName'] ?? $srcName);
        $srcFull  = $this->getFullPath($srcPath) . '/' . $srcName;
        $dstDir   = $this->getFullPath($dstPath);
        if (!is_dir($dstDir)) mkdir($dstDir, 0755, true);
        copy($srcFull, "{$dstDir}/{$dstName}");
        $smbSrc   = $this->baseSMBPath . ($srcPath ? "/{$srcPath}" : '') . "/{$srcName}";
        $smbDst   = $this->baseSMBPath . ($dstPath ? "/{$dstPath}" : '') . "/{$dstName}";
        $this->omv->copy_item($smbSrc, $smbDst);
        echo json_encode(['success' => true]);
    }

    private function duplicateItem(array $data): void
    {
        $data['targetPath'] = $data['sourcePath'] ?? '';
        $this->copyItem($data);
    }

    // ── DELETE ────────────────────────────────────────────────
    private function handleDelete(): void
    {
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $path    = $this->sanitizePath($data['path'] ?? '');
        $name    = basename($data['name'] ?? '');
        $fullPath= $this->getFullPath($path) . '/' . $name;
        $smbPath = $this->baseSMBPath . ($path ? "/{$path}" : '') . "/{$name}";

        if (is_dir($fullPath)) {
            $this->deleteDir($fullPath);
            $this->omv->delete_directory($smbPath);
        } else {
            @unlink($fullPath);
            $this->omv->delete_file($smbPath);
        }
        echo json_encode(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────
    private function getFullPath(string $relativePath): string
    {
        $full = $this->basePath . ($relativePath ? '/' . ltrim($relativePath, '/') : '');
        $real = realpath($full);
        if ($real && !str_starts_with($real, $this->basePath)) $this->sendError('Access denied', 403);
        return $full;
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace(['..', "\0", '\\'], '', $path);
        return trim($path, '/');
    }

    private function uniqueName(string $dir, string $name): string
    {
        if (!file_exists("{$dir}/{$name}")) return $name;
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $i    = 1;
        while (file_exists("{$dir}/{$base}_{$i}.{$ext}")) $i++;
        return "{$base}_{$i}.{$ext}";
    }

    private function deleteDir(string $dir): void
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    private function sendError(string $msg, int $code = 400): void
    {
        ob_clean();
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}

(new TaskFileExplorerAPI($pdo))->handleRequest();