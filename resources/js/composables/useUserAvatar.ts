import { computed, type MaybeRefOrGetter, toValue } from 'vue';

type UserWithPhoto = {
    avatar?: string | null;
    foto_url?: string | null;
};

/** Resolves the best available profile photo URL for avatars in the UI. */
export function resolveUserAvatarUrl(user: UserWithPhoto | null | undefined): string | null {
    if (user === null || user === undefined) {
        return null;
    }

    const url = user.avatar ?? user.foto_url;

    if (url === null || url === undefined || String(url).trim() === '') {
        return null;
    }

    return String(url);
}

export function useUserAvatar(user: MaybeRefOrGetter<UserWithPhoto | null | undefined>) {
    const avatarUrl = computed(() => resolveUserAvatarUrl(toValue(user)));

    const hasAvatar = computed(() => avatarUrl.value !== null);

    return { avatarUrl, hasAvatar };
}
