<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    public function test_pdf_is_streamed_from_the_api(): void
    {
        $this->signIn(['roles' => ['student']]);
        Http::fake([self::API.'me/grade-card/01ARZ3NDEKTSV4RRFFQ69G5FAV/pdf' => Http::response('%PDF-1.7 test', 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="grade-card.pdf"'])]);

        $this->get(route('documents.pdf', ['grade-card', '01ARZ3NDEKTSV4RRFFQ69G5FAV']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertSee('%PDF-1.7', false);
    }

    public function test_public_verify_page_shows_genuine_and_unknown_codes(): void
    {
        Http::fake([
            self::API.'verify/ABCD-1234' => Http::response(['data' => ['code' => 'ABCD-1234', 'type' => 'grade_card', 'title' => 'Grade card — Semester 2', 'issued_to' => 'Pranav Iyer', 'institution' => 'Bangalore Institute of Technology', 'issued_at' => '2026-11-11T10:00:00+05:30', 'summary' => ['SGPA' => 8.1]]]),
            self::API.'verify/*' => Http::response(['message' => 'No document has this code.'], 404),
        ]);

        $this->get('/verify/abcd-1234')->assertOk()->assertSee('genuine')->assertSee('Pranav Iyer')->assertSee('8.1');
        $this->get('/verify/ZZZZ-0000')->assertOk()->assertSee('No document has this code');
    }
}
