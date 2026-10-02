<?php

namespace App\Filament\Widgets;

use App\Services\Reports;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReliabilityStats extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    protected function getStats(): array
    {
        $rows = collect(app(Reports::class)->equipment(now()->startOfMonth(), now()));

        return [
            Stat::make('MTBF médio (mês)', $rows->whereNotNull('MTBF h')->count() ? round($rows->whereNotNull('MTBF h')->avg('MTBF h'), 1).' h' : '—')->description('Média dos equipamentos com falhas'),
            Stat::make('MTTR médio (mês)', $rows->whereNotNull('MTTR h')->count() ? round($rows->whereNotNull('MTTR h')->avg('MTTR h'), 1).' h' : '—')->description('Média por equipamento com reparos concluídos'),
        ];
    }
}
