<?php

namespace App\Filament\Pages;

use App\Models\WorkOrder;
use Filament\Pages\Page;

class Schedule extends Page
{
    protected static ?string $title = 'Cronograma de manutenção';

    protected string $view = 'filament.pages.schedule';

    public static function canAccess(): bool
    {
        return auth()->user()?->reports() ?? false;
    }

    public function orders()
    {
        return WorkOrder::with(['equipment', 'assignedTo'])->where('status', '!=', 'Encerrada')->orderBy('due_at')->paginate(25);
    }
}
