<?php

namespace App\Filament\Resources;

class LocationResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Location::class;

    public static function getPages(): array
    {
        return ['index' => Location\ManageRecords::route('/')];
    }
}
