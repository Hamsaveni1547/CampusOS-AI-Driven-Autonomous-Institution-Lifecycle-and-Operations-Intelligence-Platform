{{-- Temporary home until F3 (dashboard with notifications, calendar and quick links). --}}
<x-layout.app :title="__('Home')" :breadcrumbs="[__('Home') => null]">
    <x-ui.page-header :title="__('Welcome, :name', ['name' => $currentUser?->name()])" :description="$currentUser?->institution()['name'] ?? null" />

    <x-ui.card>
        <x-ui.empty-state icon="sparkles" :title="__('Your workspace is ready')" :description="__('Use the menu on the left to open the areas you have access to.')" />
    </x-ui.card>
</x-layout.app>
