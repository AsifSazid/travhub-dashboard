<?php
/**
 * FILE PATH: /api/works/file-explorer.php
 * Work File Explorer — SMB only, no local storage
 *
 * SMB path (root of the work folder):
 *   dev_clients/{clientSysId}_{ClientName}/{work_sys_id}/
 *
 * GET  ?work_id=THR-CW-26-00K001&action=list&path=
 * GET  ?work_id=...&action=list_folders
 * POST action=create_folder | upload | rename | move | copy | duplicate
 * DELETE action=delete
 */

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
require_once '../../server/db_connection.php';
require_once '../../server/live_storage.php';
require_once '../../server/safe_folder_name.php';
require_once '../../server/smb_upload_handler.php';

class WorkFileExplorerAPI
{
    private PDO             $pdo;
    private OMV_SMB_Manager $omv;
    private string          $workId;
    private string          $clientSysId;
    private string          $clientName;
    private string          $smbBase;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->omv = new OMV_SMB_Manager();

        if (!isset($_GET['work_id'])) $this->err('work_id is required', 400);
        $this->workId = (string)$_GET['work_id'];
        $this->init();
    }

    private function init(): void
    {
        // Get client info from works table
        $stmt = $this->pdo->prepare("
            SELECT w.sys_id, w.client_sys_id, c.name AS client_name
            FROM works w
            LEFT JOIN clients c ON c.sys_id = w.client_sys_id
            WHERE w.sys_id = ? LIMIT 1
        ");
        $stmt->execute([$this->workId]);
        $work = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$work) $this->err('Work not found', 404);

        $this->clientSysId = $work['client_sys_id'] ?? 'UNKNOWN';
        $this->clientName  = $work['client_name']   ?? 'Unknown';

        $prefix = trim(@file_get_contents(__DIR__ . '/../../server-name.txt') ?? '');

        // CamelCase client name
        $words    = preg_split('/[\s_]+/', trim($this->clientName));
        $camel    = implode('', array_map('ucfirst', $words));
        $camel    = preg_replace('/[^A-Za-z0-9\-]/', '', $camel);
        $clFolder = $this->clientSysId . '_' . $camel;

        $w    = safeFolderName($this->workId);
        $root = $prefix . '_clients';

        // Ensure SMB dirs exist
        $this->smbMkdir($root, $clFolder);
        $this->smbMkdir("$root/$clFolder", $w);

        // Root of work folder — shows everything inside (tasks/, financial/, etc.)
        $this->smbBase = "$root/$clFolder/$w";
    }

    private function smbMkdir(string $parent, string $child): void
    {
        $r = $this->omv->create_folder("$parent/$child");
        if ($r !== true &&
            stripos((string)$r, 'NT_STATUS_OBJECT_NAME_COLLISION') === false &&
            stripos((string)$r, 'already exists') === false) {
            error_log("[WorkFileExplorer smbMkdir] $parent/$child → $r");
        }
    }

    public function handle(): void
    {
        match ($_SERVER['REQUEST_METHOD']) {
            'GET'    => $this->handleGet(),
            'POST'   => $this->handlePost(),
            'DELETE' => $this->handleDelete(),
            default  => $this->err('Method not allowed', 405),
        };
    }

    // ── GET ───────────────────────────────────────────────────
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
        $items  = $this->smbList($smbDir);
        echo json_encode([
            'success'     => true,
            'currentPath' => $path,
            'smbBase'     => $this->smbBase,
            'workId'      => $this->workId,
            'contents'    => $items,
        ]);
    }

    private function listFolders(string $path): void
    {
        $smbDir  = $this->smbPath($path);
        $all     = $this->smbList($smbDir);
        $folders = array_filter($all, fn($i) => $i['type'] === 'folder');
        echo json_encode(['success' => true, 'folders' => array_values($folders)]);
    }

    private function smbList(string $smbDir): array
    {
        $items = [];
        $host  = env('SMB_HOST', '103.104.219.3');
        $user  = env('SMB_USER', 'travhub');
        $pass  = env('SMB_PASSWORD', 'travhub@2025');
        $share = env('SMB_SHARE', 'travhub');
        $cmd   = "smbclient //{$host}/{$share} -U {$user}%{$pass} -c 'ls \"{$smbDir}\"' 2>&1";
        exec($cmd, $output);

        foreach ($output as $line) {
            if (preg_match('/^\s+(.+?)\s+(D|A)\s+(\d+)\s+(.+)$/', $line, $m)) {
                $name = trim($m[1]);
                if ($name === '.' || $name === '..') continue;
                $isDir   = $m[2] === 'D';
                $relPath = $smbDir . '/' . $name;
                $items[] = [
                    'name'      => $name,
                    'type'      => $isDir ? 'folder' : 'file',
                    'size'      => $isDir ? '—' : $this->fmtBytes((int)$m[3]),
                    'sizeBytes' => (int)$m[3],
                    'path'      => $relPath,
                    'smb_token' => $isDir ? null : smbFileUrl($relPath),
                ];
            }
        }
        return $items;
    }

    // ── POST ─────────────────────────────────────────────────
    private function handlePost(): void
    {
        // File upload via multipart
        if (!empty($_FILES['files'])) {
            $this->uploadFiles();
            return;
        }
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

    private function uploadFiles(): void
    {
        $path = $this->sanitizePath($_POST['path'] ?? '');
        $smbDir = $this->smbPath($path);

        // Normalize files array
        $filesArr = [];
        if (is_array($_FILES['files']['name'])) {
            for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                $filesArr[] = [
                    'name'     => $_FILES['files']['name'][$i],
                    'type'     => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error'    => $_FILES['files']['error'][$i],
                    'size'     => $_FILES['files']['size'][$i],
                ];
            }
        } else {
            $filesArr[] = $_FILES['files'];
        }

        $uploaded = [];
        $errors   = [];
        $omv      = $this->omv;

        foreach ($filesArr as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error: " . $file['name']; continue;
            }
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $dest     = "$smbDir/$safeName";
            $result   = $omv->paste_file($file['tmp_name'], $dest);
            if ($result === true) {
                $uploaded[] = $safeName;
            } else {
                $errors[] = "SMB upload failed: " . $file['name'];
                error_log("[WorkFileExplorer upload] $dest → $result");
            }
        }

        echo json_encode([
            'success'  => count($uploaded) > 0,
            'uploaded' => $uploaded,
            'errors'   => $errors,
            'message'  => count($uploaded) . ' file(s) uploaded',
        ]);
    }

    private function createFolder(array $data): void
    {
        $path = $this->sanitizePath($data['path'] ?? '');
        $name = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $data['name'] ?? '');
        if (!$name) $this->err('Folder name required');
        $r = $this->omv->create_folder($this->smbPath($path) . '/' . $name);
        if ($r === true || stripos((string)$r, 'COLLISION') !== false) {
            echo json_encode(['success' => true]);
        } else {
            $this->err("Failed to create folder: $r");
        }
    }

    private function renameItem(array $data): void
    {
        $path    = $this->sanitizePath($data['path'] ?? '');
        $oldName = basename($data['oldName'] ?? '');
        $newName = basename($data['newName'] ?? '');
        if (!$oldName || !$newName) $this->err('Old and new names required');
        $src = $this->smbPath($path) . '/' . $oldName;
        $dst = $this->smbPath($path) . '/' . $newName;
        $r   = $this->omv->rename_file($src, $dst);
        echo json_encode(['success' => $r === true, 'error' => $r === true ? null : $r]);
    }

    private function moveItem(array $data): void
    {
        $src = $this->smbPath($data['sourcePath'] ?? '') . '/' . basename($data['sourceName'] ?? '');
        $dst = $this->smbPath($data['targetPath'] ?? '') . '/' . basename($data['targetName'] ?? '');
        $r   = $this->omv->rename_file($src, $dst);
        echo json_encode(['success' => $r === true, 'error' => $r === true ? null : $r]);
    }

    private function copyItem(array $data): void
    {
        $src = $this->smbPath($data['sourcePath'] ?? '') . '/' . basename($data['sourceName'] ?? '');
        $dst = $this->smbPath($data['targetPath'] ?? '') . '/' . basename($data['targetName'] ?? '');
        $r   = $this->omv->copy_file($src, $dst);
        echo json_encode(['success' => $r === true, 'error' => $r === true ? null : $r]);
    }

    private function duplicateItem(array $data): void
    {
        $path   = $this->sanitizePath($data['sourcePath'] ?? '');
        $name   = basename($data['sourceName'] ?? '');
        $newName = basename($data['targetName'] ?? ($name . ' - Copy'));
        $src = $this->smbPath($path) . '/' . $name;
        $dst = $this->smbPath($path) . '/' . $newName;
        $r   = $this->omv->copy_file($src, $dst);
        echo json_encode(['success' => $r === true, 'error' => $r === true ? null : $r]);
    }

    // ── DELETE ────────────────────────────────────────────────
    private function handleDelete(): void
    {
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $path    = $this->sanitizePath($data['path'] ?? '');
        $name    = basename($data['name'] ?? '');
        if (!$name) $this->err('Name required');
        $smbPath = $this->smbPath($path) . '/' . $name;
        $isDir   = ($data['type'] ?? '') === 'folder';
        $r = $isDir ? $this->omv->delete_directory($smbPath) : $this->omv->delete_file($smbPath);
        echo json_encode(['success' => $r === true, 'error' => $r === true ? null : $r]);
    }

    // ── Helpers ───────────────────────────────────────────────
    private function smbPath(string $relativePath): string
    {
        $rel = $this->sanitizePath($relativePath);
        return $rel ? $this->smbBase . '/' . $rel : $this->smbBase;
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace(['..', '\\'], '', $path);
        return trim($path, '/');
    }

    private function fmtBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $u = ['B','KB','MB','GB'];
        $i = (int)floor(log($bytes, 1024));
        return round($bytes / 1024 ** $i, 1) . ' ' . $u[$i];
    }

    private function err(string $msg, int $code = 400): never
    {
        http_response_code($code);
        ob_clean();
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}

try {
    (new WorkFileExplorerAPI($pdo))->handle();
} catch (Throwable $e) {
    error_log('[WorkFileExplorer] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}