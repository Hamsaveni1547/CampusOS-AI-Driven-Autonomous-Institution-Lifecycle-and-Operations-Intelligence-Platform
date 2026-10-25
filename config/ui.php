<?php

/*
| Status → badge tone. Tones: neutral, primary, success, warning, danger, info, muted.
| Every list and detail page uses <x-ui.status-badge :status="..."/>, which reads this map.
*/

return [

    'status_tones' => [
        // accounts & memberships
        'active' => 'success',
        'invited' => 'info',
        'pending_verification' => 'warning',
        'inactive' => 'neutral',
        'locked' => 'danger',
        'suspended' => 'danger',
        'revoked' => 'neutral',
        'expired' => 'muted',
        'scheduled' => 'info',
        // periods
        'planned' => 'info',
        'closed' => 'muted',
        'current' => 'primary',
        // staff
        'on_leave' => 'warning',
        'resigned' => 'neutral',
        'retired' => 'neutral',
        'terminated' => 'danger',
        // batches
        'graduated' => 'primary',
        'discontinued' => 'neutral',
        // imports & jobs
        'pending' => 'neutral',
        'validating' => 'info',
        'validated' => 'success',
        'invalid' => 'danger',
        'processing' => 'info',
        'completed' => 'success',
        'completed_with_errors' => 'warning',
        'failed' => 'danger',
        'cancelled' => 'neutral',
        // notifications & health
        'sent' => 'success',
        'ok' => 'success',
        'warn' => 'warning',
        'degraded' => 'warning',
        'fail' => 'danger',
        // security severities
        'info' => 'info',
        'warning' => 'warning',
        'critical' => 'danger',
    ],

];
