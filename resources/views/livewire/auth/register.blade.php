<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ __('Apply for admission') }}</h1>
        <p class="mt-1.5 text-sm text-ink-muted">{{ __('Create your applicant account to start an application at :institution.', ['institution' => $institution]) }}</p>
    </div>

    @if ($closed)
        <x-ui.alert tone="warning" :title="__('Registration is not open')">{{ $alert }}</x-ui.alert>
        <x-ui.button :href="route('login')" variant="secondary" block class="mt-5">{{ __('Back to sign in') }}</x-ui.button>
    @else
        @if ($alert)
            <x-ui.alert tone="danger" class="mb-5">{{ $alert }}</x-ui.alert>
        @endif

        <form wire:submit="register" class="space-y-5" novalidate>
            <x-ui.input :label="__('Full name')" wire:model="name" autocomplete="name" :hint="__('As on your marks card.')" autofocus required />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input :label="__('Email')" type="email" wire:model="email" autocomplete="email" required />
                <x-ui.input :label="__('Mobile')" type="tel" wire:model="mobile" autocomplete="tel" prefix="+91" required />
            </div>
            <x-ui.password :label="__('Password')" wire:model="password" strength="10" autocomplete="new-password" required />
            <x-ui.password :label="__('Confirm password')" wire:model="password_confirmation" autocomplete="new-password" required />
            <x-ui.checkbox wire:model="acceptTerms" :label="__('I agree to the processing of my personal data for admission')" :description="__('Your details are used only by the institution to process your application.')" />
            <x-ui.button type="submit" block size="lg" loading="register">{{ __('Create account') }}</x-ui.button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">{{ __('Sign in') }}</a>
        </p>
    @endif
</div>
