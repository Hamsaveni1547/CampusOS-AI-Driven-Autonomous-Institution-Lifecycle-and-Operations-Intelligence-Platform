<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * First password for an account created by an administrator (/setup-account?token=…&email=…).
 */
#[Layout('layouts::auth')]
#[Title('Set up your account')]
class SetupAccount extends ResetPassword
{
    protected bool $isAccountSetup = true;
}
