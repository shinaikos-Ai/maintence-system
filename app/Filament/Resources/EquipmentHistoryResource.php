<?php

namespace App\Filament\Resources;

class EquipmentHistoryResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\EquipmentHistory::class;

    public static function getPages(): array
    {
        return ['index' => EquipmentHistory\ManageRecords::route('/')];
    }
}
