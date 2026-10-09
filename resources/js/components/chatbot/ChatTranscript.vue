<script setup lang="ts">
import { nextTick, ref, watch } from "vue";
import type { ChatMessage } from "../../chatbot/types";
const props = defineProps<{ messages: ChatMessage[] }>();
const log = ref<HTMLElement>();
watch(
    () => props.messages.length,
    async () => {
        await nextTick();
        if (log.value) log.value.scrollTop = log.value.scrollHeight;
    },
);
</script>
<template>
    <div
        ref="log"
        class="chat-transcript"
        role="log"
        aria-label="Conversation"
        aria-live="polite"
        aria-relevant="additions"
        aria-atomic="false"
        tabindex="0"
    >
        <p v-if="!messages.length" class="chat-empty">
            Type a message to start.
        </p>
        <ol>
            <li
                v-for="message in messages"
                :key="message.id"
                :class="['chat-message-row', `chat-message-row-${message.role}`]"
            >
                <span v-if="message.role === 'assistant'" class="chat-avatar chat-avatar-assistant" aria-hidden="true">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="7" width="16" height="13" rx="3" />
                        <path d="M12 3v4M2 12v4M22 12v4M8 12h.01M16 12h.01M9 16h6" />
                    </svg>
                </span>
                <strong class="chat-speaker">{{ message.role === "user" ? "You" : "Assistant" }}</strong>
                <div :class="['chat-message', `chat-message-${message.role}`]">
                    <p>{{ message.text }}</p>
                </div>
                <span v-if="message.role === 'user'" class="chat-avatar chat-avatar-user" aria-hidden="true">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2" />
                    </svg>
                </span>
            </li>
        </ol>
    </div>
</template>
