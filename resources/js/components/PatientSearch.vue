<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { usePatientApi } from '@/composables/useApi';
import type { Patient } from '@/types';
import { ref, watch } from 'vue';

interface Props {
    modelValue?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
}

interface Emits {
    'update:modelValue': [value: string];
    'patient-selected': [patient: Patient];
    'patient-cleared': [];
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Digite o nome do paciente...',
    required: false,
    disabled: false,
});

const emit = defineEmits<Emits>();

const searchTerm = ref('');
const searchResults = ref<Patient[]>([]);
const isLoading = ref(false);
const showDropdown = ref(false);
const selectedPatient = ref<Patient | null>(null);

const patientApi = usePatientApi();

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;

const searchPatients = async (term: string) => {
    if (term.length < 2) {
        searchResults.value = [];
        showDropdown.value = false;
        return;
    }

    isLoading.value = true;
    try {
        const response = await patientApi.getPatients({ search: term, limit: 20 });
        searchResults.value = Array.isArray(response) ? response : response.data || [];
        showDropdown.value = true;
    } catch (err) {
        console.error('Erro ao buscar pacientes:', err);
        searchResults.value = [];
        showDropdown.value = false;
    } finally {
        isLoading.value = false;
    }
};

const selectPatient = (patient: Patient) => {
    selectedPatient.value = patient;
    searchTerm.value = patient.name;
    showDropdown.value = false;
    emit('update:modelValue', patient.id);
    emit('patient-selected', patient);
};

const clearSelection = () => {
    selectedPatient.value = null;
    searchTerm.value = '';
    searchResults.value = [];
    showDropdown.value = false;
    emit('update:modelValue', '');
    emit('patient-cleared');
};

const handleFocus = () => {
    if (searchTerm.value && !selectedPatient.value && searchResults.value.length > 0) {
        showDropdown.value = true;
    }
};

// Watch searchTerm for changes and trigger search
watch(
    searchTerm,
    (newValue) => {
        // If user is typing and we had a selection, clear it
        if (selectedPatient.value && newValue !== selectedPatient.value.name) {
            selectedPatient.value = null;
            emit('update:modelValue', '');
        }

        // Debounced search
        clearTimeout(searchTimeout);
        if (newValue.length < 2) {
            searchResults.value = [];
            showDropdown.value = false;
            return;
        }

        searchTimeout = setTimeout(() => {
            searchPatients(newValue);
        }, 300);
    },
    { immediate: false },
);

// Watch for external changes to modelValue
watch(
    () => props.modelValue,
    (newValue) => {
        if (!newValue && selectedPatient.value) {
            clearSelection();
        }
    },
);

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.patient-search-container')) {
        showDropdown.value = false;
    }
};

// Set up click outside listener
if (typeof document !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
}
</script>

<template>
    <div class="patient-search-container relative">
        <div class="relative">
            <Input
                v-model="searchTerm"
                @focus="handleFocus"
                type="text"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                class="pr-10"
            />

            <!-- Loading/Clear Button -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <svg v-if="isLoading" class="h-4 w-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <button
                    v-else-if="selectedPatient || searchTerm"
                    @click="clearSelection"
                    type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <svg v-else class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Dropdown Results -->
        <div
            v-if="showDropdown && searchResults.length > 0"
            class="ring-opacity-5 absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black focus:outline-none sm:text-sm dark:bg-gray-800 dark:ring-gray-600"
        >
            <button
                v-for="patient in searchResults"
                :key="patient.id"
                @click="selectPatient(patient)"
                type="button"
                class="relative w-full cursor-pointer py-2 pr-9 pl-3 text-left select-none hover:bg-gray-100 focus:bg-gray-100 dark:hover:bg-gray-700 dark:focus:bg-gray-700"
                :class="{ 'bg-blue-50 dark:bg-blue-900': selectedPatient?.id === patient.id }"
            >
                <div class="flex flex-col">
                    <span class="font-medium text-gray-900 dark:text-white">{{ patient.name }}</span>
                    <span v-if="patient.sus_number" class="text-xs text-gray-400 dark:text-gray-500"> SUS: {{ patient.sus_number }} </span>
                </div>
            </button>
        </div>

        <!-- No Results -->
        <div
            v-else-if="showDropdown && searchTerm.length >= 2 && !isLoading && searchResults.length === 0"
            class="ring-opacity-5 absolute z-50 mt-1 w-full rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black dark:bg-gray-800 dark:ring-gray-600"
        >
            <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Nenhum paciente encontrado</div>
        </div>
    </div>
</template>

<style scoped>
/* Additional custom styles if needed */
</style>
