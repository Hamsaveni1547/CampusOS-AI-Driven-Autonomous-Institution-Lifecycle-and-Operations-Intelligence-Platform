<?php

namespace App\Http\Controllers;

use App\Api\CampusApi;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams the API's audit CSV to the browser (the token stays on the server).
 */
class AuditExportController extends Controller
{
    public function __invoke(Request $request, CampusApi $api): Response
    {
        $response = $api->download('audit/activities/export', ['filter' => (array) $request->query('filter', [])]);

        return response($response->body(), 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => $response->header('Content-Disposition') ?: 'attachment; filename="audit.csv"',
        ]);
    }
}
