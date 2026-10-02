<?php

namespace App\Filament\Resources;

class LaborEntryResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\LaborEntry::class;

    public static function getPages(): array
    {
        return ['index' => LaborEntry\ManageRecords::route('/')];
    }
}
