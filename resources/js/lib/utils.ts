import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export const APP_TIMEZONE = 'America/Sao_Paulo';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

function datetimePart(
    parts: Intl.DateTimeFormatPart[],
    type: Intl.DateTimeFormatPartTypes,
): string {
    return parts.find((part) => part.type === type)?.value ?? '';
}

/**
 * Converts an ISO date string to the format required by HTML `datetime-local` inputs.
 */
export function toDatetimeLocalInputValue(
    value: string | null | undefined,
): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: APP_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date);

    return `${datetimePart(parts, 'year')}-${datetimePart(parts, 'month')}-${datetimePart(parts, 'day')}T${datetimePart(parts, 'hour')}:${datetimePart(parts, 'minute')}`;
}

/**
 * Formats a date/time for display using the application timezone (Brazil).
 */
export function formatDateTimeBR(
    value: string | null | undefined,
    options: Intl.DateTimeFormatOptions = { dateStyle: 'short' },
): string {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('pt-BR', {
        ...options,
        timeZone: APP_TIMEZONE,
    }).format(date);
}

/**
 * Formats a date (no time) for display using the application timezone.
 */
export function formatDateBR(value: string | null | undefined): string {
    return formatDateTimeBR(value, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
