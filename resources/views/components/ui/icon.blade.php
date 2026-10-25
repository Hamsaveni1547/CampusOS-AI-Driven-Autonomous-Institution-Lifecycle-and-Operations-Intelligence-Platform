{{--
    <x-ui.icon name="users" class="size-5" />  — decorative (hidden from screen readers)
    <x-ui.icon name="bell" label="Notifications" />  — meaningful icon with an accessible name
--}}
@props(['name', 'label' => null, 'stroke' => 2])
{!! \App\Support\Icons::svg($name, $attributes->get('class', 'size-4'), $label, (float) $stroke) !!}
