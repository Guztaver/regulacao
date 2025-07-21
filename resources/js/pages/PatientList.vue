<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Patient {
    id: string;
    name: string;
    phone: string;
    sus_number?: string;
    entries_count?: number;
    created_at?: string;
    created_by?: User;
}

interface Filters {
    search: string;
    limit: number;
}

const { t } = useTranslations();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t.dashboard,
        href: '/dashboard',
    },
    {
        title: t.patients,
        href: '/patients',
    },
];

const patient: Patient = reactive({
    id: '',
    name: '',
    phone: '',
    sus_number: '',
});

const filters: Filters = reactive({
    search: '',
    limit: 50,
});

const tempFilters: Filters = reactive({
    search: '',
    limit: 50,
});

// State management
const patients = ref<Patient[]>([]);
const loading = ref(false);
const message = ref('');
const error = ref('');
const isPatientModalOpen = ref(false);
const isFilterDialogOpen = ref(false);

const limitOptions = [10, 25, 50, 100];

// Patient functions
function createPatient() {
    loading.value = true;
    error.value = '';
    message.value = '';

    axios
        .post('/api/patients', patient)
        .then((response) => {
            message.value = response.data.message;
            patient.name = '';
            patient.phone = '';
            patient.sus_number = '';
            isPatientModalOpen.value = false;
            loadPatients();
        })
        .catch((err) => {
            console.error(err);
            if (err.response?.data?.errors) {
                const errors = Object.values(err.response.data.errors).flat();
                error.value = errors.join(', ');
            } else {
                error.value = 'Failed to create patient';
            }
        })
        .finally(() => {
            loading.value = false;
        });
}

function loadPatients() {
    loading.value = true;
    error.value = '';

    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    params.append('limit', filters.limit.toString());

    axios
        .get(`/api/patients?${params.toString()}`)
        .then((response) => {
            patients.value = response.data;
        })
        .catch((err) => {
            console.error('Error loading patients:', err);
            error.value = 'Failed to load patients';
        })
        .finally(() => {
            loading.value = false;
        });
}

function deletePatient(id: string) {
    if (!confirm('Are you sure you want to delete this patient? This action cannot be undone and will also delete all associated entries.')) {
        return;
    }

    loading.value = true;

    axios
        .delete(`/api/patients/${id}`)
        .then(() => {
            message.value = 'Patient deleted successfully';
            loadPatients();
        })
        .catch((err) => {
            console.error('Error deleting patient:', err);
            error.value = 'Failed to delete patient';
        })
        .finally(() => {
            loading.value = false;
        });
}

// Filter functions
function applyFilters() {
    Object.assign(filters, tempFilters);
    isFilterDialogOpen.value = false;
    loadPatients();
}

function clearFilters() {
    Object.assign(tempFilters, {
        search: '',
        limit: 50,
    });
    Object.assign(filters, tempFilters);
    loadPatients();
}

function openFilterDialog() {
    Object.assign(tempFilters, filters);
    isFilterDialogOpen.value = true;
}

function hasActiveFilters(): boolean {
    return !!filters.search;
}

function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatSusNumber(susNumber: string | undefined): string {
    if (!susNumber) return 'Não informado';
    // Format SUS number with spaces for readability
    return susNumber.replace(/(\d{3})(\d{4})(\d{4})(\d{4})/, '$1 $2 $3 $4');
}

// Initialize data
onMounted(() => {
    loadPatients();
});
</script>

<template>
    <Head :title="t.patients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t.patients }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Gerencie informações e registros de pacientes</p>
                </div>

                <div class="flex gap-2">
                    <!-- Create Patient Modal -->
                    <Dialog v-model:open="isPatientModalOpen">
                        <DialogTrigger as-child>
                            <Button>
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                {{ t.addPatient }}
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>{{ t.createNewPatient }}</DialogTitle>
                            </DialogHeader>

                            <form @submit.prevent="createPatient" class="grid gap-4 py-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.name }}</label>
                                    <Input type="text" v-model="patient.name" :placeholder="t.enterPatientName" class="mt-1" required />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.phone }}</label>
                                    <Input type="text" v-model="patient.phone" :placeholder="t.enterPhoneNumber" class="mt-1" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.susNumber }} (Opcional)</label>
                                    <Input type="text" v-model="patient.sus_number" :placeholder="t.enterSusNumber" class="mt-1" maxlength="15" />
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="isPatientModalOpen = false">{{ t.cancel }}</Button>
                                    <Button type="submit" :disabled="loading">
                                        <span v-if="loading">{{ t.creating }}</span>
                                        <span v-else>{{ t.create }} {{ t.patient }}</span>
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

            <!-- Search and Filter Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-md flex-1">
                    <div class="relative">
                        <Input
                            type="text"
                            v-model="filters.search"
                            placeholder="Search by name or SUS number..."
                            class="pl-10"
                            @input="loadPatients"
                        />
                        <svg
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
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
                                <DialogTitle>{{ t.filters }} {{ t.patients }}</DialogTitle>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.search }}</label>
                                    <Input type="text" v-model="tempFilters.search" :placeholder="t.searchPatients" class="mt-1" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.limitResults }}</label>
                                    <Select v-model="tempFilters.limit" class="mt-1">
                                        <option v-for="option in limitOptions" :key="option" :value="option">
                                            {{ option }} {{ t.patients.toLowerCase() }}
                                        </option>
                                    </Select>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button variant="outline" @click="clearFilters">{{ t.clearAll }}</Button>
                                <Button @click="applyFilters">{{ t.applyFilters }}</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Button @click="loadPatients" variant="outline" :disabled="loading">
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

            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ t.activeFilters }}</span>
                <span
                    v-if="filters.search"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                    {{ t.search }}: {{ filters.search }}
                </span>
                <span
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ t.limit }}: {{ filters.limit }}
                </span>
            </div>

            <!-- Patients Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Todos os {{ t.patients }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900 dark:border-white"></div>
                    </div>

                    <div v-else-if="patients.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ t.noPatientsList }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ t.adjustFilters }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comece criando seu primeiro paciente.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Informações do Paciente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        {{ t.susNumber }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Estatísticas
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                        Criado
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
                                <tr v-for="patient in patients" :key="patient.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ patient.name }}</span>
                                            <span v-if="patient.phone" class="text-xs text-gray-400">{{ patient.phone }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <span v-if="patient.sus_number" class="font-mono text-sm">{{ formatSusNumber(patient.sus_number) }}</span>
                                        <span v-else class="text-gray-500 italic">Não informado</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col text-xs">
                                            <span>{{ patient.entries_count || 0 }} {{ t.entries.toLowerCase() }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        {{ formatDate(patient.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                        {{ patient.created_by?.name || t.unknown }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" as-child>
                                                <Link :href="`/patients/${patient.id}`">
                                                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                        />
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                        />
                                                    </svg>
                                                    {{ t.view }}
                                                </Link>
                                            </Button>

                                            <Button size="sm" variant="destructive" @click="deletePatient(patient.id)" :disabled="loading">
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
    </AppLayout>
</template>
