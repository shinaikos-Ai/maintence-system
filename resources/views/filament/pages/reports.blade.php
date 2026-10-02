<x-filament-panels::page>
    <x-filament::section>
        <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:end">
            <label>Relatório <select wire:model.live="type" class="cmf-input">@foreach(\App\Services\Reports::TYPES as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label>De <input type="date" wire:model.live="from" class="cmf-input"></label>
            <label>Até <input type="date" wire:model.live="to" class="cmf-input"></label>
            @foreach(['pdf'=>'PDF','xlsx'=>'Excel'] as $format=>$label)
            <x-filament::button tag="a" href="{{ route('reports.export', ['format'=>$format,'type'=>$type,'from'=>$from,'to'=>$to]) }}">{{ $label }}</x-filament::button>
            @endforeach
        </div>
        <p style="margin-top:16px;color:#a1a1aa">Disponibilidade e MTBF usam horas corridas da frota ativa. Custos são reconhecidos no encerramento da OS. “—” indica ausência de base para cálculo.</p>
    </x-filament::section>
    @php($rows=$this->rows())
    <x-filament::section>
        <div style="overflow:auto"><table class="cmf-table">
            @if(count($rows))<thead><tr>@foreach(array_keys($rows[0]) as $key)<th>{{ $key }}</th>@endforeach</tr></thead>@endif
            <tbody>@forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>@empty<tr><td>Nenhum registro no período.</td></tr>@endforelse</tbody>
        </table></div>
    </x-filament::section>
</x-filament-panels::page>
