<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class TypesChart extends ChartWidget
{
    protected ?string $heading = 'Corretiva × Preventiva • mês';

    public static function canView(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $q = WorkOrder::whereBetween('created_at', [now()->startOfMonth(), now()])->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return ['labels' => ['Corretiva', 'Preventiva'], 'datasets' => [['label' => 'OS', 'data' => [$q['Corretiva'] ?? 0, $q['Preventiva'] ?? 0], 'backgroundColor' => ['#f97316', '#22c55e']]]];
    }
}
