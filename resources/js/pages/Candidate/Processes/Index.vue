<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ArrowRight,
    BookOpen,
    Building2,
    ClipboardPlus,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Clock,
    Download,
    Eye,
    FileText,
    Filter,
    GraduationCap,
    Search,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PulseLoader } from '@/components/ui/pulse-loader';
import { Input } from '@/components/ui/input';
import { home } from '@/routes';
import { show as applicationShow, start } from '@/routes/candidate/applications';
import { index as processesIndex, show } from '@/routes/candidate/processes';

type ProcessItem = {
    id: number;
    titulo: string;
    descricao: string;
    status: string;
    orgao?: string | null;
    area?: string | null;
    tipo_programa?: string | null;
    inscricao_inicio_em: string | null;
    inscricao_fim_em: string | null;
    edital_download_url?: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: home.url() },
            { title: 'Processos seletivos', href: processesIndex().url },
        ],
    },
});

const props = defineProps<{
    processes: {
        data: Array<ProcessItem>;
        total: number;
        current_page: number;
        last_page: number;
    };
    openEnrollmentCount: number;
    draftApplicationIdsByProcessId: Record<number, number>;
    filters: {
        search: string;
        tipo_programa: string;
    };
}>();

const searchQuery = ref(props.filters.search);

watch(
    () => props.filters.search,
    (val) => {
        if (val !== searchQuery.value) {
            searchQuery.value = val;
        }
    },
);

const debouncedSearch = useDebounceFn((value: string) => {
    router.get(
        processesIndex().url,
        { search: value, tipo_programa: props.filters.tipo_programa, page: 1 },
        { preserveState: true, replace: true },
    );
}, 400);

watch(searchQuery, (val) => {
    debouncedSearch(val);
});

function setTipoPrograma(tipo: string): void {
    const newTipo = props.filters.tipo_programa === tipo ? '' : tipo;
    router.get(
        processesIndex().url,
        { search: searchQuery.value, tipo_programa: newTipo, page: 1 },
        { preserveState: true },
    );
}

function clearFilters(): void {
    searchQuery.value = '';
    router.get(processesIndex().url, {}, { preserveState: true });
}

const hasActiveFilters = computed(() => searchQuery.value !== '' || props.filters.tipo_programa !== '');

const tipoOptions = [
    { value: 'mestrado', label: 'Mestrado' },
    { value: 'doutorado', label: 'Doutorado' },
];

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatDateShort(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
    });
}

function isInscricaoAberta(process: ProcessItem): boolean {
    const now = new Date();
    const inicio = process.inscricao_inicio_em ? new Date(process.inscricao_inicio_em) : null;
    const fim = process.inscricao_fim_em ? new Date(process.inscricao_fim_em) : null;
    if (inicio && now < inicio) return false;
    if (fim && now > fim) return false;
    return process.status === 'ativo';
}

function daysUntilClose(process: ProcessItem): number | null {
    const fim = process.inscricao_fim_em ? new Date(process.inscricao_fim_em) : null;
    if (!fim) return null;
    const now = new Date();
    if (now > fim) return null;
    return Math.ceil((fim.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
}

function isClosingSoon(process: ProcessItem): boolean {
    const days = daysUntilClose(process);
    return days !== null && days <= 7;
}

function tipoLabel(tipo: string | null | undefined): string {
    if (!tipo) return '';
    const map: Record<string, string> = { mestrado: 'Mestrado', doutorado: 'Doutorado' };
    return map[tipo] ?? tipo;
}

function cardAccentClass(process: ProcessItem): string {
    if (!isInscricaoAberta(process)) return 'bg-muted';
    if (isClosingSoon(process)) return 'bg-amber-500';
    return 'bg-green-500';
}

function startApplication(processId: number): void {
    router.post(start(processId).url);
}
</script>

<template>
    <div class="p-1">
        <Head title="Processos seletivos" />

        <div class="mx-auto flex w-full max-w-[1820px] flex-col gap-6">
            <!-- ─── Hero Section ─── -->
            <div
                class="relative overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
            >
                <div
                    class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_100%_50%,hsl(var(--primary)/0.04)_0%,transparent_100%)]"
                />
                <div class="relative flex flex-col gap-5 px-6 py-7 sm:px-8">
                    <!-- Title row -->
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm"
                            >
                                <FileText :size="20" />
                            </div>
                            <div>
                                <h1
                                    class="text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                                >
                                    Processos seletivos
                                </h1>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    Encontre e inscreva-se nos processos com inscrições abertas
                                </p>
                            </div>
                        </div>

                        <Badge
                            v-if="openEnrollmentCount > 0"
                            variant="outline"
                            class="gap-2 border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400"
                        >
                            <PulseLoader />
                            {{
                                openEnrollmentCount === 1
                                    ? '1 inscrição aberta'
                                    : `${openEnrollmentCount} inscrições abertas`
                            }}
                        </Badge>
                        <Badge
                            v-else-if="processes.total > 0"
                            variant="outline"
                            class="text-muted-foreground"
                        >
                            {{
                                processes.total === 1
                                    ? '1 processo listado'
                                    : `${processes.total} processos listados`
                            }}
                        </Badge>
                    </div>

                    <!-- Search + Filters -->
                    <div class="flex flex-col gap-3">
                        <div class="relative max-w-xl">
                            <Search
                                :size="15"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="searchQuery"
                                placeholder="Buscar por título, área ou modalidade..."
                                class="h-10 pl-9 pr-9"
                            />
                            <button
                                v-if="searchQuery"
                                class="absolute right-3 top-1/2 -translate-y-1/2 rounded text-muted-foreground transition-colors hover:text-foreground"
                                type="button"
                                @click="searchQuery = ''"
                            >
                                <X :size="14" />
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                            >
                                <Filter :size="11" />
                                Modalidade:
                            </span>
                            <button
                                v-for="opt in tipoOptions"
                                :key="opt.value"
                                type="button"
                                :class="[
                                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-all duration-150',
                                    filters.tipo_programa === opt.value
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'bg-muted text-muted-foreground hover:bg-secondary hover:text-foreground',
                                ]"
                                @click="setTipoPrograma(opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                                @click="clearFilters"
                            >
                                <X :size="11" />
                                Limpar filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── Empty State ─── -->
            <div
                v-if="processes.data.length === 0"
                class="flex flex-col items-center justify-center gap-5 rounded-2xl border border-dashed border-border bg-card py-20 text-center"
            >
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
                >
                    <BookOpen :size="28" />
                </div>
                <div class="max-w-xs">
                    <p class="text-base font-semibold text-foreground">
                        Nenhum processo encontrado
                    </p>
                    <p class="mt-1.5 text-sm text-muted-foreground">
                        <template v-if="hasActiveFilters">
                            Nenhum processo corresponde aos filtros aplicados.
                        </template>
                        <template v-else>
                            Não há processos seletivos abertos no momento.
                        </template>
                    </p>
                </div>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background px-4 py-2 text-sm font-medium text-foreground shadow-xs transition-colors hover:bg-muted"
                    @click="clearFilters"
                >
                    <X :size="13" />
                    Limpar filtros
                </button>
            </div>

            <!-- ─── Process Grid ─── -->
            <div
                v-else
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="process in processes.data"
                    :key="process.id"
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/[0.06] dark:hover:shadow-black/20"
                >
                    <!-- Accent top bar -->
                    <div
                        :class="['h-0.5 w-full shrink-0 transition-all', cardAccentClass(process)]"
                    />

                    <!-- Card body -->
                    <div class="flex flex-1 flex-col gap-4 p-5">
                        <!-- Header: icon + badges -->
                        <div class="flex items-start justify-between gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-primary ring-1 ring-primary/10"
                            >
                                <GraduationCap
                                    v-if="process.tipo_programa === 'doutorado'"
                                    :size="18"
                                />
                                <BookOpen
                                    v-else
                                    :size="18"
                                />
                            </div>
                            <div class="flex flex-wrap justify-end gap-1.5">
                                <Badge
                                    v-if="isInscricaoAberta(process)"
                                    variant="outline"
                                    class="gap-1.5 border-green-200 bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400"
                                >
                                    <PulseLoader />
                                    Aberto
                                </Badge>
                                <span
                                    v-if="isClosingSoon(process)"
                                    class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-400"
                                >
                                    <Clock :size="10" />
                                    Encerrando
                                </span>
                                <span
                                    v-if="!isInscricaoAberta(process)"
                                    class="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                                >
                                    Encerrado
                                </span>
                            </div>
                        </div>

                        <!-- Title + meta chips -->
                        <div class="space-y-2">
                            <h3
                                class="line-clamp-2 text-sm font-semibold leading-snug text-foreground transition-colors group-hover:text-primary"
                            >
                                {{ process.titulo }}
                            </h3>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-if="process.tipo_programa"
                                    class="inline-flex items-center rounded-md bg-primary/8 px-2 py-0.5 text-xs font-medium text-primary"
                                >
                                    {{ tipoLabel(process.tipo_programa) }}
                                </span>
                                <span
                                    v-if="process.orgao"
                                    class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                >
                                    <Building2 :size="10" />
                                    {{ process.orgao }}
                                </span>
                                <span
                                    v-if="process.area"
                                    class="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                >
                                    {{ process.area }}
                                </span>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="line-clamp-2 text-xs leading-relaxed text-muted-foreground">
                            {{ process.descricao || 'Sem descrição cadastrada.' }}
                        </p>

                        <!-- Date + urgency – pushed to bottom -->
                        <div class="mt-auto space-y-2">
                            <div
                                class="flex items-center gap-2 rounded-lg bg-muted/60 px-3 py-2 text-xs text-muted-foreground"
                            >
                                <Calendar
                                    :size="12"
                                    class="shrink-0"
                                />
                                <span>
                                    {{ formatDateShort(process.inscricao_inicio_em) }} →
                                    {{ formatDate(process.inscricao_fim_em) }}
                                </span>
                            </div>
                            <div
                                v-if="isClosingSoon(process)"
                                class="flex items-center gap-1.5 rounded-lg border border-amber-200/70 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 dark:border-amber-800/50 dark:bg-amber-950/20 dark:text-amber-400"
                            >
                                <Clock
                                    :size="11"
                                    class="shrink-0"
                                />
                                Encerra em {{ daysUntilClose(process) }}
                                {{
                                    daysUntilClose(process) === 1 ? 'dia' : 'dias'
                                }}
                            </div>
                            <!-- Draft progress indicator -->
                            <div
                                v-else-if="draftApplicationIdsByProcessId[process.id] && isInscricaoAberta(process)"
                                class="flex items-center gap-1.5 rounded-lg border border-blue-200/70 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 dark:border-blue-800/50 dark:bg-blue-950/20 dark:text-blue-400"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-blue-500"
                                />
                                Inscrição em andamento
                            </div>
                        </div>
                    </div>

                    <!-- Footer actions -->
                    <div
                        class="flex items-center gap-1 border-t border-border/60 bg-muted/20 px-4 py-3"
                    >
                        <Link
                            :href="show(process.id).url"
                            class="shrink-0"
                        >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-8 gap-1.5 px-3 text-xs"
                                as="span"
                            >
                                <Eye :size="13" />
                                Detalhes
                            </Button>
                        </Link>
                        <a
                            v-if="process.edital_download_url"
                            :href="process.edital_download_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="shrink-0"
                        >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-8 gap-1.5 px-3 text-xs"
                                as="span"
                            >
                                <Download :size="13" />
                                Edital
                            </Button>
                        </a>

                        <!-- Primary CTA -->
                        <div class="ml-auto shrink-0">
                            <Link
                                v-if="
                                    isInscricaoAberta(process) &&
                                    draftApplicationIdsByProcessId[process.id]
                                "
                                :href="
                                    applicationShow({
                                        application:
                                            draftApplicationIdsByProcessId[process.id],
                                    }).url
                                "
                            >
                                <Button
                                    size="sm"
                                    class="h-8 gap-1.5 px-3 text-xs"
                                    variant="outline"
                                    as="span"
                                >
                                    Continuar
                                    <ArrowRight :size="13" />
                                </Button>
                            </Link>
                            <Button
                                v-else-if="isInscricaoAberta(process)"
                                size="sm"
                                type="button"
                                class="h-8 gap-1.5 border-0 bg-emerald-600 px-3 text-xs text-white shadow-sm hover:bg-emerald-700 focus-visible:ring-emerald-500/40 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                @click="startApplication(process.id)"
                            >
                                <ClipboardPlus :size="14" />
                                Inscrição
                            </Button>
                        </div>
                    </div>
                </article>
            </div>

            <!-- ─── Pagination ─── -->
            <div
                v-if="processes.last_page > 1"
                class="flex items-center justify-center gap-3 py-2"
            >
                <button
                    :disabled="processes.current_page === 1"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground shadow-xs transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                    type="button"
                    @click="
                        router.get(
                            processesIndex().url,
                            {
                                page: processes.current_page - 1,
                                search: filters.search,
                                tipo_programa: filters.tipo_programa,
                            },
                            { preserveState: true },
                        )
                    "
                >
                    <ChevronLeft :size="15" />
                </button>

                <span class="min-w-[6rem] text-center text-sm text-muted-foreground">
                    Página {{ processes.current_page }} de {{ processes.last_page }}
                </span>

                <button
                    :disabled="processes.current_page === processes.last_page"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground shadow-xs transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                    type="button"
                    @click="
                        router.get(
                            processesIndex().url,
                            {
                                page: processes.current_page + 1,
                                search: filters.search,
                                tipo_programa: filters.tipo_programa,
                            },
                            { preserveState: true },
                        )
                    "
                >
                    <ChevronRight :size="15" />
                </button>
            </div>
        </div>
    </div>
</template>
