<div>
    <x-ui.page-header :title="__('Institution profile')" :description="$institution['code'].' · '.\App\Support\Format::label($institution['type'])" />

    <form wire:submit="save">
        <x-ui.card>
            <x-ui.section :title="__('Identity')" :description="__('Shown on documents, emails and the sign-in screen.')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Name')" wire:model="form.name" required class="sm:col-span-2" />
                    <x-ui.input :label="__('Short name')" wire:model="form.short_name" optional />
                    <x-ui.input :label="__('Established')" type="number" wire:model="form.established_year" optional />
                    <x-ui.input :label="__('Affiliation')" wire:model="form.affiliation" optional :hint="__('University or board.')" />
                    <x-ui.input :label="__('Accreditation')" wire:model="form.accreditation_codes" optional :hint="__('For example NAAC A+, NBA.')" />
                    <div class="sm:col-span-2">
                        <x-ui.file-upload :label="__('Logo')" wire:model="logo" accept="image/*" :max-kb="2048" optional />
                        @if ($institution['logo_file_id'] && ! $logo)
                            <button type="button" wire:click="removeLogo" class="mt-2 text-sm text-danger hover:underline">{{ __('Remove current logo') }}</button>
                        @endif
                    </div>
                </div>
            </x-ui.section>
            <x-ui.divider />
            <x-ui.section :title="__('Contact')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('Email')" type="email" wire:model="form.email" optional />
                    <x-ui.input :label="__('Phone')" wire:model="form.phone" optional />
                    <x-ui.input :label="__('Website')" type="url" wire:model="form.website" optional class="sm:col-span-2" />
                </div>
            </x-ui.section>
            <x-ui.divider />
            <x-ui.section :title="__('Address')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.textarea :label="__('Street address')" wire:model="form.address_line" rows="2" optional class="sm:col-span-2" />
                    <x-ui.input :label="__('City')" wire:model="form.city" optional />
                    <x-ui.input :label="__('State')" wire:model="form.state" optional />
                    <x-ui.input :label="__('PIN code')" wire:model="form.postal_code" optional />
                    <x-ui.input :label="__('Country code')" wire:model="form.country" maxlength="2" required :hint="__('Two letters, e.g. IN.')" />
                </div>
            </x-ui.section>
            <x-ui.divider />
            <x-ui.section :title="__('Region')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.combobox :label="__('Time zone')" wire:model="form.timezone" :options="collect(timezone_identifiers_list())->mapWithKeys(fn ($tz) => [$tz => $tz])->all()" required />
                    <x-ui.input :label="__('Currency')" wire:model="form.currency" maxlength="3" required :hint="__('Three letters, e.g. INR.')" />
                </div>
            </x-ui.section>
        </x-ui.card>
        @canapi('institution.profile.manage')
            <div class="mt-4 flex justify-end"><x-ui.button type="submit" loading="save">{{ __('Save changes') }}</x-ui.button></div>
        @endcanapi
    </form>
</div>
