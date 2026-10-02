<x-filament-panels::page>
    <p>Programação das ordens pendentes. Defina mecânico, prioridade e prazo na operação “Designar / Prioridade” da OS.</p>
    @php($orders=$this->orders())
    <x-filament::section>
        <div style="overflow:auto"><table class="cmf-table"><thead><tr><th>Prazo</th><th>OS</th><th>TAG</th><th>Mecânico</th><th>Prioridade</th><th>Status</th></tr></thead>
        <tbody>@forelse($orders as $order)<tr><td>{{ $order->due_at?->format('d/m/Y H:i') ?? 'A programar' }}</td><td>#{{ $order->id }}</td><td>{{ $order->equipment->tag }}</td><td>{{ $order->assignedTo?->name ?? 'Não designado' }}</td><td>{{ $order->priority }}</td><td>{{ $order->status }}</td></tr>@empty<tr><td colspan="6">Sem ordens pendentes.</td></tr>@endforelse</tbody></table></div>
        {{ $orders->links() }}
    </x-filament::section>
</x-filament-panels::page>
