<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { getInitials } from '@/composables/useInitials';

const props = withDefaults(
    defineProps<{
        name: string;
        photoUrl?: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        photoUrl: null,
        size: 'md',
    },
);

const photoErrored = ref(false);

watch(
    () => props.photoUrl,
    () => {
        photoErrored.value = false;
    },
);

const showPhoto = computed(
    () => Boolean(props.photoUrl) && !photoErrored.value,
);

const sizeClasses = computed(() => {
    const map = {
        sm: 'size-10 rounded-xl text-sm',
        md: 'size-16 rounded-2xl text-lg sm:size-20 sm:text-xl',
        lg: 'size-24 rounded-2xl text-2xl sm:size-28 sm:text-3xl',
    };

    return map[props.size];
});

const initials = computed(() => getInitials(props.name));
</script>

<template>
    <div
        :class="[
            'relative shrink-0 overflow-hidden shadow-md ring-2 ring-white ring-offset-1 ring-offset-white',
            sizeClasses,
        ]"
    >
        <img
            v-if="showPhoto"
            :src="photoUrl!"
            :alt="`Foto de ${name}`"
            class="size-full object-cover"
            @error="photoErrored = true"
        />
        <div
            v-else
            class="flex size-full items-center justify-center bg-gradient-to-br from-teal-500 to-emerald-600 font-bold text-white"
        >
            {{ initials }}
        </div>
    </div>
</template>
