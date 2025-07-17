import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Patient {
    id: string;
    name: string;
    email?: string;
    phone?: string;
    sus_number?: string;
    created_by?: User;
    created_at?: string;
    updated_at?: string;
}

export interface EntryTimeline {
    id: number;
    entry_id: string;
    user_id: number;
    action: string;
    description: string;
    metadata?: Record<string, any>;
    performed_at: string;
    created_at: string;
    updated_at: string;
    user?: User;
}

export interface EntryStatus {
    id: number;
    name: string;
    slug: string;
    color: string;
    description?: string;
    is_final: boolean;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface EntryStatusTransition {
    id: number;
    entry_id: string;
    from_status_id?: number;
    to_status_id: number;
    user_id: number;
    reason?: string;
    metadata?: Record<string, any>;
    transitioned_at: string;
    created_at: string;
    updated_at: string;
    from_status?: EntryStatus;
    to_status?: EntryStatus;
    user?: User;
    description?: string;
    scheduled_date?: string;
    notes?: string;
}

export interface Entry {
    id: string;
    patient_id: string;
    title: string;
    current_status_id: number;
    created_by: number;
    created_at?: string;
    updated_at?: string;
    patient?: Patient;
    createdBy?: User;
    current_status?: EntryStatus;
    timeline?: EntryTimeline[];
    status_transitions?: EntryStatusTransition[];
    scheduled_exam_date?: string;
    next_statuses?: EntryStatus[];
}
