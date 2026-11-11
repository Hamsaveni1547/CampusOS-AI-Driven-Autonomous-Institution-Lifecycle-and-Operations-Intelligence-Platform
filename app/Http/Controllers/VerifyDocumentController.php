<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * Public page (no sign-in) where anyone can check a printed CampusOS document by its verification code.
 */
class VerifyDocumentController extends Controller
{
    public function __invoke(string $code): View
    {
        $document = null;
        $error = null;

        try {
            $response = Http::acceptJson()->timeout(10)->get(rtrim((string) config('campusos.api.url'), '/').'/verify/'.rawurlencode(strtoupper($code)));
            $document = $response->successful() ? $response->json('data') : null;
            $error = $response->status() === 404 ? 'not_found' : ($response->successful() ? null : 'unavailable');
        } catch (\Throwable) {
            $error = 'unavailable';
        }

        return view('verify-document', ['code' => strtoupper($code), 'document' => $document, 'error' => $error]);
    }
}
