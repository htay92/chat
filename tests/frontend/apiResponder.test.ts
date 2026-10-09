import { afterEach, expect, it, vi } from "vitest";
import { apiResponder, ApiRequestError } from "../../resources/js/chatbot/apiResponder";
import { createSessionIdentity } from "../../resources/js/chatbot/sessionIdentity";
const ids = { sessionId: "00000000-0000-4000-8000-000000000001", requestId: "00000000-0000-4000-8000-000000000002" };
const reply = { data: { request_id: ids.requestId, status: "unanswered", reply_source: "faq", answer: "Prepared", replayed: true } };
afterEach(() => { vi.unstubAllGlobals(); vi.useRealTimers(); sessionStorage.clear(); });
it("005 AC-03 storage reload reuse, 24h rotation, and denied-storage fallback", () => {
    let now = 0; let sequence = 0;
    const uuid = () => `00000000-0000-4000-8000-${String(++sequence).padStart(12, "0")}`;
    const first = createSessionIdentity({ now: () => now, uuid });
    const id = first.sessionId();
    expect(createSessionIdentity({ now: () => now, uuid }).sessionId()).toBe(id);
    now = 86400000; expect(first.sessionId()).not.toBe(id);
    const denied = createSessionIdentity({ storage: () => { throw Error(); }, uuid });
    expect(denied.sessionId()).toBe(denied.sessionId());
});
it("005 AC-03 fails safely without secure randomness", () => {
    vi.stubGlobal("crypto", undefined);
    expect(() => createSessionIdentity().sessionId()).toThrow();
});
it("005 AC-12 sends exact JSON without ambient credentials and consumes current answer", async () => {
    const fetcher = vi.fn().mockResolvedValue(new Response(JSON.stringify(reply), { status: 200 })); vi.stubGlobal("fetch", fetcher);
    expect(await apiResponder("Hi", new AbortController().signal, ids)).toBe("Prepared");
    const [url, options] = fetcher.mock.calls[0]!;
    expect(url).toBe("/api/chatbot/questions"); expect(options.credentials).toBe("omit");
    expect(JSON.parse(options.body)).toEqual({ question: "Hi", session_id: ids.sessionId, request_id: ids.requestId });
    expect(options.headers).toEqual({ "Content-Type": "application/json", Accept: "application/json" });
});
it.each(["invalid JSON", JSON.stringify({ data: { ...reply.data, request_id: "wrong" } }), JSON.stringify({ data: { ...reply.data, answer: " " } }), JSON.stringify({ data: { ...reply.data, status: "reviewed" } }), JSON.stringify({ data: { ...reply.data, status: ["answered"] } }), JSON.stringify({ data: { ...reply.data, reply_source: ["faq"] } }), JSON.stringify({ data: { ...reply.data, answer: "\u0085" } }), JSON.stringify({ data: { ...reply.data, answer: "a".repeat(4001) } })])("005 AC-12 rejects malformed protocol %s", async (body) => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(new Response(body)));
    await expect(apiResponder("Hi", new AbortController().signal, ids)).rejects.toThrow();
});
it("005 AC-12 parses Retry-After without exposing response payload", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(new Response("sensitive internals", { status: 429, headers: { "Retry-After": "12" } })));
    try { await apiResponder("Hi", new AbortController().signal, ids); expect.fail(); }
    catch (e) { expect(e).toBeInstanceOf(ApiRequestError); expect((e as ApiRequestError).retryAfter).toBe(12); expect((e as Error).message).not.toContain("sensitive"); }
});
it("005 AC-12 timeout and external abort clean timers and reject late results", async () => {
    vi.useFakeTimers(); let resolve!: (r: Response) => void;
    vi.stubGlobal("fetch", vi.fn(() => new Promise<Response>((r) => { resolve = r; })));
    const promise = apiResponder("Hi", new AbortController().signal, ids);
    const rejected = expect(promise).rejects.toThrow(); await vi.advanceTimersByTimeAsync(15000); await rejected;
    expect(vi.getTimerCount()).toBe(0); resolve(new Response(JSON.stringify(reply)));
    const controller = new AbortController(); const pending = apiResponder("Hi", controller.signal, ids);
    controller.abort(); await expect(pending).rejects.toThrow(); expect(vi.getTimerCount()).toBe(0);
});
