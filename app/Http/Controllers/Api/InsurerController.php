<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Insurer;


class InsurerController extends Controller
{
    //

    public function index()
    {
        return Insurer::select('id', 'name', 'code')->get();
    }

    public function show($id)
    {
        $insurer = Insurer::findOrFail($id);
        return response()->json([
            'id' => $insurer->id,
            'name' => $insurer->name,
            'code' => $insurer->code,
            'date_preference' => $insurer->date_preference,
            'email' => $insurer->email,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:insurers,code',
            'date_preference' => 'required|in:encounter,submission',
            'email' => 'nullable|email|max:255',
        ]);

        $insurer = Insurer::create($data);

        return response()->json([
            'id' => $insurer->id,
            'name' => $insurer->name,
            'code' => $insurer->code,
            'date_preference' => $insurer->date_preference,
            'email' => $insurer->email,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $insurer = Insurer::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:insurers,code,' . $insurer->id,
            'date_preference' => 'sometimes|required|in:encounter,submission',
            'email' => 'nullable|email|max:255',
        ]);

        $insurer->update($data);

        return response()->json([
            'id' => $insurer->id,
            'name' => $insurer->name,
            'code' => $insurer->code,
            'date_preference' => $insurer->date_preference,
            'email' => $insurer->email,
        ]);
    }

    public function destroy($id)
    {
        $insurer = Insurer::findOrFail($id);
        $insurer->delete();

        return response()->json(['message' => 'Insurer deleted successfully']);
    }

    public function batches($code)
    {
        $insurer = Insurer::where('code', $code)->firstOrFail();

        return $insurer->batches()
            ->with('provider')
            ->orderByDesc('batch_date')
            ->get();
    }

    public function batchDetails($code, $batchDate = null)
    {
        $insurer = Insurer::where('code', $code)->firstOrFail();

        $query = $insurer->batches()->with(['claims.items', 'provider']);

        if ($batchDate) {
            $query->where('batch_date', $batchDate);
        }

        $batch = $query->orderByDesc('batch_date')->firstOrFail();

        return response()->json([
            'batch' => $batch,
            'claims' => $batch->claims,
        ]);
    }
}
