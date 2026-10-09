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
                :class="['chat-message', `chat-message-${message.role}`]"
            >
                <strong>{{
                    message.role === "user" ? "You" : "Assistant"
                }}</strong>
                <p>{{ message.text }}</p>
            </li>
        </ol>
    </div>
</template>
