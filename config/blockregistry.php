<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sponsor Block Registry governance (§4)
    |--------------------------------------------------------------------------
    |
    | The cross-tenant registry is the platform's network-effect moat. These
    | settings encode the legally-sensitive governance policy and MUST be
    | reviewed with counsel before go-live (§4 caveats, §13).
    |
    */

    // How a blocking agency's identity & reason are exposed to OTHER agencies:
    //   full            - show blocking agency name + structured reason
    //   anonymized      - show only count + reason category, never the agency
    //   vendor_mediated - show only "caution/blocked"; details via vendor
    'visibility' => env('BLOCK_REGISTRY_VISIBILITY', 'anonymized'),

    // A block cannot be created without a supporting document when true.
    'require_evidence' => (bool) env('BLOCK_REGISTRY_REQUIRE_EVIDENCE', true),

    // Structured, mandatory reason codes (free-text note is always additional).
    'reasons' => [
        'non_payment',
        'abuse_complaint',
        'repeated_returns',
        'fraud',
        'contract_violation',
        'absconding',
        'other',
    ],

    // Eligibility status thresholds.
    //  >= blocked_threshold active blocks  => "blocked"
    //  >= caution_threshold active blocks  => "caution"
    'blocked_threshold' => 1,
    'caution_threshold' => 1,

    // Default review window: blocks auto-flag for review after this many days.
    'default_review_days' => 365,

    // Mandatory legal disclaimer surfaced with every eligibility result.
    'disclaimer_key' => 'blockregistry.disclaimer',

];
