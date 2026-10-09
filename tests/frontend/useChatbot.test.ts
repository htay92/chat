import { effectScope } from "vue";
import { afterEach, expect, it, vi } from "vitest";
import { useChatbot } from "../../resources/js/composables/useChatbot";
import type { ChatResponder } from "../../resources/js/chatbot/types";
const scopes: ReturnType<typeof effectScope>[] = [];
afterEach(() => {
    scopes.forEach((s) => s.stop());
    scopes.length = 0;
    vi.useRealTimers();
});
function setup(responder?: ChatResponder) {
    const scope = effectScope();
    scopes.push(scope);
    return { scope, chat: scope.run(() => useChatbot(responder))! };
}
it("AC-03/04 invalid draft is preserved and never sent", async () => {
    const responder = vi.fn();
    const { chat } = setup(responder);
    chat.draft.value = " ";
    await chat.submit();
    expect(chat.validationError.value).not.toBe("");
    expect(chat.draft.value).toBe(" ");
    expect(responder).not.toHaveBeenCalled();
    expect(chat.messages.value).toEqual([]);
    chat.draft.value = "😀".repeat(1001);
    await chat.submit();
    expect(responder).not.toHaveBeenCalled();
    chat.draft.value = "ok";
    expect(chat.validationError.value).toBe("");
});
it("AC-05/07 submits once, clears draft, and appends ordered reply", async () => {
    let resolve!: (s: string) => void;
    const responder = vi.fn(
        () =>
            new Promise<string>((r) => {
                resolve = r;
            }),
    );
    const { chat } = setup(responder);
    chat.draft.value = " hello ";
    const sent = chat.submit();
    expect(chat.pending.value).toBe(true);
    expect(chat.draft.value).toBe("");
    await chat.submit();
    expect(responder).toHaveBeenCalledTimes(1);
    resolve("reply");
    await sent;
    expect(chat.messages.value.map((m) => [m.role, m.text])).toEqual([
        ["user", "hello"],
        ["assistant", "reply"],
    ]);
    expect(chat.pending.value).toBe(false);
});
it("AC-08 rejection allows retry without another user entry", async () => {
    const responder = vi
        .fn()
        .mockRejectedValueOnce(new Error("failed"))
        .mockResolvedValue("reply");
    const { chat } = setup(responder);
    chat.draft.value = "hello";
    await chat.submit();
    expect(chat.error.value).toBe(
        "Could not generate a mock reply. Please try again.",
    );
    expect(chat.pending.value).toBe(false);
    expect(chat.canRetry.value).toBe(true);
    chat.draft.value = "new draft";
    await chat.retry();
    expect(chat.draft.value).toBe("new draft");
    expect(responder.mock.calls[1]?.[0]).toBe("hello");
    expect(chat.messages.value).toHaveLength(2);
    expect(chat.error.value).toBe("");
});
it("AC-08 a new valid submission dismisses old failure", async () => {
    const responder = vi
        .fn()
        .mockRejectedValueOnce(new Error())
        .mockResolvedValue("ok");
    const { chat } = setup(responder);
    chat.draft.value = "old";
    await chat.submit();
    chat.draft.value = "new";
    await chat.submit();
    expect(chat.canRetry.value).toBe(false);
    expect(chat.messages.value.map((m) => m.text)).toEqual([
        "old",
        "new",
        "ok",
    ]);
});
it("AC-08 catches synchronous throws", async () => {
    const { chat } = setup(() => {
        throw new Error();
    });
    chat.draft.value = "hi";
    await chat.submit();
    expect(chat.canRetry.value).toBe(true);
});
it.each(["resolve", "reject"])(
    "AC-09 disposal ignores late %s and aborts signal",
    async (kind) => {
        let resolve!: (s: string) => void;
        let reject!: (e: Error) => void;
        let signal!: AbortSignal;
        const { chat, scope } = setup((_, s) => {
            signal = s;
            return new Promise((r, j) => {
                resolve = r;
                reject = j;
            });
        });
        chat.draft.value = "hello";
        const request = chat.submit();
        scope.stop();
        const snapshot = JSON.stringify({
            messages: chat.messages.value,
            error: chat.error.value,
            pending: chat.pending.value,
        });
        expect(signal.aborted).toBe(true);
        if (kind === "resolve") resolve("late");
        else reject(new Error("late"));
        await request;
        expect(
            JSON.stringify({
                messages: chat.messages.value,
                error: chat.error.value,
                pending: chat.pending.value,
            }),
        ).toBe(snapshot);
    },
);
it("AC-09 disposal clears the mock timer", async () => {
    vi.useFakeTimers();
    const { chat, scope } = setup();
    chat.draft.value = "hi";
    const sent = chat.submit();
    expect(vi.getTimerCount()).toBe(1);
    scope.stop();
    await sent;
    expect(vi.getTimerCount()).toBe(0);
});
