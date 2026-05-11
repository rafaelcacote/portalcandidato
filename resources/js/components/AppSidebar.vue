<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ClipboardCheck,
    FileText,
    FolderOpen,
    LayoutGrid,
    Settings2,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
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
            {
                title: 'Meus Documentos',
                href: '/candidato/documents',
                icon: FolderOpen,
            },
        ];
    }

    return [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }];
});
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
    </Sidebar>
    <slot />
</template>
