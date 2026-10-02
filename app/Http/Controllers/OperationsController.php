<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Audit;
use App\Models\Equipment;
use App\Models\EquipmentHistory;
use App\Models\OilAnalysis;
use App\Models\Signature;
use App\Models\WorkOrder;
use App\Services\Access;
use App\Services\Reports;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class OperationsController extends Controller
{
    public function export(Request $request, string $format)
    {
        abort_unless($request->user()->reports(), 403);
        $data = $request->validate(['type' => 'required|in:'.implode(',', array_keys(Reports::TYPES)), 'from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        abort_unless(in_array($format, ['pdf', 'xlsx']), 404);
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay()->min(now());
        $rows = app(Reports::class)->rows($data['type'], $from, $to);
        $title = Reports::TYPES[$data['type']];
        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf', compact('rows', 'title', 'from', 'to'))->setPaper('a4', 'landscape')->download('manutencao.pdf');
        }

        return response()->streamDownload(function () use ($rows) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            if (count($rows)) {
                $writer->addRow(Row::fromValues(array_keys($rows[0])));
            }
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }
            $writer->close();
        }, 'manutencao.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function card(Request $request, Equipment $equipment)
    {
        abort_unless(Access::record($request->user(), $equipment), 403);

        return view('equipment-card', compact('equipment'));
    }

    public function qr(Request $request, Equipment $equipment)
    {
        abort_unless(Access::record($request->user(), $equipment), 403);
        $qr = new QrCode(data: route('equipment.card', $equipment), size: 300, margin: 10);
        $result = (new SvgWriter)->write($qr);

        return response($result->getString())->header('Content-Type', 'image/svg+xml');
    }

    public function attachment(Request $request, Attachment $attachment)
    {
        abort_unless(Access::record($request->user(), $attachment), 403);

        return Storage::disk('local')->download($attachment->path, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function signature(Request $request, Signature $signature)
    {
        abort_unless(Access::record($request->user(), $signature), 403);

        return Storage::disk('local')->download($signature->image_path, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function equipment(Request $request, Equipment $equipment)
    {
        abort_unless($request->user()->reports(), 403);
        $title = 'Histórico · '.$equipment->tag;
        $events = EquipmentHistory::where('equipment_id', $equipment->id)->get()->map(fn ($h) => ['date' => $h->created_at, 'title' => $h->event, 'detail' => ($h->details ?? '').' · Horímetro '.$h->hour_meter.' · R$ '.$h->cost]);
        foreach (OilAnalysis::where('equipment_id', $equipment->id)->get() as $s) {
            $events->push(['date' => $s->collected_on, 'title' => 'SOS '.$s->sample_number.' · '.$s->result, 'detail' => $s->recommendation.' · Ferro '.$s->iron_ppm.' ppm · Silício '.$s->silicon_ppm.' ppm']);
        }
        $events = $events->sortByDesc('date');

        return view('timeline', compact('title', 'events'));
    }

    public function order(Request $request, WorkOrder $workOrder)
    {
        abort_unless(Access::record($request->user(), $workOrder), 403);
        $title = 'Timeline · OS #'.$workOrder->id;
        $events = Audit::where('subject_type', 'WorkOrder')->where('subject_id', $workOrder->id)->get()->map(fn ($a) => ['date' => $a->created_at, 'title' => $a->action.' · '.($a->new_values['status'] ?? ''), 'detail' => ($a->new_values['diagnosis'] ?? $a->new_values['failure_description'] ?? '').' · '.($a->new_values['service_performed'] ?? '')]);

        return view('timeline',compact('title','events'));
    }
}
