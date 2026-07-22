<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class ServerMetricsService
{
    /**
     * Get host hardware metrics (CPU, RAM, GPU, OS).
     * Caching results for 30 seconds to avoid frequent PowerShell calls.
     */
    public function getMetrics(): array
    {
        return Cache::remember('host_server_hardware_metrics', 30, function () {
            return [
                'cpu' => $this->getCpuMetrics(),
                'ram' => $this->getRamMetrics(),
                'gpu' => $this->getGpuMetrics(),
                'system' => $this->getSystemMetrics(),
            ];
        });
    }

    /**
     * Execute a PowerShell script reliably without quote/string escaping issues.
     */
    protected function runPowerShell(string $psScript): ?string
    {
        $fullScript = "\$ProgressPreference = 'SilentlyContinue'; " . $psScript;
        $bytes = mb_convert_encoding($fullScript, 'UTF-16LE', 'UTF-8');
        $base64 = base64_encode($bytes);
        $output = shell_exec("powershell -NoProfile -ExecutionPolicy Bypass -EncodedCommand {$base64}");
        if (!$output) return null;

        // Strip CLIXML progress metadata if present
        if (str_contains($output, '#< CLIXML')) {
            $pos = strpos($output, '{');
            if ($pos !== false) {
                $output = substr($output, $pos);
            } else {
                $output = preg_replace('/#< CLIXML.*?\r?\n/s', '', $output);
            }
        }
        return trim($output);
    }

    /**
     * Get CPU load percentage, model, cores, and threads.
     */
    protected function getCpuMetrics(): array
    {
        $cpuInfo = [
            'name' => 'Host Processor',
            'usage_pct' => 0.0,
            'cores' => null,
            'threads' => null,
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $rawJson = $this->runPowerShell("Get-CimInstance Win32_Processor | Select-Object Name, NumberOfCores, NumberOfLogicalProcessors | ConvertTo-Json");
                if ($rawJson) {
                    $json = json_decode($rawJson, true);
                    if (isset($json[0])) {
                        $json = $json[0];
                    }
                    if (!empty($json['Name'])) {
                        $cpuInfo['name'] = trim($json['Name']);
                    }
                    if (isset($json['NumberOfCores'])) {
                        $cpuInfo['cores'] = (int) $json['NumberOfCores'];
                    }
                    if (isset($json['NumberOfLogicalProcessors'])) {
                        $cpuInfo['threads'] = (int) $json['NumberOfLogicalProcessors'];
                    }
                }

                // Get Live CPU % load
                $usageVal = $this->runPowerShell("[math]::Round((Get-Counter '\\Processor(_Total)\\% Processor Time').CounterSamples.CookedValue, 1)");
                if ($usageVal !== null && is_numeric($usageVal)) {
                    $cpuInfo['usage_pct'] = min(100.0, max(0.0, (float) $usageVal));
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        } else {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $cpuInfo['usage_pct'] = round(($load[0] ?? 0) * 10, 1);
            }
        }

        return $cpuInfo;
    }

    /**
     * Get RAM usage (total, used, free, usage %).
     */
    protected function getRamMetrics(): array
    {
        $ramInfo = [
            'total_gb' => 0.0,
            'used_gb' => 0.0,
            'free_gb' => 0.0,
            'usage_pct' => 0.0,
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $rawJson = $this->runPowerShell("Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory | ConvertTo-Json");
                if ($rawJson) {
                    $json = json_decode($rawJson, true);
                    if (isset($json['TotalVisibleMemorySize']) && isset($json['FreePhysicalMemory'])) {
                        $totalKb = (float) $json['TotalVisibleMemorySize'];
                        $freeKb = (float) $json['FreePhysicalMemory'];
                        
                        $totalGb = round($totalKb / 1024 / 1024, 1);
                        $freeGb = round($freeKb / 1024 / 1024, 1);
                        $usedGb = max(0.0, round($totalGb - $freeGb, 1));
                        $usedPct = $totalGb > 0 ? round(($usedGb / $totalGb) * 100, 1) : 0.0;

                        $ramInfo = [
                            'total_gb' => $totalGb,
                            'used_gb' => $usedGb,
                            'free_gb' => $freeGb,
                            'usage_pct' => $usedPct,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        return $ramInfo;
    }

    /**
     * Get GPU Information (Name, Adapter RAM).
     */
    protected function getGpuMetrics(): array
    {
        $gpuInfo = [
            'name' => 'Host Graphics Accelerator',
            'vram_gb' => null,
            'status' => 'Active',
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $rawJson = $this->runPowerShell("Get-CimInstance Win32_VideoController | Select-Object Name, AdapterRAM | ConvertTo-Json");
                if ($rawJson) {
                    $json = json_decode($rawJson, true);
                    if (isset($json[0])) {
                        $json = $json[0];
                    }
                    if (!empty($json['Name'])) {
                        $gpuInfo['name'] = trim($json['Name']);
                    }
                    if (!empty($json['AdapterRAM']) && is_numeric($json['AdapterRAM'])) {
                        $vramGb = round((float) $json['AdapterRAM'] / 1024 / 1024 / 1024, 1);
                        if ($vramGb > 0) {
                            $gpuInfo['vram_gb'] = $vramGb;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        return $gpuInfo;
    }

    /**
     * Get OS & Host Runtime metrics.
     */
    protected function getSystemMetrics(): array
    {
        $osName = php_uname('s') . ' ' . php_uname('r');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $rawJson = $this->runPowerShell("Get-CimInstance Win32_OperatingSystem | Select-Object Caption, OSArchitecture | ConvertTo-Json");
                if ($rawJson) {
                    $json = json_decode($rawJson, true);
                    if (!empty($json['Caption'])) {
                        $caption = str_replace('Microsoft ', '', trim($json['Caption']));
                        $arch = !empty($json['OSArchitecture']) ? ' (' . trim($json['OSArchitecture']) . ')' : '';
                        $osName = $caption . $arch;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        return [
            'os' => $osName,
            'hostname' => gethostname(),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Artisan Dev Server',
        ];
    }
}
