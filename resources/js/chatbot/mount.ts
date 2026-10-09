import { createApp, type App } from "vue";
import ChatbotWidget from "../components/chatbot/ChatbotWidget.vue";
const mounted = new WeakMap<HTMLElement, App>();

export function mountChatbot(): App | undefined {
    const element = document.querySelector<HTMLElement>(
        "[data-chatbot-widget]",
    );
    if (!element) return;
    const existing = mounted.get(element);
    if (existing) return existing;
    const app = createApp(ChatbotWidget);
    app.mount(element);
    mounted.set(element, app);
    app.onUnmount(() => mounted.delete(element));
    return app;
}
