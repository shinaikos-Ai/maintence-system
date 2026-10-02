<?php

namespace App\Filament\Pages;

use App\Models\MaintenanceRequest;
use App\Services\Access;
use Filament\Pages\Page;

class RequestsBoard extends Page
{
    protected static ?string $title = 'Kanban de solicitações';

    protected string $view = 'filament.pages.requests-board';

    public static function canAccess(): bool
    {
        return Access::view(auth()->user(), 'MaintenanceRequest');
    }

    public function columns(): array
    {
        $result = [];
        foreach (['Aberta', 'Em Análise', 'Aprovada', 'Rejeitada'] as $status) {
            $q = Access::scope(MaintenanceRequest::with('equipment'), auth()->user(), 'MaintenanceRequest')->where('status', $status);
            $result[$status] = ['total' => $q->count(), 'items' => $q->latest()->limit(50)->get()];
        }

        return $result;
    }
}
