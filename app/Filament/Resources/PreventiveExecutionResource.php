<?php

namespace App\Filament\Resources;

class PreventiveExecutionResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\PreventiveExecution::class;

    public static function getPages(): array
    {
        return ['index' => PreventiveExecution\ManageRecords::route('/')];
    }
}
