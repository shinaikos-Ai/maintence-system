<?php

namespace App\Filament\Resources\TireHistory;

use App\Filament\Resources\TireHistoryResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = TireHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'TireHistory', $data)),
        ];
    }
}
