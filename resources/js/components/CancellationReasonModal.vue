<template>
    <Dialog :open="isOpen" @update:open="handleClose">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-red-600">
                    <AlertTriangleIcon class="h-5 w-5" />
                    Cancelar Entrada
                </DialogTitle>
                <DialogDescription>
                    Por favor, informe o motivo do cancelamento desta entrada. Esta informação será registrada no histórico.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="reason">Motivo do cancelamento *</Label>
                    <textarea
                        id="reason"
                        v-model="reason"
                        :disabled="isLoading"
                        class="min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-1 focus:ring-ring focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Digite o motivo do cancelamento..."
                        required
                    />
                    <p class="text-xs text-gray-500">Máximo 500 caracteres</p>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="handleClose" :disabled="isLoading"> Cancelar </Button>
                    <Button type="submit" variant="destructive" :disabled="isLoading || !reason.trim()" class="min-w-[100px]">
                        <svg v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        {{ isLoading ? 'Cancelando...' : 'Confirmar Cancelamento' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { Entry } from '@/types';
import { AlertTriangleIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    open: boolean;
    entry: Entry | null;
}

interface Emits {
    'update:open': [value: boolean];
    confirm: [reason: string];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isOpen = ref(false);
const reason = ref('');
const isLoading = ref(false);

// Watch for prop changes
watch(
    () => props.open,
    (newValue) => {
        isOpen.value = newValue;
        if (newValue) {
            reason.value = '';
            isLoading.value = false;
        }
    },
);

// Watch for internal changes and emit
watch(isOpen, (newValue) => {
    if (!newValue) {
        emit('update:open', false);
    }
});

function handleClose() {
    if (!isLoading.value) {
        isOpen.value = false;
    }
}

function handleSubmit() {
    if (!reason.value.trim() || isLoading.value) return;

    if (reason.value.length > 500) {
        return;
    }

    isLoading.value = true;
    emit('confirm', reason.value.trim());
}

// Reset loading state when modal closes
watch(isOpen, (newValue) => {
    if (!newValue) {
        isLoading.value = false;
    }
});
</script>
