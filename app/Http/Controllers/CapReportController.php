<?php

namespace App\Http\Controllers;

use App\Models\CapReport;
use Illuminate\Http\Request;

class CapReportController extends Controller
{
    // Registrar un nuevo reporte de problema para un capítulo
    public function store(Request $request)
    {
        $validated = $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'reason' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'reported_by' => 'nullable|string|max:255',
        ]);

        $report = CapReport::create($validated);

        return response()->json($report, 201);
    }

    // Obtener todos los reportes (opcional)
    public function index()
    {
        return CapReport::with('episode')->orderBy('created_at', 'desc')->get();
    }

    // Obtener reportes de un episodio específico (opcional)
    public function byEpisode($episode_id)
    {
        return CapReport::where('episode_id', $episode_id)->orderBy('created_at', 'desc')->get();
    }

    private function checkToken(Request $request)
    {
        $secret = env('WEBHOOK_SECRET');

        if ($request->header('X-Webhook-Token') !== $secret) {
            abort(401, 'Unauthorized');
        }
    }

    public function resolve($id)
    {
        $report = CapReport::findOrFail($id);
        if ($report->resolved) {
            return response()->json(['message' => 'Report already resolved'], 400);
        }
        $report->resolved = true;
        $report->resolved_at = now();
        $report->save();

        return response()->json($report);
    }
}