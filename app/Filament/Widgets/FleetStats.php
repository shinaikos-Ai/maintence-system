<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\PreventiveExecution;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FleetStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    protected function getStats(): array
    {
        $total = Equipment::where('status', '!=', 'Inativo')->count();
        $stopped = Equipment::where('status', 'Em Manutenção')->count();

        return [
            Stat::make('Disponibilidade atual', $total ? round(100 * ($total - $stopped) / $total, 1).'%' : '—')->description('Frota ativa disponível agora')->color('success'),
            Stat::make('Equipamentos operando', Equipment::where('status', 'Operando')->count()),
            Stat::make('Equipamentos parados', $stopped)->color('danger'),
            Stat::make('OS abertas', WorkOrder::where('status', 'Aberta')->count()),
            Stat::make('OS em andamento', WorkOrder::whereIn('status', ['Em Andamento', 'Aguardando Peças'])->count()),
            Stat::make('OS encerradas', WorkOrder::where('status', 'Encerrada')->count()),
            Stat::make('OS atrasadas', WorkOrder::where('status', '!=', 'Encerrada')->where('due_at', '<', now())->count())->color('danger'),
            Stat::make('Preventivas pendentes', PreventiveExecution::whereNull('completed_at')->count()),
            Stat::make('Custo mensal', 'R$ '.number_format(WorkOrder::whereBetween('finished_at', [now()->startOfMonth(), now()])->sum('total_cost'), 2, ',', '.')),
            Stat::make('Backlog (OS)', WorkOrder::where('status','!=','Encerrada')->count())->description('Quantidade de ordens pendentes'),
        ];
    }
}
