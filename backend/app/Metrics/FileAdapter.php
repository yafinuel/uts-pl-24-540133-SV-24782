<?php

declare(strict_types=1);

namespace App\Metrics;

use Prometheus\Storage\InMemory;

class FileAdapter extends InMemory
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? storage_path('framework/cache/prometheus_metrics.json');
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->filePath)) {
            $content = @file_get_contents($this->filePath);
            if ($content !== false) {
                $data = @json_decode($content, true);
                if (is_array($data)) {
                    $this->counters = $data['counters'] ?? [];
                    $this->gauges = $data['gauges'] ?? [];
                    $this->histograms = $data['histograms'] ?? [];
                    $this->summaries = $data['summaries'] ?? [];
                }
            }
        }
    }

    private function save(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $data = [
            'counters' => $this->counters,
            'gauges' => $this->gauges,
            'histograms' => $this->histograms,
            'summaries' => $this->summaries,
        ];
        @file_put_contents($this->filePath, json_encode($data), LOCK_EX);
    }

    public function collect(bool $sortMetrics = true): array
    {
        $this->load();
        return parent::collect($sortMetrics);
    }

    public function updateCounter(array $data): void
    {
        $this->load();
        parent::updateCounter($data);
        $this->save();
    }

    public function updateGauge(array $data): void
    {
        $this->load();
        parent::updateGauge($data);
        $this->save();
    }

    public function updateHistogram(array $data): void
    {
        $this->load();
        parent::updateHistogram($data);
        $this->save();
    }

    public function updateSummary(array $data): void
    {
        $this->load();
        parent::updateSummary($data);
        $this->save();
    }

    public function wipeStorage(): void
    {
        parent::wipeStorage();
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }
}
