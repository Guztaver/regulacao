<script setup lang="ts">
import EntryInfoModal from '@/components/EntryInfoModal.vue';
import PatientSearch from '@/components/PatientSearch.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import StatusTransitionDropdown from '@/components/StatusTransitionDropdown.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { handleApiError, useEntryApi, usePatientApi } from '@/composables/useApi';
import { useDockerRegistry } from '@/composables/useDockerRegistry';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Entry, EntryStatus, Patient } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    BarChart3,
    Calendar,
    CalendarDays,
    CheckCircle,
    ChevronRight,
    ClipboardList,
    Clock,
    FileText,
    PieChart,
    Plus,
    RefreshCw,
    Stethoscope,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';

interface DashboardStats {
    totalEntries: number;
    activeEntries: number;
    scheduledEntries: number;
    completedEntries: number;
    totalPatients: number;
    todayEntries: number;
    weekEntries: number;
    monthEntries: number;
    avgProcessingTime: number;
    statusDistribution: Array<{ status: string; count: number; color: string }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Início', href: '/' },
    { title: 'Dashboard', href: '/dashboard' },
];

const entry = reactive({
    patient_id: '',
    title: '',
    brought_by: '',
});

// State management
const stats = ref<DashboardStats>({
    totalEntries: 0,
    activeEntries: 0,
    scheduledEntries: 0,
    completedEntries: 0,
    totalPatients: 0,
    todayEntries: 0,
    weekEntries: 0,
    monthEntries: 0,
    avgProcessingTime: 0,
    statusDistribution: [],
});

const recentEntries = ref<Entry[]>([]);

const scheduledExams = ref<Entry[]>([]);
const patients = ref<Patient[]>([]);
const statuses = ref<EntryStatus[]>([]);

// Loading states
const statsLoading = ref(true);
const entriesLoading = ref(true);

const patientsLoading = ref(true);

// Modal states
const isEntryModalOpen = ref(false);
const isEntryInfoModalOpen = ref(false);
const selectedEntry = ref<Entry | null>(null);

// Messages
const message = ref('');
const error = ref('');
const entryMessage = ref('');
const entryError = ref('');
const entryLoading = ref(false);

// Patient search functionality
const selectedPatient = ref<Patient | null>(null);

// Docker registry functionality
const { imageInfo, loading: dockerLoading, fetchGhcrImageInfo, formatDate: formatDockerDate } = useDockerRegistry();

const onPatientSelected = (patient: Patient) => {
    selectedPatient.value = patient;
    entry.patient_id = patient.id;
};

const onPatientCleared = () => {
    selectedPatient.value = null;
    entry.patient_id = '';
};

// Computed properties
const statsCards = computed(() => [
    {
        title: 'Total de Entradas',
        value: stats.value.totalEntries,
        icon: ClipboardList,
        color: 'text-blue-600',
        bgColor: 'bg-blue-50',
        description: 'Todas as entradas no sistema',
    },
    {
        title: 'Entradas Ativas',
        value: stats.value.activeEntries,
        icon: Activity,
        color: 'text-green-600',
        bgColor: 'bg-green-50',
        description: 'Entradas em andamento',
    },
    {
        title: 'Exames Agendados',
        value: stats.value.scheduledEntries,
        icon: CalendarDays,
        color: 'text-yellow-600',
        bgColor: 'bg-yellow-50',
        description: 'Exames marcados para hoje',
    },
    {
        title: 'Entradas Concluídas',
        value: stats.value.completedEntries,
        icon: CheckCircle,
        color: 'text-purple-600',
        bgColor: 'bg-purple-50',
        description: 'Processadas com sucesso',
    },
    {
        title: 'Total de Pacientes',
        value: stats.value.totalPatients,
        icon: Users,
        color: 'text-indigo-600',
        bgColor: 'bg-indigo-50',
        description: 'Pacientes cadastrados',
    },
    {
        title: 'Entradas Hoje',
        value: stats.value.todayEntries,
        icon: TrendingUp,
        color: 'text-orange-600',
        bgColor: 'bg-orange-50',
        description: 'Criadas nas últimas 24h',
    },
]);

const quickActions = computed(() => [
    {
        title: 'Nova Entrada',
        description: 'Criar entrada para paciente',
        icon: Plus,
        color: 'text-blue-600',
        bgColor: 'bg-blue-50',
        action: () => (isEntryModalOpen.value = true),
    },
    {
        title: 'Pacientes',
        description: 'Gerenciar pacientes',
        icon: Users,
        color: 'text-green-600',
        bgColor: 'bg-green-50',
        action: () => router.visit('/patients'),
    },
    {
        title: 'Entradas Ativas',
        description: 'Ver todas as entradas ativas',
        icon: Activity,
        color: 'text-purple-600',
        bgColor: 'bg-purple-50',
        action: () => router.visit('/entries/active'),
    },
    {
        title: 'Exames Agendados',
        description: 'Verificar agenda de exames',
        icon: Calendar,
        color: 'text-yellow-600',
        bgColor: 'bg-yellow-50',
        action: () => router.visit('/entries/scheduled'),
    },
]);

// Data loading functions
async function loadStats() {
    try {
        statsLoading.value = true;
        const entryApi = useEntryApi();
        const patientApi = usePatientApi();

        // Load basic stats
        const [allEntries, activeEntries, scheduledEntries, completedEntries, allPatients, statusesData] = await Promise.all([
            entryApi.getEntries({ limit: 1000 }),
            entryApi.getActiveEntries({ limit: 1000 }),
            entryApi.getScheduledEntries({ limit: 1000 }),
            entryApi.getCompletedEntries({ limit: 1000 }),
            patientApi.getPatients({ limit: 1000 }),
            entryApi.getStatuses(),
        ]);

        const allEntriesData = Array.isArray(allEntries) ? allEntries : allEntries.data || [];
        const activeEntriesData = Array.isArray(activeEntries) ? activeEntries : activeEntries.data || [];
        const scheduledEntriesData = Array.isArray(scheduledEntries) ? scheduledEntries : scheduledEntries.data || [];
        const completedEntriesData = Array.isArray(completedEntries) ? completedEntries : completedEntries.data || [];
        const patientsData = Array.isArray(allPatients) ? allPatients : allPatients.data || [];
        const statusesData2 = Array.isArray(statusesData) ? statusesData : statusesData.data || [];

        statuses.value = statusesData2;

        // Calculate today's entries
        const today = new Date();
        const todayString = today.toISOString().split('T')[0];
        const todayEntries = allEntriesData.filter((entry: Entry) => entry.created_at && entry.created_at.startsWith(todayString));

        // Calculate status distribution
        const statusCounts = new Map();
        allEntriesData.forEach((entry: Entry) => {
            const status = entry.current_status?.name || 'Pendente';
            const color = entry.current_status?.color || '#6B7280';
            statusCounts.set(status, {
                count: (statusCounts.get(status)?.count || 0) + 1,
                color: color,
            });
        });

        const statusDistribution = Array.from(statusCounts.entries()).map(([status, data]) => ({
            status,
            count: data.count,
            color: data.color,
        }));

        stats.value = {
            totalEntries: allEntriesData.length,
            activeEntries: activeEntriesData.length,
            scheduledEntries: scheduledEntriesData.length,
            completedEntries: completedEntriesData.length,
            totalPatients: patientsData.length,
            todayEntries: todayEntries.length,
            weekEntries: 0, // TODO: Calculate
            monthEntries: 0, // TODO: Calculate
            avgProcessingTime: 0, // TODO: Calculate
            statusDistribution,
        };
    } catch (err) {
        console.error('Erro ao carregar estatísticas:', err);
        error.value = handleApiError(err);
    } finally {
        statsLoading.value = false;
    }
}

async function loadRecentEntries() {
    try {
        entriesLoading.value = true;
        const entryApi = useEntryApi();
        const response = await entryApi.getEntries({ limit: 5 });
        recentEntries.value = Array.isArray(response) ? response : response.data || [];
    } catch (err) {
        console.error('Erro ao carregar entradas recentes:', err);
        error.value = handleApiError(err);
    } finally {
        entriesLoading.value = false;
    }
}

async function loadScheduledExams() {
    try {
        const entryApi = useEntryApi();
        const response = await entryApi.getScheduledEntries({ limit: 5 });
        scheduledExams.value = Array.isArray(response) ? response : response.data || [];
    } catch (err) {
        console.error('Erro ao carregar exames agendados:', err);
    }
}

async function loadPatients() {
    try {
        patientsLoading.value = true;
        const patientApi = usePatientApi();
        const response = await patientApi.getPatients({ limit: 100 });
        patients.value = Array.isArray(response) ? response : response.data || [];
    } catch (err) {
        console.error('Erro ao carregar pacientes:', err);
        error.value = handleApiError(err);
    } finally {
        patientsLoading.value = false;
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
            brought_by: '',
        });
        onPatientCleared();

        isEntryModalOpen.value = false;

        // Refresh data
        await Promise.all([loadStats(), loadRecentEntries(), loadScheduledExams()]);
    } catch (err) {
        console.error(err);
        entryError.value = handleApiError(err);
    } finally {
        entryLoading.value = false;
    }
}

function showEntryInfo(entry: Entry): void {
    selectedEntry.value = entry;
    isEntryInfoModalOpen.value = true;
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

function onStatusChanged() {
    // Refresh data after status change
    Promise.all([loadStats(), loadRecentEntries(), loadScheduledExams()]);
    entryMessage.value = 'Status atualizado com sucesso!';
}

function onStatusError(errorMessage: string) {
    entryError.value = errorMessage;
    // Refresh data even on error to ensure consistency
    Promise.all([loadStats(), loadRecentEntries(), loadScheduledExams()]);
}

async function refreshAllData() {
    await Promise.all([loadStats(), loadRecentEntries(), loadScheduledExams(), loadPatients()]);
}

// Clear messages after some time
const clearMessage = (messageRef: any) => {
    setTimeout(() => {
        messageRef.value = '';
    }, 5000);
};

// Initialize data
onMounted(async () => {
    await refreshAllData();
    // Fetch Docker image info
    fetchGhcrImageInfo('guztaver', 'regulacao');
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
        <TooltipProvider>
            <div class="flex h-full flex-1 flex-col gap-8 p-6">
                <!-- Header Section -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">Visão geral completa da lista da regulação</p>
                    </div>

                    <div class="flex gap-3">
                        <Button @click="refreshAllData" variant="outline" :disabled="statsLoading">
                            <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': statsLoading }" />
                            Atualizar
                        </Button>

                        <Dialog v-model:open="isEntryModalOpen">
                            <DialogTrigger as-child>
                                <Button>
                                    <Plus class="mr-2 h-4 w-4" />
                                    Nova Entrada
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
                                                placeholder="Digite o nome do paciente..."
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                        <Input type="text" v-model="entry.title" placeholder="Digite o título da entrada" class="mt-1" required />
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Trazido por</label>
                                        <Input type="text" v-model="entry.brought_by" placeholder="Ex: Responsável, Agente SUS, etc." class="mt-1" />
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
                    class="rounded-lg border border-green-400 bg-green-50 px-4 py-3 text-green-800 dark:border-green-600 dark:bg-green-900/20 dark:text-green-200"
                >
                    {{ message }}
                </div>
                <div
                    v-if="error"
                    class="rounded-lg border border-red-400 bg-red-50 px-4 py-3 text-red-800 dark:border-red-600 dark:bg-red-900/20 dark:text-red-200"
                >
                    {{ error }}
                </div>
                <div
                    v-if="entryMessage"
                    class="rounded-lg border border-green-400 bg-green-50 px-4 py-3 text-green-800 dark:border-green-600 dark:bg-green-900/20 dark:text-green-200"
                >
                    {{ entryMessage }}
                </div>
                <div
                    v-if="entryError"
                    class="rounded-lg border border-red-400 bg-red-50 px-4 py-3 text-red-800 dark:border-red-600 dark:bg-red-900/20 dark:text-red-200"
                >
                    {{ entryError }}
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <Card v-for="stat in statsCards" :key="stat.title" class="transition-all hover:shadow-lg">
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.title }}</p>
                                    <div class="flex items-center gap-2">
                                        <span v-if="statsLoading" class="text-2xl font-bold text-gray-400">
                                            <Skeleton class="h-8 w-16" />
                                        </span>
                                        <span v-else class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ stat.value?.toLocaleString() }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ stat.description }}</p>
                                </div>
                                <div :class="[stat.bgColor, 'rounded-full p-3']">
                                    <component :is="stat.icon" :class="[stat.color, 'h-6 w-6']" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                    <!-- Left Column -->
                    <div class="space-y-8 lg:col-span-8">
                        <!-- Recent Entries -->
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Activity class="h-5 w-5" />
                                        Entradas Recentes
                                    </CardTitle>
                                    <CardDescription>Últimas entradas criadas no sistema</CardDescription>
                                </div>
                                <Button variant="outline" size="sm" @click="() => router.visit('/entries/active')">
                                    Ver Todas
                                    <ChevronRight class="ml-2 h-4 w-4" />
                                </Button>
                            </CardHeader>
                            <CardContent>
                                <div v-if="entriesLoading" class="space-y-4">
                                    <div v-for="i in 3" :key="i" class="flex items-center gap-4">
                                        <Skeleton class="h-12 w-12 rounded-full" />
                                        <div class="flex-1 space-y-2">
                                            <Skeleton class="h-4 w-3/4" />
                                            <Skeleton class="h-3 w-1/2" />
                                        </div>
                                    </div>
                                </div>

                                <div v-else-if="recentEntries.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <ClipboardList class="mx-auto h-12 w-12 text-gray-400" />
                                    <p class="mt-2">Nenhuma entrada encontrada</p>
                                </div>

                                <div v-else class="space-y-4">
                                    <div
                                        v-for="entry in recentEntries"
                                        :key="entry.id"
                                        class="flex items-center gap-4 rounded-lg border border-gray-200 p-4 transition-all hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                    >
                                        <Avatar class="h-12 w-12">
                                            <AvatarImage :src="''" />
                                            <AvatarFallback class="bg-blue-100 text-blue-600">
                                                {{ entry.patient?.name?.charAt(0) || 'P' }}
                                            </AvatarFallback>
                                        </Avatar>

                                        <div class="flex-1 space-y-1">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @click="showEntryInfo(entry)"
                                                    class="font-medium text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400"
                                                >
                                                    {{ entry.title }}
                                                </button>
                                                <StatusBadge v-if="entry.current_status" :entry="entry" />
                                            </div>
                                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <Users class="h-4 w-4" />
                                                    {{ entry.patient?.name || 'Paciente Desconhecido' }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <Clock class="h-4 w-4" />
                                                    {{ formatDate(entry.created_at) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <StatusTransitionDropdown
                                                :entry="entry"
                                                @status-changed="onStatusChanged"
                                                @error="onStatusError"
                                                size="sm"
                                            />
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button size="sm" variant="ghost" @click="showEntryInfo(entry)">
                                                        <FileText class="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Ver detalhes</TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Status Distribution -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <PieChart class="h-5 w-5" />
                                    Distribuição por Status
                                </CardTitle>
                                <CardDescription>Visualização do status das entradas</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="statsLoading" class="space-y-4">
                                    <div v-for="i in 4" :key="i" class="flex items-center gap-4">
                                        <Skeleton class="h-4 w-4 rounded" />
                                        <Skeleton class="h-4 flex-1" />
                                        <Skeleton class="h-4 w-12" />
                                    </div>
                                </div>

                                <div v-else-if="stats.statusDistribution.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <BarChart3 class="mx-auto h-12 w-12 text-gray-400" />
                                    <p class="mt-2">Nenhum dado disponível</p>
                                </div>

                                <div v-else class="space-y-4">
                                    <div v-for="item in stats.statusDistribution" :key="item.status" class="flex items-center gap-4">
                                        <div class="h-4 w-4 rounded" :style="{ backgroundColor: item.color }"></div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ item.status }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ item.count }}
                                                </span>
                                            </div>
                                            <div class="mt-1 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                                <div
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :style="{
                                                        width: `${(item.count / stats.totalEntries) * 100}%`,
                                                        backgroundColor: item.color,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-8 lg:col-span-4">
                        <!-- Quick Actions -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <TrendingUp class="h-5 w-5" />
                                    Ações Rápidas
                                </CardTitle>
                                <CardDescription>Acesso rápido às principais funcionalidades</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3">
                                    <button
                                        v-for="action in quickActions"
                                        :key="action.title"
                                        @click="action.action"
                                        class="flex w-full cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-4 text-left transition-all hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                    >
                                        <div :class="[action.bgColor, 'rounded-lg p-2']">
                                            <component :is="action.icon" :class="[action.color, 'h-5 w-5']" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ action.title }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ action.description }}</p>
                                        </div>
                                        <ChevronRight class="h-4 w-4 text-gray-400" />
                                    </button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Scheduled Exams -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Calendar class="h-5 w-5" />
                                    Exames Agendados
                                </CardTitle>
                                <CardDescription>Próximos exames programados</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="scheduledExams.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <CalendarDays class="mx-auto h-12 w-12 text-gray-400" />
                                    <p class="mt-2">Nenhum exame agendado</p>
                                </div>

                                <div v-else class="space-y-4">
                                    <div
                                        v-for="exam in scheduledExams"
                                        :key="exam.id"
                                        class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                                    >
                                        <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                                            <Stethoscope class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ exam.patient?.name || 'Paciente Desconhecido' }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatDate(exam.scheduled_exam_date) }}
                                            </p>
                                        </div>
                                        <Button size="sm" variant="outline" @click="showEntryInfo(exam)">
                                            <FileText class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- System Status -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Activity class="h-5 w-5" />
                                    Status do Sistema
                                </CardTitle>
                                <CardDescription>Indicadores de saúde do sistema</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500" />
                                            <span class="text-sm text-gray-700 dark:text-gray-300">API Status</span>
                                        </div>
                                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Online</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500" />
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Banco de Dados</span>
                                        </div>
                                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Conectado</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <AlertCircle class="h-4 w-4 text-yellow-500" />
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Última Atualização</span>
                                        </div>
                                        <span v-if="dockerLoading" class="text-sm font-medium text-gray-600 dark:text-gray-400"> Carregando... </span>
                                        <span v-else-if="imageInfo" class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            {{ formatDockerDate(imageInfo.lastUpdated) }}
                                        </span>
                                        <span v-else class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            {{ formatDate(new Date().toISOString()) }}
                                        </span>
                                    </div>
                                    <div v-if="imageInfo" class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <FileText class="h-4 w-4 text-blue-500" />
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Versão</span>
                                        </div>
                                        <Tooltip>
                                            <TooltipTrigger>
                                                <span class="cursor-help text-sm font-medium text-gray-600 dark:text-gray-400">
                                                    {{ imageInfo.version }}
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <div class="text-xs">
                                                    <div>Imagem: ghcr.io/guztaver/regulacao</div>
                                                    <div>Digest: {{ imageInfo.digest }}</div>
                                                </div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Entry Info Modal -->
            <EntryInfoModal v-model:open="isEntryInfoModalOpen" :entry="selectedEntry" />
        </TooltipProvider>
    </AppLayout>
</template>

<style scoped>
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
