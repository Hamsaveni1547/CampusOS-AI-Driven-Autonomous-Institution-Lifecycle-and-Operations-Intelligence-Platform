Add sign-in, two-step verification and account recovery screens (F2).

- Screens: `/login`, `/login/verify` (authenticator, email code with resend timer, recovery code, remember this device), `/forgot-password`, `/reset-password`, `/setup-account` (invitation), `/register/{institution}` (applicant), `/required/email`, `/required/password`, `/required/two-step` (QR code, email option, recovery codes with download/copy/print).
- Behaviour: friendly messages for wrong details, locked, not-set-up and suspended accounts; frontend rate limit on sign-in; trusted device kept in an encrypted HTTP-only cookie for 30 days; expired sign-in steps return to sign-in with a notice; pending requirements chain (password change → two-step setup).
- Config: Livewire set to class-based components; page layouts `layouts::auth` and `layouts::app`.
