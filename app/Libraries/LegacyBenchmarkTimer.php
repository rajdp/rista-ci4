<?php

namespace App\Libraries;

use CodeIgniter\Debug\Timer;

/**
 * Provides a compatibility layer for legacy controllers that still rely on the
 * old CodeIgniter 3 Benchmark API (`mark()`/`elapsed_time()`).
 *
 * The class bridges those calls to the modern CI4 `Timer` implementation so we
 * retain timing data without touching every call site.
 */
class LegacyBenchmarkTimer extends Timer
{
    /**
     * Mimics the CI3 `mark()` helper by delegating to Timer::start/stop.
     *
     * @return $this
     */
    public function mark(string $name): self
    {
        $normalized = strtolower($name);

        if ($this->isEndMarker($normalized)) {
            $base = $this->extractBaseName($normalized, '_end');

            // Ensure the timer exists before attempting to stop it.
            if (! $this->has($base)) {
                $this->start($base);
            }

            $this->stop($base);

            return $this;
        }

        $base = $this->isStartMarker($normalized)
            ? $this->extractBaseName($normalized, '_start')
            : $normalized;

        $this->start($base);

        return $this;
    }

    /**
     * Backwards-compatible wrapper around Timer::getElapsedTime().
     *
     * @param string $start    Marker name that originally ended with `_start`.
     * @param string $end      Marker name that originally ended with `_end`.
     * @param int    $decimals Decimal precision for the result.
     *
     * @return string|null
     */
    public function elapsed_time(string $start = '', string $end = '', int $decimals = 4): ?string
    {
        $timerName = $this->resolveTimerName($start, $end);

        if ($timerName === null) {
            return null;
        }

        $elapsed = $this->getElapsedTime($timerName, $decimals);

        return $elapsed !== null ? number_format($elapsed, $decimals, '.', '') : null;
    }

    private function isStartMarker(string $name): bool
    {
        return str_ends_with($name, '_start');
    }

    private function isEndMarker(string $name): bool
    {
        return str_ends_with($name, '_end');
    }

    private function extractBaseName(string $name, string $suffix): string
    {
        return rtrim(substr($name, 0, -strlen($suffix)));
    }

    private function resolveTimerName(string $start, string $end): ?string
    {
        $start = strtolower($start);
        $end   = strtolower($end);

        if ($start !== '' && $this->isStartMarker($start)) {
            return $this->extractBaseName($start, '_start');
        }

        if ($end !== '' && $this->isEndMarker($end)) {
            return $this->extractBaseName($end, '_end');
        }

        // Fall back to whichever marker name is provided.
        if ($start !== '') {
            return $start;
        }

        if ($end !== '') {
            return $end;
        }

        return null;
    }
}
