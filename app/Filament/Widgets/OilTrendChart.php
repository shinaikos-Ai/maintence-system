<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\OilAnalysis;
use Filament\Widgets\ChartWidget;

class OilTrendChart extends ChartWidget
{
    protected ?string $heading = 'SOS • tendência por equipamento (últimas 20 amostras)';

    public ?string $filter = null;

    public static function canView(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    protected function getFilters(): ?array
    {
        return Equipment::orderBy('tag')->pluck('tag', 'id')->all();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $id = $this->filter ?? Equipment::orderBy('tag')->value('id');
        $rows = OilAnalysis::where('equipment_id', $id)->orderByDesc('collected_on')->limit(20)->get()->reverse()->values();

        return ['labels' => $rows->map(fn ($a) => $a->collected_on->format('d/m/Y'))->all(), 'datasets' => [
            ['label' => 'Ferro ppm', 'data' => $rows->pluck('iron_ppm')->all(), 'borderColor' => '#fb923c'],
            ['label' => 'Silício ppm', 'data' => $rows->pluck('silicon_ppm')->all(), 'borderColor' => '#38bdf8'],
        ]];
    }
}
