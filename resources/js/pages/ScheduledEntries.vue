<script setup lang="ts">
import EntryInfoModal from '@/components/EntryInfoModal.vue';
import StatusTransitionDropdown from '@/components/StatusTransitionDropdown.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { handleApiError, useEntryApi } from '@/composables/useApi';
// import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Entry } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, reactive, ref, watch } from 'vue';

interface Filters {
    date_from: string;
    date_to: string;
    patient_name: string;
    entry_id: string;
    limit: number;
}

// const { t } = useTranslations();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Início', href: '/' },
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Entradas Agendadas', href: '/entries/scheduled' },
];

const entries = ref<Entry[]>([]);
const loading = ref(false);
const message = ref('');
const error = ref('');
const isFilterDialogOpen = ref(false);
const isEntryInfoModalOpen = ref(false);
const selectedEntry = ref<Entry | null>(null);

const filters = reactive<Filters>({
    date_from: '',
    date_to: '',
    patient_name: '',
    entry_id: '',
    limit: 50,
});

const tempFilters = reactive<Filters>({
    date_from: '',
    date_to: '',
    patient_name: '',
    entry_id: '',
    limit: 50,
});

const limitOptions = [10, 25, 50, 100, 200];

async function loadScheduledEntries() {
    const entryApi = useEntryApi();
    loading.value = true;
    error.value = '';

    try {
        const params: any = {};

        if (filters.date_from) params.date_from = filters.date_from;
        if (filters.date_to) params.date_to = filters.date_to;
        if (filters.patient_name) params.patient_name = filters.patient_name;
        if (filters.entry_id) params.entry_id = filters.entry_id;
        if (filters.limit) params.limit = filters.limit;

        const response = await entryApi.getScheduledEntries(params);
        entries.value = Array.isArray(response) ? response : response.data || [];
    } catch (err) {
        console.error('Erro ao carregar entradas agendadas:', err);
        error.value = handleApiError(err);
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    Object.assign(filters, tempFilters);
    isFilterDialogOpen.value = false;
    loadScheduledEntries();
}

function clearFilters() {
    Object.assign(tempFilters, {
        date_from: '',
        date_to: '',
        patient_name: '',
        entry_id: '',
        limit: 50,
    });
    Object.assign(filters, tempFilters);
    loadScheduledEntries();
    isFilterDialogOpen.value = false;
}

function openFilterDialog() {
    Object.assign(tempFilters, filters);
    isFilterDialogOpen.value = true;
}

async function deleteEntry(id: string) {
    if (!confirm('Tem certeza que deseja excluir esta entrada? Esta ação não pode ser desfeita.')) {
        return;
    }

    const entryApi = useEntryApi();
    loading.value = true;

    try {
        await entryApi.deleteEntry(id);
        message.value = 'Entrada excluída com sucesso';
        loadScheduledEntries();
    } catch (err) {
        console.error(err);
        error.value = handleApiError(err);
    } finally {
        loading.value = false;
    }
}

function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatScheduledDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function showEntryInfo(entry: Entry): void {
    selectedEntry.value = entry;
    isEntryInfoModalOpen.value = true;
}

function hasActiveFilters(): boolean {
    return !!(filters.date_from || filters.date_to || filters.patient_name || filters.entry_id);
}

function getStatusText(entry: Entry): string {
    if (entry.current_status) {
        return entry.current_status.name;
    }

    // Fallback para sistema legado
    if (entry.completed) return 'Concluído';
    if (entry.exam_ready) return 'Pronto';
    if (entry.exam_scheduled) return 'Agendado';
    return 'Pendente';
}

function getStatusColorClass(entry: Entry): string {
    if (entry.current_status) {
        const color = entry.current_status.color.toLowerCase();
        switch (color) {
            case '#10b981': // Verde
                return 'text-green-600 dark:text-green-400';
            case '#3b82f6': // Azul
                return 'text-blue-600 dark:text-blue-400';
            case '#8b5cf6': // Roxo
                return 'text-purple-600 dark:text-purple-400';
            case '#f59e0b': // Âmbar
                return 'text-yellow-600 dark:text-yellow-400';
            case '#ef4444': // Vermelho
                return 'text-red-600 dark:text-red-400';
            default:
                return 'text-gray-500 dark:text-gray-400';
        }
    }

    // Fallback para sistema legado
    if (entry.completed) return 'text-green-600 dark:text-green-400';
    if (entry.exam_ready) return 'text-blue-600 dark:text-blue-400';
    if (entry.exam_scheduled) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-gray-500 dark:text-gray-400';
}

function getDaysUntilScheduled(dateString: string | undefined): string {
    if (!dateString) return '';

    const now = new Date();
    const scheduledDate = new Date(dateString);
    const diffTime = scheduledDate.getTime() - now.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return `${Math.abs(diffDays)} dias atrás`;
    } else if (diffDays === 0) {
        return 'Hoje';
    } else if (diffDays === 1) {
        return 'Amanhã';
    } else {
        return `Em ${diffDays} dias`;
    }
}

function getSchedulePriorityClass(dateString: string | undefined): string {
    if (!dateString) return '';

    const now = new Date();
    const scheduledDate = new Date(dateString);
    const diffTime = scheduledDate.getTime() - now.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return 'text-red-600 dark:text-red-400 font-semibold'; // Overdue
    } else if (diffDays === 0) {
        return 'text-orange-600 dark:text-orange-400 font-semibold'; // Today
    } else if (diffDays <= 3) {
        return 'text-yellow-600 dark:text-yellow-400 font-medium'; // Soon
    } else {
        return 'text-gray-600 dark:text-gray-400'; // Future
    }
}

function onStatusChanged() {
    loadScheduledEntries();
    message.value = 'Status atualizado com sucesso!';
}

function onStatusError(errorMessage: string) {
    error.value = errorMessage;
    loadScheduledEntries();
}

// Clear messages after some time
const clearMessage = (messageRef: any) => {
    setTimeout(() => {
        messageRef.value = '';
    }, 5000);
};

// Initialize data
onMounted(() => {
    loadScheduledEntries();
});

// Clear messages automatically
watch([message, error], () => {
    if (message.value) clearMessage(message);
    if (error.value) clearMessage(error);
});
</script>

<template>
    <Head title="Entradas Agendadas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Entradas Agendadas</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Entradas que possuem data de agendamento ({{ entries.length }} entradas)</p>
                </div>

                <div class="flex gap-2">
                    <Dialog v-model:open="isFilterDialogOpen">
                        <DialogTrigger as-child>
                            <Button @click="openFilterDialog" variant="outline">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"
                                    />
                                </svg>
                                Filtros
                                <span v-if="hasActiveFilters()" class="ml-1 h-2 w-2 rounded-full bg-blue-500"></span>
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Filtrar Entradas Agendadas</DialogTitle>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Data Inicial</label>
                                        <Input type="date" v-model="tempFilters.date_from" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Data Final</label>
                                        <Input type="date" v-model="tempFilters.date_to" class="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Paciente</label>
                                    <Input type="text" v-model="tempFilters.patient_name" placeholder="Buscar por nome do paciente..." class="mt-1" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">ID da Entrada</label>
                                    <Input type="text" v-model="tempFilters.entry_id" placeholder="Digite o ID específico..." class="mt-1" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Limite de Resultados</label>
                                    <Select v-model="tempFilters.limit" class="mt-1">
                                        <option v-for="option in limitOptions" :key="option" :value="option">{{ option }} entradas</option>
                                    </Select>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button variant="outline" @click="clearFilters"> Limpar Tudo </Button>
                                <Button @click="applyFilters"> Aplicar Filtros </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Button @click="loadScheduledEntries" variant="outline" :disabled="loading">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Atualizar
                    </Button>
                </div>
            </div>

            <!-- Messages -->
            <div
                v-if="message"
                class="rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700 dark:border-green-600 dark:bg-green-900 dark:text-green-200"
            >
                {{ message }}
            </div>
            <div
                v-if="error"
                class="rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700 dark:border-red-600 dark:bg-red-900 dark:text-red-200"
            >
                {{ error }}
            </div>

            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Filtros ativos:</span>
                <span
                    v-if="filters.date_from"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    De: {{ filters.date_from }}
                </span>
                <span
                    v-if="filters.date_to"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    Até: {{ filters.date_to }}
                </span>
                <span
                    v-if="filters.patient_name"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    Paciente: {{ filters.patient_name }}
                </span>
                <span
                    v-if="filters.entry_id"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    ID: {{ filters.entry_id }}
                </span>
                <span
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                >
                    Limite: {{ filters.limit }}
                </span>
            </div>

            <!-- Entries Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Entradas Agendadas</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900 dark:border-white"></div>
                    </div>

                    <div v-else-if="entries.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0l-.5 5m6.5-5l.5 5M12 9v12"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nenhuma entrada agendada encontrada</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Não há entradas com data de agendamento ou ajuste seus filtros.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Paciente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Título
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Data Agendada
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Prazo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-600 dark:bg-gray-800">
                                <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <button
                                            @click="showEntryInfo(entry)"
                                            class="cursor-pointer font-mono text-xs text-blue-600 underline hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            :title="'Clique para ver detalhes da entrada'"
                                        >
                                            {{ entry.id }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ entry.patient?.name || 'Paciente Desconhecido' }}</span>
                                            <span v-if="entry.patient?.email" class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ entry.patient.email }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        <p class="max-w-xs truncate text-left">
                                            {{ entry.title }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ formatScheduledDate(entry.scheduled_exam_date) }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Criado em {{ formatDate(entry.created_at) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <span :class="['text-xs font-medium', getSchedulePriorityClass(entry.scheduled_exam_date)]">
                                            {{ getDaysUntilScheduled(entry.scheduled_exam_date) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <span :class="['text-xs font-medium', getStatusColorClass(entry)]">
                                            {{ getStatusText(entry) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <div class="flex gap-2">
                                            <!-- Status Transition Dropdown -->
                                            <StatusTransitionDropdown
                                                :entry="entry"
                                                @status-changed="onStatusChanged"
                                                @error="onStatusError"
                                                size="sm"
                                            />

                                            <!-- Delete Button -->
                                            <Button size="sm" variant="destructive" @click="deleteEntry(entry.id)" :disabled="loading">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                                Excluir
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Entry Info Modal -->
        <EntryInfoModal v-model:open="isEntryInfoModalOpen" :entry="selectedEntry" />
    </AppLayout>
</template>
