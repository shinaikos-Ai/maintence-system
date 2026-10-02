<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Access
{
    public const READ_ONLY = ['StockBalance', 'StockMovement', 'LaborEntry', 'PartUsage', 'PreventiveExecution', 'EquipmentHistory', 'ComponentHistory', 'TireHistory', 'Audit', 'Signature', 'Notification'];

    public const WORKFLOW = ['WorkOrder', 'MaintenanceRequest'];

    public static function view(User $u, string $kind): bool
    {
        if (! $u->active) {
            return false;
        }
        if ($u->role === 'Administrador') {
            return true;
        }
        if ($kind === 'User' || $kind === 'Audit') {
            return false;
        }
        if ($u->reports()) {
            return true;
        }

        return in_array($kind, $u->role === 'Mecânico'
            ? ['Equipment', 'MaintenanceRequest', 'WorkOrder', 'Part', 'PartUsage', 'LaborEntry', 'Attachment', 'Signature']
            : ['Equipment', 'MaintenanceRequest', 'Attachment']);
    }

    public static function create(User $u, string $kind): bool
    {
        if (! self::view($u, $kind) || in_array($kind, self::READ_ONLY) || $kind === 'WorkOrder') {
            return false;
        }
        if ($kind === 'MaintenanceRequest') {
            return in_array($u->role, ['Administrador', 'Controlador', 'Mecânico', 'Operador']);
        }
        if ($kind === 'Attachment') {
            return $u->role !== 'Gerente';
        }

        return $kind === 'User' ? $u->role === 'Administrador' : $u->manages();
    }

    public static function edit(User $u, string $kind): bool
    {
        return self::create($u, $kind) && ! in_array($kind, ['MaintenanceRequest', 'Attachment']);
    }

    public static function delete(User $u, string $kind): bool
    {
        return $u->active && $u->role === 'Administrador' && in_array($kind, ['Equipment', 'Part', 'Component', 'Tire', 'OilAnalysis', 'PreventivePlan', 'Contract', 'CostCenter', 'Location']);
    }

    public static function scope(Builder $q, User $u, string $kind): Builder
    {
        if (! self::view($u, $kind)) {
            return $q->whereRaw('1=0');
        }
        if ($u->reports()) {
            return $q;
        }
        if ($kind === 'MaintenanceRequest') {
            return $q->where('requested_by', $u->id);
        }
        if ($kind === 'WorkOrder') {
            return $q->where('assigned_to', $u->id);
        }
        if (in_array($kind, ['PartUsage', 'LaborEntry', 'Signature'])) {
            return $q->whereIn('work_order_id', WorkOrder::where('assigned_to', $u->id)->select('id'));
        }
        if ($kind === 'Attachment') {
            return $q->where('user_id', $u->id);
        }

        return $q;
    }

    public static function record(User $u, Model $m): bool
    {
        return self::scope($m->newQuery(), $u, class_basename($m))->whereKey($m->id)->exists();
    }
}
