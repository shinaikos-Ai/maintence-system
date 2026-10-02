<?php

namespace App\Filament\Resources\EquipmentHistory;

use App\Filament\Resources\EquipmentHistoryResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = EquipmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'EquipmentHistory', $data)),
        ];
    }
}
