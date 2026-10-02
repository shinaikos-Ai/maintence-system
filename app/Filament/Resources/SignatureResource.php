<?php

namespace App\Filament\Resources;

class SignatureResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Signature::class;

    public static function getPages(): array
    {
        return ['index' => Signature\ManageRecords::route('/')];
    }
}
