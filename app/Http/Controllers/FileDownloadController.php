<?php

namespace App\Http\Controllers;

use App\Api\CampusApi;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams a stored file from the API to the browser; the API checks access.
 */
class FileDownloadController extends Controller
{
    public function __invoke(string $file, CampusApi $api): Response
    {
        $response = $api->download('files/'.$file.'/download');

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'Content-Disposition' => $response->header('Content-Disposition') ?: 'attachment',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
