<script setup lang="ts">
import "../../chatbot/widget.css";
import { nextTick, ref, useId } from "vue";
import type { ChatResponder } from "../../chatbot/types";
import { useChatbot } from "../../composables/useChatbot";
import ChatLauncher from "./ChatLauncher.vue";
import ChatPanel from "./ChatPanel.vue";
import { validateMessage } from "../../chatbot/validation";
const props = defineProps<{ responder?: ChatResponder }>();
const {
    draft,
    messages,
    pending,
    validationError,
    error,
    canRetry,
    retryDisabled,
    submit,
    retry,
} = useChatbot(props.responder);
const isOpen = ref(false);
const root = ref<HTMLElement>();
const panel = ref<InstanceType<typeof ChatPanel>>();
const panelId = `chat-${useId()}`;
async function open() {
    isOpen.value = true;
    await nextTick();
    root.value
        ?.querySelector<HTMLElement>(
            pending.value ? '[aria-label="Close chat"]' : "textarea",
        )
        ?.focus();
}
async function close() {
    isOpen.value = false;
    await nextTick();
    root.value?.querySelector<HTMLElement>('[aria-label="Open chat"]')?.focus();
}
function send() {
    if (pending.value) return;
    if (validateMessage(draft.value).error)
        root.value?.querySelector<HTMLElement>("textarea")?.focus();
    else panel.value?.focus();
    void submit();
}
function resend() {
    if (retryDisabled.value) return;
    panel.value?.focus();
    void retry();
}
</script>
<template>
    <div ref="root" class="chatbot-widget">
        <ChatLauncher :expanded="isOpen" :panel-id="panelId" @open="open" />
        <ChatPanel
            v-show="isOpen"
            ref="panel"
            :panel-id="panelId"
            :messages="messages"
            :draft="draft"
            :pending="pending"
            :validation-error="validationError"
            :error="error"
            :can-retry="canRetry"
            :retry-disabled="retryDisabled"
            @close="close"
            @send="send"
            @retry="resend"
            @update:draft="draft = $event"
        />
    </div>
</template>
