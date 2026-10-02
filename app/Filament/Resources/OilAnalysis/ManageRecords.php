<?php

namespace App\Filament\Resources\OilAnalysis;

use App\Filament\Resources\OilAnalysisResource;
use App\Services\Catalog;
use Filament\Actions\CreateAction;

class ManageRecords extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = OilAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => app(Catalog::class)->save(auth()->user(), 'OilAnalysis', $data)),
        ];
    }
}
