<?php

namespace App\Http\Controllers;

use App\Services\BlockRegistry\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Agency-side: raise a dispute against a block (§4). */
class DisputeController extends Controller
{
    public function store(Request $request, DisputeService $service)
    {
        $this->authorizeAbility('dispute.raise');

        $data = $request->validate([
            'block_id' => ['nullable', 'integer'],
            'civil_id' => ['required_without:block_id', 'string'],
            'disputant_type' => ['nullable', Rule::in(['agency', 'sponsor'])],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $disputes = $service->raise($data);

        return response()->json([
            'message' => __('blockregistry.dispute_raised'),
            'disputes' => $disputes->pluck('id'),
        ], 201);
    }
}
