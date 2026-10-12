import { afterEach, describe, expect, it, vi } from 'vitest';
import { mountAdmin } from '../../resources/js/admin/mount';
import { mountChatbot } from '../../resources/js/chatbot/mount';

afterEach(() => { document.body.innerHTML = ''; vi.unstubAllGlobals(); });
describe('Admin and public mount boundaries', () => {
    it('mounts admin once without mounting a public widget and safely unmounts a pending read', () => {
        document.body.innerHTML = '<div data-chatbot-admin data-page="dashboard"></div>';
        const fetch = vi.fn((_url, options) => new Promise((_resolve, reject) => options.signal.addEventListener('abort', () => reject(new DOMException('Aborted', 'AbortError')))));
        vi.stubGlobal('fetch', fetch);
        const app = mountAdmin(); expect(app).toBeDefined(); expect(mountAdmin()).toBe(app); expect(mountChatbot()).toBeUndefined();
        app!.unmount(); expect(fetch.mock.calls[0][1].signal.aborted).toBe(true);
    });
    it('does not mount administration on the public host page', () => {
        document.body.innerHTML = '<div data-chatbot-widget></div>';
        expect(mountAdmin()).toBeUndefined();
        const app = mountChatbot(); expect(app).toBeDefined(); app!.unmount();
    });
});
