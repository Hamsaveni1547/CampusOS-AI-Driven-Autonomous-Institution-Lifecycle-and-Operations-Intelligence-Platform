{{--
    <x-file-picker model="files" :files="$files" /> — choose up to 5 photos or documents to attach (Livewire multiple upload).
--}}
@props(['model', 'files' => [], 'label' => null])
<div {{ $attributes }}>
    <p class="mb-1 text-sm font-medium text-ink">{{ $label ?? __('Attachments') }} <span class="font-normal text-ink-subtle">{{ __('(optional, up to 5 · images, PDF, Word · 10 MB each)') }}</span></p>
    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-line px-4 py-3 text-sm text-ink-muted hover:border-primary/50">
        <x-ui.icon name="paperclip" class="size-4" />
        <span>{{ $files === [] ? __('Choose files…') : trans_choice(':n file chosen|:n files chosen', count($files), ['n' => count($files)]) }}</span>
        <input type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" wire:model="{{ $model }}" class="sr-only" />
        <span wire:loading wire:target="{{ $model }}" class="ml-auto text-xs">{{ __('Uploading…') }}</span>
    </label>
    @if ($files !== [])
        <ul class="mt-2 space-y-1 text-xs text-ink-muted">
            @foreach ($files as $file)<li wire:key="fp-{{ $loop->index }}">{{ $file->getClientOriginalName() }}</li>@endforeach
        </ul>
    @endif
    @error($model)<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
    @error($model.'.*')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
</div>
