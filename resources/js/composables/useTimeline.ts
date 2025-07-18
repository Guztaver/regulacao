import type { EntryTimeline } from '@/types';

export function useTimeline() {
    function getTimelineIcon(action: string): string {
        const iconMap: Record<string, string> = {
            created: 'plus',
            completed: 'check',
            uncompleted: 'x',
            exam_scheduled: 'calendar',
            exam_ready: 'check-circle',
            updated: 'edit',
            deleted: 'trash',
        };
        return iconMap[action] || 'activity';
    }

    function getTimelineColor(action: string): string {
        const colorMap: Record<string, string> = {
            created: 'text-blue-600 dark:text-blue-400',
            completed: 'text-green-600 dark:text-green-400',
            uncompleted: 'text-yellow-600 dark:text-yellow-400',
            exam_scheduled: 'text-purple-600 dark:text-purple-400',
            exam_ready: 'text-indigo-600 dark:text-indigo-400',
            updated: 'text-gray-600 dark:text-gray-400',
            deleted: 'text-red-600 dark:text-red-400',
        };
        return colorMap[action] || 'text-gray-600 dark:text-gray-400';
    }

    function getTimelineBackgroundColor(action: string): string {
        const backgroundMap: Record<string, string> = {
            created: 'bg-blue-100 dark:bg-blue-900',
            completed: 'bg-green-100 dark:bg-green-900',
            uncompleted: 'bg-yellow-100 dark:bg-yellow-900',
            exam_scheduled: 'bg-purple-100 dark:bg-purple-900',
            exam_ready: 'bg-indigo-100 dark:bg-indigo-900',
            updated: 'bg-gray-100 dark:bg-gray-900',
            deleted: 'bg-red-100 dark:bg-red-900',
        };
        return backgroundMap[action] || 'bg-gray-100 dark:bg-gray-900';
    }

    function formatTimelineDate(dateString: string): string {
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now.getTime() - date.getTime();
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);

            if (diffMins < 1) return 'agora mesmo';
            if (diffMins < 60) return `${diffMins}m atrás`;
            if (diffHours < 24) return `${diffHours}h atrás`;
            if (diffDays < 7) return `${diffDays}d atrás`;

            return new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }).format(date);
        } catch {
            return 'Data Inválida';
        }
    }

    function getActionDisplayName(action: string): string {
        const displayNames: Record<string, string> = {
            created: 'Criado',
            completed: 'Concluído',
            uncompleted: 'Não Concluído',
            exam_scheduled: 'Exame Agendado',
            exam_ready: 'Exame Pronto',
            updated: 'Atualizado',
            deleted: 'Excluído',
        };
        return displayNames[action] || action.charAt(0).toUpperCase() + action.slice(1);
    }

    function formatTimelineMetadata(metadata: Record<string, any> | null | undefined): string {
        if (!metadata || Object.keys(metadata).length === 0) return '';

        const parts: string[] = [];

        if (metadata.scheduled_date) {
            try {
                const date = new Date(metadata.scheduled_date);
                const formatted = new Intl.DateTimeFormat('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                }).format(date);
                parts.push(`Agendado para: ${formatted}`);
            } catch {
                parts.push(`Agendado para: ${metadata.scheduled_date}`);
            }
        }

        if (metadata.changes) {
            const changeDescriptions = Object.entries(metadata.changes).map(([key, value]) => `${key}: ${value}`);
            parts.push(`Alterações: ${changeDescriptions.join(', ')}`);
        }

        if (metadata.title) {
            parts.push(`Título: ${metadata.title}`);
        }

        return parts.join(' | ');
    }

    function sortTimelineByDate(timeline: EntryTimeline[]): EntryTimeline[] {
        return [...timeline].sort((a, b) => {
            const dateA = new Date(a.performed_at);
            const dateB = new Date(b.performed_at);
            return dateB.getTime() - dateA.getTime(); // Most recent first
        });
    }

    function groupTimelineByDate(timeline: EntryTimeline[]): Record<string, EntryTimeline[]> {
        const grouped: Record<string, EntryTimeline[]> = {};

        timeline.forEach((item) => {
            const date = new Date(item.performed_at);
            const dateKey = new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            }).format(date);

            if (!grouped[dateKey]) {
                grouped[dateKey] = [];
            }
            grouped[dateKey].push(item);
        });

        return grouped;
    }

    return {
        getTimelineIcon,
        getTimelineColor,
        getTimelineBackgroundColor,
        formatTimelineDate,
        getActionDisplayName,
        formatTimelineMetadata,
        sortTimelineByDate,
        groupTimelineByDate,
    };
}
