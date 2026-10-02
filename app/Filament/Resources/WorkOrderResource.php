<?php

namespace App\Filament\Resources;

class WorkOrderResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\WorkOrder::class;

    public static function getPages(): array
    {
        return ['index' => WorkOrder\ManageRecords::route('/')];
    }
}
