<?php

namespace App\Models\Concerns;

use App\Models\Audit;
use App\Models\Equipment;
use App\Models\EquipmentHistory;

trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                if ($model instanceof Audit) {
                    return;
                }
                $hidden = ['password', 'remember_token'];
                Audit::create(['user_id' => auth()->id(), 'action' => $event, 'subject_type' => class_basename($model),
                    'subject_id' => $model->id, 'old_values' => $event === 'created' ? null : collect($model->getRawOriginal())->except($hidden)->all(),
                    'new_values' => $event === 'deleted' ? null : collect($model->getAttributes())->except($hidden)->all()]);
            });
        }
        static::updated(function ($model) {
            if ($model instanceof Equipment && $model->wasChanged(['hour_meter', 'mileage'])) {
                EquipmentHistory::create(['equipment_id' => $model->id, 'event' => 'Atualização de leitura', 'hour_meter' => $model->hour_meter, 'mileage' => $model->mileage]);
            }
        });
    }
}
