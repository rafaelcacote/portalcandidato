/**
 * Brazilian CPF / CEP helpers for client-side validation and masks.
 */

export function cpfDigitsOnly(value: string): string {
    return value.replace(/\D/g, '');
}

/** Validates 11-digit CPF (same algorithm as backend `App\Rules\Cpf`). */
export function isValidCpfDigits(digits: string): boolean {
    if (digits.length !== 11 || /^(\d)\1{10}$/.test(digits)) {
        return false;
    }

    for (let t = 9; t < 11; t++) {
        let d = 0;

        for (let c = 0; c < t; c++) {
            d += Number.parseInt(digits[c]!, 10) * (t + 1 - c);
        }

        d = ((10 * d) % 11) % 10;

        if (Number.parseInt(digits[t]!, 10) !== d) {
            return false;
        }
    }

    return true;
}

export function cepDigitsOnly(value: string): string {
    return value.replace(/\D/g, '').slice(0, 8);
}

/** Formats 11-digit CPF as `000.000.000-00`; returns `-` when empty; raw value when not 11 digits. */
export function formatCpfDisplay(value: string | null | undefined): string {
    if (value == null || value === '') {
        return '-';
    }

    const d = cpfDigitsOnly(value);

    if (d.length !== 11) {
        return value;
    }

    return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`;
}

export function formatCepDisplay(digits: string): string {
    const d = cepDigitsOnly(digits);

    if (d.length <= 5) {
        return d;
    }

    return `${d.slice(0, 5)}-${d.slice(5)}`;
}
