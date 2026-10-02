<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class TrendChart extends ChartWidget
{
    protected ?string $heading = 'Custos mensais • últimos 6 meses';

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
        $costs = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $labels[] = $m->format('m/Y');
            $costs[] = (float) WorkOrder::whereBetween('finished_at', [$m, $m->copy()->endOfMonth()])->sum('total_cost');
        }

        return ['labels' => $labels, 'datasets' => [['label' => 'Custo R$', 'data' => $costs, 'borderColor' => '#f97316']]];
    }
}
