<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\PartUsage;
use App\Models\PreventiveExecution;
use App\Models\WorkOrder;
use Carbon\Carbon;

class Reports
{
    public const TYPES = ['frota' => 'Disponibilidade / MTBF / MTTR', 'parados' => 'Equipamentos parados', 'abertas' => 'OS abertas', 'encerradas' => 'OS encerradas', 'equipamento' => 'Custos por equipamento / TAG', 'contrato' => 'Custos por contrato', 'fabricante' => 'Custos por fabricante', 'consumo' => 'Consumo de peças', 'preventivas' => 'Preventivas executadas / pendentes'];

    public function equipment(Carbon $from, Carbon $to): array
    {
        $rows = [];
        $orders = WorkOrder::whereNotNull('started_at')->where('started_at', '<=', $to)->where(fn ($q) => $q->whereNull('finished_at')->orWhere('finished_at', '>=', $from))->get()->groupBy('equipment_id');
        $costs = WorkOrder::whereBetween('finished_at', [$from, $to])->selectRaw('equipment_id, SUM(total_cost) as total')->groupBy('equipment_id')->pluck('total', 'equipment_id');
        foreach (Equipment::with('contract')->where('status', '!=', 'Inativo')->get() as $e) {
            $start = $from->max($e->created_at);
            if ($start > $to) {
                continue;
            }
            $window = max(0, $start->diffInSeconds($to));
            $ranges = [];
            $failures = 0;
            $repairs = [];
            foreach ($orders->get($e->id, collect()) as $w) {
                $a = max($start->timestamp, $w->started_at->timestamp);
                $b = min($to->timestamp, ($w->finished_at ?? now())->timestamp);
                if ($b > $a) {
                    $ranges[] = [$a, $b];
                }
                if ($w->type === 'Corretiva' && $w->started_at->between($from, $to)) {
                    $failures++;
                }
                if ($w->type === 'Corretiva' && $w->finished_at?->between($from, $to)) {
                    $repairs[] = $w->started_at->diffInSeconds($w->finished_at) / 3600;
                }
            }
            usort($ranges, fn ($a, $b) => $a[0] <=> $b[0]);
            $end = 0;
            $down = 0;
            foreach ($ranges as [$a,$b]) {
                $down += max(0, $b - max($end, $a));
                $end = max($end, $b);
            }
            $up = max(0, $window - $down) / 3600;
            $rows[] = ['TAG' => $e->tag, 'Fabricante' => $e->manufacturer, 'Contrato' => $e->contract?->name ?? 'Sem contrato', 'Status' => $e->status, 'Disponibilidade %' => $window ? round(100 * (1 - $down / $window), 2) : null, 'Horas paradas' => round($down / 3600, 2), 'Falhas' => $failures, 'MTBF h' => $failures ? round($up / $failures, 2) : null, 'MTTR h' => count($repairs) ? round(array_sum($repairs) / count($repairs), 2) : null, 'Custo R$' => (float) ($costs[$e->id] ?? 0)];
        }

        return $rows;
    }

    public function rows(string $type, Carbon $from, Carbon $to): array
    {
        if (in_array($type, ['frota', 'parados', 'equipamento', 'contrato', 'fabricante'])) {
            $rows = collect($this->equipment($from, $to));
            if ($type === 'parados') {
                return $rows->where('Status', 'Em Manutenção')->values()->all();
            }
            if (in_array($type, ['contrato', 'fabricante'])) {
                $key = $type === 'contrato' ? 'Contrato' : 'Fabricante';

                return $rows->groupBy($key)->map(fn ($group, $name) => [$key => $name, 'Custo R$' => $group->sum('Custo R$')])->values()->all();
            }

            return $rows->all();
        }
        if ($type === 'consumo') {
            return PartUsage::with(['part', 'workOrder'])->where('status', 'Liberada')->whereBetween('updated_at', [$from, $to])->get()->map(fn ($p) => ['OS' => $p->work_order_id, 'Peça' => $p->part->description, 'Quantidade' => $p->quantity, 'Custo R$' => round($p->quantity * $p->unit_cost, 2)])->all();
        }
        if ($type === 'preventivas') {
            return PreventiveExecution::with(['preventivePlan', 'workOrder'])->whereBetween('created_at', [$from, $to])->get()->map(fn ($e) => ['Plano' => $e->preventivePlan->name, 'OS' => $e->work_order_id, 'Status' => $e->completed_at ? 'Executada' : 'Pendente', 'Conclusão' => $e->completed_at?->format('d/m/Y H:i') ?? ''])->all();
        }

        return WorkOrder::with('equipment')->when($type === 'encerradas', fn ($q) => $q->where('status', 'Encerrada')->whereBetween('finished_at', [$from, $to]), fn ($q) => $q->where('status', '!=', 'Encerrada')->where('created_at', '<=', $to))->get()->map(fn ($w) => ['OS' => $w->id, 'TAG' => $w->equipment->tag, 'Tipo' => $w->type, 'Status' => $w->status, 'Prazo' => $w->due_at?->format('d/m/Y H:i') ?? '', 'Custo R$' => $w->total_cost])->all();
    }
}
