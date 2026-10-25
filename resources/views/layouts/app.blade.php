{{-- Livewire page layout for signed-in screens (#[Layout('layouts::app')]). --}}
<x-layout.app :title="$title ?? null" :breadcrumbs="$breadcrumbs ?? []" :width="$width ?? 'full'">
    {{ $slot }}
</x-layout.app>
