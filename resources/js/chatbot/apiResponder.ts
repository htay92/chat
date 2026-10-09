import type { ChatResponder } from "./types";
import { UUID_V4 } from "./sessionIdentity";
import { trimUnicode } from "./validation";

export class ApiRequestError extends Error {
    constructor(
        public readonly status = 0,
        public readonly retryAfter = 0,
    ) {
        super("Could not send your question. Please try again.");
    }
}

export const apiResponder: ChatResponder = async (message, signal, context) => {
    if (
        !context ||
        !UUID_V4.test(context.sessionId) ||
        !UUID_V4.test(context.requestId)
    ) throw new ApiRequestError();

    const controller = new AbortController();
    let rejectAbort!: (reason: Error) => void;
    const aborted = new Promise<never>((_, reject) => { rejectAbort = reject; });
    const abort = () => {
        controller.abort();
        rejectAbort(new ApiRequestError());
    };
    signal.addEventListener("abort", abort, { once: true });
    const timer = setTimeout(abort, 15000);

    try {
        if (signal.aborted) throw new ApiRequestError();
        const work = async () => {
            const response = await fetch("/api/chatbot/questions", {
                method: "POST",
                credentials: "omit",
                headers: { "Content-Type": "application/json", Accept: "application/json" },
                body: JSON.stringify({
                    question: message,
                    session_id: context.sessionId,
                    request_id: context.requestId,
                }),
                signal: controller.signal,
            });
            if (!response.ok) {
                const raw = response.headers.get("Retry-After") ?? "";
                const parsed = /^\d+$/.test(raw)
                    ? Number(raw)
                    : Math.ceil((Date.parse(raw) - Date.now()) / 1000);
                const wait = Number.isFinite(parsed) && parsed > 0
                    ? Math.min(parsed, 86400) : 60;
                throw new ApiRequestError(response.status, response.status === 429 ? wait : 0);
            }
            const body: unknown = await response.json();
            const data = (body as { data?: Record<string, unknown> } | null)?.data;
            if (
                !data ||
                data.request_id !== context.requestId ||
                (data.status !== "answered" && data.status !== "unanswered") ||
                (data.reply_source !== "faq" && data.reply_source !== "fallback") ||
                typeof data.replayed !== "boolean" ||
                typeof data.answer !== "string" ||
                !trimUnicode(data.answer) ||
                Array.from(data.answer).length > 4000
            ) throw new ApiRequestError();
            return data.answer;
        };
        return await Promise.race([work(), aborted]);
    } finally {
        clearTimeout(timer);
        signal.removeEventListener("abort", abort);
    }
};
