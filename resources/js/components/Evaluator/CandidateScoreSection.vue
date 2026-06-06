<script setup lang="ts">
import { BarChart3, Trophy } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    criteria: Array<{
        id: number;
        nome: string;
        peso: number;
        pontuacao_max: number;
    }>;
    scores: Array<{ process_criteria_id: number; pontuacao: number }>;
    observacoes: string;
    titleScoringCurrent?: number;
    titleScoringMax?: number;
}>();

const emit = defineEmits<{
    'update:scores': [
        value: Array<{ process_criteria_id: number; pontuacao: number }>,
    ];
    'update:observacoes': [value: string];
}>();

function updateScore(index: number, value: number): void {
    const updated = [...props.scores];
    updated[index] = { ...updated[index], pontuacao: value };
    emit('update:scores', updated);
}

const totalScore = computed(() => {
    const criteriaPart = props.scores.reduce(
        (sum, s) => sum + (Number(s.pontuacao) || 0),
        0,
    );
    const titlePart = Number(props.titleScoringCurrent ?? 0);

    return criteriaPart + titlePart;
});

const maxScore = computed(() => {
    const criteriaPart = props.criteria.reduce(
        (sum, c) => sum + c.pontuacao_max,
        0,
    );
    const titlePart = Number(props.titleScoringMax ?? 0);

    return criteriaPart + titlePart;
});

const sectionHeading = computed(() => {
    if (props.criteria.length > 0) {
        return 'Pontuação por critério e titulação';
    }

    return 'Pontuação — formação acadêmica / titulação';
});

const sectionSubheading = computed(() => {
    if (props.criteria.length > 0) {
        return 'Critérios do edital e pontos por documento de titulação (respeitando tetos por item e por grupo).';
    }

    return 'Atribua pontos em cada linha de titulação; o sistema respeita o valor por unidade, a quantidade declarada e o teto do grupo.';
});

const scorePercentage = computed(() => {
    if (maxScore.value === 0) {
        return 0;
    }

    return Math.min(100, Math.round((totalScore.value / maxScore.value) * 100));
});

const scoreColor = computed(() => {
    if (scorePercentage.value >= 70) {
        return 'bg-emerald-500';
    }

    if (scorePercentage.value >= 40) {
        return 'bg-amber-400';
    }

    return 'bg-red-400';
});

const scoreLabelColor = computed(() => {
    if (scorePercentage.value >= 70) {
        return 'text-emerald-600';
    }

    if (scorePercentage.value >= 40) {
        return 'text-amber-600';
    }

    return 'text-red-600';
});
</script>

<template>
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex size-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600"
                >
                    <BarChart3 class="size-4.5" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        {{ sectionHeading }}
                    </h3>
                    <p class="text-xs text-slate-500">
                        {{ sectionSubheading }}
                    </p>
                </div>
            </div>

            <!-- Total score badge -->
            <div
                class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-50 to-indigo-50 px-4 py-2 ring-1 ring-violet-200/60"
            >
                <Trophy class="size-4 text-violet-500" />
                <span class="text-xs font-semibold text-slate-500"
                    >Pontuação total</span
                >
                <span
                    :class="['text-lg font-bold tabular-nums', scoreLabelColor]"
                >
                    {{ totalScore.toFixed(1) }}
                </span>
                <span class="text-xs text-slate-400">/ {{ maxScore }}</span>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="px-6 pt-4 pb-2">
            <div class="flex items-center justify-between gap-3">
                <div
                    class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"
                >
                    <div
                        :class="[
                            'h-full rounded-full transition-all duration-500',
                            scoreColor,
                        ]"
                        :style="{ width: `${scorePercentage}%` }"
                    />
                </div>
                <span
                    :class="[
                        'shrink-0 text-sm font-bold tabular-nums',
                        scoreLabelColor,
                    ]"
                >
                    {{ scorePercentage }}%
                </span>
            </div>
        </div>

        <!-- Criteria list -->
        <div v-if="criteria.length > 0" class="flex flex-col gap-2 px-4 py-3">
            <div
                v-for="(criterion, index) in criteria"
                :key="criterion.id"
                class="group flex flex-col gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-slate-200 hover:bg-white sm:flex-row sm:items-center"
            >
                <!-- Criterion info -->
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-800">
                            {{ criterion.nome }}
                        </p>
                        <span
                            class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-slate-500 uppercase"
                        >
                            Peso {{ criterion.peso }}
                        </span>
                    </div>
                </div>

                <!-- Score input -->
                <div class="flex shrink-0 items-center gap-3">
                    <span class="text-xs font-medium text-slate-400">
                        Máx.
                        <strong class="text-slate-600">{{
                            criterion.pontuacao_max
                        }}</strong>
                    </span>
                    <div class="relative">
                        <input
                            type="number"
                            :value="scores[index]?.pontuacao ?? 0"
                            :min="0"
                            :max="criterion.pontuacao_max"
                            step="0.5"
                            class="w-24 rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-sm font-bold text-slate-800 transition-colors outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20"
                            @input="
                                (e) =>
                                    updateScore(
                                        index,
                                        parseFloat(
                                            (e.target as HTMLInputElement)
                                                .value,
                                        ) || 0,
                                    )
                            "
                        />
                    </div>
                    <span class="text-xs text-slate-400">
                        / {{ criterion.pontuacao_max }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Observations -->
        <div class="border-t border-slate-100 px-6 py-5">
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                Observações gerais
                <span class="ml-1 font-normal text-slate-400">(opcional)</span>
            </label>
            <textarea
                :value="observacoes"
                rows="4"
                placeholder="Comentários sobre a avaliação geral do candidato..."
                class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 text-sm text-slate-700 placeholder-slate-400 transition-colors outline-none focus:border-violet-400 focus:bg-white focus:ring-2 focus:ring-violet-400/20"
                @input="
                    (e) =>
                        emit(
                            'update:observacoes',
                            (e.target as HTMLTextAreaElement).value,
                        )
                "
            />
        </div>
    </div>
</template>
