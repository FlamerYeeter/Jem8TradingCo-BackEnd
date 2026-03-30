<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_backup;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class AdminBackupController extends Controller
{
    // ✅ POST - Run Backup
    public function adminRunBackup(Request $request)
    {
        try {
            $request->validate([
                'backup_type' => 'required|integer|in:1,2,3',
            ]);

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

            \Storage::disk('public')->makeDirectory('backups');

            if ($request->backup_type == admin_backup::TYPE_DATABASE) {
                $this->exportDatabase($fullPath);
            } elseif ($request->backup_type == admin_backup::TYPE_FILES) {
                $this->exportFiles($fullPath);
            } elseif ($request->backup_type == admin_backup::TYPE_FULL) {
                $this->exportFull($fullPath);
            }

            // After the export call, before create():
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
            

            // ✅ Log: backup created
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

    // ✅ GET - Backup History
    public function adminHistoryBackup()
    {
        try {
            $backups = admin_backup::orderBy('created_at', 'desc')->get();

            // ✅ Log: viewed backup history
            ActivityLog::log(Auth::user(), 'Viewed backup history', 'backups', [
                'description'     => Auth::user()->first_name . ' viewed the backup history',
                'reference_table' => 'admin_backups',
            ]);

            return response()->json(['status' => 'success', 'data' => $backups], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ GET - Download Backup
    public function adminDownloadBackup($id)
    {
        try {
            $backup   = admin_backup::findOrFail($id);
            $fullPath = storage_path('app/public/' . $backup->backup_path);

            if (!file_exists($fullPath)) {
                return response()->json(['status' => 'error', 'message' => 'Backup file not found'], 404);
            }

            // ✅ Log: downloaded backup
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

    // ✅ DELETE - Delete Backup
    public function adminDeleteBackup($id)
    {
        try {
            $backup   = admin_backup::findOrFail($id);
            $fileName = $backup->file_name;

            $backup->delete();

            // ✅ Log: deleted backup
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

    // ✅ POST - Upload & Restore
    public function adminUploadRestore(Request $request)
    {
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

            // ✅ Log: restored backup
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

    // ✅ Private helpers (unchanged)
        private function exportDatabase($fullPath)
    {
        $db        = config('database.connections.mysql.database');
        $user      = config('database.connections.mysql.username');
        $password  = config('database.connections.mysql.password');
        $host      = config('database.connections.mysql.host');

        // Try common paths; falls back to just 'mysqldump' if on PATH
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
            'mysqldump', // fallback: rely on system PATH
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        return 'mysqldump'; // last resort
    }

    private function exportFiles($fullPath)
    {
        $zip    = new \ZipArchive();
        $source = storage_path('app/public');
        if ($zip->open($fullPath, \ZipArchive::CREATE) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source));
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $zip->addFile($file->getPathname(), str_replace($source . DIRECTORY_SEPARATOR, '', $file->getPathname()));
                }
            }
            $zip->close();
        }
    }

    private function exportFull($fullPath)
    {
        $zip     = new \ZipArchive();
        $source  = storage_path('app/public');
        $tempSql = storage_path('app/public/backups/temp_db.sql');
        $this->exportDatabase($tempSql);
        if ($zip->open($fullPath, \ZipArchive::CREATE) === true) {
            $zip->addFile($tempSql, 'database.sql');
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source));
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $zip->addFile($file->getPathname(), str_replace($source . DIRECTORY_SEPARATOR, '', $file->getPathname()));
                }
            }
            $zip->close();
        }
        if (file_exists($tempSql)) unlink($tempSql);
    }
}