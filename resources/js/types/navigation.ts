import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    children?: NavItem[];
    /**
     * When true, a click shows a notice instead of visiting `href` (e.g. feature not ready yet).
     */
    comingSoon?: boolean;
    /** Custom body for the coming-soon notice; falls back to a default message. */
    comingSoonMessage?: string;
};

export type NavSection = {
    label: string;
    items: NavItem[];
};
