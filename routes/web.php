<?php

use App\Http\Controllers\OperationsController;
use App\Http\Middleware\ActiveUser;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::middleware(['auth', ActiveUser::class])->group(function () {
    Route::get('/reports/{format}', [OperationsController::class, 'export'])->name('reports.export');
    Route::get('/equipment/{equipment}/card', [OperationsController::class, 'card'])->name('equipment.card');
    Route::get('/equipment/{equipment}/qr', [OperationsController::class, 'qr'])->name('equipment.qr');
    Route::get('/equipment/{equipment}/timeline', [OperationsController::class, 'equipment'])->name('equipment.timeline');
    Route::get('/work-orders/{workOrder}/timeline', [OperationsController::class, 'order'])->name('work-order.timeline');
    Route::get('/attachments/{attachment}', [OperationsController::class, 'attachment'])->name('attachment.download');
    Route::get('/signatures/{signature}', [OperationsController::class, 'signature'])->name('signature.download');
});
