<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class FailuresChart extends ChartWidget
{
    protected ?string $heading = 'Tendência de quebras • últimos 6 meses';

    public static function canView(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $labels[] = $m->format('m/Y');
            $counts[] = WorkOrder::where('type', 'Corretiva')->whereBetween('started_at', [$m, $m->copy()->endOfMonth()])->count();
        }

        return ['labels' => $labels, 'datasets' => [['label' => 'Falhas com OS iniciada', 'data' => $counts, 'borderColor' => '#ef4444']]];
    }
}
