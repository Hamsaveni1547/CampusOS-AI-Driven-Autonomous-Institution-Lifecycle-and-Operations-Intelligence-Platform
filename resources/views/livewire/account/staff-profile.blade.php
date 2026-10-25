@php($f = \App\Support\Format::class)
<div>
    <x-ui.page-header :title="__('My account')">
        <x-slot:tabs><x-layout.account-tabs /></x-slot:tabs>
    </x-ui.page-header>

    @if ($employee === null)
        <x-ui.card>
            <x-ui.empty-state icon="briefcase" :title="__('No staff record')" :description="__('You do not have a staff record in this institution. Contact HR if this is wrong.')" />
        </x-ui.card>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2" :title="__('Employment')" :description="__('Kept up to date by HR. Ask HR to correct anything that is wrong.')">
                <x-ui.description-list :items="[
                    __('Employee code') => $employee['employee_code'],
                    __('Status') => $f::label($employee['status']),
                    __('Department') => $employee['department']['name'] ?? null,
                    __('Designation') => $employee['designation']['name'] ?? null,
                    __('Staff type') => $f::label($employee['staff_type']),
                    __('Employment') => $f::label($employee['employment_type']),
                    __('Joined on') => $f::date($employee['joined_on']),
                    __('Reports to') => $employee['reports_to']['name'] ?? null,
                    __('Contract ends') => $employee['contract_ends_on'] ? $f::date($employee['contract_ends_on']) : null,
                    __('Total experience') => __(':years years', ['years' => $employee['total_experience_years']]),
                ]" />
            </x-ui.card>

            <x-ui.card :title="__('Qualifications')" :padding="false">
                @forelse ($employee['qualifications'] as $qualification)
                    <div wire:key="q-{{ $qualification['id'] }}" class="border-b border-line px-5 py-3 last:border-0">
                        <p class="text-sm font-medium text-ink">{{ $qualification['degree'] }} @if ($qualification['specialization'])<span class="font-normal text-ink-muted">· {{ $qualification['specialization'] }}</span>@endif</p>
                        <p class="text-xs text-ink-muted">{{ implode(' · ', array_filter([$qualification['university'], $qualification['year_of_passing'], $qualification['grade']])) }}</p>
                    </div>
                @empty
                    <x-ui.empty-state compact icon="award" :title="__('No qualifications recorded')" />
                @endforelse
            </x-ui.card>
        </div>
    @endif
</div>
