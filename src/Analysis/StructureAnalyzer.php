<?php

declare(strict_types=1);

namespace Aurire\AiMcpAnalysis\Analysis;

use Illuminate\Support\Facades\File;

class StructureAnalyzer
{
    /**
     * Analyze project structure
     */
    public function analyze(): array
    {
        return [
            'directories' => $this->getDirectoryStructure(),
            'file_counts' => $this->getFileCounts(),
            'structure_health' => $this->assessStructureHealth(),
            'missing_conventions' => $this->checkMissingConventions()
        ];
    }

    /**
     * Get directory structure
     */
    private function getDirectoryStructure(): array
    {
        $structure = [];
        $commonDirectories = [
            'app' => 'Application Logic',
            'app/Models' => 'Data Models',
            'app/Http/Controllers' => 'HTTP Controllers',
            'app/Http/Middleware' => 'HTTP Middleware',
            'app/Services' => 'Business Logic Services',
            'app/Repositories' => 'Data Access Layer',
            'config' => 'Configuration Files',
            'database/migrations' => 'Database Migrations',
            'routes' => 'Route Definitions',
            'resources/views' => 'View Templates',
            'tests' => 'Test Suite'
        ];

        foreach ($commonDirectories as $dir => $description) {
            try {
                $exists = File::isDirectory(base_path($dir));
                $structure[$dir] = [
                    'exists' => $exists,
                    'description' => $description,
                    'file_count' => $exists ? $this->countFilesInDirectory(base_path($dir)) : 0
                ];
            } catch (\Exception $e) {
                $structure[$dir] = [
                    'exists' => false,
                    'description' => $description,
                    'file_count' => 0,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $structure;
    }

    /**
     * Get file counts by type
     */
    private function getFileCounts(): array
    {
        $counts = [
            'php_files' => 0,
            'blade_templates' => 0,
            'javascript_files' => 0,
            'css_files' => 0,
            'config_files' => 0,
            'migration_files' => 0
        ];

        try {
            // Count PHP files
            $counts['php_files'] = $this->countFilesByExtension('php');

            // Count Blade templates
            $counts['blade_templates'] = $this->countFilesByExtension('blade.php');

            // Count JS files
            $counts['javascript_files'] = $this->countFilesByExtension('js');

            // Count CSS files
            $counts['css_files'] = $this->countFilesByExtension('css');

            // Count config files
            if (File::isDirectory(base_path('config'))) {
                $counts['config_files'] = $this->countFilesInDirectory(base_path('config'));
            }

            // Count migrations
            if (File::isDirectory(base_path('database/migrations'))) {
                $counts['migration_files'] = $this->countFilesInDirectory(base_path('database/migrations'));
            }
        } catch (\Exception $e) {
            // Return partial counts on error
        }

        return $counts;
    }

    /**
     * Assess structure health
     */
    private function assessStructureHealth(): array
    {
        $score = 0;
        $maxScore = 0;
        $issues = [];

        // Check for essential directories
        $essentialDirs = ['app', 'config', 'routes'];
        foreach ($essentialDirs as $dir) {
            $maxScore++;
            if (File::isDirectory(base_path($dir))) {
                $score++;
            } else {
                $issues[] = "Missing essential directory: {$dir}";
            }
        }

        // Check for good practices
        $goodPracticeDirs = ['app/Services', 'app/Repositories', 'tests'];
        foreach ($goodPracticeDirs as $dir) {
            $maxScore++;
            if (File::isDirectory(base_path($dir))) {
                $score++;
            } else {
                $issues[] = "Recommended directory missing: {$dir}";
            }
        }

        return [
            'score' => $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0,
            'max_score' => $maxScore,
            'current_score' => $score,
            'issues' => $issues
        ];
    }

    /**
     * Check for missing Laravel conventions
     */
    private function checkMissingConventions(): array
    {
        $missing = [];

        // Check for common Laravel files/directories
        $conventions = [
            'app/Http/Kernel.php' => 'HTTP Kernel',
            'app/Console/Kernel.php' => 'Console Kernel',
            'app/Exceptions/Handler.php' => 'Exception Handler',
            'routes/web.php' => 'Web Routes',
            'routes/api.php' => 'API Routes'
        ];

        foreach ($conventions as $path => $description) {
            if (!File::exists(base_path($path))) {
                $missing[] = [
                    'path' => $path,
                    'description' => $description,
                    'severity' => $this->getConventionSeverity($path)
                ];
            }
        }

        return $missing;
    }

    /**
     * Count files in directory
     */
    private function countFilesInDirectory(string $path): int
    {
        try {
            $files = File::allFiles($path);
            return count($files);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Count files by extension
     */
    private function countFilesByExtension(string $extension): int
    {
        $count = 0;
        try {
            $files = File::allFiles(base_path());
            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), $extension)) {
                    $count++;
                }
            }
        } catch (\Exception $e) {
            // Return 0 on error
        }
        return $count;
    }

    /**
     * Get convention severity
     */
    private function getConventionSeverity(string $path): string
    {
        $critical = ['app/Http/Kernel.php', 'routes/web.php'];
        $important = ['app/Console/Kernel.php', 'app/Exceptions/Handler.php'];

        if (in_array($path, $critical)) {
            return 'critical';
        } elseif (in_array($path, $important)) {
            return 'important';
        }

        return 'optional';
    }
}
