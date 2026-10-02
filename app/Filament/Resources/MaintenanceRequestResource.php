<?php

namespace App\Filament\Resources;

class MaintenanceRequestResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\MaintenanceRequest::class;

    public static function getPages(): array
    {
        return ['index' => MaintenanceRequest\ManageRecords::route('/')];
    }
}
