import { computed, onScopeDispose, ref, watch } from "vue";
import { mockResponder } from "../chatbot/mockResponder";
import { validateMessage } from "../chatbot/validation";
import type { ChatMessage, ChatResponder } from "../chatbot/types";

export function useChatbot(responder: ChatResponder = mockResponder) {
    const draft = ref("");
    const messages = ref<ChatMessage[]>([]);
    const pending = ref(false);
    const validationError = ref("");
    const error = ref("");
    const failedInput = ref<string | null>(null);
    const canRetry = computed(
        () => failedInput.value !== null && !pending.value,
    );
    let sequence = 0;
    let operation = 0;
    let disposed = false;
    let controller: AbortController | undefined;

    watch(
        draft,
        () => {
            validationError.value = "";
        },
        { flush: "sync" },
    );

    async function respond(text: string) {
        const id = ++operation;
        controller = new AbortController();
        const signal = controller.signal;
        pending.value = true;
        error.value = "";
        failedInput.value = null;
        const current = () => !disposed && id === operation && !signal.aborted;
        try {
            const reply = await responder(text, signal);
            if (!current()) return;
            messages.value.push({
                id: ++sequence,
                role: "assistant",
                text: reply,
            });
        } catch {
            if (!current()) return;
            error.value = "Could not generate a mock reply. Please try again.";
            failedInput.value = text;
        } finally {
            if (current()) pending.value = false;
        }
    }

    async function submit() {
        if (disposed || pending.value) return;
        const { text, error: invalid } = validateMessage(draft.value);
        validationError.value = invalid;
        if (invalid) return;
        messages.value.push({ id: ++sequence, role: "user", text });
        draft.value = "";
        await respond(text);
    }

    async function retry() {
        if (disposed || !canRetry.value || failedInput.value === null) return;
        await respond(failedInput.value);
    }

    onScopeDispose(() => {
        disposed = true;
        operation++;
        controller?.abort();
    });

    return {
        draft,
        messages,
        pending,
        validationError,
        error,
        canRetry,
        submit,
        retry,
    };
}
