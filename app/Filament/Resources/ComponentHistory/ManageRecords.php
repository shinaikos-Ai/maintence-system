<?php

namespace App\Filament\Resources\ComponentHistory;

use App\Filament\Resources\ComponentHistoryResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = ComponentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'ComponentHistory', $data)),
        ];
    }
}
