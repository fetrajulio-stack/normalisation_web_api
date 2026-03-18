<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Datamap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatamapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
 public function index(Request $request)
{
    $query = Datamap::with(['champ']);

    if ($request->filled('codification_id')) {
        $query->where('codification_id', $request->codification_id);
    }

    $datamaps = $query->get()->map(function ($item) {
        return [
            'idq' => $item->champ->nom_champ, // 🔥 IMPORTANT
            'position' => $item->position,
            'longueur' => $item->longueur,
        ];
    });

    return response()->json([
        'datamap' => $datamaps
    ]);
}

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $request->validate([
        'codification_id' => 'required|exists:codifications,id',
        'datamap' => 'required|array',
        'datamap.*.position' => 'required|integer|min:0',
        'datamap.*.longueur' => 'required|integer|min:1',
        'datamap.*.idq' => 'required|string'
    ]);

    DB::beginTransaction();

    try {

        // 🔥 1. Supprimer les anciens datamaps
        Datamap::where('codification_id', $request->codification_id)->delete();

        $bulkData = [];

        foreach ($request->datamap as $item) {

            $champ = \App\Models\Champ::where('nom_champ', $item['idq'])->first();

            if (!$champ) continue;

            $bulkData[] = [
                'position' => $item['position'],
                'longueur' => $item['longueur'],
                'codification_id' => $request->codification_id,
                'champ_id' => $champ->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // ⚡ Insert en masse (rapide)
        Datamap::insert($bulkData);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Datamap remplacé avec succès'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified resource.
     */
    public function show(Datamap $datamap)
    {
        $datamap->load(['codification', 'champ']);

        return response()->json([
            'status' => 'success',
            'data' => $datamap
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Datamap $datamap)
    {
        $validated = $request->validate([
            'position' => 'required|integer|min:1',
            'longueur' => 'required|integer|min:1',
            'codification_id' => 'required|exists:codifications,id',
            'champ_id' => 'required|exists:champs,id'
        ]);

        $datamap->update($validated);

        $datamap->load(['codification', 'champ']);

        return response()->json([
            'status' => 'success',
            'message' => 'Datamap updated successfully',
            'data' => $datamap
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Datamap $datamap)
    {
        $datamap->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Datamap deleted successfully'
        ]);
    }
}

