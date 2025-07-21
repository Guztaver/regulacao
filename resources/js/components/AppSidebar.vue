<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useTranslations } from '@/composables/useTranslations';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Calendar, CheckCircle, Clock, FileCheck, FileText, Folder, LayoutGrid, Users, XCircle } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const { t } = useTranslations();

const mainNavItems: NavItem[] = [
    {
        title: t.dashboard,
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Entradas',
        href: '/entries/active',
        icon: FileText,
        items: [
            {
                title: 'Entradas Ativas',
                href: '/entries/active',
                icon: Clock,
            },
            {
                title: 'Entradas Agendadas',
                href: '/entries/scheduled',
                icon: Calendar,
            },
            {
                title: 'Exames Prontos',
                href: '/entries/exam-ready',
                icon: FileCheck,
            },
            {
                title: t.completedEntries,
                href: '/entries/completed',
                icon: CheckCircle,
            },
            {
                title: 'Entradas Canceladas',
                href: '/entries/cancelled',
                icon: XCircle,
            },
        ],
    },
    {
        title: t.patients,
        href: '/patients',
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Prefeitura de Pau Brasil',
        href: 'https://www.paubrasil.ba.gov.br/',
        icon: Folder,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
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
