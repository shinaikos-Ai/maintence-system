<?php

namespace App\Filament\Resources;

class OilAnalysisResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\OilAnalysis::class;

    public static function getPages(): array
    {
        return ['index' => OilAnalysis\ManageRecords::route('/')];
    }
}
