<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockDispute;
use App\Services\BlockRegistry\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Vendor-side: mediate disputes (§4). */
class DisputeModerationController extends Controller
{
    public function index(Request $request)
    {
        $disputes = BlockDispute::query()
            ->with('block:id,blocking_tenant_id,reason_code,status,moderation_state')
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->boolean('open_only', true) && ! $request->has('status'),
                fn ($q) => $q->where('status', 'open'))
            ->latest()
            ->paginate(30);

        return response()->json($disputes);
    }

    public function resolve(Request $request, BlockDispute $dispute, DisputeService $service)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['uphold', 'remove', 'reject'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json($service->resolve($dispute, $data['action'], $data['note'] ?? null));
    }
}
