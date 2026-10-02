<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>body{font:10px DejaVu Sans;color:#222}h1{color:#c65c0a}table{width:100%;border-collapse:collapse}th,td{padding:5px;border-bottom:1px solid #ccc;text-align:left}th{background:#eee}</style></head><body>
<h1>CMF Maintenance Enterprise</h1><h2>{{ $title }}</h2><p>{{ $from }} a {{ $to }} · Emitido em {{ now()->format('d/m/Y H:i') }}</p>
<table>@if(count($rows))<thead><tr>@foreach(array_keys($rows[0]) as $key)<th>{{ $key }}</th>@endforeach</tr></thead>@endif<tbody>
@forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>@empty<tr><td>Sem registros.</td></tr>@endforelse
</tbody></table></body></html>
