<?php

namespace App\Filament\Resources\LaborEntry;

use App\Filament\Resources\LaborEntryResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = LaborEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'LaborEntry', $data)),
        ];
    }
}
