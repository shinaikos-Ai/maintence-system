<?php

namespace App\Filament\Resources;

use App\Filament\Support\WorkflowActions;
use App\Models\LaborEntry;
use App\Models\TireHistory;
use App\Services\Access;
use App\Services\Catalog;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class MaintenanceResource extends Resource
{
    public static function kind(): string
    {
        return class_basename(static::getModel());
    }

    public static function definition(): array
    {
        return static::kind() === 'User' ? ['label' => 'Usuários', 'required' => ['name', 'email', 'role'], 'fields' => [
            'name' => ['type' => 'string', 'label' => 'Nome'], 'email' => ['type' => 'string', 'label' => 'E-mail'],
            'password' => ['type' => 'password', 'label' => 'Senha'], 'role' => ['type' => 'select', 'label' => 'Perfil', 'options' => ['Operador', 'Mecânico', 'Controlador', 'Gerente', 'Administrador']],
            'active' => ['type' => 'bool', 'label' => 'Ativo'],
        ]] : config('maintenance.'.static::kind());
    }

    public static function getModelLabel(): string
    {
        return static::definition()['label'];
    }

    public static function getPluralModelLabel(): string
    {
        return static::definition()['label'];
    }

    public static function getNavigationGroup(): ?string
    {
        return match (static::kind()) {
            'Equipment','Component','Tire','OilAnalysis' => 'Frota',
            'MaintenanceRequest','WorkOrder','PartUsage','LaborEntry','Attachment','Signature' => 'Operação',
            'Part','StockMovement','StockBalance' => 'Estoque',
            'PreventivePlan','PreventiveExecution' => 'Preventivas',
            'EquipmentHistory','ComponentHistory','TireHistory','Notification' => 'Histórico',
            default => 'Administração'
        };
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && Access::view(auth()->user(), static::kind());
    }

    public static function canCreate(): bool
    {
        return auth()->check() && Access::create(auth()->user(), static::kind());
    }

    public static function canEdit(Model $record): bool
    {
        return Access::edit(auth()->user(), static::kind());
    }

    public static function canView(Model $record): bool
    {
        return Access::record(auth()->user(), $record);
    }

    public static function canDelete(Model $record): bool
    {
        return Access::delete(auth()->user(), static::kind());
    }

    public static function getEloquentQuery(): Builder
    {
        return Access::scope(parent::getEloquentQuery(), auth()->user(), static::kind());
    }

    public static function titleField(string $kind): string
    {
        return match ($kind) {
            'Equipment' => 'tag','Part' => 'description','MaintenanceRequest','WorkOrder' => 'id','Tire' => 'fire_number','OilAnalysis' => 'sample_number',default => 'name'
        };
    }

    public static function form(Schema $schema): Schema
    {
        $components = [];
        $kind = static::kind();
        $definition = static::definition();
        foreach ($definition['fields'] as $name => $f) {
            if ($kind === 'MaintenanceRequest' && in_array($name, ['requested_by', 'status'])) {
                continue;
            }
            if ($kind === 'Attachment' && $name === 'user_id') {
                continue;
            }
            if ($kind === 'Part' && $name === 'stock') {
                continue;
            }
            $c = match ($f['type']) {
                'text','json' => Textarea::make($name)->rows(3),
                'select' => Select::make($name)->options(array_combine($f['options'], $f['options'])),
                'bool' => Toggle::make($name)->default(true),
                'date' => DatePicker::make($name),
                'datetime' => DateTimePicker::make($name),
                'file' => FileUpload::make($name)->disk('local')->directory('attachments')->visibility('private')->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])->maxSize(10240)->preventFilePathTampering(),
                'ref' => Select::make($name)->searchable()->getSearchResultsUsing(function (string $search) use ($f) {
                    $class = 'App\\Models\\'.$f['target'];
                    $title = static::titleField($f['target']);
                    $q = Access::scope($class::query(), auth()->user(), $f['target']);
                    if ($title === 'id') {
                        $q->where('id', is_numeric($search) ? $search : 0);
                    } else {
                        $q->where($title, 'like', '%'.$search.'%');
                    }

                    return $q->limit(50)->pluck($title, 'id');
                })->getOptionLabelUsing(function ($value) use ($f) {
                    $class = 'App\\Models\\'.$f['target'];

                    return Access::scope($class::query(), auth()->user(), $f['target'])->find($value)?->{static::titleField($f['target'])};
                }),
                default => TextInput::make($name)->maxLength(255),
            };
            if (in_array($f['type'], ['decimal', 'money', 'int'])) {
                $c->numeric()->minValue(0)->default(0);
            }
            if ($name === 'password') {
                $c->password()->minLength(12)->required(fn (string $operation) => $operation === 'create')->dehydrated(fn ($state) => filled($state));
            }
            if ($name === 'email') {
                $c->email();
            }
            if (in_array($name, ['tag', 'code', 'sample_number', 'fire_number', 'email'])) {
                $c->unique(ignoreRecord: true);
            }
            $c->label($f['label']);
            if (in_array($name, $definition['required'])) {
                $c->required();
            }
            $components[] = $c;
        }

        return $schema->components($components)->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        $entries = [];
        foreach (static::definition()['fields'] as $name => $field) {
            if ($name === 'password') {
                continue;
            }
            $entry = TextEntry::make($name)->label($field['label'])->placeholder('—');
            if ($field['type'] === 'json') {
                $entry->state(fn ($record) => json_encode($record->$name, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            if ($field['type'] === 'file') {
                $entry->state('Arquivo privado')->url(fn ($record) => route(static::kind() === 'Signature' ? 'signature.download' : 'attachment.download', $record));
            }
            if ($field['type'] === 'ref') {
                $entry->state(function ($record) use ($name, $field) {
                    $relation = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', $name)))));

                    return $record->$relation?->{static::titleField($field['target'])};
                });
            }
            $entries[] = $entry;
        }
        if (static::kind() === 'WorkOrder') {
            $entries[] = TextEntry::make('labor_total')->label('Horas trabalhadas')->state(fn ($record) => LaborEntry::where('work_order_id', $record->id)->sum('hours'));
        }
        if (static::kind() === 'Tire') {
            $entries[] = TextEntry::make('life_cost')->label('Custo histórico do pneu')->state(fn ($record) => TireHistory::where('tire_id', $record->id)->sum('cost'));
        }
        $entries[] = TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i');

        return $schema->components($entries)->columns(2);
    }

    public static function table(Table $table): Table
    {
        $columns = [TextColumn::make('id')->label('#')->sortable()];
        foreach (static::definition()['fields'] as $name => $f) {
            if (in_array($name, ['password', 'path', 'image_path'])) {
                continue;
            }
            if (in_array($f['type'], ['text', 'json'])) {
                continue;
            }
            $path = $name;
            if ($f['type'] === 'ref') {
                $rel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', $name)))));
                $path = $rel.'.'.static::titleField($f['target']);
            }
            $c = TextColumn::make($path)->label($f['label'])->sortable()->toggleable(isToggledHiddenByDefault: count($columns) > 6);
            if (in_array($f['type'], ['string', 'select'])) {
                $c->searchable();
            }
            if ($f['type'] === 'select') {
                $c->badge();
            }
            if ($f['type'] === 'money') {
                $c->money('BRL');
            }
            if ($f['type'] === 'datetime') {
                $c->dateTime('d/m/Y H:i');
            }
            if ($f['type'] === 'date') {
                $c->date('d/m/Y');
            }
            $columns[] = $c;
        }
        if (static::kind() === 'PreventivePlan') {
            $columns[] = TextColumn::make('alert')->label('Vencimento')->state(function ($record) {
                $e = $record->equipment;
                $remaining = $record->basis === 'Calendário' ? today()->diffInDays($record->next_date, false) : $record->next_value - ($record->basis === 'Horímetro' ? $e->hour_meter : $e->mileage);

                return $remaining <= 0 ? 'Vencida' : ($remaining <= $record->warning_threshold ? 'Atenção' : 'Em dia');
            })->badge()->color(fn ($state) => match ($state) {
                'Vencida' => 'danger','Atenção' => 'warning',default => 'success'
            });
        }
        if (in_array(static::kind(), ['Component', 'Tire'])) {
            $columns[] = TextColumn::make('total_hours')->label('Horas acumuladas')->state(fn ($record) => (float) $record->worked_hours + max(0, ($record->equipment?->hour_meter ?? 0) - $record->installation_meter));
        }
        $filters = [];
        foreach (static::definition()['fields'] as $name => $f) {
            if ($f['type'] === 'select') {
                $filters[] = SelectFilter::make($name)->label($f['label'])->options(array_combine($f['options'], $f['options']));
            }
        }

        return $table->columns($columns)->filters($filters)->defaultSort('id', 'desc')->paginated([10, 25, 50])
            ->recordActions([
                ViewAction::make()->label('Detalhes'),
                EditAction::make()->using(fn (Model $record, array $data) => app(Catalog::class)->save(auth()->user(), static::kind(), $data, $record)),
                DeleteAction::make()->using(fn (Model $record) => app(Catalog::class)->delete(auth()->user(), $record)),
                ActionGroup::make(WorkflowActions::for(static::kind()))->label('Operações'),
            ]);
    }
}
