import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

/**
 * `x-data="otpInput({ length: 6 })"` — one box per digit, paste a whole code,
 * arrow keys and backspace move between boxes. The joined value is written to the
 * hidden `[x-ref=value]` input (bind it with wire:model) and `otp-complete` fires when full.
 */
export function registerOtpInput(Alpine: AlpineStatic): void {
    Alpine.data('otpInput', (options: { length: number }) => ({
        length: options.length,
        digits: Array.from({ length: options.length }, () => '') as string[],

        boxes(): HTMLInputElement[] {
            return Array.from(this.$el.querySelectorAll<HTMLInputElement>('[data-otp-box]'));
        },

        input(index: number, event: Event): void {
            const target = event.target as HTMLInputElement;
            const value = target.value.replace(/\D/g, '');

            if (value.length > 1) {
                this.fill(value, index);

                return;
            }

            this.digits[index] = value;
            target.value = value;

            if (value !== '' && index < this.length - 1) {
                this.boxes()[index + 1].focus();
            }

            this.commit();
        },

        keydown(index: number, event: KeyboardEvent): void {
            const boxes = this.boxes();

            if (event.key === 'Backspace' && this.digits[index] === '' && index > 0) {
                boxes[index - 1].focus();
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                boxes[index - 1].focus();
            } else if (event.key === 'ArrowRight' && index < this.length - 1) {
                event.preventDefault();
                boxes[index + 1].focus();
            }
        },

        paste(event: ClipboardEvent): void {
            event.preventDefault();
            this.fill((event.clipboardData?.getData('text') ?? '').replace(/\D/g, ''), 0);
        },

        fill(value: string, from: number): void {
            const boxes = this.boxes();

            for (let offset = 0; offset < value.length && from + offset < this.length; offset++) {
                this.digits[from + offset] = value[offset];
                boxes[from + offset].value = value[offset];
            }

            boxes[Math.min(from + value.length, this.length - 1)].focus();
            this.commit();
        },

        commit(): void {
            const code = this.digits.join('');
            const hidden = this.$refs.value as HTMLInputElement | undefined;

            if (hidden) {
                hidden.value = code;
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (code.length === this.length) {
                this.$dispatch('otp-complete', code);
            }
        },
    }));
}
