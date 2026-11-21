<?php

namespace App\Http\Controllers\Klien;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Rekomendasi paket (Tahap D1). Bagiannya hidup di detail acara draft;
 * route eksplisit ini mengarahkan ke bagian tersebut.
 */
class RecommendationController extends Controller
{
    public function show(Request $request, Event $event): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);

        return redirect()->to(route('klien.acara.detail', $event).'#rekomendasi');
    }
}
