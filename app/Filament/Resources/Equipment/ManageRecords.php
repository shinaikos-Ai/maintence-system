<?php

namespace App\Filament\Resources\Equipment;

use App\Filament\Resources\EquipmentResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'Equipment', $data)),
        ];
    }
}
