<?php

namespace App\Filament\Resources;

class TireHistoryResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\TireHistory::class;

    public static function getPages(): array
    {
        return ['index' => TireHistory\ManageRecords::route('/')];
    }
}
