{{-- Tabs shared by the My account pages. --}}
@php
    $isStaff = (bool) ($currentUser?->roles() !== [] && array_diff($currentUser->roles(), ['student', 'applicant']) !== []);
    $tabs = [
        'account.profile' => __('Profile'),
        'account.security' => __('Security'),
        'account.notifications' => __('Notifications'),
    ];
    if ($isStaff) {
        $tabs['account.staff-profile'] = __('Staff record');
    }
@endphp
<nav class="flex gap-5 overflow-x-auto border-b border-line" aria-label="{{ __('My account') }}">
    @foreach ($tabs as $name => $label)
        <x-ui.tab-link :href="route($name)" :active="request()->routeIs($name)">{{ $label }}</x-ui.tab-link>
    @endforeach
</nav>
