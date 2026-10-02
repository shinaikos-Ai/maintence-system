<?php

namespace App\Filament\Resources\PreventiveExecution;

use App\Filament\Resources\PreventiveExecutionResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = PreventiveExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'PreventiveExecution', $data)),
        ];
    }
}
