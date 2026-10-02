<?php

namespace App\Filament\Resources;

class TireResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Tire::class;

    public static function getPages(): array
    {
        return ['index' => Tire\ManageRecords::route('/')];
    }
}
