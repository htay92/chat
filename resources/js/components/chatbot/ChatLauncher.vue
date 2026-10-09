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
        Chat
    </button>
</template>
