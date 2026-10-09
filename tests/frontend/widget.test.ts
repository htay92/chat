import { mount, flushPromises, type VueWrapper } from "@vue/test-utils";
import { afterEach, expect, it, vi } from "vitest";
import ChatbotWidget from "../../resources/js/components/chatbot/ChatbotWidget.vue";
const wrappers: VueWrapper[] = [];
function setup(responder?: (m: string, s: AbortSignal) => Promise<string>) {
    const wrapper = mount(ChatbotWidget, {
        attachTo: document.body,
        props: responder ? { responder } : {},
    });
    wrappers.push(wrapper);
    return wrapper;
}
afterEach(() => {
    wrappers.forEach((w) => w.unmount());
    wrappers.length = 0;
    document.body.innerHTML = "";
    vi.useRealTimers();
});
it("AC-01/02/10 starts closed, opens accessible dialog, and restores launcher focus", async () => {
    const w = setup();
    const launcher = w.get("button");
    expect(launcher.attributes("aria-expanded")).toBe("false");
    expect(w.get('[role="dialog"]').isVisible()).toBe(false);
    await launcher.trigger("click");
    const panel = w.get('[role="dialog"]');
    expect(panel.isVisible()).toBe(true);
    expect(panel.attributes("aria-modal")).toBeUndefined();
    expect(launcher.attributes("aria-controls")).toBe(panel.attributes("id"));
    expect(w.text()).toContain("Demo only — responses are mocked.");
    expect(document.activeElement).toBe(w.get("textarea").element);
    await panel.trigger("keydown", { key: "Escape" });
    expect(panel.isVisible()).toBe(false);
    expect(document.activeElement).toBe(launcher.element);
});
it("AC-02 preserves draft while closed and resets on a fresh mount", async () => {
    const w = setup();
    await w.get("button").trigger("click");
    await w.get("textarea").setValue("draft");
    await w.get('[aria-label="Close chat"]').trigger("click");
    await w.get('[aria-label="Open chat"]').trigger("click");
    expect((w.get("textarea").element as HTMLTextAreaElement).value).toBe(
        "draft",
    );
    const fresh = setup();
    expect((fresh.get("textarea").element as HTMLTextAreaElement).value).toBe(
        "",
    );
});
it("AC-07/10 reopening pending focuses close; completion never steals focus", async () => {
    let resolve!: (s: string) => void;
    const w = setup(
        () =>
            new Promise((r) => {
                resolve = r;
            }),
    );
    await w.get("button").trigger("click");
    await w.get("textarea").setValue("hi");
    await w.get("form").trigger("submit");
    expect((w.get("textarea").element as HTMLTextAreaElement).disabled).toBe(
        true,
    );
    expect(document.activeElement).toBe(w.get('[role="dialog"]').element);
    await w.get('[aria-label="Close chat"]').trigger("click");
    await w.get('[aria-label="Open chat"]').trigger("click");
    expect(document.activeElement).toBe(
        w.get('[aria-label="Close chat"]').element,
    );
    resolve("done");
    await flushPromises();
    expect(document.activeElement).toBe(
        w.get('[aria-label="Close chat"]').element,
    );
    expect(w.text()).toContain("done");
});
it("AC-02 Escape on launcher does not close panel", async () => {
    const w = setup();
    await w.get("button").trigger("click");
    await w
        .get('[aria-label="Open chat"]')
        .trigger("keydown", { key: "Escape" });
    expect(w.get('[role="dialog"]').isVisible()).toBe(true);
});
it("AC-03 Enter sends, Shift+Enter and composing Enter do not", async () => {
    const responder = vi.fn().mockResolvedValue("reply");
    const w = setup(responder);
    await w.get("button").trigger("click");
    const input = w.get("textarea");
    await input.setValue("hello\nworld");
    await input.trigger("keydown", { key: "Enter", shiftKey: true });
    await input.trigger("keydown", { key: "Enter", isComposing: true });
    expect(responder).not.toHaveBeenCalled();
    await input.trigger("keydown", { key: "Enter" });
    await flushPromises();
    expect(responder).toHaveBeenCalledTimes(1);
    expect(responder.mock.calls[0]?.[0]).toBe("hello\nworld");
});
it("AC-03/04 validation is associated and clears on edit", async () => {
    const responder = vi.fn();
    const w = setup(responder);
    await w.get("button").trigger("click");
    await w.get("textarea").setValue(" ");
    await w.get("form").trigger("submit");
    const input = w.get("textarea");
    expect(input.attributes("aria-invalid")).toBe("true");
    const id = input.attributes("aria-describedby");
    expect(w.get(`#${id}`).text()).toBe("Please enter a message.");
    expect(document.activeElement).toBe(input.element);
    expect(responder).not.toHaveBeenCalled();
    await input.setValue("ok");
    expect(input.attributes("aria-invalid")).toBe("false");
});
it("AC-05/11 plain-text transcript has speaker labels and polite live semantics", async () => {
    const attack = "<img src=x onerror=alert(1)>";
    const w = setup(async () => attack);
    await w.get("button").trigger("click");
    expect(w.text()).toContain("Type a message to start.");
    await w.get("textarea").setValue(attack);
    await w.get("form").trigger("submit");
    await flushPromises();
    const log = w.get('[role="log"]');
    expect(log.attributes("aria-live")).toBe("polite");
    expect(log.text()).toContain("You");
    expect(log.text()).toContain("Assistant");
    expect(log.text()).toContain(attack);
    expect(w.find("img").exists()).toBe(false);
});
it("AC-08/11 responder failure is an alert, re-enables input, and Retry preserves user message", async () => {
    const responder = vi
        .fn()
        .mockRejectedValueOnce(new Error())
        .mockResolvedValue("fixed");
    const w = setup(responder);
    await w.get("button").trigger("click");
    await w.get("textarea").setValue("hi");
    await w.get("form").trigger("submit");
    await flushPromises();
    expect(w.get('[role="alert"]').text()).toContain("Could not generate");
    expect((w.get("textarea").element as HTMLTextAreaElement).disabled).toBe(
        false,
    );
    const retry = w.findAll("button").find((b) => b.text() === "Retry")!;
    await retry.trigger("click");
    await flushPromises();
    expect(w.get('[role="log"]').findAll("li")).toHaveLength(2);
    expect(w.find('[role="alert"]').exists()).toBe(false);
});
it("AC-07/09 pending guards keyboard and unmount clears timers", async () => {
    vi.useFakeTimers();
    const clear = vi.spyOn(globalThis, "clearTimeout");
    const w = setup();
    await w.get("button").trigger("click");
    await w.get("textarea").setValue("hi");
    await w.get("form").trigger("submit");
    expect(w.get('[role="status"]').text()).toBe("Replying…");
    await w.get("textarea").trigger("keydown", { key: "Enter" });
    expect(w.get('[role="log"]').findAll("li")).toHaveLength(1);
    w.unmount();
    wrappers.splice(wrappers.indexOf(w), 1);
    await flushPromises();
    expect(clear).toHaveBeenCalled();
});
