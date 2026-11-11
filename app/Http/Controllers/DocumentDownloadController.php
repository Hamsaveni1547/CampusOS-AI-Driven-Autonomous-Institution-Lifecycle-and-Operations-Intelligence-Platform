<?php

namespace App\Http\Controllers;

use App\Api\CampusApi;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams an official PDF (receipt, payslip, hall ticket, grade card) from the API; the API checks access.
 */
class DocumentDownloadController extends Controller
{
    /** @var array<string, string> document type => API path pattern */
    public const PATHS = [
        'receipt' => 'fee-payments/%s/pdf',
        'payslip' => 'payslips/%s/pdf',
        'hall-ticket' => 'me/hall-ticket/%s/pdf',
        'grade-card' => 'me/grade-card/%s/pdf',
    ];

    public function __invoke(string $type, string $id, CampusApi $api): Response
    {
        abort_unless(isset(self::PATHS[$type]), 404);
        $response = $api->download(sprintf(self::PATHS[$type], $id));

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $response->header('Content-Disposition') ?: 'attachment; filename="'.$type.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
