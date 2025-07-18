<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
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
import { useTranslations } from '@/composables/useTranslations';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps<{
    items: NavItem[];
}>();

const { t } = useTranslations();
const page = usePage();

function isActiveUrl(href: string): boolean {
    return page.url === href;
}

function hasActiveSubItem(item: NavItem): boolean {
    return item.items?.some((subItem) => isActiveUrl(subItem.href)) ?? false;
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ t.platform }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <!-- Menu item with submenu -->
                <Collapsible v-if="item.items" as-child :default-open="hasActiveSubItem(item)">
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :tooltip="item.title"
                                :is-active="hasActiveSubItem(item)"
                                class="sidebar-menu-item group cursor-pointer"
                            >
                                <component :is="item.icon" class="sidebar-icon size-4" />
                                <span>{{ item.title }}</span>
                                <ChevronRight class="sidebar-arrow ml-auto size-4" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent class="sidebar-submenu">
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                    <SidebarMenuSubButton as-child :is-active="isActiveUrl(subItem.href)" class="sidebar-submenu-item cursor-pointer">
                                        <Link :href="subItem.href">
                                            <component :is="subItem.icon" class="sidebar-icon size-4" />
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Regular menu item -->
                <SidebarMenuButton v-else as-child :is-active="isActiveUrl(item.href)" :tooltip="item.title" class="sidebar-menu-item cursor-pointer">
                    <Link :href="item.href">
                        <component :is="item.icon" class="sidebar-icon size-4" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
