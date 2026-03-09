<?php

namespace App\Services;

class PythonRunner
{
    private string $basePath;
    private string $pythonPath;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 2);
        $isWindows = (DIRECTORY_SEPARATOR === '\\' || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $this->pythonPath = $_ENV['PYTHON_PATH'] ?? ($isWindows ? 'python' : 'python3');
    }

    /**
     * Run a Python script and return decoded JSON from stdout, or throw on failure.
     */
    public function runJson(string $scriptRelativePath, array $args = []): array
    {
        $output = $this->run($scriptRelativePath, $args);
        $decoded = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Python script did not return valid JSON: ' . substr($output, 0, 500));
        }
        return $decoded;
    }

    /**
     * Run a Python script; return stdout. stderr is appended to exception if exit code !== 0.
     */
    public function run(string $scriptRelativePath, array $args = []): string
    {
        $scriptPath = $this->basePath . DIRECTORY_SEPARATOR . ltrim($scriptRelativePath, '/\\');
        if (!is_file($scriptPath)) {
            throw new \RuntimeException("Script not found: {$scriptPath}");
        }
        $escapedScript = escapeshellarg($scriptPath);
        $escapedArgs = array_map('escapeshellarg', $args);
        $cmd = $this->pythonPath . ' ' . $escapedScript . ' ' . implode(' ', $escapedArgs);
        $descriptorSpec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $descriptorSpec, $pipes, $this->basePath);
        if (!is_resource($proc)) {
            throw new \RuntimeException('Failed to start Python process');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        if ($exitCode !== 0) {
            throw new \RuntimeException("Python script failed (exit {$exitCode}). stderr: " . $stderr . "\nstdout: " . $stdout);
        }
        return trim($stdout);
    }

    /**
     * Run import script: python/import_csv_to_mysql.py --path=...
     */
    public function runImport(string $csvPath): array
    {
        $path = strpos($csvPath, ' ') !== false ? escapeshellarg($csvPath) : $csvPath;
        return $this->runJson('python/import_csv_to_mysql.py', ['--path=' . $path]);
    }

    /**
     * Run normalization: python/normalize_profiles.py
     */
    public function runNormalize(): array
    {
        return $this->runJson('python/normalize_profiles.py');
    }

    /**
     * Run recommendation: python/recommend.py --job_id=N [--top_k=200]
     */
    public function runRecommend(int $jobId, int $topK = 200): array
    {
        return $this->runJson('python/recommend.py', ["--job_id={$jobId}", "--top_k={$topK}"]);
    }

    /**
     * Run PDF extraction: python/extract_pdf_text.py --path=...
     */
    public function runExtractPdf(string $pdfPath): array
    {
        $path = strpos($pdfPath, ' ') !== false ? escapeshellarg($pdfPath) : $pdfPath;
        return $this->runJson('python/extract_pdf_text.py', ['--path=' . $pdfPath]);
    }

    /**
     * Run a command and return output/error
     */
    public function runCommand(array $command): array
    {
        $escapedCmd = array_map('escapeshellarg', $command);
        $cmd = implode(' ', $escapedCmd);
        $descriptorSpec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $descriptorSpec, $pipes, $this->basePath);
        if (!is_resource($proc)) {
            return ['error' => 'Failed to start process'];
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        return [
            'output' => trim($stdout),
            'error' => trim($stderr),
            'exit_code' => $exitCode
        ];
    }
}
