<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_backup;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminBackupController extends Controller
{
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

            $timestamp = now()->format('Ymd_His');
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
    // ══════════════════════════════════════════════════════════════════════════
    public function adminUploadRestore(Request $request)
    {
        set_time_limit(300);

        try {
            $request->validate([
                'backup_file' => 'required|file|mimes:txt,zip,x-sql|max:51200',
            ]);

            $file     = $request->file('backup_file');
            $fullPath = storage_path('app/public/backups/' . $file->getClientOriginalName());

            $file->move(storage_path('app/public/backups'), $file->getClientOriginalName());

            if ($file->getClientOriginalExtension() == 'sql') {
                $db       = config('database.connections.mysql.database');
                $user     = config('database.connections.mysql.username');
                $password = config('database.connections.mysql.password');
                $host     = config('database.connections.mysql.host');

                $pdo = new \PDO("mysql:host={$host};dbname={$db}", $user, $password);
                $sql = file_get_contents($fullPath);
                $pdo->exec($sql);
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
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
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

        $command = sprintf(
            '"%s" --user=%s --password=%s --host=%s %s -r "%s" 2>&1',
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
    // ✅ PRIVATE - Export Files (images/documents ONLY, skip backups folder)
    //
    //   Only zips these folders under storage/app/public:
    //     • images/
    //     • uploads/
    //     • products/
    //     • documents/
    //
    //   Skips: backups/, temp files, .sql files, hidden files
    // ══════════════════════════════════════════════════════════════════════════
    private function exportFiles($fullPath)
    {
        set_time_limit(300);

        // ── Only include these specific subdirectories ────────────────────────
        // Add or remove folder names here to match your actual storage structure
        $allowedFolders = ['images', 'uploads', 'products', 'documents', 'photos'];

        $zip    = new \ZipArchive();
        $source = storage_path('app/public');

        if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not create zip file for files backup.');
        }

        $addedFiles = 0;

        foreach ($allowedFolders as $folder) {
            $folderPath = $source . DIRECTORY_SEPARATOR . $folder;

            // Skip if the folder doesn't exist in this project
            if (!is_dir($folderPath)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isDir()) continue;

                $filePath = $file->getRealPath();

                // Skip hidden files, temp files, and sql files
                $basename = basename($filePath);
                if (str_starts_with($basename, '.') || str_ends_with($basename, '.sql')) {
                    continue;
                }

                // Store with relative path: images/photo.jpg, uploads/doc.pdf, etc.
                $relativePath = $folder . DIRECTORY_SEPARATOR . substr(
                    $filePath,
                    strlen($folderPath) + 1
                );

                $zip->addFile($filePath, $relativePath);
                $addedFiles++;
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            // Still valid — just means no files in those folders yet
            // The zip will be created but essentially empty
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Export Full (SQL dump + images/docs only, NO backups folder)
    // ══════════════════════════════════════════════════════════════════════════
    private function exportFull($fullPath)
    {
        set_time_limit(300);

        $allowedFolders = ['images', 'uploads', 'products', 'documents', 'photos'];

        $zip     = new \ZipArchive();
        $source  = storage_path('app/public');

        // Write temp SQL into a NON-public temp location so it never gets zipped recursively
        $tempSql = storage_path('app/temp_db_export.sql');

        $this->exportDatabase($tempSql);

        if ($zip->open($fullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not open zip file for writing: ' . $fullPath);
        }

        // ── Add the SQL dump ─────────────────────────────────────────────────
        $zip->addFile($tempSql, 'database.sql');

        // ── Add only allowed file folders ────────────────────────────────────
        foreach ($allowedFolders as $folder) {
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

        // ── Clean up temp SQL file ───────────────────────────────────────────
        if (file_exists($tempSql)) {
            unlink($tempSql);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ✅ PRIVATE - Auto-purge backups older than 3 months
    //   Called automatically every time a new backup is created
    // ══════════════════════════════════════════════════════════════════════════
    private function purgeOldBackups()
    {
        $cutoff  = now()->subMonths(3);
        $old     = admin_backup::where('created_at', '<', $cutoff)->get();

        foreach ($old as $backup) {
            $fullPath = storage_path('app/public/' . $backup->backup_path);

            // Delete physical file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete DB record
            $backup->delete();
        }
    }
}
