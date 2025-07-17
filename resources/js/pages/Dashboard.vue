<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { reactive, ref } from 'vue';

interface Patient {
    id: string;
    name: string;
    email: string;
    phone: string;
}

interface Entry {
    id: string;
    patient_id: string;
    title: string;
    patient?: Patient;
    created_at?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const patient: Patient = reactive({
    id: '',
    name: '',
    email: '',
    phone: '',
});

const message = ref('');
const error = ref('');
const loading = ref(false);

const entryMessage = ref('');
const entryError = ref('');
const entryLoading = ref(false);

const patients = ref<Patient[]>([]);
const entry = reactive({
    patient_id: '',
    title: '',
});

function createPatient() {
    loading.value = true;
    error.value = '';
    message.value = '';

    axios
        .post('/api/patients', patient)
        .then((response) => {
            message.value = response.data.message;
            patient.name = '';
            patient.email = '';
            patient.phone = '';
        })
        .catch((err) => console.error(err))
        .finally(() => {
            loading.value = false;
        });
}

const entries: Entry[] = reactive([]);

function loadPatients() {
    axios
        .get('/api/patients')
        .then((response) => {
            patients.value = response.data;
        })
        .catch((err) => {
            console.error('Erro ao carregar pacientes:', err);
        });
}

function loadEntries() {
    axios
        .get('/api/entries')
        .then((response) => {
            entries.splice(0, entries.length, ...response.data);
        })
        .catch((err) => {
            console.error('Erro ao carregar entries:', err);
        });
}

function createEntry() {
    entryLoading.value = true;
    entryError.value = '';
    entryMessage.value = '';

    axios
        .post('/api/entries', entry)
        .then((response) => {
            entryMessage.value = response.data.message;
            entry.patient_id = '';
            entry.title = '';
            loadEntries();
        })
        .catch((err) => console.error(err))
        .finally(() => {
            entryLoading.value = false;
        });
}

// Load data on component mount
loadPatients();
loadEntries();

function completeEntry(id: string) {
    axios
        .put(`/api/entries/${id}/complete`)
        .then((response) => {
            entryMessage.value = response.data.message;
            loadEntries();
        })
        .catch((err) => console.error(err))
        .finally(() => {
            entryLoading.value = false;
        });
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Forms Section -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Create Patient Form -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Create Patient</h2>

                    <div v-if="message" class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                        {{ message }}
                    </div>
                    <div v-if="error" class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                        {{ error }}
                    </div>

                    <form @submit.prevent="createPatient">
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input
                                type="text"
                                id="name"
                                v-model="patient.name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                required
                            />
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input
                                type="email"
                                id="email"
                                v-model="patient.email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                required
                            />
                        </div>
                        <div class="mb-4">
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input
                                type="text"
                                id="phone"
                                v-model="patient.phone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="loading"
                        >
                            <span v-if="loading">Creating...</span>
                            <span v-else>Create Patient</span>
                        </button>
                    </form>
                </div>

                <!-- Create Entry Form -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Create Entry</h2>

                    <div v-if="entryMessage" class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                        {{ entryMessage }}
                    </div>
                    <div v-if="entryError" class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                        {{ entryError }}
                    </div>

                    <form @submit.prevent="createEntry">
                        <div class="mb-4">
                            <label for="patient_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Patient</label>
                            <select
                                id="patient_id"
                                v-model="entry.patient_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                required
                            >
                                <option value="">Select a patient</option>
                                <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                    {{ patient.name }} ({{ patient.email }})
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                            <input
                                type="text"
                                id="title"
                                v-model="entry.title"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Enter entry title"
                                required
                            />
                        </div>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="entryLoading"
                        >
                            <span v-if="entryLoading">Creating...</span>
                            <span v-else>Create Entry</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Entries List Section -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Entries</h2>

                <div v-if="entries.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                    No entries found. Create your first entry above.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                    Patient
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                    Title
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                    Created At
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-600 dark:bg-gray-800">
                            <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ entry.id }}
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ entry.patient?.name || 'Unknown Patient' }}
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ entry.title }}
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    {{ entry.created_at ? new Date(entry.created_at).toLocaleDateString() : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                    <button
                                        class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
                                        @click="completeEntry(entry.id)"
                                    >
                                        Complete
                                    </button>
                                    <button class="ml-2 rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
