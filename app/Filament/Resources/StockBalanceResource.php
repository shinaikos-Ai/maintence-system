<?php

namespace App\Filament\Resources;

class StockBalanceResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\StockBalance::class;

    public static function getPages(): array
    {
        return ['index' => StockBalance\ManageRecords::route('/')];
    }
}
