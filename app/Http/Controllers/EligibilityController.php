<?php

namespace App\Http\Controllers;

use App\Services\BlockRegistry\EligibilityService;
use Illuminate\Http\Request;

/**
 * FLAGSHIP endpoint: scan/enter a Civil ID -> instant cross-agency eligibility
 * result (§4/§12). Target: < 3s, unmistakable block prompt.
 */
class EligibilityController extends Controller
{
    public function check(Request $request, EligibilityService $service)
    {
        $data = $request->validate([
            'civil_id' => ['required', 'string', 'min:6', 'max:32'],
        ]);

        return response()->json($service->check($data['civil_id']));
    }
}
