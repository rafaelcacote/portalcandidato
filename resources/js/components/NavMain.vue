<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem, NavSection } from '@/types';

const props = defineProps<{
    items?: NavItem[];
    sections?: NavSection[];
}>();

const { isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();

function showComingSoonNotice(item: NavItem): void {
    const description =
        item.comingSoonMessage ??
        'Esta área ainda está em desenvolvimento e estará disponível em breve.';

    toast(item.title, {
        description,
    });
}

const resolvedSections = computed<NavSection[]>(() => {
    if (props.sections?.length) {
        return props.sections;
    }

    return [{ label: 'Platform', items: props.items ?? [] }];
});
</script>

<template>
    <SidebarGroup
        v-for="section in resolvedSections"
        :key="section.label"
        class="px-2 py-0"
    >
        <SidebarGroupLabel>{{ section.label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in section.items" :key="item.title">
                <template v-if="item.children?.length">
                    <Collapsible
                        :default-open="isCurrentOrParentUrl(item.href)"
                        class="group/collapsible"
                    >
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :is-active="isCurrentOrParentUrl(item.href)"
                                :tooltip="item.title"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="child in item.children"
                                    :key="child.title"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="isCurrentUrl(child.href)"
                                    >
                                        <Link :href="child.href">
                                            <span>{{ child.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </template>

                <SidebarMenuButton
                    v-else-if="item.comingSoon"
                    type="button"
                    :tooltip="item.title"
                    @click="showComingSoonNotice(item)"
                >
                    <component :is="item.icon" />
                    <span>{{ item.title }}</span>
                </SidebarMenuButton>

                <SidebarMenuButton
                    v-else
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
