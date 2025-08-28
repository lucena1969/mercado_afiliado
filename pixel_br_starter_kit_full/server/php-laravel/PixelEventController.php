<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PixelEventController extends Controller
{
    public function collect(Request $request)
    {
        $evt = $request->all();
        $evt['event_time'] = $evt['event_time'] ?? time();
        $evt['event_id'] = $evt['event_id'] ?? ('evt_' . time());
        Log::info('pixel_event', $evt);
        return response()->json(['ok' => true]);
    }
}
