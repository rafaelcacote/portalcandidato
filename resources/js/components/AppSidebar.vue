<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChevronRight,
    ClipboardCheck,
    FileText,
    FolderOpen,
    LayoutGrid,
    MoreHorizontal,
    Settings2,
    Sparkles,
    UserCircle,
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { useUserAvatar } from '@/composables/useUserAvatar';
import { dashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminCandidatesIndex } from '@/routes/admin/candidates';
import { index as adminEvaluatorsIndex } from '@/routes/admin/evaluators';
import { index as adminProcessesIndex } from '@/routes/admin/processes';
import {
    contacts as adminReportsContacts,
    evaluated as adminReportsEvaluated,
    index as adminReportsIndex,
} from '@/routes/admin/reports';
import { index as adminDocumentTypesIndex } from '@/routes/admin/support-tables/document-types';
import { index as adminTitleTypesIndex } from '@/routes/admin/support-tables/title-types';
import { dashboard as candidateDashboard } from '@/routes/candidate';
import { index as candidateApplicationsIndex } from '@/routes/candidate/applications';
import { index as candidateDocumentsIndex } from '@/routes/candidate/documents';
import { index as candidateProcessesIndex } from '@/routes/candidate/processes';
import { dashboard as evaluatorDashboard } from '@/routes/evaluator';
import { index as evaluatorProcessesIndex } from '@/routes/evaluator/processes';
import { edit as profileEdit } from '@/routes/profile';
import type { NavItem, NavSection } from '@/types';
import type { Auth } from '@/types/auth';

const page = usePage<{ auth: Auth }>();

const { avatarUrl: userAvatarUrl, hasAvatar: hasUserAvatar } = useUserAvatar(
    () => page.props.auth?.user,
);

const isAdmin = computed(
    () => page.props.auth?.roles?.includes('admin') ?? false,
);

const isEvaluator = computed(
    () => page.props.auth?.roles?.includes('avaliador') ?? false,
);

const isCandidate = computed(
    () => page.props.auth?.roles?.includes('candidato') ?? false,
);

const usesRoleSidebar = computed(
    () => isAdmin.value || isEvaluator.value || isCandidate.value,
);

const roleSidebarHomeUrl = computed(() => {
    if (isAdmin.value) {
        return adminDashboard.url();
    }

    if (isEvaluator.value) {
        return evaluatorDashboard.url();
    }

    if (isCandidate.value) {
        return candidateDashboard.url();
    }

    return dashboard();
});

const roleSidebarSubtitle = computed(() => {
    if (isAdmin.value) {
        return 'Painel administrativo';
    }

    if (isEvaluator.value) {
        return 'Painel do avaliador';
    }

    if (isCandidate.value) {
        return 'Painel do candidato';
    }

    return '';
});

const adminNavSections = computed<NavSection[]>(() => [
    {
        label: 'Principal',
        items: [
            {
                title: 'Dashboard',
                href: adminDashboard.url(),
                icon: LayoutGrid,
            },
            {
                title: 'Processos Seletivos',
                href: adminProcessesIndex.url(),
                icon: FileText,
            },
            {
                title: 'Avaliadores',
                href: adminEvaluatorsIndex.url(),
                icon: Users,
            },
            {
                title: 'Candidatos',
                href: adminCandidatesIndex.url(),
                icon: UserCircle,
            },
            {
                title: 'Relatórios',
                href: adminReportsIndex.url(),
                icon: ClipboardCheck,
                children: [
                    {
                        title: 'Candidatos inscritos',
                        href: adminReportsIndex.url(),
                    },
                    {
                        title: 'Candidatos avaliados',
                        href: adminReportsEvaluated.url(),
                    },
                    {
                        title: 'Contatos (e-mail)',
                        href: adminReportsContacts.url(),
                    },
                ],
            },
        ],
    },
    {
        label: 'Apoio',
        items: [
            {
                title: 'Tabelas de Apoio',
                href: adminDocumentTypesIndex.url(),
                icon: Settings2,
                children: [
                    {
                        title: 'Tipos de Documentos',
                        href: adminDocumentTypesIndex.url(),
                    },
                    {
                        title: 'Tipos de Títulos',
                        href: adminTitleTypesIndex.url(),
                    },
                ],
            },
        ],
    },
]);

const adminShortcutLinks = computed(() => [
    { title: 'Novo processo', href: adminProcessesIndex.url() },
    { title: 'Tipos de documentos', href: adminDocumentTypesIndex.url() },
    { title: 'Avaliadores', href: adminEvaluatorsIndex.url() },
    { title: 'Candidatos', href: adminCandidatesIndex.url() },
    { title: 'Relatórios', href: adminReportsIndex.url() },
]);

const candidateNavSections = computed<NavSection[]>(() => [
    {
        label: 'Principal',
        items: [
            {
                title: 'Dashboard',
                href: candidateDashboard.url(),
                icon: LayoutGrid,
            },
            {
                title: 'Processos abertos',
                href: candidateProcessesIndex.url(),
                icon: FileText,
            },
            {
                title: 'Minhas inscrições',
                href: candidateApplicationsIndex.url(),
                icon: ClipboardCheck,
            },
            {
                title: 'Meus documentos',
                href: candidateDocumentsIndex.url(),
                icon: FolderOpen,
            },
            { title: 'Meu perfil', href: profileEdit().url, icon: UserCircle },
        ],
    },
]);

const candidateShortcutLinks = computed(() => [
    { title: 'Dashboard', href: candidateDashboard.url() },
    { title: 'Processos abertos', href: candidateProcessesIndex.url() },
    { title: 'Minhas inscrições', href: candidateApplicationsIndex.url() },
    { title: 'Meus documentos', href: candidateDocumentsIndex.url() },
    { title: 'Meu perfil', href: profileEdit().url },
]);

const roleShortcutLinks = computed(() => {
    if (isAdmin.value) {
        return adminShortcutLinks.value;
    }

    if (isEvaluator.value) {
        return [
            { title: 'Dashboard', href: evaluatorDashboard.url() },
            {
                title: 'Processos atribuídos',
                href: evaluatorProcessesIndex().url,
            },
        ];
    }

    if (isCandidate.value) {
        return candidateShortcutLinks.value;
    }

    return [];
});

const evaluatorNavSections = computed<NavSection[]>(() => [
    {
        label: 'Principal',
        items: [
            {
                title: 'Dashboard',
                href: evaluatorDashboard.url(),
                icon: LayoutGrid,
            },
            {
                title: 'Processos Atribuídos',
                href: evaluatorProcessesIndex().url,
                icon: ClipboardCheck,
            },
        ],
    },
]);

function showComingSoonShortcut(title: string): void {
    toast(title, {
        description:
            'Esta área ainda está em desenvolvimento e estará disponível em breve.',
    });
}

const mainNavItems = computed<NavItem[]>(() => {
    const roles = page.props.auth?.roles ?? [];

    if (roles.includes('admin')) {
        return [];
    }

    if (roles.includes('avaliador')) {
        return [];
    }

    if (roles.includes('candidato')) {
        return [];
    }

    return [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <!-- ── Logo ─────────────────────────────────────────────────────── -->
        <SidebarHeader class="px-3 pt-4 pb-2">
            <SidebarMenu>
                <SidebarMenuItem>
                    <template v-if="usesRoleSidebar">
                        <SidebarMenuButton
                            size="lg"
                            as-child
                            class="h-auto rounded-xl px-2.5 py-2.5 transition-all hover:!bg-white/5"
                        >
                            <Link
                                :href="roleSidebarHomeUrl"
                                class="flex flex-col gap-1.5"
                            >
                                <!-- Expanded: logos PROENSP + UEA -->
                                <div
                                    class="flex min-w-0 flex-wrap items-center gap-2.5 group-data-[collapsible=icon]:hidden"
                                >
                                    <img
                                        src="/img/logo_pro_little.svg"
                                        alt="PROENSP"
                                        class="h-7 w-auto max-w-[9rem] shrink object-contain object-left opacity-90 brightness-0 invert"
                                    />
                                    <img
                                        src="/img/uea_00.svg"
                                        alt="UEA"
                                        class="h-8 w-8 shrink-0 object-contain opacity-90 brightness-0 invert"
                                    />
                                </div>
                                <!-- Collapsed: icon fallback -->
                                <span
                                    class="hidden size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/15 group-data-[collapsible=icon]:flex"
                                >
                                    <Sparkles class="size-4" />
                                </span>
                                <span
                                    class="text-[0.58rem] font-bold tracking-[0.14em] text-slate-500 uppercase group-data-[collapsible=icon]:hidden"
                                >
                                    {{ roleSidebarSubtitle }}
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </template>

                    <SidebarMenuButton v-else size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- Divider -->
        <div v-if="usesRoleSidebar" class="mx-3 h-px bg-white/5" />

        <!-- ── Navigation ────────────────────────────────────────────────── -->
        <SidebarContent>
            <NavMain v-if="isAdmin" :sections="adminNavSections" />
            <NavMain v-else-if="isEvaluator" :sections="evaluatorNavSections" />
            <NavMain v-else-if="isCandidate" :sections="candidateNavSections" />
            <NavMain v-else :items="mainNavItems" />
        </SidebarContent>

        <!-- ── Footer (admin / avaliador / candidato) ───────────────────── -->
        <SidebarFooter
            v-if="usesRoleSidebar && page.props.auth?.user"
            class="px-3 pt-2 pb-4"
        >
            <div class="mb-2 h-px w-full bg-white/5" />

            <!-- Quick shortcuts -->
            <Collapsible v-slot="{ open }" class="mb-2">
                <CollapsibleTrigger
                    class="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-xs font-medium text-slate-400 transition-all outline-none hover:bg-white/5 hover:text-slate-200 focus-visible:ring-1 focus-visible:ring-sidebar-ring"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 ring-1 ring-amber-500/15"
                    >
                        <Zap class="size-3.5" />
                    </span>
                    <span
                        class="flex-1 truncate group-data-[collapsible=icon]:hidden"
                        >Atalhos rápidos</span
                    >
                    <ChevronRight
                        class="size-3.5 shrink-0 text-slate-600 transition-transform group-data-[collapsible=icon]:hidden"
                        :class="open ? 'rotate-90' : ''"
                    />
                </CollapsibleTrigger>

                <CollapsibleContent>
                    <ul class="mt-1 space-y-px pb-1 pl-1">
                        <li v-for="link in roleShortcutLinks" :key="link.title">
                            <button
                                v-if="link.comingSoon"
                                type="button"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs text-slate-500 transition-colors hover:bg-white/5 hover:text-slate-300"
                                @click="showComingSoonShortcut(link.title)"
                            >
                                <span
                                    class="size-1 shrink-0 rounded-full bg-slate-700"
                                />
                                {{ link.title }}
                            </button>
                            <Link
                                v-else
                                :href="link.href"
                                class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs text-slate-500 transition-colors hover:bg-white/5 hover:text-slate-300"
                            >
                                <span
                                    class="size-1 rounded-full bg-slate-700"
                                />
                                {{ link.title }}
                            </Link>
                        </li>
                    </ul>
                </CollapsibleContent>
            </Collapsible>

            <!-- User card -->
            <div
                class="flex items-center gap-2.5 rounded-xl bg-white/[0.04] px-2.5 py-2 ring-1 ring-white/[0.06] group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-2"
            >
                <Avatar class="size-8 shrink-0 rounded-lg ring-1 ring-white/10">
                    <AvatarImage
                        v-if="hasUserAvatar"
                        :src="userAvatarUrl!"
                        :alt="page.props.auth.user.name"
                    />
                    <AvatarFallback
                        class="rounded-lg bg-gradient-to-br from-emerald-500/20 to-teal-500/20 text-[11px] font-bold text-emerald-300"
                    >
                        {{ getInitials(page.props.auth.user.name) }}
                    </AvatarFallback>
                </Avatar>

                <div
                    class="min-w-0 flex-1 group-data-[collapsible=icon]:hidden"
                >
                    <p class="truncate text-xs font-semibold text-slate-200">
                        {{ page.props.auth.user.name }}
                    </p>
                    <p class="truncate text-[10px] text-slate-500">
                        {{ page.props.auth.user.email }}
                    </p>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-slate-500 ring-sidebar-ring transition-colors outline-none group-data-[collapsible=icon]:hidden hover:bg-white/10 hover:text-slate-300 focus-visible:ring-1"
                            aria-label="Menu do usuário"
                        >
                            <MoreHorizontal class="size-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="page.props.auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
