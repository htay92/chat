<script setup lang="ts">
import { ref } from "vue";
import type { ChatMessage } from "../../chatbot/types";
import ChatComposer from "./ChatComposer.vue";
import ChatTranscript from "./ChatTranscript.vue";
defineProps<{
    panelId: string;
    messages: ChatMessage[];
    draft: string;
    pending: boolean;
    validationError: string;
    error: string;
    canRetry: boolean;
}>();
defineEmits<{
    close: [];
    send: [];
    retry: [];
    "update:draft": [value: string];
}>();
const panel = ref<HTMLElement>();
defineExpose({ focus: () => panel.value?.focus() });
</script>
<template>
    <section
        :id="panelId"
        ref="panel"
        class="chat-panel"
        role="dialog"
        :aria-labelledby="`${panelId}-title`"
        tabindex="-1"
        @keydown.esc.stop.prevent="$emit('close')"
    >
        <header class="chat-header">
            <h2 :id="`${panelId}-title`">Demo chatbot</h2>
            <button
                class="chat-button chat-close"
                type="button"
                aria-label="Close chat"
                @click="$emit('close')"
            >
                Close
            </button>
        </header>
        <p class="chat-notice">Demo only — responses are mocked.</p>
        <ChatTranscript :messages="messages" />
        <div class="chat-feedback">
            <p role="status" aria-live="polite">
                {{ pending ? "Replying…" : "" }}
            </p>
            <p v-if="error" role="alert" class="chat-error">{{ error }}</p>
            <button
                v-if="canRetry"
                class="chat-button"
                type="button"
                @click="$emit('retry')"
            >
                Retry
            </button>
        </div>
        <ChatComposer
            :input-id="`${panelId}-input`"
            :draft="draft"
            :pending="pending"
            :error="validationError"
            @update:draft="$emit('update:draft', $event)"
            @submit="$emit('send')"
        />
    </section>
</template>
