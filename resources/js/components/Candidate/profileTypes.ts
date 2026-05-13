/**
 * Subset of `User` attributes shared via Inertia for the candidate profile.
 *
 * Mirrors the fillables in `App\Models\User` plus a few framework fields
 * (id/email_verified_at/updated_at). Every personal field is optional because
 * candidates may have an incomplete profile during onboarding.
 */
export type CandidateProfileUser = {
    id?: number;
    name?: string | null;
    email?: string | null;
    email_verified_at?: string | null;
    cpf?: string | null;
    telefone?: string | null;
    telefone_fixo?: string | null;
    data_nascimento?: string | null;
    foto_path?: string | null;
    foto_url?: string | null;
    avatar?: string | null;
    identidade?: string | null;
    orgao_emissor?: string | null;
    identidade_uf?: string | null;
    identidade_data_emissao?: string | null;
    naturalidade?: string | null;
    nacionalidade?: string | null;
    sexo?: string | null;
    endereco?: string | null;
    endereco_numero?: string | null;
    bairro?: string | null;
    cep?: string | null;
    cidade?: string | null;
    endereco_uf?: string | null;
    pais?: string | null;
    updated_at?: string | null;
    [key: string]: unknown;
};

/** Returns true when the value is meaningful for display (non-null, non-blank). */
export function hasValue(value: unknown): boolean {
    if (value === null || value === undefined) {
        return false;
    }

    return String(value).trim() !== '';
}

/** Returns the value as a clean string or null when empty. */
export function asText(value: unknown): string | null {
    if (!hasValue(value)) {
        return null;
    }

    return String(value).trim();
}

/** Pretty date in pt-BR (day/month/year). Returns null when not parseable. */
export function formatDateBR(value: unknown): string | null {
    const text = asText(value);

    if (text === null) {
        return null;
    }

    const date = new Date(text);

    if (Number.isNaN(date.getTime())) {
        return text;
    }

    return date.toLocaleDateString('pt-BR');
}

/** Friendly relative time ("há 2 dias") or null when input is missing. */
export function formatRelative(value: unknown): string | null {
    const text = asText(value);

    if (text === null) {
        return null;
    }

    const ts = new Date(text).getTime();

    if (Number.isNaN(ts)) {
        return null;
    }

    const diffMs = Date.now() - ts;
    const minutes = Math.floor(diffMs / 60000);

    if (minutes < 1) {
        return 'há instantes';
    }

    if (minutes < 60) {
        return `há ${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `há ${hours} h`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `há ${days} dia${days === 1 ? '' : 's'}`;
    }

    const months = Math.floor(days / 30);

    if (months < 12) {
        return `há ${months} mês${months === 1 ? '' : 'es'}`;
    }

    const years = Math.floor(months / 12);

    return `há ${years} ano${years === 1 ? '' : 's'}`;
}

/**
 * Masks a CPF preserving the first 3 and last 2 digits (e.g. `123.•••.•••-25`).
 * Falls back to the raw input when fewer than 11 digits are present.
 */
export function maskCpf(value: unknown): string | null {
    const text = asText(value);

    if (text === null) {
        return null;
    }

    const digits = text.replace(/\D/g, '');

    if (digits.length < 11) {
        return text;
    }

    const head = digits.slice(0, 3);
    const tail = digits.slice(-2);

    return `${head}.•••.•••-${tail}`;
}

/** Masks the middle of an RG, keeping the first 2 and last 2 visible chars. */
export function maskRg(value: unknown): string | null {
    const text = asText(value);

    if (text === null) {
        return null;
    }

    const compact = text.replace(/\s+/g, '');

    if (compact.length <= 4) {
        return compact;
    }

    return `${compact.slice(0, 2)}•••${compact.slice(-2)}`;
}

/** Single line address (logradouro + número, bairro). */
export function buildStreetLine(user: CandidateProfileUser): string | null {
    const street = asText(user.endereco);

    if (street === null) {
        return null;
    }

    const number = asText(user.endereco_numero);
    const district = asText(user.bairro);
    const left = number !== null ? `${street}, ${number}` : street;

    return district !== null ? `${left} — ${district}` : left;
}

/** "Cidade / UF" or just one of them when the other is missing. */
export function buildCityLine(user: CandidateProfileUser): string | null {
    const city = asText(user.cidade);
    const uf = asText(user.endereco_uf);

    if (city !== null && uf !== null) {
        return `${city} / ${uf}`;
    }

    return city ?? uf;
}

/**
 * Fields tracked by the profile completion meter.
 *
 * Mirrors `User::candidateProfileIsComplete()` (server-side source of truth)
 * plus `data_nascimento` and `identidade_data_emissao`.
 */
export const PROFILE_COMPLETION_FIELDS: ReadonlyArray<{
    key: keyof CandidateProfileUser;
    label: string;
}> = [
    { key: 'name', label: 'Nome completo' },
    { key: 'email', label: 'E-mail' },
    { key: 'cpf', label: 'CPF' },
    { key: 'data_nascimento', label: 'Data de nascimento' },
    { key: 'sexo', label: 'Sexo' },
    { key: 'naturalidade', label: 'Naturalidade' },
    { key: 'nacionalidade', label: 'Nacionalidade' },
    { key: 'telefone', label: 'Telefone (celular)' },
    { key: 'identidade', label: 'Identidade (RG)' },
    { key: 'orgao_emissor', label: 'Órgão emissor' },
    { key: 'identidade_uf', label: 'UF da identidade' },
    { key: 'identidade_data_emissao', label: 'Data de emissão da identidade' },
    { key: 'endereco', label: 'Logradouro' },
    { key: 'endereco_numero', label: 'Número' },
    { key: 'bairro', label: 'Bairro' },
    { key: 'cep', label: 'CEP' },
    { key: 'cidade', label: 'Cidade' },
    { key: 'endereco_uf', label: 'UF' },
    { key: 'pais', label: 'País' },
];

export type ProfileCompletionState = {
    filled: number;
    total: number;
    percent: number;
    missing: string[];
    isComplete: boolean;
};

/** Computes how complete the candidate profile is, plus what is still missing. */
export function getProfileCompletion(user: CandidateProfileUser | null): ProfileCompletionState {
    const total = PROFILE_COMPLETION_FIELDS.length;

    if (user === null) {
        return { filled: 0, total, percent: 0, missing: PROFILE_COMPLETION_FIELDS.map((f) => f.label), isComplete: false };
    }

    const missing: string[] = [];
    let filled = 0;

    for (const field of PROFILE_COMPLETION_FIELDS) {
        if (hasValue(user[field.key])) {
            filled += 1;
        } else {
            missing.push(field.label);
        }
    }

    const percent = total === 0 ? 0 : Math.round((filled / total) * 100);

    return { filled, total, percent, missing, isComplete: filled === total };
}
