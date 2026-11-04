{{--
    <x-attachments :files="$complaint['attachments']" /> — download links for files attached to a record.
--}}
@props(['files' => []])
@if ($files !== [])
    <ul {{ $attributes->class(['flex flex-wrap gap-2']) }}>
        @foreach ($files as $file)
            <li wire:key="att-{{ $file['id'] }}">
                <a href="{{ route('files.download', $file['id']) }}" class="inline-flex items-center gap-1.5 rounded-md border border-line px-2.5 py-1.5 text-xs text-ink hover:border-primary/50 hover:bg-primary-soft/30">
                    <x-ui.icon :name="str_starts_with($file['mime_type'], 'image/') ? 'image' : 'paperclip'" class="size-3.5 text-ink-muted" />
                    <span class="max-w-48 truncate">{{ $file['name'] }}</span>
                    <span class="text-ink-subtle">{{ number_format($file['size_bytes'] / 1024, 0) }} KB</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
