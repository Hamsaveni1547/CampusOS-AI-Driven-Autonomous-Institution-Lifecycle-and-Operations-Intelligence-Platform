<div>
    <x-ui.page-header :title="__('My account')">
        <x-slot:tabs><x-layout.account-tabs /></x-slot:tabs>
    </x-ui.page-header>

    <form wire:submit="save">
        <x-ui.card>
            <x-ui.section :title="__('Photo')" :description="__('Shown to colleagues in lists and on your profile.')">
                <div class="flex items-center gap-4">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" alt="" class="size-16 rounded-full object-cover">
                    @else
                        <x-ui.avatar :name="$me?->name()" size="xl" />
                    @endif
                    <label class="cursor-pointer">
                        <span class="inline-flex h-9 items-center gap-2 rounded-md border border-line-strong bg-surface px-3.5 text-sm font-medium text-ink shadow-xs hover:bg-surface-muted">
                            <x-ui.icon name="upload" /> {{ __('Upload photo') }}
                        </span>
                        <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    </label>
                    <span wire:loading wire:target="photo" class="text-sm text-ink-muted">{{ __('Uploading…') }}</span>
                </div>
                @error('photo')<p class="text-xs text-danger">{{ $message }}</p>@enderror
            </x-ui.section>

            <x-ui.divider />

            <x-ui.section :title="__('Personal details')" :description="__('Your email and mobile are managed by your institution.')">
                <x-ui.input :label="__('Full name')" wire:model="name" autocomplete="name" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Email')" :value="$me?->email()" disabled />
                    <x-ui.input :label="__('Mobile')" :value="$me?->get('mobile') ?? '—'" disabled />
                </div>
            </x-ui.section>

            <x-ui.divider />

            <x-ui.section :title="__('Language and time')" :description="__('Dates and times are shown in your timezone.')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select :label="__('Language')" wire:model="locale" :options="['en' => 'English', 'kn' => 'ಕನ್ನಡ (Kannada)', 'hi' => 'हिन्दी (Hindi)', 'ta' => 'தமிழ் (Tamil)', 'te' => 'తెలుగు (Telugu)', 'ml' => 'മലയാളം (Malayalam)', 'mr' => 'मराठी (Marathi)']" />
                    <x-ui.select :label="__('Timezone')" wire:model="timezone" :options="$timezones" :placeholder="__('Institution default')" />
                </div>
            </x-ui.section>

            <x-slot:footer>
                <x-ui.button type="submit" loading="save">{{ __('Save changes') }}</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </form>
</div>
