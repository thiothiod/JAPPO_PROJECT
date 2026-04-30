<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SeanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'groupement_id' => 'required|exists:groupements,id',
            'date_seance' => 'required|date',
        ]);

        $seance = \App\Models\Seance::create([
            'groupement_id' => $request->groupement_id,
            'date_seance' => $request->date_seance,
            'statut' => 'planifiee',
        ]);

        return response()->json([
            'message' => 'Séance créée avec succès.',
            'seance' => $seance,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
