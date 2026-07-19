<?php
// FILE PATH: api/old_tasks/file-explorer.php
ob_start();
require_once '../../server/env.php';
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/safe_folder_name.php';

if (!function_exists('smbFileUrl')) {
    function smbFileUrl(string $smbPath, bool $download = false): string {
        $ipPort = trim(@file_get_contents(__DIR__ . '/../../server/ippath.txt') ?? '');
        $base   = rtrim($ipPort, '/') . '/api/file/serve.php?smb_token=';
        $secret = function_exists('env') ? env('SMB_TOKEN_SECRET', 'th_smb_s3cr3t_2025') : 'th_smb_s3cr3t_2025';
        $hmac   = hash_hmac('sha256', $smbPath, $secret, true);
        $token  = rtrim(strtr(base64_encode($smbPath . '|' . $hmac), '+/', '-_'), '=');
        return $base . $token . ($download ? '&dl=1' : '');
    }
}

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

class TaskFileExplorerAPI
{
    private PDO            $pdo;
    private OMV_SMB_Manager $omv;
    private string         $taskId;
    private string         $workSysId;
    private string         $clientSysId;
    private string         $clientName;
    private string         $smbBase;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->omv = new OMV_SMB_Manager();

        if (!isset($_GET['task_id'])) $this->err('task_id is required', 400);
        $this->taskId = (string)$_GET['task_id'];
        $this->init();
    }

    private function init(): void
    {
        $stmt = $this->pdo->prepare("
            SELECT t.sys_id, t.work_sys_id,
                   w.client_sys_id, w.client_name
            FROM old_tasks t
            JOIN com_works w ON w.sys_id = t.work_sys_id
            WHERE t.sys_id = ? LIMIT 1
        ");
        $stmt->execute([$this->taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$task) $this->err('Task not found', 404);

        $this->workSysId   = $task['work_sys_id'];
        $this->clientSysId = $task['client_sys_id'] ?? 'UNKNOWN';
        $this->clientName  = $task['client_name']   ?? 'Unknown';

        $prefix = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');

        // CamelCase client name
        $words     = preg_split('/[\s_]+/', trim($task['client_name'] ?? 'Unknown'));
        $camel     = implode('', array_map('ucfirst', $words));
        $camel     = preg_replace('/[^A-Za-z0-9\-]/', '', $camel);
        $clFolder  = ($task['client_sys_id'] ?? 'UNKNOWN') . '_' . $camel;

        $w = safeFolderName($task['work_sys_id']);
        $t = safeFolderName($task['sys_id']);

        // Ensure all SMB dirs exist
        $root = $prefix . '_clients';
        $this->smbMkdir($root, $clFolder);
        $this->smbMkdir("$root/$clFolder", $w);
        $this->smbMkdir("$root/$clFolder/$w", $t);

        // Show task root — files/ and financial/ subfolders are inside
        $this->smbBase = "$root/$clFolder/$w/$t";
    }

    private function smbMkdir(string $parent, string $child): void {
        $r = $this->omv->create_folder("$parent/$child");
        if ($r !== true) {
            if (
                stripos((string)$r, 'NT_STATUS_OBJECT_NAME_COLLISION') === false &&
                stripos((string)$r, 'already exists') === false
            ) {
                error_log("[file-explorer SMB mkdir] $parent/$child → $r");
            }
        }
    }

    public function handleRequest(): void
    {
        match ($_SERVER['REQUEST_METHOD']) {
            'GET'    => $this->handleGet(),
            'POST'   => $this->handlePost(),
            'DELETE' => $this->handleDelete(),
            default  => $this->err('Method not allowed', 405),
        };
    }

    // ── GET: list ─────────────────────────────────────────────
    private function handleGet(): void
    {
        $action = $_GET['action'] ?? 'list';
        $path   = $this->sanitizePath($_GET['path'] ?? '');
        match ($action) {
            'list'         => $this->listContents($path),
            'list_folders' => $this->listFolders($path),
            default        => $this->err('Invalid action'),
        };
    }

    private function listContents(string $path): void
    {
        $smbDir = $this->smbPath($path);
        // List via smbclient ls
        $items  = $this->smbList($smbDir);
        ob_clean(); echo json_encode(['success' => true, 'currentPath' => $path, 'smbBase' => $this->smbBase, 'contents' => $items]);
    }

    private function listFolders(string $path): void
    {
        $smbDir = $this->smbPath($path);
        $all    = $this->smbList($smbDir);
        $folders= array_filter($all, fn($i) => $i['type'] === 'folder');
        ob_clean(); echo json_encode(['success' => true, 'folders' => array_values($folders)]);
    }

    private function smbList(string $smbDir): array
    {
        $raw   = $this->omv->list_directory($smbDir);
        $items = [];
        foreach ($raw as $r) {
            $relPath = $smbDir . '/' . $r['name'];
            $items[] = [
                'name'      => $r['name'],
                'type'      => $r['type'],
                'size'      => $r['type'] === 'folder' ? '—' : $this->fmtBytes($r['sizeBytes']),
                'sizeBytes' => $r['sizeBytes'],
                'path'      => $relPath,
                'smb_token' => $r['type'] === 'file' ? smbFileUrl($relPath) : null,
            ];
        }
        return $items;
    }


    // ── POST ─────────────────────────────────────────────────
    private function handlePost(): void
    {
        $isMultipart = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart');
        if ($isMultipart) { $this->handleUpload(); return; }
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';
        match ($action) {
            'create_folder' => $this->createFolder($data),
            'rename'        => $this->renameItem($data),
            'move'          => $this->moveItem($data),
            'copy'          => $this->copyItem($data),
            'duplicate'     => $this->duplicateItem($data),
            default         => $this->err("Unknown action: {$action}"),
        };
    }

    private function handleUpload(): void
    {
        $path  = $this->sanitizePath($_POST['path'] ?? $_GET['path'] ?? '');
        $files = $_FILES['files'] ?? [];
        if (!is_array($files['name'])) {
            foreach ($files as $k => $v) $files[$k] = [$v];
        }

        // Normalize to array of file entries
        $filesArr = [];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $filesArr[] = [
                'name'     => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'type'     => $files['type'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
        if (empty($filesArr)) {
            echo json_encode(['success' => false, 'error' => 'No valid files']); return;
        }

        // Use task sys_id as base for naming
        $ctx = [
            'client_sys_id' => $this->clientSysId,
            'client_name'   => $this->clientName,
            'work_sys_id'   => $this->workSysId,
            'task_sys_id'   => $this->taskId,
            'module'        => 'files' . ($path ? '/' . $path : ''),
        ];

        $upload = smbUploadFiles($filesArr, $this->taskId, $ctx);

        if ($upload['success']) {
            // Store in tasks.files_json
            $stmt = $this->pdo->prepare("SELECT files_json FROM tasks WHERE sys_id = ? LIMIT 1");
            $stmt->execute([$this->taskId]);
            $existing = json_decode($stmt->fetchColumn() ?: '[]', true) ?? [];
            $merged   = array_values(array_unique(array_merge($existing, $upload['files_json'])));
            $this->pdo->prepare("UPDATE tasks SET files_json = ? WHERE sys_id = ?")
                ->execute([json_encode($merged), $this->taskId]);
        }

        echo json_encode([
            'success' => $upload['success'],
            'message' => count($upload['files_json']) . ' file(s) uploaded',
            'files'   => $upload['files_json'],
            'errors'  => $upload['errors'],
        ]);
    }

    private function createFolder(array $data): void
    {
        $path   = $this->sanitizePath($data['path'] ?? '');
        $name   = safeFolderName($data['name'] ?? '');
        if (!$name) { echo json_encode(['success' => false, 'error' => 'Name required']); return; }
        $r = $this->omv->create_folder($this->smbPath($path) . '/' . $name);
        echo json_encode(['success' => $r === true, 'message' => $r === true ? 'Created' : $r]);
    }

    private function renameItem(array $data): void
    {
        $path    = $this->sanitizePath($data['path'] ?? '');
        $oldName = basename($data['oldName'] ?? '');
        $newName = basename($data['newName'] ?? '');
        $dir     = $this->smbPath($path);
        $r = $this->omv->rename_item("$dir/$oldName", "$dir/$newName");
        echo json_encode(['success' => $r === true]);
    }

    private function moveItem(array $data): void
    {
        $srcPath = $this->sanitizePath($data['sourcePath'] ?? '');
        $srcName = basename($data['sourceName'] ?? '');
        $dstPath = $this->sanitizePath($data['targetPath'] ?? '');
        $dstName = basename($data['targetName'] ?? $srcName);
        $r = $this->omv->move_item(
            $this->smbPath($srcPath) . '/' . $srcName,
            $this->smbPath($dstPath) . '/' . $dstName
        );
        echo json_encode(['success' => $r === true]);
    }

    private function copyItem(array $data): void
    {
        $srcPath = $this->sanitizePath($data['sourcePath'] ?? '');
        $srcName = basename($data['sourceName'] ?? '');
        $dstPath = $this->sanitizePath($data['targetPath'] ?? '');
        $dstName = basename($data['targetName'] ?? $srcName);
        $r = $this->omv->copy_item(
            $this->smbPath($srcPath) . '/' . $srcName,
            $this->smbPath($dstPath) . '/' . $dstName
        );
        echo json_encode(['success' => $r === true]);
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
        $smbPath = $this->smbPath($path) . '/' . $name;
        $isDir   = ($data['type'] ?? '') === 'folder';

        if ($isDir) $this->omv->delete_directory($smbPath);
        else        $this->omv->delete_file($smbPath);

        echo json_encode(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────
    private function smbPath(string $relativePath): string
    {
        $rel = $this->sanitizePath($relativePath);
        return $rel ? $this->smbBase . '/' . $rel : $this->smbBase;
    }

    private function sanitizePath(string $path): string
    {
        return trim(str_replace(['..', "\0", '\\'], '', $path), '/');
    }

    private function fmtBytes(int $b): string
    {
        if ($b < 1024) return "{$b} B";
        if ($b < 1048576) return round($b / 1024, 1) . ' KB';
        return round($b / 1048576, 1) . ' MB';
    }

    private function err(string $msg, int $code = 400): void
    {
        ob_clean(); http_response_code($code);
        echo json_encode(['success' => false, 'error' => $msg]); exit;
    }
}

(new TaskFileExplorerAPI($pdo))->handleRequest();