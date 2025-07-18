<script setup lang="ts">
import EntryInfoModal from '@/components/EntryInfoModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Entry } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';

interface CompletedEntry extends Entry {
    completed: boolean;
}

interface Filters {
    date_from: string;
    date_to: string;
    patient_name: string;
    entry_id: string;
    limit: number;
}

const { t } = useTranslations();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t.dashboard,
        href: '/dashboard',
    },
    {
        title: t.completedEntries,
        href: '/entries/completed',
    },
];

const entries = ref<CompletedEntry[]>([]);
const loading = ref(false);
const message = ref('');
const error = ref('');
const isFilterDialogOpen = ref(false);
const isEntryInfoModalOpen = ref(false);
const selectedEntry = ref<CompletedEntry | null>(null);

const filters: Filters = reactive({
    date_from: '',
    date_to: '',
    patient_name: '',
    entry_id: '',
    limit: 10,
});

const tempFilters: Filters = reactive({
    date_from: '',
    date_to: '',
    patient_name: '',
    entry_id: '',
    limit: 10,
});

const limitOptions = [10, 25, 50, 100];

function loadCompletedEntries() {
    loading.value = true;
    error.value = '';
    message.value = '';

    const params = new URLSearchParams();
    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);
    if (filters.patient_name) params.append('patient_name', filters.patient_name);
    if (filters.entry_id) params.append('entry_id', filters.entry_id);
    if (filters.limit) params.append('limit', filters.limit.toString());
    params.append('completed', 'true');

    axios
        .get(`/api/entries/completed?${params.toString()}`)
        .then((response) => {
            entries.value = response.data.entries as CompletedEntry[];
        })
        .catch((err) => {
            console.error(err);
            error.value = 'Erro ao carregar entradas concluídas';
        })
        .finally(() => {
            loading.value = false;
        });
}

function applyFilters() {
    Object.assign(filters, tempFilters);
    isFilterDialogOpen.value = false;
    loadCompletedEntries();
}

function clearFilters() {
    Object.assign(tempFilters, {
        date_from: '',
        date_to: '',
        patient_name: '',
        entry_id: '',
        limit: 10,
    });
    Object.assign(filters, tempFilters);
    loadCompletedEntries();
}

function openFilterDialog() {
    Object.assign(tempFilters, filters);
    isFilterDialogOpen.value = true;
}

function uncompleteEntry(entryId: string) {
    loading.value = true;
    error.value = '';

    axios
        .patch(`/api/entries/${entryId}`, { completed: false })
        .then(() => {
            message.value = 'Entrada marcada como não concluída com sucesso!';
            loadCompletedEntries();
        })
        .catch((err) => {
            console.error(err);
            error.value = 'Erro ao alterar status da entrada';
        })
        .finally(() => {
            loading.value = false;
        });
}

function deleteEntry(entryId: string) {
    if (!confirm('Tem certeza que deseja excluir esta entrada?')) return;

    loading.value = true;
    error.value = '';

    axios
        .delete(`/api/entries/${entryId}`)
        .then(() => {
            message.value = 'Entrada excluída com sucesso!';
            loadCompletedEntries();
        })
        .catch((err) => {
            console.error(err);
            error.value = 'Erro ao excluir entrada';
        })
        .finally(() => {
            loading.value = false;
        });
}

function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function showEntryInfo(entry: CompletedEntry) {
    selectedEntry.value = entry;
    isEntryInfoModalOpen.value = true;
}

function getCreatorName(entry: CompletedEntry): string {
    if (typeof entry.created_by === 'object' && entry.created_by?.name) {
        return entry.created_by.name;
    }
    if (entry.createdBy?.name) {
        return entry.createdBy.name;
    }
    return t.unknown;
}

function hasActiveFilters(): boolean {
    return filters.date_from !== '' || filters.date_to !== '' || filters.patient_name !== '' || filters.entry_id !== '';
}

// Initialize data
onMounted(() => {
    loadCompletedEntries();
});
</script>

<template>
    <Head :title="t.completedEntries" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t.completedEntries }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Visualize e gerencie entradas de pacientes concluídas</p>
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
                                {{ t.filters }}
                                <span v-if="hasActiveFilters()" class="ml-1 h-2 w-2 rounded-full bg-blue-500"></span>
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>{{ t.filters }} {{ t.completedEntries }}</DialogTitle>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.dateFrom }}</label>
                                        <Input type="date" v-model="tempFilters.date_from" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.dateTo }}</label>
                                        <Input type="date" v-model="tempFilters.date_to" class="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.patientName }}</label>
                                    <Input type="text" v-model="tempFilters.patient_name" :placeholder="t.searchByPatientName" class="mt-1" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.entryId }}</label>
                                    <Input type="text" v-model="tempFilters.entry_id" :placeholder="t.enterSpecificEntryId" class="mt-1" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.limitResults }}</label>
                                    <Select v-model="tempFilters.limit" class="mt-1">
                                        <option v-for="option in limitOptions" :key="option" :value="option">{{ option }} {{ t.entries }}</option>
                                    </Select>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button variant="outline" @click="clearFilters">{{ t.clearAll }}</Button>
                                <Button @click="applyFilters">{{ t.applyFilters }}</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Button @click="loadCompletedEntries" variant="outline" :disabled="loading">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        {{ t.refresh }}
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
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ t.activeFilters }}</span>
                <span
                    v-if="filters.date_from"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    {{ t.from }} {{ filters.date_from }}
                </span>
                <span
                    v-if="filters.date_to"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    {{ t.to }} {{ filters.date_to }}
                </span>
                <span
                    v-if="filters.patient_name"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    {{ t.patient }}: {{ filters.patient_name }}
                </span>
                <span
                    v-if="filters.entry_id"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    {{ t.id }}: {{ filters.entry_id }}
                </span>
                <span
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ t.limit }}: {{ filters.limit }}
                </span>
            </div>

            <!-- Entries Table -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t.completedEntries }} ({{ entries.length }})</CardTitle>
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
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nenhuma entrada concluída encontrada</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajuste seus filtros para encontrar mais resultados.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        {{ t.id }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        {{ t.patient }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        {{ t.title }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Data de Conclusão
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        {{ t.addedBy }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        {{ t.actions }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-600 dark:bg-gray-800">
                                <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <button
                                            @click="showEntryInfo(entry)"
                                            class="cursor-pointer font-mono text-xs text-blue-600 underline hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            :title="t.entryDetails"
                                        >
                                            {{ entry.id }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ entry.patient?.name || t.unknownPatient }}</span>
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
                                        {{ formatDate(entry.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        {{ getCreatorName(entry) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" @click="uncompleteEntry(entry.id)" :disabled="loading">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                                                    />
                                                </svg>
                                                Reabrir
                                            </Button>

                                            <Button size="sm" variant="destructive" @click="deleteEntry(entry.id)" :disabled="loading">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                                {{ t.delete }}
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
