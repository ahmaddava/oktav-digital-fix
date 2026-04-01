<?php

namespace App\Filament\Resources\ProductionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Production;
use App\Models\Machine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class ProductionStats extends StatsOverviewWidget
{
    protected function getPollingInterval(): ?string
    {
        return '5s';
    }

    #[On('refresh-stats')]
    public function handleRefreshEvent(): void
    {
        $this->refresh();
    }

    protected function getStats(): array
    {
        try {
            $from = $this->filters['from'] ?? null;
            $until = $this->filters['until'] ?? null;

            $currentPeriod = $this->getDatePeriod($from, $until);
            $previousPeriod = $this->getPreviousPeriod($currentPeriod['start']);

            // Load all machines dynamically
            $machines = Machine::all();

            $stats = [];

            foreach ($machines as $machine) {
                // Determine current status (Busy or Free)
                // Look for items currently "started" on this machine
                $activeItem = \App\Models\InvoiceProduct::where('machine_id', $machine->id)
                    ->where('status', 'started')
                    ->with(['invoice', 'product'])
                    ->latest('id')
                    ->first();

                if ($activeItem) {
                    $jobInfo = ($activeItem->product_name ?? $activeItem->product?->product_name) . ' (' . ($activeItem->invoice?->invoice_number ?? '-') . ')';
                    $stats[] = Stat::make($machine->name, __('Sedang Digunakan'))
                        ->description($jobInfo)
                        ->color('warning')
                        ->icon('heroicon-m-cog-8-tooth');
                } else {
                    $stats[] = Stat::make($machine->name, __('Tersedia / Free'))
                        ->description(__('Mesin sedang tidak memproses item'))
                        ->color('success')
                        ->icon('heroicon-m-check-circle');
                }

                // If machine uses clicks, show click stats
                if ($machine->use_clicks) {
                    // Current period clicks
                    $currentClicks = Production::where('machine_id', $machine->id)
                        ->whereBetween('created_at', [$currentPeriod['start'], $currentPeriod['end']])
                        ->sum('total_clicks');

                    // Previous period clicks
                    $previousClicks = Production::where('machine_id', $machine->id)
                        ->whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])
                        ->sum('total_clicks');

                    $description = $this->getTrendDescription($currentClicks, $previousClicks);

                    $stats[] = Stat::make(__('Clicks') . ' ' . $machine->name, number_format($currentClicks))
                        ->description($description)
                        ->color($this->getTrendColor($currentClicks, $previousClicks))
                        ->icon($this->getTrendIcon($currentClicks, $previousClicks));

                    // Counter stats per machine
                    $counter = DB::table('productions')
                        ->where('machine_id', $machine->id)
                        ->sum('total_counter');

                    $stats[] = Stat::make(__('Total Counter') . ' ' . $machine->name, number_format($counter))
                        ->description(__('Akumulasi counter mesin'))
                        ->color('info')
                        ->icon('heroicon-m-cpu-chip');
                }
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error in ProductionStats::getStats: ' . $e->getMessage());
            return [
                Stat::make('Error', 'Terjadi kesalahan saat memuat data')
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-triangle'),
            ];
        }
    }

    private function getDatePeriod($from, $until): array
    {
        $start = $from ? Carbon::parse($from) : now()->startOfMonth();
        $end = $until ? Carbon::parse($until) : now()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->translatedFormat('F Y')
        ];
    }

    private function getPreviousPeriod(Carbon $currentStart): array
    {
        $previous = $currentStart->copy()->subMonth();
        return [
            'start' => $previous->startOfMonth(),
            'end' => $previous->endOfMonth()
        ];
    }

    private function getTrendDescription($current, $previous): string
    {
        $difference = $current - $previous;
        $formattedDiff = number_format(abs($difference));

        if ($previous === 0 || $previous == 0) {
            return "Peningkatan {$formattedDiff} (100%) dari bulan sebelumnya";
        }

        $percentage = round(abs($difference) / $previous * 100, 2);
        $trend = $difference >= 0 ? 'peningkatan' : 'penurunan';

        return "{$trend} {$formattedDiff} ({$percentage}%) dari bulan sebelumnya";
    }

    private function getTrendColor($current, $previous): string
    {
        return $current >= $previous ? 'success' : 'danger';
    }

    private function getTrendIcon($current, $previous): string
    {
        return $current >= $previous
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }

    public static function canView(): bool
    {
        return true;
    }
}