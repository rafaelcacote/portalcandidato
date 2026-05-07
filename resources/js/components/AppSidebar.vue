<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardCheck,
    FileText,
    FolderGit2,
    LayoutGrid,
    Settings2,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import type { Auth } from '@/types/auth';

const page = usePage<{ auth: Auth }>();

const mainNavItems = computed<NavItem[]>(() => {
    const roles = page.props.auth?.roles ?? [];

    if (roles.includes('admin')) {
        return [
            { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutGrid },
            { title: 'Processos', href: '/admin/processes', icon: FileText },
            {
                title: 'Tabelas de Apoio',
                href: '/admin/support-tables/document-types',
                icon: Settings2,
                children: [
                    {
                        title: 'Tipos de Documentos',
                        href: '/admin/support-tables/document-types',
                    },
                    {
                        title: 'Tipos de Títulos',
                        href: '/admin/support-tables/title-types',
                    },
                ],
            },
            { title: 'Avaliadores', href: '/admin/evaluators', icon: Users },
            {
                title: 'Relatórios',
                href: '/admin/reports',
                icon: ClipboardCheck,
            },
        ];
    }

    if (roles.includes('avaliador')) {
        return [
            {
                title: 'Dashboard',
                href: '/avaliador/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Processos Atribuídos',
                href: '/avaliador/processes',
                icon: ClipboardCheck,
            },
        ];
    }

    if (roles.includes('candidato')) {
        return [
            {
                title: 'Dashboard',
                href: '/candidato/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Processos Abertos',
                href: '/candidato/processes',
                icon: FileText,
            },
            {
                title: 'Minhas Inscrições',
                href: '/candidato/applications',
                icon: ClipboardCheck,
            },
        ];
    }

    return [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }];
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
