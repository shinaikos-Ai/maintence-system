<?php

namespace App\Filament\Resources;

class ContractResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Contract::class;

    public static function getPages(): array
    {
        return ['index' => Contract\ManageRecords::route('/')];
    }
}
