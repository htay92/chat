import { createApp, type App } from 'vue';
import AdminApp from './AdminApp.vue';
import type { AdminPage } from './api';
import './admin.css';

const mounted = new WeakMap<HTMLElement, App>();
export function mountAdmin(): App | undefined {
    const element = document.querySelector<HTMLElement>('[data-chatbot-admin]');
    if (!element) return;
    const existing = mounted.get(element);
    if (existing) return existing;
    const app = createApp(AdminApp, {
        page: element.dataset.page as AdminPage,
        resourceId: element.dataset.resourceId ? Number(element.dataset.resourceId) : undefined,
        sourceQuestionId: element.dataset.sourceQuestionId ? Number(element.dataset.sourceQuestionId) : undefined,
    });
    app.mount(element);
    mounted.set(element, app);
    app.onUnmount(() => mounted.delete(element));
    return app;
}
