<?php

namespace App\Filament\Resources\MaintenanceRequest;

use App\Filament\Resources\MaintenanceRequestResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'MaintenanceRequest', $data)),
        ];
    }
}
