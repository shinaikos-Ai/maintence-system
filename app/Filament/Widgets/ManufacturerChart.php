<?php

namespace App\Filament\Widgets;

use App\Services\Reports;
use Filament\Widgets\ChartWidget;

class ManufacturerChart extends ChartWidget
{
    protected ?string $heading = 'Custos por fabricante • mês';

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
        $rows = collect(app(Reports::class)->rows('fabricante', now()->startOfMonth(), now()));

        return ['labels' => $rows->pluck('Fabricante')->all(), 'datasets' => [['label' => 'Custo R$', 'data' => $rows->pluck('Custo R$')->all(), 'backgroundColor' => '#fb923c']]];
    }
}
