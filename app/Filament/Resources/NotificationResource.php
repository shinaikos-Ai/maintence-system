<?php

namespace App\Filament\Resources;

class NotificationResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Notification::class;

    public static function getPages(): array
    {
        return ['index' => Notification\ManageRecords::route('/')];
    }
}
