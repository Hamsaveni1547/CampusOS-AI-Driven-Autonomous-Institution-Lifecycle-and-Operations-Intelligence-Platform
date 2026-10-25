{{-- Livewire page layout for sign-in screens (#[Layout('layouts::auth')]). --}}
<x-layout.auth :title="$title ?? null" :wide="$wide ?? false">
    {{ $slot }}
</x-layout.auth>
