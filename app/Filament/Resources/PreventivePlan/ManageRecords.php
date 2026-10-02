<?php

namespace App\Filament\Resources\PreventivePlan;

use App\Filament\Resources\PreventivePlanResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = PreventivePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'PreventivePlan', $data)),
        ];
    }
}
