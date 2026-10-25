import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

interface Rule {
    label: string;
    test: (value: string) => boolean;
}

/**
 * `x-data="passwordStrength({ minLength })"` — live checklist for the password rules
 * the API enforces (length, upper + lower case, number, symbol). Reads the input in
 * `[x-ref=input]` inside the same element.
 */
export function registerPasswordStrength(Alpine: AlpineStatic): void {
    Alpine.data('passwordStrength', (options: { minLength: number }) => ({
        value: '',

        rules(): Rule[] {
            return [
                { label: `At least ${options.minLength} characters`, test: (value) => value.length >= options.minLength },
                { label: 'Upper and lower case letters', test: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value) },
                { label: 'A number', test: (value) => /\d/.test(value) },
                { label: 'A symbol', test: (value) => /[^A-Za-z0-9]/.test(value) },
            ];
        },

        get passed(): number {
            return this.rules().filter((rule) => rule.test(this.value)).length;
        },

        get level(): 'empty' | 'weak' | 'fair' | 'strong' {
            if (this.value === '') {
                return 'empty';
            }

            return this.passed <= 2 ? 'weak' : this.passed === 3 ? 'fair' : 'strong';
        },

        track(event: Event): void {
            this.value = (event.target as HTMLInputElement).value;
        },
    }));
}
