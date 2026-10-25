<div>
    <x-ui.page-header :title="__('My account')">
        <x-slot:tabs><x-layout.account-tabs /></x-slot:tabs>
    </x-ui.page-header>

    <form wire:submit="save">
        <x-ui.card :title="__('Notification settings')" :description="__('Choose how you hear about each kind of update. Security and account messages are always delivered.')" :padding="false">
            <table class="w-full text-sm">
                <thead class="border-b border-line bg-surface-muted/60 text-xs font-medium text-ink-muted">
                    <tr>
                        <th scope="col" class="px-5 py-2.5 text-left">{{ __('Category') }}</th>
                        <th scope="col" class="w-24 px-3 py-2.5 text-center">{{ __('In-app') }}</th>
                        <th scope="col" class="w-24 px-3 py-2.5 text-center">{{ __('Email') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as $row)
                        <tr wire:key="p-{{ $row['category'] }}">
                            <td class="px-5 py-3">
                                <span class="font-medium text-ink">{{ __($row['label']) }}</span>
                                @if ($row['mandatory'])
                                    <span class="ml-2 inline-flex items-center gap-1 text-xs text-ink-subtle"><x-ui.icon name="lock" class="size-3" /> {{ __('Always on') }}</span>
                                @endif
                            </td>
                            @foreach (['database', 'mail'] as $channel)
                                <td class="px-3 py-3 text-center">
                                    <input type="checkbox" wire:model="preferences.{{ $row['category'] }}.{{ $channel }}" @disabled($row['mandatory'])
                                        aria-label="{{ __($row['label']) }} — {{ $channel === 'mail' ? __('Email') : __('In-app') }}"
                                        class="size-4 rounded-[4px] border-line-strong text-primary focus:ring-2 focus:ring-primary/30 disabled:opacity-50">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-slot:footer><x-ui.button type="submit" loading="save">{{ __('Save settings') }}</x-ui.button></x-slot:footer>
        </x-ui.card>
    </form>
</div>
