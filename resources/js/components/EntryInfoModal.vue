<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { handleApiError, useEntryDocumentApi } from '@/composables/useApi';
import { useTranslations } from '@/composables/useTranslations';
import type { Entry, EntryDocument } from '@/types';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import StatusBadge from './StatusBadge.vue';
import Timeline from './Timeline.vue';

interface Props {
    open: boolean;
    entry: Entry | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();

// Document upload state
const documentApi = useEntryDocumentApi();
const isUploading = ref(false);
const uploadError = ref('');
const uploadSuccess = ref('');
const fileInput = ref<HTMLInputElement>();
const selectedFile = ref<File>();
const documentType = ref('');
const documentDescription = ref('');
const documentTypes = ref<Record<string, string>>({});
const entryDocuments = ref<EntryDocument[]>([]);
const showDocumentUpload = ref(false);

// Computed properties
const hasDocuments = computed(() => entryDocuments.value.length > 0);
const canUpload = computed(() => selectedFile.value && documentType.value);

function formatDate(dateString?: string): string {
    if (!dateString) return 'N/A';

    try {
        const date = new Date(dateString);
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

function closeModal() {
    emit('update:open', false);
}

function handleEscapeKey(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.open) {
        closeModal();
    }
}

function handleBodyScroll(open: boolean) {
    if (open) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Watch for modal open/close changes to handle body scroll
watch(
    () => props.open,
    (newOpen) => {
        handleBodyScroll(newOpen);
    },
    { immediate: true },
);

onMounted(() => {
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscapeKey);
    // Ensure body scroll is restored when component unmounts
    document.body.style.overflow = '';
});

// Document management functions
async function loadDocumentTypes() {
    try {
        const response = await documentApi.getEntryDocumentTypes();
        documentTypes.value = response.document_types;
    } catch (error) {
        console.error('Failed to load document types:', error);
    }
}

async function loadEntryDocuments() {
    if (!props.entry?.id) return;

    try {
        const response = await documentApi.getEntryDocuments(props.entry.id);
        entryDocuments.value = response.documents;
    } catch (error) {
        console.error('Failed to load entry documents:', error);
    }
}

function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        selectedFile.value = input.files[0];
        uploadError.value = '';
        uploadSuccess.value = '';
    }
}

function resetUploadForm() {
    selectedFile.value = undefined;
    documentType.value = '';
    documentDescription.value = '';
    uploadError.value = '';
    uploadSuccess.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

async function uploadDocument() {
    if (!props.entry?.id || !selectedFile.value || !documentType.value) return;

    isUploading.value = true;
    uploadError.value = '';
    uploadSuccess.value = '';

    try {
        await documentApi.uploadEntryDocument(props.entry.id, selectedFile.value, documentType.value, documentDescription.value);

        uploadSuccess.value = 'Documento enviado com sucesso!';
        resetUploadForm();
        await loadEntryDocuments();

        // Auto-hide success message after 3 seconds
        setTimeout(() => {
            uploadSuccess.value = '';
        }, 3000);
    } catch (error) {
        uploadError.value = handleApiError(error);
    } finally {
        isUploading.value = false;
    }
}

async function deleteDocument(documentId: string) {
    if (!props.entry?.id) return;

    if (!confirm('Tem certeza que deseja excluir este documento?')) return;

    try {
        await documentApi.deleteEntryDocument(props.entry.id, documentId);
        await loadEntryDocuments();
    } catch (error) {
        console.error('Failed to delete document:', error);
    }
}

async function downloadDocument(documentId: string, fileName: string) {
    if (!props.entry?.id) return;

    try {
        const response = await documentApi.downloadEntryDocument(props.entry.id, documentId);
        const blob = new Blob([response.data]);
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    } catch (error) {
        console.error('Failed to download document:', error);
    }
}

// Watch for entry changes to load documents
watch(
    () => props.entry?.id,
    async (newEntryId) => {
        if (newEntryId) {
            await loadEntryDocuments();
        }
    },
    { immediate: true },
);

// Load document types on component mount
onMounted(async () => {
    await loadDocumentTypes();
});

// Print function
function printEntry() {
    if (!props.entry?.id) return;

    // Open print page in new window
    const printUrl = `/api/entries/${props.entry.id}/print`;
    window.open(printUrl, '_blank');
}
</script>

<template>
    <!-- Modal Backdrop -->
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click="closeModal">
        <!-- Lighter Overlay -->
        <div class="bg-opacity-80 bg-opacity-20 bg-gray-30 fixed inset-0 bg-black"></div>

        <!-- Modal Content - Larger and Scrollable -->
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-4xl flex-col rounded-lg bg-white shadow-xl dark:bg-gray-800" @click.stop>
            <!-- Fixed Header -->
            <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 p-6 dark:border-gray-700">
                <h3 class="flex items-center gap-2 text-xl font-semibold text-gray-900 dark:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    {{ t.entryDetails }}
                </h3>
                <div class="flex items-center gap-2">
                    <button
                        @click="printEntry"
                        class="cursor-pointer rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white"
                        title="Imprimir entrada"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            />
                        </svg>
                    </button>
                    <button
                        @click="closeModal"
                        class="cursor-pointer rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <div v-if="entry" class="space-y-6 p-6">
                    <!-- Main Info Grid -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Entry Status -->
                            <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Status Atual</h4>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ t.status }}:</span>
                                    <StatusBadge :entry="entry" />
                                </div>
                            </div>

                            <!-- Exam Schedule Date -->
                            <div v-if="entry.scheduled_exam_date" class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Agendamento</h4>
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span class="text-sm font-medium text-purple-800 dark:text-purple-200">
                                        {{ formatDate(entry.scheduled_exam_date) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Entry Basic Info -->
                            <div class="space-y-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Informações Básicas</h4>

                                <div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.id }}:</span>
                                    <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                        {{ entry.id }}
                                    </code>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.addedBy }}:</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ entry?.createdBy?.name || t.unknown }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Entry Title -->
                            <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ t.title }}</h4>
                                <p class="text-sm leading-relaxed text-gray-900 dark:text-gray-100">
                                    {{ entry.title }}
                                </p>
                            </div>

                            <!-- Status Description -->
                            <div v-if="entry.current_status?.description" class="rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Descrição do Status</h4>
                                <p class="text-sm leading-relaxed text-gray-900 dark:text-gray-100">
                                    {{ entry.current_status.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Information -->
                    <div class="rounded-lg bg-green-50 p-6 dark:bg-green-900/20">
                        <h4 class="mb-4 flex items-center gap-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            Informações do Paciente
                        </h4>

                        <div v-if="entry.patient" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.name }}:</span>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <a
                                        :href="`/patients/${entry.patient?.id}`"
                                        class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-200"
                                    >
                                        {{ entry.patient?.name || t.unknownPatient }}
                                    </a>
                                </p>
                            </div>

                            <div v-if="entry.patient.email">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.email }}:</span>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ entry.patient.email }}
                                </p>
                            </div>

                            <div v-if="entry.patient.sus_number">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ t.susNumber }}:</span>
                                <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                    {{ entry.patient.sus_number }}
                                </code>
                            </div>

                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">ID do Paciente:</span>
                                <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                    {{ entry.patient.id }}
                                </code>
                            </div>
                        </div>

                        <div v-else class="space-y-2">
                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">ID do Paciente:</span>
                                <code class="mt-1 block rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-600">
                                    {{ entry.patient_id }}
                                </code>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Detalhes do paciente não disponíveis nesta visualização</p>
                        </div>
                    </div>

                    <!-- Status Transitions -->
                    <div class="rounded-lg bg-gray-50 p-6 dark:bg-gray-700">
                        <h4 class="mb-4 flex items-center gap-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                />
                            </svg>
                            Histórico de Status
                        </h4>

                        <div v-if="entry.status_transitions && entry.status_transitions.length > 0" class="space-y-3">
                            <div
                                v-for="transition in entry.status_transitions"
                                :key="transition.id"
                                class="flex items-start space-x-3 rounded-lg border bg-white p-4 shadow-sm dark:bg-gray-800"
                            >
                                <div
                                    class="mt-1 h-3 w-3 flex-shrink-0 rounded-full"
                                    :style="{ backgroundColor: transition.to_status?.color || '#6B7280' }"
                                ></div>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{
                                                transition.from_status
                                                    ? `${transition.from_status.name} → ${transition.to_status?.name}`
                                                    : `Set to ${transition.to_status?.name}`
                                            }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(transition.transitioned_at) }}
                                        </p>
                                    </div>
                                    <p v-if="transition.reason" class="mb-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ transition.reason }}
                                    </p>
                                    <p v-if="transition.scheduled_date" class="mb-1 text-xs text-purple-600 dark:text-purple-400">
                                        📅 {{ t.scheduled }}: {{ formatDate(transition.scheduled_date) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">por {{ transition.user?.name || t.unknown }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            <p class="mt-2">Nenhum histórico de status disponível</p>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="rounded-lg bg-amber-50 p-6 dark:bg-amber-900/20">
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="flex items-center gap-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                    />
                                </svg>
                                Documentos
                                <span
                                    v-if="hasDocuments"
                                    class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-800 dark:text-amber-200"
                                >
                                    {{ entryDocuments.length }}
                                </span>
                            </h4>
                            <Button @click="showDocumentUpload = !showDocumentUpload" variant="outline" size="sm" class="gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                    />
                                </svg>
                                {{ showDocumentUpload ? 'Cancelar' : 'Enviar Documento' }}
                            </Button>
                        </div>

                        <!-- Document Upload Form -->
                        <div
                            v-if="showDocumentUpload"
                            class="mb-6 rounded-lg border border-amber-200 bg-white p-4 dark:border-amber-700 dark:bg-gray-800"
                        >
                            <h5 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Enviar Novo Documento</h5>

                            <!-- File Input -->
                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Arquivo </label>
                                <Input
                                    ref="fileInput"
                                    type="file"
                                    @change="handleFileSelect"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt"
                                    class="cursor-pointer"
                                />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Formatos aceitos: PDF, DOC, DOCX, JPG, PNG, TXT (máximo 10MB)
                                </p>
                            </div>

                            <!-- Document Type -->
                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Tipo de Documento </label>
                                <select
                                    v-model="documentType"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    <option value="">Selecione um tipo</option>
                                    <option v-for="(label, value) in documentTypes" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Descrição (opcional) </label>
                                <Input v-model="documentDescription" placeholder="Descrição do documento..." class="w-full" />
                            </div>

                            <!-- Upload Actions -->
                            <div class="flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <Button @click="uploadDocument" :disabled="!canUpload || isUploading" class="flex-1">
                                        <svg
                                            v-if="isUploading"
                                            class="mr-2 h-4 w-4 animate-spin"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            />
                                        </svg>
                                        {{ isUploading ? 'Enviando...' : 'Enviar Documento' }}
                                    </Button>
                                    <Button @click="resetUploadForm" variant="outline" :disabled="isUploading"> Limpar </Button>
                                </div>

                                <!-- Success Message -->
                                <div
                                    v-if="uploadSuccess"
                                    class="rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-200"
                                >
                                    {{ uploadSuccess }}
                                </div>

                                <!-- Error Message -->
                                <div v-if="uploadError" class="rounded-md bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
                                    {{ uploadError }}
                                </div>
                            </div>
                        </div>

                        <!-- Documents List -->
                        <div v-if="hasDocuments" class="space-y-3">
                            <div
                                v-for="document in entryDocuments"
                                :key="document.id"
                                class="flex items-center justify-between rounded-lg border bg-white p-4 shadow-sm dark:border-gray-600 dark:bg-gray-800"
                            >
                                <div class="flex items-start gap-3">
                                    <!-- File Icon -->
                                    <div class="rounded-md bg-gray-100 p-2 dark:bg-gray-700">
                                        <svg
                                            v-if="document.is_pdf"
                                            class="h-5 w-5 text-red-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                            />
                                        </svg>
                                        <svg
                                            v-else-if="document.is_image"
                                            class="h-5 w-5 text-blue-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        <svg v-else class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                    </div>

                                    <!-- Document Info -->
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ document.original_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ document.document_type_label }} • {{ document.formatted_file_size }}
                                        </p>
                                        <p v-if="document.description" class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                            {{ document.description }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(document.created_at) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Document Actions -->
                                <div class="flex gap-2">
                                    <Button @click="downloadDocument(document.id, document.original_name)" variant="outline" size="sm" class="gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                        Baixar
                                    </Button>
                                    <Button @click="deleteDocument(document.id)" variant="destructive" size="sm" class="gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            </div>
                        </div>

                        <!-- No Documents Message -->
                        <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                            <p class="mt-2">Nenhum documento anexado</p>
                            <p class="text-sm">Clique em "Enviar Documento" para adicionar arquivos</p>
                        </div>
                    </div>

                    <!-- Legacy Activity Timeline (for backward compatibility) -->
                    <div v-if="entry.timeline && entry.timeline.length > 0" class="rounded-lg bg-indigo-50 p-6 dark:bg-indigo-900/20">
                        <h4 class="mb-4 flex items-center gap-2 text-lg font-medium text-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            Linha do Tempo de Atividades
                        </h4>

                        <Timeline :timeline="entry.timeline || []" max-height="32rem" />
                    </div>
                </div>

                <div v-else class="p-6 py-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">Nenhuma entrada selecionada</p>
                </div>
            </div>

            <!-- Footer with Print Button -->
            <!-- <div v-if="entry" class="flex-shrink-0 border-t border-gray-200 p-6 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Entrada criada em {{ formatDate(entry.created_at) }}</div>
                    <div class="flex gap-2">

                        <Button @click="printEntry" variant="outline" size="sm" class="gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                            Imprimir Entrada
                        </Button>
                        <Button @click="closeModal" variant="secondary" size="sm"> Fechar </Button>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</template>
