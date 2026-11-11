<?php

namespace App\Livewire\Ai;

use App\Livewire\Concerns\InteractsWithApi;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * How much AI the college uses, and which models it uses (picked from each provider's live list).
 */
#[Layout('layouts::app')]
#[Title('AI usage')]
class Usage extends Component
{
    use InteractsWithApi;

    #[Url(except: 14)]
    public int $days = 14;

    /** @var array<string, array{fast: string, smart: string}> provider => chosen models ('' = default) */
    public array $chosen = [];

    /** @var array<string, array<string, mixed>> provider => last test result */
    public array $tests = [];

    public function mount(): void
    {
        foreach ($this->models()['providers'] ?? [] as $p) {
            $this->chosen[$p['provider']] = ['fast' => (string) $p['chosen']['fast'], 'smart' => (string) $p['chosen']['smart']];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function models(bool $refresh = false): array
    {
        return (array) $this->api()->withoutCache(fn () => $this->api()->get('ai/models', $refresh ? ['refresh' => 1] : []))->data();
    }

    public function refreshModels(): void
    {
        $this->models(true);
        $this->toast('success', __('Model lists refreshed'));
    }

    public function saveModels(): void
    {
        $payload = [];
        foreach ($this->chosen as $provider => $tiers) {
            foreach (['fast', 'smart'] as $tier) {
                $payload["{$provider}_{$tier}_model"] = $tiers[$tier] ?: null;
            }
        }

        $this->callApi(fn () => $this->api()->put('settings/ai', $payload), onSuccess: function (): void {
            $this->tests = [];
            $this->toast('success', __('Models saved'), __('New AI requests use them straight away.'));
        });
    }

    public function test(string $provider): void
    {
        $this->callApi(fn () => $this->api()->post('ai/models/test', ['provider' => $provider]), onSuccess: fn ($response) => $this->tests[$provider] = (array) $response->data());
    }

    public function render(): View
    {
        return view('livewire.ai.usage', [
            'u' => (array) $this->api()->withoutCache(fn () => $this->api()->get('ai/usage', ['days' => $this->days]))->data(),
            'm' => $this->models(),
        ]);
    }
}
