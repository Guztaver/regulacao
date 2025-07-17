<script setup lang="ts">
import EntryInfoModal from '@/components/EntryInfoModal.vue';
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

interface Props {
    patientId: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Patient {
    id: string;
    name: string;
    email: string;
    phone: string;
    sus_number?: string;
    documents_count?: number;
    entries_count?: number;
    created_at?: string;
    updated_at?: string;
    created_by?: User;
}

interface PatientDocument {
    id: string;
    original_name: string;
    file_name: string;
    mime_type: string;
    file_size: number;
    formatted_file_size: string;
    document_type: string;
    document_type_label: string;
    description?: string;
    url: string;
    is_image: boolean;
    is_pdf: boolean;
    created_at: string;
}

interface Entry {
    id: string;
    patient_id: string;
    title: string;
    completed: boolean;
    created_at: string;
    patient?: Patient;
    created_by?: User;
}

interface DocumentType {
    [key: string]: string;
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
        title: 'Detalhes do Paciente',
        href: `/patients/${props.patientId}`,
    },
];

const patient = ref<Patient | null>(null);
const documents = ref<PatientDocument[]>([]);
const entries = ref<Entry[]>([]);
const documentTypes = ref<DocumentType>({});
const summary = ref({
    total_documents: 0,
    total_entries: 0,
    active_entries: 0,
    completed_entries: 0,
});

const editingPatient = reactive({
    name: '',
    email: '',
    phone: '',
    sus_number: '',
});

const isEntryInfoModalOpen = ref(false);
const selectedEntry = ref<Entry | null>(null);

const newDocument = reactive({
    file: null as File | null,
    document_type: '',
    description: '',
});

// State management
const loading = ref(false);
const documentsLoading = ref(false);
const message = ref('');
const error = ref('');
const isEditModalOpen = ref(false);
const isDocumentModalOpen = ref(false);
const uploadProgress = ref(0);

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
                editingPatient.email = patient.value.email;
                editingPatient.phone = patient.value.phone;
                editingPatient.sus_number = patient.value.sus_number || '';
            }
        })
        .catch((err) => {
            console.error('Error loading patient:', err);
            error.value = 'Failed to load patient information';
        })
        .finally(() => {
            loading.value = false;
        });
}

// Load patient documents
function loadDocuments() {
    documentsLoading.value = true;

    axios
        .get(`/api/patients/${props.patientId}/documents`)
        .then((response) => {
            documents.value = response.data.documents;
        })
        .catch((err) => {
            console.error('Error loading documents:', err);
            error.value = 'Failed to load documents';
        })
        .finally(() => {
            documentsLoading.value = false;
        });
}

// Load document types
function loadDocumentTypes() {
    axios
        .get('/api/document-types')
        .then((response) => {
            documentTypes.value = response.data.document_types;
        })
        .catch((err) => {
            console.error('Error loading document types:', err);
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
                error.value = 'Failed to update patient';
            }
        })
        .finally(() => {
            loading.value = false;
        });
}

// Upload document
function uploadDocument() {
    if (!newDocument.file) {
        error.value = 'Please select a file';
        return;
    }

    const formData = new FormData();
    formData.append('file', newDocument.file);
    formData.append('document_type', newDocument.document_type);
    if (newDocument.description) {
        formData.append('description', newDocument.description);
    }

    loading.value = true;
    error.value = '';
    message.value = '';

    axios
        .post(`/api/patients/${props.patientId}/documents`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                }
            },
        })
        .then((response) => {
            message.value = response.data.message;
            newDocument.file = null;
            newDocument.document_type = '';
            newDocument.description = '';
            uploadProgress.value = 0;
            isDocumentModalOpen.value = false;
            loadDocuments();
            loadPatient(); // Refresh counts
        })
        .catch((err) => {
            console.error('Error uploading document:', err);
            if (err.response?.data?.errors) {
                const errors = Object.values(err.response.data.errors).flat();
                error.value = errors.join(', ');
            } else {
                error.value = 'Failed to upload document';
            }
        })
        .finally(() => {
            loading.value = false;
            uploadProgress.value = 0;
        });
}

// Delete document
function deleteDocument(documentId: string) {
    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        return;
    }

    loading.value = true;

    axios
        .delete(`/api/patients/${props.patientId}/documents/${documentId}`)
        .then((response) => {
            message.value = response.data.message;
            loadDocuments();
            loadPatient(); // Refresh counts
        })
        .catch((err) => {
            console.error('Error deleting document:', err);
            error.value = 'Failed to delete document';
        })
        .finally(() => {
            loading.value = false;
        });
}

// Download document
function downloadDocument(documentId: string) {
    window.open(`/api/patients/${props.patientId}/documents/${documentId}/download`, '_blank');
}

// File input handling
function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        newDocument.file = target.files[0];
    }
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

function showEntryInfo(entry: Entry): void {
    selectedEntry.value = entry;
    isEntryInfoModalOpen.value = true;
}

function formatSusNumber(susNumber: string | undefined): string {
    if (!susNumber) return 'Not informed';
    return susNumber.replace(/(\d{3})(\d{4})(\d{4})(\d{4})/, '$1 $2 $3 $4');
}

function getFileIcon(document: PatientDocument): string {
    if (document.is_image) return '🖼️';
    if (document.is_pdf) return '📄';
    return '📎';
}

// Computed properties
// Computed properties for future use
// const activeEntries = computed(() => entries.value.filter((entry) => !entry.completed));
// const completedEntries = computed(() => entries.value.filter((entry) => entry.completed));

// Initialize
onMounted(() => {
    loadPatient();
    loadDocuments();
    loadDocumentTypes();
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Patient not found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The patient you're looking for doesn't exist.</p>
                <Button as-child class="mt-4">
                    <Link href="/patients">Back to Patients</Link>
                </Button>
            </div>

            <!-- Patient Information -->
            <template v-else>
                <!-- Header Section -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ patient?.name }}</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Patient information and documents</p>
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
                                    Edit Patient
                                </Button>
                            </DialogTrigger>

                            <DialogContent class="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>Edit Patient Information</DialogTitle>
                                </DialogHeader>

                                <form @submit.prevent="updatePatient" class="grid gap-4 py-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
                                        <Input type="text" v-model="editingPatient.name" class="mt-1" required />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email *</label>
                                        <Input type="email" v-model="editingPatient.email" class="mt-1" required />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                        <Input type="text" v-model="editingPatient.phone" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">SUS Number</label>
                                        <Input type="text" v-model="editingPatient.sus_number" class="mt-1" maxlength="15" />
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isEditModalOpen = false">Cancel</Button>
                                        <Button type="submit" :disabled="loading">
                                            <span v-if="loading">Updating...</span>
                                            <span v-else>Update Patient</span>
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>

                        <!-- Upload Document Modal -->
                        <Dialog v-model:open="isDocumentModalOpen">
                            <DialogTrigger as-child>
                                <Button>
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                        />
                                    </svg>
                                    Upload Document
                                </Button>
                            </DialogTrigger>

                            <DialogContent class="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>Upload Patient Document</DialogTitle>
                                </DialogHeader>

                                <form @submit.prevent="uploadDocument" class="grid gap-4 py-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Document Type *</label>
                                        <Select v-model="newDocument.document_type" class="mt-1" required placeholder="Select document type">
                                            <option v-for="(label, key) in documentTypes" :key="key" :value="key">
                                                {{ label }}
                                            </option>
                                        </Select>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">File *</label>
                                        <input
                                            type="file"
                                            @change="handleFileChange"
                                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                                            accept="image/*,application/pdf,.doc,.docx,.txt"
                                            required
                                        />
                                        <p class="mt-1 text-xs text-gray-500">Max file size: 10MB. Supported: Images, PDF, DOC, TXT</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                        <Input type="text" v-model="newDocument.description" placeholder="Optional description" class="mt-1" />
                                    </div>

                                    <!-- Upload Progress -->
                                    <div v-if="uploadProgress > 0" class="h-2.5 w-full rounded-full bg-gray-200">
                                        <div
                                            class="h-2.5 rounded-full bg-blue-600 transition-all duration-300"
                                            :style="`width: ${uploadProgress}%`"
                                        ></div>
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isDocumentModalOpen = false">Cancel</Button>
                                        <Button type="submit" :disabled="loading || uploadProgress > 0">
                                            <span v-if="loading">Uploading...</span>
                                            <span v-else>Upload Document</span>
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
                            <CardTitle>Patient Information</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</p>
                                    <p class="text-lg font-medium">{{ patient?.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</p>
                                    <p>{{ patient?.email }}</p>
                                </div>
                                <div v-if="patient?.phone">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</p>
                                    <p>{{ patient.phone }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">SUS Number</p>
                                    <p class="font-mono">{{ formatSusNumber(patient?.sus_number) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered</p>
                                    <p>{{ formatDate(patient?.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Added By</p>
                                    <p>{{ patient?.created_by?.name || 'Unknown' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Statistics Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Statistics</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Entries</span>
                                    <span class="font-medium">{{ summary.total_entries }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Active Entries</span>
                                    <span class="font-medium text-blue-600">{{ summary.active_entries }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Completed Entries</span>
                                    <span class="font-medium text-green-600">{{ summary.completed_entries }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Documents</span>
                                    <span class="font-medium">{{ summary.total_documents }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <Button as-child variant="outline" class="w-full justify-start">
                                    <Link href="/dashboard">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Create Entry
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
                                        View Completed
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
                                        All Patients
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Documents Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Documents ({{ documents.length }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="documentsLoading" class="flex items-center justify-center py-8">
                            <div class="h-6 w-6 animate-spin rounded-full border-b-2 border-gray-900 dark:border-white"></div>
                        </div>

                        <div v-else-if="documents.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No documents uploaded</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by uploading the first document.</p>
                        </div>

                        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="document in documents" :key="document.id" class="rounded-lg border border-gray-200 p-4 dark:border-gray-600">
                                <div class="flex items-start justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center">
                                            <span class="mr-2 text-2xl">{{ getFileIcon(document) }}</span>
                                            <div class="flex-1">
                                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ document.original_name }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ document.document_type_label }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            <p>{{ document.formatted_file_size }}</p>
                                            <p>{{ formatDate(document.created_at) }}</p>
                                            <p v-if="document.description" class="mt-1 italic">{{ document.description }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <Button size="sm" variant="outline" @click="downloadDocument(document.id)">
                                        <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                        Download
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteDocument(document.id)">
                                        <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Entries Section -->
                <Card v-if="entries.length > 0">
                    <CardHeader>
                        <CardTitle>Recent Entries</CardTitle>
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
                                    {{ entry.completed ? 'Completed' : 'Active' }}
                                </span>
                            </div>
                            <div v-if="entries.length > 5" class="text-center">
                                <Button variant="outline" size="sm" as-child>
                                    <Link href="/dashboard">View All Entries</Link>
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
