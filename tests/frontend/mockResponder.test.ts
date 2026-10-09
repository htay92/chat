import { afterEach, expect, it, vi } from "vitest";
import { mockResponder } from "../../resources/js/chatbot/mockResponder";
afterEach(() => vi.useRealTimers());
it("AC-06 resolves only at 500 ms without network calls", async () => {
    vi.useFakeTimers();
    const fetchSpy = vi.spyOn(globalThis, "fetch");
    const xhr = vi.spyOn(XMLHttpRequest.prototype, "open");
    const done = vi.fn();
    const result = mockResponder("hello", new AbortController().signal);
    result.then(done);
    await vi.advanceTimersByTimeAsync(499);
    expect(done).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(1);
    expect(await result).toBe("Mock reply: You said: hello");
    expect(fetchSpy).not.toHaveBeenCalled();
    expect(xhr).not.toHaveBeenCalled();
});
it("AC-09 abort clears timer and rejects safely", async () => {
    vi.useFakeTimers();
    const controller = new AbortController();
    const result = mockResponder("hello", controller.signal);
    const handled = expect(result).rejects.toMatchObject({
        name: "AbortError",
    });
    controller.abort();
    await handled;
    expect(vi.getTimerCount()).toBe(0);
});
it("AC-09 already aborted signals schedule nothing", async () => {
    vi.useFakeTimers();
    const controller = new AbortController();
    controller.abort();
    await expect(
        mockResponder("hello", controller.signal),
    ).rejects.toMatchObject({ name: "AbortError" });
    expect(vi.getTimerCount()).toBe(0);
});
