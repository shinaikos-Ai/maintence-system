<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $equipment->tag }}</title><style>body{background:#111318;color:#eee;font:18px system-ui;max-width:700px;margin:auto;padding:28px}h1,a{color:#fb923c}dt{color:#a1a1aa}dd{margin:0 0 20px}img{background:white;padding:10px;border-radius:12px}</style></head><body><a href="/admin">← Painel</a><h1>{{ $equipment->tag }}</h1><p>{{ $equipment->manufacturer }} {{ $equipment->model }}</p>
<dl><dt>Status</dt><dd>{{ $equipment->status }}</dd><dt>Horímetro</dt><dd>{{ $equipment->hour_meter }} h</dd><dt>Quilometragem</dt><dd>{{ $equipment->mileage }} km</dd><dt>Criticidade</dt><dd>{{ $equipment->criticality }}</dd></dl>
<img src="{{ route('equipment.qr',$equipment) }}" width="200" height="200" alt="QR Code do equipamento">
<p><a href="/admin/maintenance-requests">Solicitações de manutenção</a></p>
@if(auth()->user()->reports())<p><a href="{{ route('equipment.timeline',$equipment) }}">Histórico completo</a></p>@endif
</body></html>
