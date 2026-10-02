<?php

namespace App\Filament\Support;

use App\Models\Location;
use App\Models\Part;
use App\Models\User;
use App\Services\Lifecycle;
use App\Services\Maintenance;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class WorkflowActions
{
    public static function for(string $kind): array
    {
        $svc = fn () => app(Maintenance::class);
        $worker = fn ($record) => $record->status !== 'Encerrada' && (auth()->user()->manages() || (auth()->user()->role === 'Mecânico' && $record->assigned_to === auth()->id()));
        $num = fn ($name, $label) => TextInput::make($name)->label($label)->numeric()->minValue(0)->required();

        return match ($kind) {
            'Component','Tire' => [
                Action::make('event')->label('Registrar evento / custo')->visible(fn () => auth()->user()->manages())->schema([
                    Select::make('action')->label('Evento')->options(array_combine($events = ['Inspeção', 'Reforma', 'Substituição', 'Descarte'], $events))->required(),
                    $num('reading', 'Horímetro'), $num('cost', 'Custo'), Textarea::make('description')->label('Detalhes')->required(),
                ])->action(fn ($record, $data) => app(Lifecycle::class)->record(auth()->user(), $record, $data)),
            ],
            'MaintenanceRequest' => [
                Action::make('review')->label('Analisar')->visible(fn () => auth()->user()->manages())->schema([Select::make('status')->label('Decisão')->options(array_combine($s = ['Em Análise', 'Aprovada', 'Rejeitada'], $s))->required()])->action(fn ($record, $data) => $svc()->review(auth()->user(), $record, $data['status'])),
            ],
            'WorkOrder' => [
                Action::make('assign')->label('Designar / Prioridade')->visible(fn ($record) => auth()->user()->manages() && $record->status !== 'Encerrada')->schema([
                    Select::make('assigned_to')->label('Mecânico')->options(fn () => User::where('role', 'Mecânico')->where('active', true)->pluck('name', 'id'))->required(),
                    Select::make('priority')->label('Prioridade')->options(array_combine($p = ['Baixa', 'Média', 'Alta', 'Crítica'], $p))->required(),
                    DateTimePicker::make('due_at')->label('Prazo')->required(),
                ])->action(fn ($record, $data) => $svc()->assign(auth()->user(), $record, $data)),
                Action::make('start')->label('Iniciar / Retomar')->visible($worker)->requiresConfirmation()->action(fn ($record) => $svc()->start(auth()->user(), $record)),
                Action::make('diagnose')->label('Diagnóstico / Serviço')->visible($worker)->schema([Textarea::make('diagnosis')->label('Diagnóstico')->required(), Textarea::make('root_cause')->label('Causa raiz')->required(), Textarea::make('service_performed')->label('Serviço executado')->required()])->fillForm(fn ($record) => $record->only(['diagnosis', 'root_cause', 'service_performed']))->action(fn ($record, $data) => $svc()->diagnose(auth()->user(), $record, $data)),
                Action::make('parts')->label('Solicitar peça')->visible($worker)->schema([Select::make('part_id')->label('Peça')->options(fn () => Part::pluck('description', 'id'))->searchable()->required(), $num('quantity', 'Quantidade')])->action(fn ($record, $data) => $svc()->requestPart(auth()->user(), $record, $data)),
                Action::make('labor')->label('Registrar mão de obra')->visible($worker)->schema([$num('hours', 'Horas'), $num('hourly_rate', 'Valor hora'), DatePicker::make('worked_on')->label('Data')->default(today())->required(), Textarea::make('description')->label('Atividade')->required()])->action(fn ($record, $data) => $svc()->labor(auth()->user(), $record, $data)),
                Action::make('close')->label('Encerrar OS')->color('success')->visible($worker)->schema([$num('closing_hour_meter', 'Horímetro final'), $num('closing_mileage', 'Quilometragem final')])->requiresConfirmation()->action(fn ($record, $data) => $svc()->close(auth()->user(), $record, $data)),
                Action::make('sign')->label('Assinar OS encerrada')->visible(fn ($record) => $record->status === 'Encerrada' && (auth()->user()->reports() || $record->assigned_to === auth()->id()))->schema([FileUpload::make('path')->label('Imagem da assinatura (PNG/JPG)')->disk('local')->directory('signatures')->visibility('private')->acceptedFileTypes(['image/png', 'image/jpeg'])->maxSize(2048)->preventFilePathTampering()->required()])->action(fn ($record, $data) => $svc()->sign(auth()->user(), $record, $data['path'])),
                Action::make('timeline')->label('Timeline')->url(fn ($record) => route('work-order.timeline', $record))->openUrlInNewTab(),
            ],
            'PartUsage' => [
                Action::make('release')->label('Liberar / Consumir')->visible(fn ($record) => auth()->user()->manages() && $record->status === 'Solicitada')->requiresConfirmation()->action(fn ($record) => $svc()->release(auth()->user(), $record, true)),
                Action::make('reject')->label('Rejeitar')->visible(fn ($record) => auth()->user()->manages() && $record->status === 'Solicitada')->requiresConfirmation()->action(fn ($record) => $svc()->release(auth()->user(), $record, false)),
            ],
            'Part' => [Action::make('stock')->label('Movimentar estoque')->visible(fn () => auth()->user()->manages())->schema([
                Select::make('type')->label('Tipo')->options(array_combine($t = ['Entrada', 'Saída', 'Transferência', 'Ajuste', 'Inventário'], $t))->required(),
                $num('quantity', 'Quantidade (saldo contado em ajuste/inventário)'), $num('unit_cost', 'Custo unitário de entrada'),
                Select::make('from_location_id')->label('Localização de origem / contagem')->options(fn () => Location::pluck('name', 'id')),
                Select::make('to_location_id')->label('Destino da entrada / transferência')->options(fn () => Location::pluck('name', 'id')),
                Textarea::make('reason')->label('Motivo')->required(),
            ])->action(fn ($record, $data) => $svc()->stock(auth()->user(), $record, $data))],
            'Equipment' => [
                Action::make('qr')->label('QR Code')->url(fn ($record) => route('equipment.qr', $record))->openUrlInNewTab(),
                Action::make('history')->label('Histórico completo')->visible(fn () => auth()->user()->reports())->url(fn ($record) => route('equipment.timeline', $record))->openUrlInNewTab(),
            ],
            'Attachment' => [Action::make('download')->label('Baixar')->url(fn ($record) => route('attachment.download', $record))],
            'Signature' => [Action::make('download')->label('Ver assinatura')->url(fn ($record) => route('signature.download', $record))],
            default => [],
        };
    }
}
