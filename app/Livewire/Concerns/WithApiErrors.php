<?php

namespace App\Livewire\Concerns;

use App\Api\Exceptions\ApiException;
use App\Api\Exceptions\Unauthenticated;
use App\Api\Exceptions\ValidationFailed;
use Closure;
use Illuminate\Support\Facades\Route;

/**
 * Run an API call from a Livewire action and show its errors in the right place:
 * field errors under the fields, rule errors as a toast or in $formError, 401 → sign in.
 *
 *     $this->callApi(fn () => $this->api()->endpoint('rooms')->create($data), onSuccess: fn () => ...);
 */
trait WithApiErrors
{
    /**
     * Message of the last business-rule error, for showing inside a form.
     */
    public ?string $formError = null;

    /**
     * @template T
     *
     * @param  Closure(): T  $call
     * @param  array<string, string>  $fieldMap  API field → Livewire property (e.g. ['email' => 'form.email'])
     * @param  Closure(T): mixed|null  $onSuccess
     * @return T|null
     */
    protected function callApi(Closure $call, array $fieldMap = [], ?Closure $onSuccess = null, bool $toastErrors = true): mixed
    {
        $this->formError = null;
        $this->resetErrorBag();

        try {
            $result = $call();
        } catch (Unauthenticated) {
            $this->redirect(Route::has('login') ? route('login') : url('/login'));

            return null;
        } catch (ValidationFailed $exception) {
            foreach ($exception->errors as $field => $messages) {
                $this->addError($fieldMap[$field] ?? $this->mapField($field), (string) ($messages[0] ?? ''));
            }

            return null;
        } catch (ApiException $exception) {
            $this->formError = $exception->getMessage();
            $this->handleApiError($exception);

            if ($toastErrors) {
                $this->toast('error', $exception->getMessage(), $exception->status >= 500 && $exception->traceId ? __('Reference: :id', ['id' => $exception->traceId]) : null);
            }

            return null;
        }

        if ($onSuccess !== null) {
            $onSuccess($result);
        }

        return $result;
    }

    /**
     * Hook for components that react to specific error codes.
     */
    protected function handleApiError(ApiException $exception): void {}

    /**
     * Default field mapping: API "email" → Livewire "form.email" when the component has a `form` property.
     */
    protected function mapField(string $field): string
    {
        return property_exists($this, 'form') ? 'form.'.$field : $field;
    }

    protected function toast(string $type, string $title, ?string $message = null): void
    {
        $this->dispatch('toast', type: $type, title: $title, message: $message);
    }
}
