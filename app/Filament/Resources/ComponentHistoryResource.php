<?php

namespace App\Filament\Resources;

class ComponentHistoryResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\ComponentHistory::class;

    public static function getPages(): array
    {
        return ['index' => ComponentHistory\ManageRecords::route('/')];
    }
}
