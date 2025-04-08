<?php

namespace App\Http\Controllers;

use App\Models\LastPagination;
use Illuminate\Http\Request;

class LastPaginationController extends Controller
{
    // Actualizar o crear el número de página visitada
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'page' => 'required|integer|min:1',
        ]);

        $pagination = LastPagination::updateOrCreate(
            ['type' => $request->type],
            ['page' => $request->page]
        );

        return response()->json($pagination);
    }

    // Consultar la última página visitada
    public function show($type)
    {
        $pagination = LastPagination::where('type', $type)->first();

        if (!$pagination) {
            return response()->json(['message' => 'No se encontró paginación'], 404);
        }

        return response()->json($pagination);
    }
}
