<?php

use App\Http\Controllers\Api\TicketEscalationController;
use Illuminate\Support\Facades\Route;

Route::post('/tickets/{ticket}/escalate', [TicketEscalationController::class, 'escalate'])
    ->name('tickets.escalate');
