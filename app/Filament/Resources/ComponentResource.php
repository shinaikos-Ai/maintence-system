<?php

namespace App\Filament\Resources;

class ComponentResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Component::class;

    public static function getPages(): array
    {
        return ['index' => Component\ManageRecords::route('/')];
    }
}
