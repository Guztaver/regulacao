<script setup lang="ts">
import { reactive, ref } from 'vue';

interface PatientEntry {
    id: string;
    title: string;
    current_status: {
        name: string;
        slug: string;
        color: string;
    };
    created_at: string;
    scheduled_exam_date: string | null;
    recent_transitions: Array<{
        from_status: string | null;
        to_status: string;
        performed_at: string;
        reason: string;
    }>;
}

interface PatientData {
    patient: {
        name: string;
        sus_number: string;
        entries_count: number;
    };
    entries: PatientEntry[];
}

const form = reactive({
    sus_number: '',
    processing: false,
});

const searchResult = ref<PatientData | null>(null);
const error = ref<string>('');
const showResults = ref(false);

const formatSusNumber = (value: string) => {
    // Remove all non-digits
    const digits = value.replace(/\D/g, '');

    // Limit to 15 digits and format as XXX.XXXX.XXX.XXXX
    if (digits.length <= 15) {
        form.sus_number = digits
            .replace(/(\d{3})(\d{4})(\d{3})(\d{4})/, '$1.$2.$3.$4')
            .replace(/(\d{3})(\d{4})(\d{3})(\d{1,4})/, '$1.$2.$3.$4')
            .replace(/(\d{3})(\d{4})(\d{1,3})/, '$1.$2.$3')
            .replace(/(\d{3})(\d{1,4})/, '$1.$2');
    }
};

const handleSusInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    formatSusNumber(target.value);
};

const searchPatient = async () => {
    if (!form.sus_number) {
        error.value = 'Por favor, digite o número do SUS';
        return;
    }

    // Remove formatting for API call
    const cleanSusNumber = form.sus_number.replace(/\D/g, '');

    if (cleanSusNumber.length !== 15) {
        error.value = 'Número do SUS deve ter 15 dígitos';
        return;
    }

    form.processing = true;
    error.value = '';
    searchResult.value = null;
    showResults.value = false;

    try {
        const response = await fetch('/patient-lookup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                sus_number: cleanSusNumber,
            }),
        });

        const data = await response.json();

        if (data.success) {
            searchResult.value = data;
            showResults.value = true;
        } else {
            error.value = data.message || 'Paciente não encontrado';
        }
    } catch (err) {
        error.value = 'Erro ao buscar paciente. Tente novamente.';
        console.error('Lookup error:', err);
    } finally {
        form.processing = false;
    }
};

const resetSearch = () => {
    form.sus_number = '';
    searchResult.value = null;
    error.value = '';
    showResults.value = false;
};

const getStatusColor = (color: string) => {
    const colorMap: Record<string, string> = {
        blue: 'bg-accent text-accent-foreground',
        green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        red: 'bg-destructive/10 text-destructive',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        gray: 'bg-muted text-muted-foreground',
    };
    return colorMap[color] || colorMap['gray'];
};
</script>

<template>
    <div class="mx-auto w-full max-w-4xl">
        <!-- Search Form -->
        <div class="mb-6 rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800">
            <div class="mb-4 flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Consulte Seus Exames</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Digite seu número do SUS para ver o status dos seus procedimentos</p>
                </div>
            </div>

            <form @submit.prevent="searchPatient" class="space-y-4">
                <div>
                    <label for="sus_number" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Número do SUS (Cartão Nacional de Saúde)
                    </label>
                    <input
                        id="sus_number"
                        type="text"
                        :value="form.sus_number"
                        @input="handleSusInput"
                        placeholder="000.0000.000.0000"
                        maxlength="18"
                        class="w-full rounded-lg border border-input bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus:border-transparent focus:ring-2 focus:ring-ring"
                        :disabled="form.processing"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">Formato: 15 dígitos (exemplo: 123.4567.890.1234)</p>
                </div>

                <div class="flex space-x-3">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.sus_number"
                        class="flex-1 rounded-lg bg-primary px-6 py-3 font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center">
                            <svg
                                class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            Buscando...
                        </span>
                        <span v-else>Consultar</span>
                    </button>

                    <button
                        v-if="showResults"
                        type="button"
                        @click="resetSearch"
                        class="rounded-lg border border-border px-6 py-3 text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        Nova Busca
                    </button>
                </div>
            </form>

            <!-- Error Message -->
            <div v-if="error" class="mt-4 rounded-lg border border-destructive/20 bg-destructive/10 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-destructive">{{ error }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div v-if="showResults && searchResult" class="rounded-xl bg-card p-6 shadow-lg">
            <!-- Patient Info -->
            <div class="mb-6 border-b border-border pb-4">
                <h3 class="mb-2 text-lg font-semibold text-card-foreground">Informações do Paciente</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm text-muted-foreground">Nome</p>
                        <p class="font-medium text-card-foreground">{{ searchResult.patient.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total de Procedimentos</p>
                        <p class="font-medium text-card-foreground">{{ searchResult.patient.entries_count }}</p>
                    </div>
                </div>
            </div>

            <!-- Entries List -->
            <div>
                <h4 class="mb-4 text-lg font-semibold text-card-foreground">Seus Procedimentos</h4>

                <div v-if="searchResult.entries.length === 0" class="py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <p class="mt-2 text-sm text-muted-foreground">Nenhum procedimento encontrado.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="entry in searchResult.entries" :key="entry.id" class="rounded-lg border border-border bg-card p-4">
                        <div class="mb-3 flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="font-medium text-card-foreground">{{ entry.title }}</h5>
                                <p class="text-sm text-muted-foreground">Criado em {{ entry.created_at }}</p>
                            </div>
                            <span
                                :class="getStatusColor(entry.current_status.color)"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ entry.current_status.name }}
                            </span>
                        </div>

                        <div v-if="entry.scheduled_exam_date" class="mb-3 rounded-lg bg-muted/50 p-2">
                            <div class="flex items-center text-sm text-foreground">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <span class="font-medium">Data do Exame: {{ entry.scheduled_exam_date }}</span>
                            </div>
                        </div>

                        <!-- Recent Transitions -->
                        <div v-if="entry.recent_transitions.length > 0" class="border-t border-border pt-3">
                            <p class="mb-2 text-sm font-medium text-card-foreground">Histórico Recente:</p>
                            <div class="space-y-2">
                                <div v-for="transition in entry.recent_transitions" :key="`${entry.id}-${transition.performed_at}`" class="text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">
                                            <span v-if="transition.from_status">{{ transition.from_status }} → </span>{{ transition.to_status }}
                                        </span>
                                        <span class="text-xs text-muted-foreground">{{ transition.performed_at || 'Data não disponível' }}</span>
                                    </div>
                                    <p v-if="transition.reason" class="mt-1 text-xs text-muted-foreground">{{ transition.reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Notice -->
        <div class="mt-6 rounded-lg border border-border bg-muted/30 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <div class="ml-3">
                    <p class="text-sm text-foreground">
                        <strong>Importante:</strong> Se você não encontrar seus dados ou tiver dúvidas sobre algum procedimento, entre em contato com
                        a unidade de saúde responsável pelo seu atendimento.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
