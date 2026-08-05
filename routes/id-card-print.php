<?php

use App\Models\IdCard;
use Illuminate\Support\Facades\Route;

Route::get('/id-card/{idCard}/print', function (IdCard $idCard) {
    $idCard->load(['employee.position', 'signedBy']);
    return view('documents.id_cards.print', compact('idCard'));
})->name('id-card.print');
