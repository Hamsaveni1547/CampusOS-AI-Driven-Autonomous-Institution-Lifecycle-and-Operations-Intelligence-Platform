@props(['name'])
<div role="tabpanel" id="panel-{{ $name }}" aria-labelledby="tab-{{ $name }}" x-show="active === @js($name)" x-cloak tabindex="0" {{ $attributes->class(['focus:outline-none']) }}>
    {{ $slot }}
</div>
