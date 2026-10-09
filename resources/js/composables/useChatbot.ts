import { computed, onScopeDispose, ref, watch } from "vue";
import { apiResponder, ApiRequestError } from "../chatbot/apiResponder";
import { createSessionIdentity } from "../chatbot/sessionIdentity";
import { validateMessage } from "../chatbot/validation";
import type { ChatMessage, ChatResponder, ExchangeContext } from "../chatbot/types";

export function useChatbot(responder: ChatResponder = apiResponder) {
    const draft = ref("");
    const messages = ref<ChatMessage[]>([]);
    const pending = ref(false);
    const validationError = ref("");
    const error = ref("");
    const failedInput = ref<string | null>(null);
    const canRetry = computed(
        () => failedInput.value !== null && !pending.value,
    );
    const identity = createSessionIdentity();
    const retryDisabled = ref(false);
    let waitTimer: ReturnType<typeof setTimeout> | undefined;
    let failedContext: Readonly<ExchangeContext> | undefined;
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

    async function respond(text: string, context?: Readonly<ExchangeContext>) {
        const id = ++operation;
        controller = new AbortController();
        const signal = controller.signal;
        clearTimeout(waitTimer);
        retryDisabled.value = false;
        pending.value = true;
        error.value = "";
        failedInput.value = null;
        const current = () => !disposed && id === operation && !signal.aborted;
        try {
            context ??= identity.exchange();
            const reply = await responder(text, signal, context);
            if (!current()) return;
            messages.value.push({
                id: ++sequence,
                role: "assistant",
                text: reply,
            });
        } catch (failure) {
            if (!current()) return;
            error.value = "Could not send your question. Please try again.";
            failedInput.value = text;
            failedContext = context;
            if (failure instanceof ApiRequestError && failure.status === 422)
                validationError.value = "The server could not accept this question. Check your message.";
            if (failure instanceof ApiRequestError && failure.status === 429) {
                retryDisabled.value = true;
                error.value = `Too many questions. Please wait ${failure.retryAfter} seconds before retrying.`;
                waitTimer = setTimeout(() => {
                    if (!disposed && id === operation) {
                        retryDisabled.value = false;
                        error.value = "Could not send your question. Please try again.";
                    }
                }, failure.retryAfter * 1000);
            }
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
        failedContext = undefined;
        await respond(text);
    }

    async function retry() {
        if (disposed || retryDisabled.value || !canRetry.value || failedInput.value === null) return;
        await respond(failedInput.value, failedContext);
    }

    onScopeDispose(() => {
        disposed = true;
        operation++;
        controller?.abort();
        clearTimeout(waitTimer);
    });

    return {
        draft,
        messages,
        pending,
        validationError,
        error,
        canRetry,
        retryDisabled,
        submit,
        retry,
    };
}
