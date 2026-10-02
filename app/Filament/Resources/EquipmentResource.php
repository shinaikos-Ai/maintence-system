<?php

namespace App\Filament\Resources;

class EquipmentResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Equipment::class;

    public static function getPages(): array
    {
        return ['index' => Equipment\ManageRecords::route('/')];
    }
}
