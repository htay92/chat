import { afterEach, expect, it } from "vitest";
import { mountChatbot } from "../../resources/js/chatbot/mount";
afterEach(() => {
    document.body.innerHTML = "";
});
it("AC-01 absent mount target is harmless", () => {
    expect(mountChatbot()).toBeUndefined();
    expect(document.querySelector('[role="dialog"]')).toBeNull();
});
it("AC-01 mounts one widget without replacing website content", () => {
    document.body.innerHTML =
        '<main><a href="/existing">Existing content</a></main><div data-chatbot-widget></div>';
    const app = mountChatbot();
    expect(document.querySelector("main")?.textContent).toBe(
        "Existing content",
    );
    expect(document.querySelector("a")?.getAttribute("href")).toBe("/existing");
    expect(document.querySelectorAll('[role="dialog"]')).toHaveLength(1);
    expect(mountChatbot()).toBe(app);
    expect(document.querySelectorAll('[role="dialog"]')).toHaveLength(1);
    app?.unmount();
    expect(document.querySelector('[role="dialog"]')).toBeNull();
});
