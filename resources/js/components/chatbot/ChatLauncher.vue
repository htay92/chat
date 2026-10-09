<script setup lang="ts">
defineProps<{ expanded: boolean; panelId: string }>();
defineEmits<{ open: [] }>();
</script>
<template>
    <button
        type="button"
        class="chat-button chat-launcher"
        aria-label="Open chat"
        :aria-expanded="expanded"
        :aria-controls="panelId"
        @click="$emit('open')"
    >
        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-1 1v-9.5A8.5 8.5 0 0 1 11.5 3H13a8 8 0 0 1 8 8.5Z" />
            <path d="M8 10h8M8 14h5" />
        </svg>
    </button>
</template>
