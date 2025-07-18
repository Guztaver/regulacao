<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Entry } from '@/types';

interface Props {
    entry: Entry;
    variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'link' | 'destructive';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    showText?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'outline',
    size: 'sm',
    showText: true,
    class: '',
});

function printEntry() {
    if (!props.entry?.id) return;

    // Open print page in new window
    const printUrl = `/api/entries/${props.entry.id}/print`;
    const printWindow = window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');

    if (!printWindow) {
        alert('Janela de impressão bloqueada. Por favor, permita pop-ups para este site.');
        return;
    }

    printWindow.focus();
}
</script>

<template>
    <Button @click="printEntry" :variant="variant" :size="size" :class="props.class" :title="showText ? undefined : 'Imprimir entrada'" class="gap-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
            />
        </svg>
        <span v-if="showText">Imprimir</span>
    </Button>
</template>
