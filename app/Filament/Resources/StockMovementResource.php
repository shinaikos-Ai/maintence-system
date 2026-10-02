<?php

namespace App\Filament\Resources;

class StockMovementResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\StockMovement::class;

    public static function getPages(): array
    {
        return ['index' => StockMovement\ManageRecords::route('/')];
    }
}
