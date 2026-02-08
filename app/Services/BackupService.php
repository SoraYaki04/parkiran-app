<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupService
{
    protected string $backupPath;
    protected string $backupDisk = 'local';
    
    public function __construct()
    {
        $this->backupPath = storage_path('app/Laravel');
    }

    /**
     * Create a new database backup using Spatie's artisan command.
     */
    public function createBackup(): array
    {
        try {
            Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);

            return [
                'success' => true,
                'message' => 'Backup created successfully!',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * List all available backups.
     */
    public function listBackups(): array
    {
        $backups = [];
        
        if (!File::exists($this->backupPath)) {
            return $backups;
        }

        $files = File::files($this->backupPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'zip') {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'size_raw' => $file->getSize(),
                    'date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'age' => $this->getAge($file->getMTime()),
                ];
            }
        }

        // Sort by date descending (newest first)
        usort($backups, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return $backups;
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup(string $filename): array
    {
        $path = $this->backupPath . '/' . $filename;

        if (!File::exists($path)) {
            return ['success' => false, 'message' => 'Backup not found'];
        }

        File::delete($path);

        return ['success' => true, 'message' => 'Backup deleted'];
    }

    /**
     * Get full path to a backup file for download.
     */
    public function getBackupPath(string $filename): ?string
    {
        $path = $this->backupPath . '/' . $filename;
        return File::exists($path) ? $path : null;
    }

    /**
     * Restore database from a backup file.
     */
    public function restoreBackup(string $filename): array
    {
        $zipPath = $this->backupPath . '/' . $filename;

        if (!File::exists($zipPath)) {
            return ['success' => false, 'message' => 'Backup file not found'];
        }

        try {
            // Create temporary directory for extraction
            $tempDir = storage_path('app/temp_restore_' . time());
            File::makeDirectory($tempDir, 0755, true);

            // Extract ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Could not open backup archive');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // Find SQL file in extracted content (recursively)
            $sqlFile = $this->findSqlFile($tempDir);

            if (!$sqlFile) {
                File::deleteDirectory($tempDir);
                throw new \Exception('No SQL file found in backup');
            }

            // Restore Database
            $this->importDatabase($sqlFile);

            // Cleanup
            File::deleteDirectory($tempDir);

            return ['success' => true, 'message' => 'Database restored successfully!'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()];
        }
    }

    /**
     * Find SQL file recursively within a directory.
     */
    private function findSqlFile(string $dir): ?string
    {
        $files = File::allFiles($dir);
        
        foreach ($files as $file) {
            if (strtolower($file->getExtension()) === 'sql') {
                return $file->getPathname();
            }
            // Spatie backup uses .gz sometimes, check for that too
            if (str_ends_with($file->getFilename(), '.sql.gz')) {
                 // Decompress
                $gzContent = file_get_contents($file->getPathname());
                $sqlContent = gzdecode($gzContent);
                $sqlPath = $dir . '/db-dump.sql';
                file_put_contents($sqlPath, $sqlContent);
                return $sqlPath;
            }
        }

        return null;
    }

    /**
     * Import SQL file into the database using mysql command.
     */
    private function importDatabase(string $sqlPath): void
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'mysql -h %s -P %s -u %s %s %s < "%s"',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '-p' . escapeshellarg($password) : '',
            escapeshellarg($database),
            $sqlPath
        );

        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('MySQL import failed: ' . implode("\n", $output));
        }
    }

    /**
     * Format bytes to human readable string.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get human-readable age of a file.
     */
    private function getAge(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        
        return floor($diff / 604800) . ' weeks ago';
    }
}
