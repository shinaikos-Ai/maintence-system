<?php

namespace App\Filament\Resources;

class AuditResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Audit::class;

    public static function getPages(): array
    {
        return ['index' => Audit\ManageRecords::route('/')];
    }
}
