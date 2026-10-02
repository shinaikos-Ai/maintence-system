<?php

namespace App\Filament\Resources;

class PartResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Part::class;

    public static function getPages(): array
    {
        return ['index' => Part\ManageRecords::route('/')];
    }
}
