<?php

namespace App\Filament\Resources;

class CostCenterResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\CostCenter::class;

    public static function getPages(): array
    {
        return ['index' => CostCenter\ManageRecords::route('/')];
    }
}
