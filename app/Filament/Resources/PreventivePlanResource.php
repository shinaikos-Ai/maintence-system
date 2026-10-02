<?php

namespace App\Filament\Resources;

class PreventivePlanResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\PreventivePlan::class;

    public static function getPages(): array
    {
        return ['index' => PreventivePlan\ManageRecords::route('/')];
    }
}
