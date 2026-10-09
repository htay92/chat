import { effectScope } from "vue";
import { mount, flushPromises } from "@vue/test-utils";
import { afterEach, expect, it, vi } from "vitest";
import { useChatbot } from "../../resources/js/composables/useChatbot";
import { ApiRequestError } from "../../resources/js/chatbot/apiResponder";
import ChatbotWidget from "../../resources/js/components/chatbot/ChatbotWidget.vue";
const scopes: ReturnType<typeof effectScope>[] = [];
afterEach(() => { scopes.forEach((s) => s.stop()); scopes.length = 0; vi.unstubAllGlobals(); vi.useRealTimers(); sessionStorage.clear(); document.body.innerHTML = ""; });
it("005 AC-12 retries retain exchange IDs across identity rotation, new sends get new IDs", async () => {
    vi.useFakeTimers();
    const responder = vi.fn().mockRejectedValueOnce(new Error()).mockResolvedValue("prepared");
    const scope = effectScope(); scopes.push(scope); const chat = scope.run(() => useChatbot(responder))!;
    chat.draft.value = "Hi"; await chat.submit();
    const original = responder.mock.calls[0]?.[2]; expect(original).toBeDefined();
    vi.setSystemTime(Date.now() + 86400001); await chat.retry();
    expect(responder.mock.calls[1]?.[2]).toEqual(original); expect(chat.messages.value).toHaveLength(2);
    chat.draft.value = "New"; await chat.submit();
    expect(responder.mock.calls[2]?.[2].requestId).not.toBe(original.requestId);
    expect(responder.mock.calls[2]?.[2].sessionId).not.toBe(original.sessionId);
});
it("005 AC-12 rate-limit wait disables Retry without automatic resubmission", async () => {
    vi.useFakeTimers(); const responder = vi.fn().mockRejectedValueOnce(new ApiRequestError(429, 3)).mockResolvedValue("ok");
    const scope = effectScope(); scopes.push(scope); const chat = scope.run(() => useChatbot(responder))!;
    chat.draft.value = "Hi"; await chat.submit();
    expect(chat.retryDisabled.value).toBe(true); expect(chat.error.value).toContain("3 seconds");
    await chat.retry(); expect(responder).toHaveBeenCalledTimes(1);
    await vi.advanceTimersByTimeAsync(3000); expect(chat.retryDisabled.value).toBe(false); expect(responder).toHaveBeenCalledTimes(1);
    await chat.retry(); expect(responder).toHaveBeenCalledTimes(2); expect(chat.messages.value).toHaveLength(2);
});
it("005 AC-09/12 production default uses FAQ API and preserves decorative rows and labels", async () => {
    vi.stubGlobal("fetch", vi.fn(async (_url, options) => {
        const body = JSON.parse(options.body); return new Response(JSON.stringify({ data: { request_id: body.request_id, status: "unanswered", reply_source: "fallback", answer: "<script>literal FAQ</script>", replayed: false } }), { status: 201 });
    }));
    const w = mount(ChatbotWidget, { attachTo: document.body });
    try {
        await w.get('[aria-label="Open chat"]').trigger("click");
        expect(w.get(".chat-notice").text()).toBe("Demo FAQ answers. Questions are normally deleted within 91 days to improve FAQs. Don’t include personal or sensitive information.");
        await w.get("textarea").setValue("Hi"); await w.get("form").trigger("submit"); await flushPromises();
        const rows = w.get('[role="log"]').findAll("li"); expect(rows).toHaveLength(2);
        expect(rows[0]!.get("strong").text()).toBe("You"); expect(rows[1]!.get("strong").text()).toBe("Assistant");
        expect(w.findAll(".chat-avatar")).toHaveLength(2); expect(w.find("script").exists()).toBe(false);
        expect(rows[1]!.text()).toContain("<script>literal FAQ</script>"); expect(fetch).toHaveBeenCalledTimes(1);
    } finally { w.unmount(); }
});
it("005 AC-02 shared Unicode edge-whitespace policy prevents blank network requests", async () => {
    const responder = vi.fn(); const scope = effectScope(); scopes.push(scope); const chat = scope.run(() => useChatbot(responder))!;
    chat.draft.value = "\u0085\uFEFF"; await chat.submit(); expect(responder).not.toHaveBeenCalled(); expect(chat.messages.value).toHaveLength(0);
});
