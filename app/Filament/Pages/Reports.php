<?php

namespace App\Filament\Pages;

use App\Services\Reports as ReportService;
use Carbon\Carbon;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $title = 'Relatórios';

    protected string $view = 'filament.pages.reports';

    public string $type = 'frota';

    public string $from = '';

    public string $to = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = today()->toDateString();
    }

    public function rows(): array
    {
        $this->validate(['type' => 'required|in:'.implode(',', array_keys(ReportService::TYPES)), 'from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);

        return app(ReportService::class)->rows($this->type, Carbon::parse($this->from)->startOfDay(), Carbon::parse($this->to)->endOfDay()->min(now()));
    }
}
