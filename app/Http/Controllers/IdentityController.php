<?php

namespace App\Http\Controllers;

use App\Services\Identity\IdentityReadException;
use App\Services\Identity\IdentityService;
use Illuminate\Http\Request;

/**
 * Identity auto-read / auto-fill (§5). Returns normalised data the client uses
 * to pre-populate a sponsor or worker form, flagging mismatches.
 */
class IdentityController extends Controller
{
    public function read(Request $request, IdentityService $service)
    {
        $request->validate([
            'driver' => ['nullable', 'string', 'in:paci,hawyti,smartcard,mrz_ocr,fake'],
            'civil_id' => ['nullable', 'string'],
            'mrz' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
            'card_data' => ['nullable', 'array'],
        ]);

        try {
            $data = $service->read(
                $request->input('driver'),
                $request->except('driver'),
                $request->user(),
            );
        } catch (IdentityReadException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $data->toArray(),
            'verification_level' => $data->verificationLevel,
        ]);
    }

    /**
     * Begin a Hawyti consent session and return the QR payload for the sponsor
     * to scan (§5.2). The client polls /identity/read with the session_id.
     */
    public function hawytiSession(Request $request)
    {
        // Integration seam: create a PACI Hawyti consent session and return its
        // QR. Stubbed until PACI approval (§13).
        return response()->json([
            'message' => __('identity.hawyti_pending'),
            'ttl' => config('identity.drivers.hawyti.qr_ttl'),
        ], 501);
    }
}
