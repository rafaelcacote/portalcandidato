<script setup lang="ts">
import {
    AtSign,
    Briefcase,
    CalendarDays,
    GraduationCap,
    Hash,
    IdCard,
    Layers,
    Phone,
    UserRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { maskCpf } from '@/components/Candidate/profileTypes';
import CandidateAvatar from '@/components/Evaluator/CandidateAvatar.vue';
import CandidateStatusBadge from '@/components/Evaluator/CandidateStatusBadge.vue';

const props = defineProps<{
    application: {
        id: number;
        status: string;
        numero_protocolo: string | null;
        created_at: string;
        user: {
            name: string;
            email: string;
            cpf?: string | null;
            telefone?: string | null;
            foto_url?: string | null;
            photo_url?: string | null;
        };
        selectionProcess?: { titulo: string } | null;
        employment_relationship_summary?: {
            concorre_vagas_sem_vinculo: boolean;
            resposta_label: string;
        } | null;
        research_line_summary?: {
            linha_pesquisa: string;
            linha_pesquisa_label: string;
            orientador: string;
        } | null;
    };
}>();

const photoUrl = computed(
    () =>
        props.application.user.photo_url ??
        props.application.user.foto_url ??
        null,
);

const cpfMasked = computed(() => maskCpf(props.application.user.cpf));

const formattedDate = computed(() => {
    if (!props.application.created_at) {
        return '—';
    }

    return new Date(props.application.created_at).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80"
    >
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div
                class="absolute top-0 right-0 h-full w-1/2 bg-gradient-to-l from-teal-50/50 via-emerald-50/20 to-transparent"
            />
        </div>

        <div
            class="relative flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-center sm:gap-6 sm:px-6"
        >
            <div
                class="flex shrink-0 flex-col items-center gap-2 sm:items-start"
            >
                <CandidateAvatar
                    :name="application.user.name"
                    :photo-url="photoUrl"
                    size="lg"
                />
                <p
                    class="text-center text-[11px] font-medium text-slate-400 sm:text-left"
                >
                    Foto do candidato
                </p>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[11px] font-semibold tracking-wide text-teal-600 uppercase"
                        >
                            Candidato
                        </p>
                        <h2
                            class="mt-0.5 truncate text-lg font-bold tracking-tight text-slate-900 sm:text-xl"
                        >
                            {{ application.user.name }}
                        </h2>
                        <p
                            class="mt-1 flex items-center gap-1.5 truncate text-sm text-slate-500"
                        >
                            <AtSign class="size-3.5 shrink-0 text-slate-400" />
                            {{ application.user.email }}
                        </p>
                    </div>
                    <CandidateStatusBadge
                        :status="application.status"
                        size="md"
                    />
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <span
                        v-if="cpfMasked"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-100"
                    >
                        <IdCard class="size-3.5 shrink-0 text-slate-400" />
                        <span class="font-medium text-slate-500">CPF</span>
                        <span class="font-semibold text-slate-800">{{
                            cpfMasked
                        }}</span>
                    </span>

                    <span
                        v-if="application.user.telefone"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-100"
                    >
                        <Phone class="size-3.5 shrink-0 text-slate-400" />
                        <span class="font-medium text-slate-500">Telefone</span>
                        <span class="font-semibold text-slate-800">{{
                            application.user.telefone
                        }}</span>
                    </span>

                    <span
                        class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-100"
                    >
                        <Hash class="size-3.5 shrink-0 text-slate-400" />
                        <span class="font-medium text-slate-500"
                            >Inscrição</span
                        >
                        <span class="font-semibold text-slate-800">
                            #{{
                                application.numero_protocolo ?? application.id
                            }}
                        </span>
                    </span>

                    <span
                        class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-100"
                    >
                        <CalendarDays
                            class="size-3.5 shrink-0 text-slate-400"
                        />
                        <span class="font-medium text-slate-500"
                            >Data da inscrição</span
                        >
                        <span class="font-semibold text-slate-800">{{
                            formattedDate
                        }}</span>
                    </span>

                    <span
                        v-if="application.selectionProcess"
                        class="flex items-center gap-2 rounded-xl bg-teal-50/80 px-3 py-2 text-xs text-slate-600 ring-1 ring-teal-100 sm:col-span-2"
                    >
                        <Layers class="size-3.5 shrink-0 text-teal-500" />
                        <span class="font-medium text-slate-500"
                            >Processo seletivo</span
                        >
                        <span class="line-clamp-1 font-semibold text-slate-800">
                            {{ application.selectionProcess.titulo }}
                        </span>
                    </span>

                    <span
                        v-if="application.employment_relationship_summary"
                        class="flex items-start gap-2 rounded-xl bg-sky-50/80 px-3 py-2.5 text-xs text-slate-600 ring-1 ring-sky-100 sm:col-span-2"
                    >
                        <Briefcase
                            class="mt-0.5 size-3.5 shrink-0 text-sky-500"
                        />
                        <span class="min-w-0">
                            <span class="block font-medium text-slate-500"
                                >Vínculo empregatício</span
                            >
                            <span
                                class="mt-0.5 block font-semibold text-slate-800"
                            >
                                Concorre às vagas sem vínculo empregatício:
                                {{
                                    application.employment_relationship_summary
                                        .resposta_label
                                }}
                            </span>
                        </span>
                    </span>

                    <span
                        v-if="application.research_line_summary"
                        class="flex items-start gap-2 rounded-xl bg-violet-50/80 px-3 py-2.5 text-xs text-slate-600 ring-1 ring-violet-100 sm:col-span-2"
                    >
                        <GraduationCap
                            class="mt-0.5 size-3.5 shrink-0 text-violet-500"
                        />
                        <span class="min-w-0">
                            <span class="block font-medium text-slate-500"
                                >Linha de pesquisa</span
                            >
                            <span
                                class="mt-0.5 block font-semibold text-slate-800"
                            >
                                {{
                                    application.research_line_summary
                                        .linha_pesquisa_label
                                }}
                            </span>
                        </span>
                    </span>

                    <span
                        v-if="application.research_line_summary?.orientador"
                        class="flex items-start gap-2 rounded-xl bg-violet-50/80 px-3 py-2.5 text-xs text-slate-600 ring-1 ring-violet-100 sm:col-span-2"
                    >
                        <UserRound
                            class="mt-0.5 size-3.5 shrink-0 text-violet-500"
                        />
                        <span class="min-w-0">
                            <span class="block font-medium text-slate-500"
                                >Orientador</span
                            >
                            <span
                                class="mt-0.5 block font-semibold text-slate-800"
                            >
                                {{
                                    application.research_line_summary.orientador
                                }}
                            </span>
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
