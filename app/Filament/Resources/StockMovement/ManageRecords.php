<?php

namespace App\Filament\Resources\StockMovement;

use App\Filament\Resources\StockMovementResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'StockMovement', $data)),
        ];
    }
}
