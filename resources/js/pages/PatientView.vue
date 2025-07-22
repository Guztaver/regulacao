<script setup lang="ts">
import EntryInfoModal from '@/components/EntryInfoModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Entry, type Patient } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';

interface Props {
    patientId: string;
}

interface PatientViewEntry extends Entry {
    completed: boolean;
}

const props = defineProps<Props>();

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
    {
        title: t.patientDetails,
        href: `/patients/${props.patientId}`,
    },
];

const patient = ref<Patient | null>(null);
const entries = ref<PatientViewEntry[]>([]);
const summary = ref({
    total_entries: 0,
    active_entries: 0,
    completed_entries: 0,
});

const editingPatient = reactive({
    name: '',
    phone: '',
    sus_number: '',
});

const isEntryInfoModalOpen = ref(false);
const selectedEntry = ref<PatientViewEntry | null>(null);

// State management
const loading = ref(false);
const message = ref('');
const error = ref('');
const isEditModalOpen = ref(false);

// Load patient data
function loadPatient() {
    loading.value = true;
    error.value = '';

    axios
        .get(`/api/patients/${props.patientId}`)
        .then((response) => {
            patient.value = response.data.patient;
            summary.value = response.data.summary;
            entries.value = response.data.patient.entries || [];

            // Populate edit form
            if (patient.value) {
                editingPatient.name = patient.value.name;
                editingPatient.phone = patient.value.phone || '';
                editingPatient.sus_number = patient.value.sus_number || '';
            }
        })
        .catch((err) => {
            console.error('Error loading patient:', err);
            error.value = t.failedToLoadPatientInfo;
        })
        .finally(() => {
            loading.value = false;
        });
}

// Update patient
function updatePatient() {
    loading.value = true;
    error.value = '';
    message.value = '';

    axios
        .put(`/api/patients/${props.patientId}`, editingPatient)
        .then((response) => {
            message.value = response.data.message;
            patient.value = response.data.patient;
            isEditModalOpen.value = false;
        })
        .catch((err) => {
            console.error('Error updating patient:', err);
            if (err.response?.data?.errors) {
                const errors = Object.values(err.response.data.errors).flat();
                error.value = errors.join(', ');
            } else {
                error.value = t.failedToUpdatePatient;
            }
        })
        .finally(() => {
            loading.value = false;
        });
}

// Utility functions
function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function showEntryInfo(entry: PatientViewEntry): void {
    selectedEntry.value = entry;
    isEntryInfoModalOpen.value = true;
}

function getPatientCreatorName(patient: Patient | null): string {
    if (!patient) return 'Unknown';
    if (typeof patient.created_by === 'object' && patient.created_by?.name) {
        return patient.created_by.name;
    }
    return 'Unknown';
}

function formatSusNumber(susNumber: string | undefined): string {
    if (!susNumber) return t.notInformed;
    return susNumber.replace(/(\d{3})(\d{4})(\d{4})(\d{4})/, '$1 $2 $3 $4');
}

// Computed properties
// Computed properties for future use
// const activeEntries = computed(() => entries.value.filter((entry) => !entry.completed));
// const completedEntries = computed(() => entries.value.filter((entry) => entry.completed));

// Initialize
onMounted(() => {
    loadPatient();
});
</script>

<template>
    <Head :title="`Patient: ${patient?.name || 'Loading...'}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Loading State -->
            <div v-if="loading && !patient" class="flex items-center justify-center py-12">
                <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900 dark:border-white"></div>
            </div>

            <!-- Patient not found -->
            <div v-else-if="!loading && !patient" class="py-12 text-center text-gray-500 dark:text-gray-400">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ t.patientNotFound }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ t.patientNotFoundDesc }}</p>
                <Button as-child class="mt-4">
                    <Link href="/patients">{{ t.backToPatients }}</Link>
                </Button>
            </div>

            <!-- Patient Information -->
            <template v-else>
                <!-- Header Section -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ patient?.name }}</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ t.patientInfoAndEntries }}</p>
                    </div>

                    <div class="flex gap-2">
                        <!-- Edit Patient Modal -->
                        <Dialog v-model:open="isEditModalOpen">
                            <DialogTrigger as-child>
                                <Button variant="outline">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                    {{ t.editPatient }}
                                </Button>
                            </DialogTrigger>

                            <DialogContent class="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>{{ t.editPatient }}</DialogTitle>
                                </DialogHeader>

                                <form @submit.prevent="updatePatient" class="grid gap-4 py-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.name }} *</label>
                                        <Input type="text" v-model="editingPatient.name" class="mt-1" required />
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.phone }}</label>
                                        <Input type="text" v-model="editingPatient.phone" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.susNumber }}</label>
                                        <Input type="text" v-model="editingPatient.sus_number" class="mt-1" maxlength="15" />
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isEditModalOpen = false">{{ t.cancel }}</Button>
                                        <Button type="submit" :disabled="loading">
                                            <span v-if="loading">{{ t.loading }}</span>
                                            <span v-else>{{ t.updatePatient }}</span>
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

                <!-- Patient Overview -->
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Patient Info Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t.patientInformation }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t.name }}</p>
                                    <p class="text-lg font-medium">{{ patient?.name }}</p>
                                </div>

                                <div v-if="patient?.phone">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t.phone }}</p>
                                    <p>{{ patient.phone }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t.susNumber }}</p>
                                    <p class="font-mono">{{ formatSusNumber(patient?.sus_number) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t.created }}</p>
                                    <p>{{ formatDate(patient?.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t.addedBy }}</p>
                                    <p>{{ getPatientCreatorName(patient) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Statistics Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t.statistics }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Total {{ t.entries }}</span>
                                    <span class="font-medium">{{ summary.total_entries }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ t.activeEntries }}</span>
                                    <span class="font-medium text-blue-600">{{ summary.active_entries }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ t.completedEntries }}</span>
                                    <span class="font-medium text-green-600">{{ summary.completed_entries }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t.quickActions }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <Button as-child variant="outline" class="w-full justify-start">
                                    <Link href="/dashboard">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        {{ t.addEntry }}
                                    </Link>
                                </Button>
                                <Button as-child variant="outline" class="w-full justify-start">
                                    <Link href="/entries/completed">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        {{ t.viewCompleted }}
                                    </Link>
                                </Button>
                                <Button as-child variant="outline" class="w-full justify-start">
                                    <Link href="/patients">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                        </svg>
                                        {{ t.allPatients }}
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Entries Section -->
                <Card v-if="entries.length > 0">
                    <CardHeader>
                        <CardTitle>{{ t.recentEntries }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="entry in entries.slice(0, 5)"
                                :key="entry.id"
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-600"
                            >
                                <div class="flex-1" @click="showEntryInfo(entry)">
                                    <p
                                        class="text-sm font-medium text-blue-600 underline hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        {{ entry.title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(entry.created_at) }}</p>
                                </div>
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs',
                                        entry.completed
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    ]"
                                >
                                    {{ entry.completed ? t.completed : t.active }}
                                </span>
                            </div>
                            <div v-if="entries.length > 5" class="text-center">
                                <Button variant="outline" size="sm" as-child>
                                    <Link href="/dashboard">{{ t.viewAllEntries }}</Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>

        <!-- Entry Info Modal -->
        <EntryInfoModal v-model:open="isEntryInfoModalOpen" :entry="selectedEntry" />
    </AppLayout>
</template>
