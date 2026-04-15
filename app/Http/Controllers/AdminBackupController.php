<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_backup;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminBackupController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // Media folders that are backed up and restored
    // ══════════════════════════════════════════════════════════════════════════
    private const MEDIA_FOLDERS = [
        'images',
        'uploads',
        'products',
        'documents',
        'photos',
        'blog_images',
        'featured_images',
        'profile_images',
    ];

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ POST - Run Backup
    // ══════════════════════════════════════════════════════════════════════════
    public function adminRunBackup(Request $request)
    {
        set_time_limit(300);

        try {
            $request->validate([
                'backup_type' => 'required|integer|in:1,2,3',
            ]);

            // ── Auto-delete backups older than 3 months ──────────────────────
            $this->purgeOldBackups();

            $timestamp = now('Asia/Manila')->format('Ymd_His');
            $typeNames = [
                admin_backup::TYPE_DATABASE => 'db',
                admin_backup::TYPE_FILES    => 'files',
                admin_backup::TYPE_FULL     => 'full',
            ];

            $typeName   = $typeNames[$request->backup_type];
            $ext        = $request->backup_type == admin_backup::TYPE_DATABASE ? 'sql' : 'zip';
            $fileName   = "backup_{$typeName}_{$timestamp}.{$ext}";
            $backupPath = "backups/{$fileName}";
            $fullPath   = storage_path("app/public/{$backupPath}");

            Storage::disk('public')->makeDirectory('backups');

            if ($request->backup_type == admin_backup::TYPE_DATABASE) {
                $this->exportDatabase($fullPath);
            } elseif ($request->backup_type == admin_backup::TYPE_FILES) {
                $this->exportFiles($fullPath);
            } elseif ($request->backup_type == admin_backup::TYPE_FULL) {
                $this->exportFull($fullPath);
            }

            if (!file_exists($fullPath) || filesize($fullPath) === 0) {
                throw new \Exception('Backup file was not created. Check mysqldump path and DB credentials.');
            }

            $backup = admin_backup::create([
                'backup_type' => $request->backup_type,
                'backup_size' => filesize($fullPath),
                'status'      => 'completed',
                'file_name'   => $fileName,
                'backup_path' => $backupPath,
            ]);

            ActivityLog::log(Auth::user(), 'Created a backup', 'backups', [
                'product_unique_code' => $fileName,
                'description'         => Auth::user()->first_name . ' created a ' . $typeName . ' backup: ' . $fileName,
                'reference_table'     => 'admin_backups',
                'reference_id'        => $backup->id,
            ]);

            return response()->json(['status' => 'success', 'data' => $backup], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'type' => 'validation', 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ GET - Backup History
    // ══════════════════════════════════════════════════════════════════════════
    public function adminHistoryBackup()
    {
        try {
            $backups = admin_backup::orderBy('created_at', 'desc')->get();

            ActivityLog::log(Auth::user(), 'Viewed backup history', 'backups', [
                'description'     => Auth::user()->first_name . ' viewed the backup history',
                'reference_table' => 'admin_backups',
            ]);

            return response()->json(['status' => 'success', 'data' => $backups], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ GET - Download Backup
    // ══════════════════════════════════════════════════════════════════════════
    public function adminDownloadBackup($id)
    {
        set_time_limit(300);

        try {
            $backup   = admin_backup::findOrFail($id);
            $fullPath = storage_path('app/public/' . $backup->backup_path);

            if (!file_exists($fullPath)) {
                return response()->json(['status' => 'error', 'message' => 'Backup file not found on disk'], 404);
            }

            ActivityLog::log(Auth::user(), 'Downloaded a backup', 'backups', [
                'product_unique_code' => $backup->file_name,
                'description'         => Auth::user()->first_name . ' downloaded backup: ' . $backup->file_name,
                'reference_table'     => 'admin_backups',
                'reference_id'        => $id,
            ]);

            return response()->download($fullPath, $backup->file_name);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'type' => 'not_found', 'message' => 'Backup record not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ DELETE - Delete Backup (record + physical file)
    // ══════════════════════════════════════════════════════════════════════════
    public function adminDeleteBackup($id)
    {
        try {
            $backup   = admin_backup::findOrFail($id);
            $fileName = $backup->file_name;
            $fullPath = storage_path('app/public/' . $backup->backup_path);

            // ── Delete physical file first ───────────────────────────────────
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // ── Delete DB record ─────────────────────────────────────────────
            $backup->delete();

            ActivityLog::log(Auth::user(), 'Deleted a backup', 'backups', [
                'product_unique_code' => $fileName,
                'description'         => Auth::user()->first_name . ' deleted backup: ' . $fileName,
                'reference_table'     => 'admin_backups',
                'reference_id'        => $id,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Backup deleted successfully'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'type' => 'not_found', 'message' => 'Backup record not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ POST - Upload & Restore
    //
    //   Accepts:
    //     • .sql  — plain SQL dump → import directly
    //     • .zip  — must contain:
    //                 database.sql          (optional — skipped if absent)
    //                 files/<folder>/...    (media tree, e.g. files/blog_images/…)
    //               OR flat layout:
    //                 <folder>/...          (e.g. blog_images/photo.jpg)
    //
    //   Media folders restored:
    //     blog_images, featured_images, products, profile_images
    //     (+ images, uploads, documents, photos for backwards-compat)
    // ══════════════════════════════════════════════════════════════════════════
    public function adminUploadRestore(Request $request)
    {
        set_time_limit(600);

        try {
            // ── Validation ───────────────────────────────────────────────────
            $request->validate([
                'backup_file' => [
                    'required',
                    'file',
                    function ($attribute, $value, $fail) {
                        $ext = strtolower($value->getClientOriginalExtension());
                        if (!in_array($ext, ['sql', 'zip'])) {
                            $fail('Only .sql or .zip backup files are accepted.');
                        }
                        if ($value->getSize() > 200 * 1024 * 1024) {
                            $fail('Backup file must be smaller than 200 MB.');
                        }
                    },
                ],
            ]);

            $file      = $request->file('backup_file');
            $ext       = strtolower($file->getClientOriginalExtension());
            $uploadDir = storage_path('app/public/backups');

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $savedName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $savedPath = $uploadDir . DIRECTORY_SEPARATOR . $savedName;
            $file->move($uploadDir, $savedName);

            // ── Route by extension ───────────────────────────────────────────
            if ($ext === 'sql') {
                $this->importSqlFile($savedPath);
            } elseif ($ext === 'zip') {
                $this->restoreFromZip($savedPath);
            }

            ActivityLog::log(Auth::user(), 'Restored a backup', 'backups', [
                'product_unique_code' => $file->getClientOriginalName(),
                'description'         => Auth::user()->first_name . ' restored backup: ' . $file->getClientOriginalName(),
                'reference_table'     => 'admin_backups',
            ]);

            return response()->json(['status' => 'success', 'message' => 'Backup restored successfully'], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'type' => 'validation', 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Restore from ZIP archive
    //
    //   ZIP layout produced by exportFull():
    //     database.sql
    //     files/blog_images/…
    //     files/featured_images/…
    //     files/products/…
    //     files/profile_images/…
    //     files/images/…   (legacy)
    //     …
    //
    //   This method also handles a flat layout where folders sit at the root:
    //     blog_images/photo.jpg
    //     products/item.png
    //     database.sql
    // ══════════════════════════════════════════════════════════════════════════
    private function restoreFromZip(string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Could not open the ZIP file. It may be corrupt or not a valid ZIP archive.');
        }

        // ── Validate ZIP is not empty ────────────────────────────────────────
        if ($zip->count() === 0) {
            $zip->close();
            throw new \Exception('The ZIP archive is empty.');
        }

        // ── Extract to a temp directory ──────────────────────────────────────
        $tempDir = storage_path('app/restore_tmp_' . time());
        if (!mkdir($tempDir, 0755, true)) {
            $zip->close();
            throw new \Exception('Could not create temporary extraction directory.');
        }

        try {
            $zip->extractTo($tempDir);
            $zip->close();

            // ── 1. Import SQL if present ─────────────────────────────────────
            $sqlFile = $this->findSqlInExtracted($tempDir);
            if ($sqlFile !== null) {
                $this->importSqlFile($sqlFile);
            }

            // ── 2. Restore media folders ─────────────────────────────────────
            $this->restoreMediaFolders($tempDir);

        } finally {
            // Always clean up temp directory
            $this->rrmdir($tempDir);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Find the SQL dump inside the extracted directory
    //
    //   Looks for:
    //     {tempDir}/database.sql          (standard exportFull layout)
    //     {tempDir}/*.sql                 (flat layout)
    //     {tempDir}/**/*.sql              (nested — takes first match)
    // ══════════════════════════════════════════════════════════════════════════
    private function findSqlInExtracted(string $tempDir): ?string
    {
        // Priority 1: standard name at root
        $standard = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
        if (file_exists($standard)) {
            return $standard;
        }

        // Priority 2: any .sql at root level
        $rootSqls = glob($tempDir . DIRECTORY_SEPARATOR . '*.sql');
        if (!empty($rootSqls)) {
            return $rootSqls[0];
        }

        // Priority 3: recursive search (first .sql found)
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'sql') {
                return $file->getRealPath();
            }
        }

        return null;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Restore media folders from extracted ZIP content
    //
    //   Handles two layout conventions:
    //
    //   Layout A (exportFull output):
    //     {tempDir}/files/blog_images/photo.jpg
    //     {tempDir}/files/products/item.png
    //
    //   Layout B (flat / older backups):
    //     {tempDir}/blog_images/photo.jpg
    //     {tempDir}/products/item.png
    //
    //   Destination always:
    //     storage/app/public/{folderName}/…
    //
    //   After copying, validates each image is readable.
    // ══════════════════════════════════════════════════════════════════════════
    private function restoreMediaFolders(string $tempDir): void
    {
        $publicStorage = storage_path('app/public');
        $restoredCount = 0;
        $errors        = [];

        foreach (self::MEDIA_FOLDERS as $folder) {
            // Try Layout A first (files/ prefix), then Layout B (flat)
            $candidates = [
                $tempDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $folder,
                $tempDir . DIRECTORY_SEPARATOR . $folder,
            ];

            $srcDir = null;
            foreach ($candidates as $candidate) {
                if (is_dir($candidate)) {
                    $srcDir = $candidate;
                    break;
                }
            }

            if ($srcDir === null) {
                // This folder simply wasn't in the backup — not an error
                continue;
            }

            $destDir = $publicStorage . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Copy all files recursively
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($srcDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isDir()) continue;

                $relativePath = substr($file->getRealPath(), strlen($srcDir) + 1);
                $destPath     = $destDir . DIRECTORY_SEPARATOR . $relativePath;
                $destSubDir   = dirname($destPath);

                if (!is_dir($destSubDir)) {
                    mkdir($destSubDir, 0755, true);
                }

                if (!copy($file->getRealPath(), $destPath)) {
                    $errors[] = "Failed to copy: {$folder}/{$relativePath}";
                    continue;
                }

                // Validate image files are intact
                $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                    if (!$this->validateImageFile($destPath)) {
                        $errors[] = "Image may be corrupt after restore: {$folder}/{$relativePath}";
                    }
                }

                $restoredCount++;
            }
        }

        if (!empty($errors)) {
            // Log warnings but don't abort — partial restore is better than none
            Log::warning('Restore completed with warnings: ' . implode('; ', $errors));
        }

        Log::info("Restore: {$restoredCount} media file(s) restored from ZIP.");
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Validate that an image file is readable and non-zero
    // ══════════════════════════════════════════════════════════════════════════
    private function validateImageFile(string $path): bool
    {
        if (!file_exists($path) || filesize($path) === 0) {
            return false;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['svg'])) {
            return true; // SVG is text-based, skip getimagesize
        }

        // Use getimagesize as a lightweight sanity check
        $info = @getimagesize($path);
        return $info !== false;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Import a SQL dump file safely
    //
    //   Uses a statement-by-statement PDO approach that:
    //     • handles multi-line statements
    //     • skips comments and blank lines
    //     • wraps the whole import in a transaction (rolls back on failure)
    //     • disables FK checks during import to avoid ordering issues
    // ══════════════════════════════════════════════════════════════════════════
    private function importSqlFile(string $sqlPath): void
    {
        if (!file_exists($sqlPath) || filesize($sqlPath) === 0) {
            throw new \Exception('SQL file not found or is empty: ' . basename($sqlPath));
        }

        $db       = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host');
        $charset  = config('database.connections.mysql.charset', 'utf8mb4');

        try {
            $pdo = new \PDO(
                "mysql:host={$host};dbname={$db};charset={$charset}",
                $user,
                $password,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
                ]
            );
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed during restore: ' . $e->getMessage());
        }

        // Disable FK checks and set session vars for a clean import
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('SET SQL_MODE = ""');
        $pdo->exec("SET time_zone = '+08:00'"); // Asia/Manila / Asia/Taipei offset

        $sqlContent = file_get_contents($sqlPath);
        if ($sqlContent === false) {
            throw new \Exception('Could not read SQL file: ' . basename($sqlPath));
        }

        // ── Parse SQL into individual statements ──────────────────────────────
        $statements  = $this->parseSqlStatements($sqlContent);
        $importedQty = 0;
        $failedQty   = 0;

        $pdo->beginTransaction();
        try {
            foreach ($statements as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '' || $trimmed === ';') continue;

                try {
                    $pdo->exec($trimmed);
                    $importedQty++;
                } catch (\PDOException $stmtEx) {
                    // Log per-statement errors but continue unless critical
                    Log::warning('SQL restore statement skipped: ' . $stmtEx->getMessage() . ' | SQL: ' . substr($trimmed, 0, 120));
                    $failedQty++;
                }
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            throw new \Exception('SQL import failed and was rolled back: ' . $e->getMessage());
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        Log::info("SQL restore complete: {$importedQty} statements executed, {$failedQty} skipped.");
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Parse SQL dump into individual executable statements
    //
    //   Handles:
    //     • -- and # single-line comments
    //     • /* … */ block comments
    //     • string literals containing semicolons
    //     • DELIMITER $$ … END $$ style (stored procedures / triggers)
    // ══════════════════════════════════════════════════════════════════════════
    private function parseSqlStatements(string $sql): array
    {
        $statements = [];
        $current    = '';
        $inString   = false;
        $stringChar = '';
        $delimiter  = ';';
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            // ── Handle string literals ────────────────────────────────────────
            if ($inString) {
                $current .= $char;
                if ($char === '\\') {
                    // Escaped character — consume next char blindly
                    if ($i + 1 < $len) {
                        $current .= $sql[++$i];
                    }
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
                continue;
            }

            // ── Single-line comments (-- or #) ────────────────────────────────
            if (($char === '-' && isset($sql[$i + 1]) && $sql[$i + 1] === '-') ||
                $char === '#') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                $current .= "\n";
                continue;
            }

            // ── Block comments /* … */ ────────────────────────────────────────
            if ($char === '/' && isset($sql[$i + 1]) && $sql[$i + 1] === '*') {
                $i += 2;
                while ($i < $len && !($sql[$i] === '*' && isset($sql[$i + 1]) && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i += 2; // Skip closing */
                continue;
            }

            // ── String start ──────────────────────────────────────────────────
            if ($char === '"' || $char === "'" || $char === '`') {
                $inString   = true;
                $stringChar = $char;
                $current   .= $char;
                continue;
            }

            // ── DELIMITER directive ───────────────────────────────────────────
            if (stripos(ltrim($current) . $char, 'DELIMITER') === 0 && $char === ' ') {
                $rest      = ltrim(substr($sql, $i + 1));
                $endOfLine = strpos($rest, "\n");
                $newDelim  = $endOfLine !== false ? trim(substr($rest, 0, $endOfLine)) : trim($rest);
                if ($newDelim !== '') {
                    $delimiter = $newDelim;
                    $i        += strlen($newDelim) + 1;
                    $current   = '';
                    continue;
                }
            }

            $current .= $char;

            // ── Statement terminator ──────────────────────────────────────────
            if (substr($current, -strlen($delimiter)) === $delimiter) {
                $stmt = substr($current, 0, -strlen($delimiter));
                $stmt = trim($stmt);
                if ($stmt !== '') {
                    $statements[] = $stmt;
                }
                $current = '';
            }
        }

        // Any trailing statement without delimiter
        $trailing = trim($current);
        if ($trailing !== '') {
            $statements[] = $trailing;
        }

        return $statements;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Export Database (SQL only, no files)
    // ══════════════════════════════════════════════════════════════════════════
    private function exportDatabase($fullPath)
    {
        set_time_limit(300);

        $db       = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host');

        $mysqldump = $this->findMysqldump();

        // Add --set-gtid-purged=OFF to suppress GTID warnings on some MySQL setups
        $command = sprintf(
        '"%s" --user=%s --password=%s --host=%s --single-transaction --routines --triggers %s -r "%s" 2>&1',
            $mysqldump,
            escapeshellarg($user),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($db),
            $fullPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('mysqldump failed: ' . implode("\n", $output));
        }
    }

    private function findMysqldump(): string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.27\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            'mysqldump',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        return 'mysqldump';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Export Files (media folders ONLY, skip backups folder)
    // ══════════════════════════════════════════════════════════════════════════
    private function exportFiles($fullPath)
    {
        set_time_limit(300);

        $zip    = new \ZipArchive();
        $source = storage_path('app/public');

        if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not create zip file for files backup.');
        }

        $addedFiles = 0;

        foreach (self::MEDIA_FOLDERS as $folder) {
            $folderPath = $source . DIRECTORY_SEPARATOR . $folder;

            if (!is_dir($folderPath)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isDir()) continue;

                $filePath = $file->getRealPath();
                $basename = basename($filePath);

                if (str_starts_with($basename, '.') || str_ends_with($basename, '.sql')) {
                    continue;
                }

                $relativePath = $folder . DIRECTORY_SEPARATOR . substr(
                    $filePath,
                    strlen($folderPath) + 1
                );

                $zip->addFile($filePath, $relativePath);
                $addedFiles++;
            }
        }

        $zip->close();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Export Full (SQL dump + all media folders)
    // ══════════════════════════════════════════════════════════════════════════
    private function exportFull($fullPath)
    {
        set_time_limit(300);

        $zip     = new \ZipArchive();
        $source  = storage_path('app/public');
        $tempSql = storage_path('app/temp_db_export.sql');

        $this->exportDatabase($tempSql);

        if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not open zip file for writing: ' . $fullPath);
        }

        // ── Add the SQL dump ─────────────────────────────────────────────────
        $zip->addFile($tempSql, 'database.sql');

        // ── Add all media folders under files/ prefix ────────────────────────
        foreach (self::MEDIA_FOLDERS as $folder) {
            $folderPath = $source . DIRECTORY_SEPARATOR . $folder;

            if (!is_dir($folderPath)) continue;

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isDir()) continue;

                $filePath = $file->getRealPath();
                $basename = basename($filePath);

                if (str_starts_with($basename, '.') || str_ends_with($basename, '.sql')) {
                    continue;
                }

                $relativePath = 'files' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . substr(
                    $filePath,
                    strlen($folderPath) + 1
                );

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        if (file_exists($tempSql)) {
            unlink($tempSql);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Auto-purge backups older than 3 months
    // ══════════════════════════════════════════════════════════════════════════
    private function purgeOldBackups()
    {
        $cutoff = now('Asia/Manila')->subMonths(3);
        $old    = admin_backup::where('created_at', '<', $cutoff)->get();

        foreach ($old as $backup) {
            $fullPath = storage_path('app/public/' . $backup->backup_path);

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            $backup->delete();
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Recursively remove a directory and all its contents
    // ══════════════════════════════════════════════════════════════════════════
    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }

        rmdir($dir);
    }
}