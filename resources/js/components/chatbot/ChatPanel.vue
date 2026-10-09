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
            <div class="chat-heading">
                <span class="chat-assistant-icon">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="7" width="16" height="13" rx="4" />
                        <path d="M12 3v4M8 12h.01M16 12h.01M9 16h6" />
                    </svg>
                </span>
                <div class="chat-heading-text">
                    <h2 :id="`${panelId}-title`">Demo chatbot</h2>
                    <span class="chat-demo-badge">Demo</span>
                </div>
            </div>
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
