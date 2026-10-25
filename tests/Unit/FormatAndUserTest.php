<?php

namespace Tests\Unit;

use App\Auth\CurrentUser;
use App\Support\Format;
use Tests\TestCase;

class FormatAndUserTest extends TestCase
{
    public function test_indian_number_grouping(): void
    {
        $this->assertSame('0', Format::number(0));
        $this->assertSame('999', Format::number(999));
        $this->assertSame('1,000', Format::number(1000));
        $this->assertSame('1,23,456', Format::number(123456));
        $this->assertSame('12,34,56,789', Format::number(123456789));
        $this->assertSame('-45,000.50', Format::number(-45000.5, 2));
        $this->assertSame('—', Format::number(null));
    }

    public function test_dates_use_indian_timezone_by_default(): void
    {
        $this->assertSame('26 Oct 2026', Format::date('2026-10-25T20:00:00Z'));
        $this->assertSame('26 Oct 2026, 01:30 AM', Format::dateTime('2026-10-25T20:00:00Z'));
        $this->assertSame('—', Format::date(null));
        $this->assertSame('—', Format::date('not a date'));
    }

    public function test_bytes_and_labels(): void
    {
        $this->assertSame('512 B', Format::bytes(512));
        $this->assertSame('1.5 MB', Format::bytes(1572864));
        $this->assertSame('On leave', Format::label('on_leave'));
    }

    public function test_current_user_helpers(): void
    {
        $user = new CurrentUser($this->me(['permissions' => ['a.b', 'c.d'], 'roles' => ['hod']]));

        $this->assertSame('AR', $user->initials());
        $this->assertTrue($user->can('a.b'));
        $this->assertFalse($user->can('x.y'));
        $this->assertTrue($user->canAny(['x.y', 'c.d']));
        $this->assertTrue($user->hasAnyRole(['hod', 'faculty']));
        $this->assertSame('Asia/Kolkata', $user->timezone());
        $this->assertSame('RVCE', $user->institution()['code']);
    }
}
