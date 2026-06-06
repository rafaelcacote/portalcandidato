import type { FlashToast } from '@/types/ui';

type PrimeToastSeverity =
    | 'success'
    | 'info'
    | 'warn'
    | 'error'
    | 'secondary'
    | 'contrast';

const summaries: Record<FlashToast['type'], string> = {
    info: 'Informação',
    success: 'Sucesso',
    warning: 'Atenção',
    error: 'Erro',
};

/** Maps app flash types to PrimeVue Toast `severity`. */
export function flashTypeToSeverity(
    type: FlashToast['type'],
): PrimeToastSeverity {
    if (type === 'warning') {
        return 'warn';
    }

    return type;
}

export function summaryForFlashType(type: FlashToast['type']): string {
    return summaries[type];
}
