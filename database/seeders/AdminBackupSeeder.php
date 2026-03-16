<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\admin_backup;

class AdminBackupSeeder extends Seeder
{
    public function run(): void
    {
        $backups = [
            [
                'backup_type' => admin_backup::TYPE_DATABASE,
                'backup_size' => 15728640,  // 15 MB
                'status'      => 'completed',
                'file_name'   => 'backup_db_20251008_122500.sql',
                'backup_path' => 'backups/backup_db_20251008_122500.sql',
                'created_at'  => '2025-10-08 12:25:00',
            ],
            [
                'backup_type' => admin_backup::TYPE_FILES,
                'backup_size' => 5242880,   // 5 MB
                'status'      => 'completed',
                'file_name'   => 'backup_files_20251008_122500.zip',
                'backup_path' => 'backups/backup_files_20251008_122500.zip',
                'created_at'  => '2025-10-08 12:25:00',
            ],
            [
                'backup_type' => admin_backup::TYPE_DATABASE,
                'backup_size' => 13631488,  // 13 MB
                'status'      => 'completed',
                'file_name'   => 'backup_db_20251008_130000.sql',
                'backup_path' => 'backups/backup_db_20251008_130000.sql',
                'created_at'  => '2025-10-08 13:00:00',
            ],
            [
                'backup_type' => admin_backup::TYPE_FULL,
                'backup_size' => 70254592,  // 67 MB
                'status'      => 'completed',
                'file_name'   => 'backup_full_20251008_140000.zip',
                'backup_path' => 'backups/backup_full_20251008_140000.zip',
                'created_at'  => '2025-10-08 14:00:00',
            ],
            [
                'backup_type' => admin_backup::TYPE_FILES,
                'backup_size' => 26214400,  // 25 MB
                'status'      => 'completed',
                'file_name'   => 'backup_files_20251008_150000.zip',
                'backup_path' => 'backups/backup_files_20251008_150000.zip',
                'created_at'  => '2025-10-08 15:00:00',
            ],
            [
                'backup_type' => admin_backup::TYPE_DATABASE,
                'backup_size' => 18874368,  // 18 MB
                'status'      => 'completed',
                'file_name'   => 'backup_db_20251008_160000.sql',
                'backup_path' => 'backups/backup_db_20251008_160000.sql',
                'created_at'  => '2025-10-08 16:00:00',
            ],
            [
                'backup_type' => admin_backup::TYPE_DATABASE,
                'backup_size' => 12582912,  // 12 MB
                'status'      => 'failed',
                'file_name'   => 'backup_db_20251008_170000.sql',
                'backup_path' => 'backups/backup_db_20251008_170000.sql',
                'created_at'  => '2025-10-08 17:00:00',
            ],
        ];

        foreach ($backups as $backup) {
            admin_backup::create($backup);
        }
    }
}