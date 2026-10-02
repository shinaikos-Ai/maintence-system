<?php

namespace App\Filament\Resources;

class AttachmentResource extends MaintenanceResource
{
    protected static ?string $model = \App\Models\Attachment::class;

    public static function getPages(): array
    {
        return ['index' => Attachment\ManageRecords::route('/')];
    }
}
