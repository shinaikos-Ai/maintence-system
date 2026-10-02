<?php

namespace App\Filament\Resources;

class PartUsageResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\PartUsage::class;

    public static function getPages(): array
    {
        return ['index' => PartUsage\ManageRecords::route('/')];
    }
}
