<?php

namespace App\Filament\Resources\WorkOrder;

use App\Filament\Resources\WorkOrderResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'WorkOrder', $data)),
        ];
    }
}
