<x-filament-panels::page>
    <p>Solicitações agrupadas por etapa. Use “Analisar” na lista de solicitações para registrar a decisão.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px">
    @foreach($this->columns() as $status=>$column)
    <x-filament::section heading="{{ $status }} ({{ $column['total'] }})">
        @forelse($column['items'] as $r)
        <article style="border:1px solid #52525b;border-left:4px solid #f97316;border-radius:8px;padding:14px;margin-bottom:12px">
            <strong>#{{ $r->id }} · {{ $r->equipment->tag }}</strong><p>{{ Str::limit($r->description,120) }}</p><small>{{ $r->priority }} · {{ $r->created_at->format('d/m/Y') }}</small>
        </article>
        @empty<p>Sem solicitações.</p>@endforelse
        @if($column['total']>50)<p>Exibindo as 50 mais recentes. A lista completa está em Solicitações.</p>@endif
    </x-filament::section>
    @endforeach
    </div>
</x-filament-panels::page>
