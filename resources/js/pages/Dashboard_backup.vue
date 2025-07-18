<script setup lang="ts">
import EntryInfoModal from '@/components/EntryInfoModal.vue';
import PatientSearch from '@/components/PatientSearch.vue';
import StatusTransitionDropdown from '@/components/StatusTransitionDropdown.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { handleApiError, useEntryApi, usePatientApi } from '@/composables/useApi';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Entry, Patient } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, reactive, ref, watch } from 'vue';

interface Filters {
    date_from: string;
    date_to: string;
    patient_name: string;
    entry_id: string;
    limit: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Início', href: '/' },
    { title: 'Dashboard', href: '/dashboard' },
];

const entry = reactive({
    patient_id: '',
    title: '',
});

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

// Messages and loading states
const message = ref('');
const error = ref('');
const entryMessage = ref('');
const entryError = ref('');
const entryLoading = ref(false);

// Modal states

const isEntryModalOpen = ref(false);
const isFilterDialogOpen = ref(false);
const isEntryInfoModalOpen = ref(false);
const selectedEntry = ref<Entry | null>(null);

// Data
const patients = ref<Patient[]>([]);
const entries = ref<Entry[]>([]);
const limitOptions = [10, 25, 50, 100, 200];

// Patient search functionality
const selectedPatient = ref<Patient | null>(null);

const onPatientSelected = (patient: Patient) => {
    selectedPatient.value = patient;
    entry.patient_id = patient.id;
};

const onPatientCleared = () => {
    selectedPatient.value = null;
    entry.patient_id = '';
};

async function loadPatients() {
    const patientApi = usePatientApi();
    try {
        const response = await patientApi.getPatients({ limit: 1000 });
        patients.value = Array.isArray(response) ? response : response.data || [];
    } catch (err) {
        console.error('Erro ao carregar pacientes:', err);
        error.value = handleApiError(err);
    }
}

async function loadEntries() {
    const entryApi = useEntryApi();
    entryLoading.value = true;
    entryError.value = '';

    try {
        const params: any = {};
        if (filters.date_from) params.date_from = filters.date_from;
        if (filters.date_to) params.date_to = filters.date_to;
        if (filters.patient_name) params.patient_name = filters.patient_name;
        if (filters.entry_id) params.entry_id = filters.entry_id;
        if (filters.limit) params.limit = filters.limit;

        const response = await entryApi.getEntries(params);
        entries.value = Array.isArray(response) ? response : response.data || [];
    } catch (err) {
        console.error('Erro ao carregar entradas:', err);
        entryError.value = handleApiError(err);
    } finally {
        entryLoading.value = false;
    }
}

async function createEntry() {
    if (!entry.patient_id || !entry.title) {
        entryError.value = 'Por favor, selecione um paciente e informe o título.';
        return;
    }

    const entryApi = useEntryApi();
    entryLoading.value = true;
    entryError.value = '';
    entryMessage.value = '';

    try {
        await entryApi.createEntry(entry);
        entryMessage.value = 'Entrada criada com sucesso!';

        // Reset form
        Object.assign(entry, {
            patient_id: '',
            title: '',
        });
        onPatientCleared();

        isEntryModalOpen.value = false;
        loadEntries();
    } catch (err) {
        console.error(err);
        entryError.value = handleApiError(err);
    } finally {
        entryLoading.value = false;
    }
}

async function deleteEntry(id: string) {
    if (!confirm('Tem certeza que deseja excluir esta entrada? Esta ação não pode ser desfeita.')) {
        return;
    }

    const entryApi = useEntryApi();
    entryLoading.value = true;

    try {
        await entryApi.deleteEntry(id);
        entryMessage.value = 'Entrada excluída com sucesso';
        loadEntries();
    } catch (err) {
        console.error(err);
        entryError.value = handleApiError(err);
    } finally {
        entryLoading.value = false;
    }
}

function applyFilters() {
    Object.assign(filters, tempFilters);
    isFilterDialogOpen.value = false;
    loadEntries();
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
    loadEntries();
    isFilterDialogOpen.value = false;
}

function openFilterDialog() {
    Object.assign(tempFilters, filters);
    isFilterDialogOpen.value = true;
}

function hasActiveFilters(): boolean {
    return !!(filters.date_from || filters.date_to || filters.patient_name || filters.entry_id);
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

function showEntryInfo(entry: Entry): void {
    selectedEntry.value = entry;
    isEntryInfoModalOpen.value = true;
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

function onStatusChanged(updatedEntry: Entry) {
    // Recarregar todas as entradas para garantir que os dados estejam atualizados
    loadEntries();
    entryMessage.value = 'Status atualizado com sucesso!';
}

function onStatusError(errorMessage: string) {
    entryError.value = errorMessage;
    // Recarregar entradas mesmo em caso de erro para garantir estado consistente
    loadEntries();
}

// Clear messages after some time
const clearMessage = (messageRef: any) => {
    setTimeout(() => {
        messageRef.value = '';
    }, 5000);
};

// Initialize data
onMounted(() => {
    loadPatients();
    loadEntries();
});

// Clear messages automatically
watch([message, error, entryMessage, entryError], () => {
    if (message.value) clearMessage(message);
    if (error.value) clearMessage(error);
    if (entryMessage.value) clearMessage(entryMessage);
    if (entryError.value) clearMessage(entryError);
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Visão geral e acesso rápido às seções principais</p>
                </div>

                <div class="flex gap-2">
                    <!-- Create Entry Modal -->
                    <Dialog v-model:open="isEntryModalOpen">
                        <DialogTrigger as-child>
                            <Button>
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Adicionar Entrada
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Criar Nova Entrada</DialogTitle>
                            </DialogHeader>

                            <form @submit.prevent="createEntry" class="grid gap-4 py-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Paciente</label>
                                    <div class="mt-1">
                                        <PatientSearch
                                            v-model="entry.patient_id"
                                            @patient-selected="onPatientSelected"
                                            @patient-cleared="onPatientCleared"
                                            placeholder="Digite o nome ou email do paciente..."
                                            required
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                    <Input type="text" v-model="entry.title" placeholder="Digite o título da entrada" class="mt-1" required />
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="isEntryModalOpen = false"> Cancelar </Button>
                                    <Button type="submit" :disabled="entryLoading">
                                        <span v-if="entryLoading">Criando...</span>
                                        <span v-else>Criar Entrada</span>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
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
            <div
                v-if="entryMessage"
                class="rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700 dark:border-green-600 dark:bg-green-900 dark:text-green-200"
            >
                {{ entryMessage }}
            </div>
            <div
                v-if="entryError"
                class="rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700 dark:border-red-600 dark:bg-red-900 dark:text-red-200"
            >
                {{ entryError }}
            </div>

            <!-- Dashboard Overview Cards -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Active Entries Card -->
                <Card class="cursor-pointer transition-shadow hover:shadow-lg" @click="$inertia.visit('/entries/active')">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Entradas Ativas</CardTitle>
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ entries.filter((e) => !e.current_status || !['completed', 'cancelled'].includes(e.current_status.slug)).length }}
                        </div>
                        <p class="text-xs text-muted-foreground">Entradas não concluídas</p>
                        <div class="mt-4">
                            <Button variant="outline" class="w-full">
                                Ver Entradas Ativas
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Scheduled Entries Card -->
                <Card class="cursor-pointer transition-shadow hover:shadow-lg" @click="$inertia.visit('/entries/scheduled')">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Entradas Agendadas</CardTitle>
                        <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0l-.5 5m6.5-5l.5 5M12 9v12"
                            ></path>
                        </svg>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ entries.filter((e) => e.scheduled_exam_date).length }}</div>
                        <p class="text-xs text-muted-foreground">Com data agendada</p>
                        <div class="mt-4">
                            <Button variant="outline" class="w-full">
                                Ver Entradas Agendadas
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Completed Entries Card -->
                <Card class="cursor-pointer transition-shadow hover:shadow-lg" @click="$inertia.visit('/entries/completed')">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Entradas Concluídas</CardTitle>
                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ entries.filter((e) => e.current_status && ['completed', 'cancelled'].includes(e.current_status.slug)).length }}
                        </div>
                        <p class="text-xs text-muted-foreground">Finalizadas</p>
                        <div class="mt-4">
                            <Button variant="outline" class="w-full">
                                Ver Entradas Concluídas
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Entries Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span>Entradas Recentes</span>
                        <Button @click="loadEntries" variant="outline" size="sm" :disabled="entryLoading">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                            Atualizar
                        </Button>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="entryLoading" class="flex items-center justify-center py-12">
                        <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900 dark:border-white"></div>
                    </div>

                    <div v-else-if="entries.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nenhuma entrada encontrada</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Crie sua primeira entrada.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="entry in entries.slice(0, 5)"
                            :key="entry.id"
                            class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="showEntryInfo(entry)"
                                        class="font-mono text-xs text-blue-600 underline hover:text-blue-800 dark:text-blue-400"
                                    >
                                        {{ entry.id }}
                                    </button>
                                    <span class="text-sm font-medium">{{ entry.patient?.name || 'Paciente Desconhecido' }}</span>
                                    <span :class="['rounded-full px-2 py-1 text-xs', getStatusColorClass(entry)]">
                                        {{ getStatusText(entry) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ entry.title }}</p>
                                <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ formatDate(entry.created_at) }}</span>
                                    <span v-if="entry.scheduled_exam_date">Agendado: {{ formatDate(entry.scheduled_exam_date) }}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <StatusTransitionDropdown :entry="entry" @status-changed="onStatusChanged" @error="onStatusError" size="sm" />
                            </div>
                        </div>

                        <div v-if="entries.length > 5" class="border-t pt-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Mostrando 5 de {{ entries.length }} entradas.
                                <button @click="$inertia.visit('/entries/active')" class="text-blue-600 underline hover:text-blue-800">
                                    Ver todas
                                </button>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Entry Info Modal -->
        <EntryInfoModal v-model:open="isEntryInfoModalOpen" :entry="selectedEntry" />
    </AppLayout>
</template>
