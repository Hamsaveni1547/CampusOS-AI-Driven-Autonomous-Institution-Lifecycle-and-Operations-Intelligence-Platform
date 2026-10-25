{{--
    <x-ui.description-list :items="['Email' => $user['email'], 'Status' => $badgeHtml]" columns="2" />
    or with slots:
    <x-ui.description-list>
        <x-ui.description-item label="Email">{{ $email }}</x-ui.description-item>
    </x-ui.description-list>
--}}
@props(['items' => [], 'columns' => 2])
<dl {{ $attributes->class(['grid gap-x-6 gap-y-4', $columns >= 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : ($columns == 2 ? 'sm:grid-cols-2' : '')]) }}>
    @foreach ($items as $label => $value)
        <x-ui.description-item :label="$label">{{ $value === null || $value === '' ? '—' : $value }}</x-ui.description-item>
    @endforeach
    {{ $slot }}
</dl>
