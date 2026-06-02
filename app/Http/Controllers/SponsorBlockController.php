<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Services\BlockRegistry\BlockService;
use Illuminate\Http\Request;

class SponsorBlockController extends Controller
{
    public function block(Request $request, Sponsor $sponsor, BlockService $service)
    {
        $data = $request->validate([
            'reason_code' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
            'evidence' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'consent' => ['accepted'],
        ]);

        // Evidence goes to the encrypted private vault (§9).
        if ($request->hasFile('evidence')) {
            $data['evidence_path'] = $request->file('evidence')->store(
                'block-evidence/'.$sponsor->getKey(),
                'local'
            );
        }

        $block = $service->block($sponsor, $data);

        return response()->json([
            'message' => __('blockregistry.blocked'),
            'block_id' => $block->id,
            'review_at' => $block->review_at?->toIso8601String(),
        ], 201);
    }

    public function unblock(Request $request, Sponsor $sponsor, BlockService $service)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $block = $service->unblock($sponsor, $data['reason'] ?? null);

        abort_if($block === null, 404, __('blockregistry.no_active_block'));

        return response()->json(['message' => __('blockregistry.unblocked')]);
    }
}
