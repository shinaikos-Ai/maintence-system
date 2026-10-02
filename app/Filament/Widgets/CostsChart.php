<?php

namespace App\Filament\Widgets;

use App\Services\Reports;
use Filament\Widgets\ChartWidget;

class CostsChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 equipamentos • custo no mês';

    public static function canView(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = collect(app(Reports::class)->equipment(now()->startOfMonth(), now()))->sortByDesc('Custo R$')->take(10);

        return ['labels' => $rows->pluck('TAG')->all(), 'datasets' => [['label' => 'Custo R$', 'data' => $rows->pluck('Custo R$')->all(), 'backgroundColor' => '#f97316']]];
    }
}
