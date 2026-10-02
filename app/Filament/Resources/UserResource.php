<?php

namespace App\Filament\Resources;

class UserResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\User::class;

    public static function getPages(): array
    {
        return ['index' => User\ManageRecords::route('/')];
    }
}
