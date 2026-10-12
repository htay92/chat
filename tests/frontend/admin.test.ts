import { describe, it, expect, vi, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { nextTick } from 'vue';
import AdminApp from '../../resources/js/admin/AdminApp.vue';
import { adminRequest, AdminError } from '../../resources/js/admin/api';

afterEach(() => vi.unstubAllGlobals());
const answer = (data: unknown, status=200) => new Response(JSON.stringify(data), { status, headers: { 'Content-Type': 'application/json' }});

describe('Admin API security and state', () => {
    it('uses same-origin CSRF, keeps search in the body and never retries a write', async () => {
        document.head.innerHTML = '<meta name="csrf-token" content="token">';
        const fetch = vi.fn().mockResolvedValue(answer({ code: 'csrf_expired' }, 419)); vi.stubGlobal('fetch', fetch);
        await expect(adminRequest('/data/faqs', 'POST', { question: 'private' })).rejects.toMatchObject({ status: 419 });
        expect(fetch).toHaveBeenCalledTimes(1);
        expect(fetch.mock.calls[0][0]).toBe('/admin/chatbot/data/faqs');
        expect(fetch.mock.calls[0][1]).toMatchObject({ credentials: 'same-origin', headers: expect.objectContaining({ 'X-CSRF-TOKEN': 'token' }) });
    });

    it('shows dashboard loading then accessible metric cards', async () => {
        let resolve!: (v: Response) => void;
        vi.stubGlobal('fetch', vi.fn(() => new Promise<Response>(r => { resolve=r; })));
        const w=mount(AdminApp, { props: { page: 'dashboard' } });
        expect(w.get('[role="status"]').text()).toContain('Loading');
        resolve(answer({ data: { total_questions: 8, originally_unanswered: 5, active_faqs: 2, inactive_faqs: 3 } })); await flushPromises();
        expect(w.text()).toContain('Originally unanswered'); expect(w.findAll('.admin-metric')).toHaveLength(4);
        w.unmount();
    });

    it('renders question text literally and supports empty/filter states', async () => {
        const fetch=vi.fn().mockResolvedValueOnce(answer({ data: [{ id: 1, question: '<script>private</script>', status: 'unanswered', created_at: '2026-10-09T00:00:00Z' }], next_cursor: null, previous_cursor: null }))
            .mockResolvedValueOnce(answer({ data: [], next_cursor: null, previous_cursor: null })); vi.stubGlobal('fetch',fetch);
        const w=mount(AdminApp,{props:{page:'questions'}}); await flushPromises();
        expect(w.find('script').exists()).toBe(false); expect(w.text()).toContain('<script>private</script>');
        await w.get('#admin-search').setValue('private'); await w.get('form[data-search]').trigger('submit'); await flushPromises();
        expect(w.text()).toContain('No questions found'); expect(fetch.mock.calls[1][1].body).toContain('private');
        w.unmount();
    });

    it('preserves edit input on conflict and offers reload instead of overwriting', async () => {
        const fetch=vi.fn().mockResolvedValueOnce(answer({data:{id:1,question:'Hi',answer:'Old',is_active:true,revision:'a'.repeat(64)}}))
            .mockResolvedValueOnce(answer({code:'stale_revision'},409)); vi.stubGlobal('fetch',fetch);
        const w=mount(AdminApp,{props:{page:'faq-edit',resourceId:1}}); await flushPromises();
        await w.get('#faq-answer').setValue('Unsaved'); await w.get('form[data-faq]').trigger('submit'); await flushPromises();
        expect((w.get('#faq-answer').element as HTMLTextAreaElement).value).toBe('Unsaved');
        expect(w.text()).toContain('Reload current version'); expect(fetch).toHaveBeenCalledTimes(2);
        w.unmount();
    });

    it('prefills an independent draft with blank answer and a privacy warning', async () => {
        vi.stubGlobal('fetch',vi.fn().mockResolvedValue(answer({data:{id:2,question:'Visitor text',status:'unanswered'}})));
        const w=mount(AdminApp,{props:{page:'faq-create',sourceQuestionId:2}}); await flushPromises();
        expect((w.get('#faq-question').element as HTMLTextAreaElement).value).toBe('Visitor text');
        expect((w.get('#faq-answer').element as HTMLTextAreaElement).value).toBe(''); expect(w.text()).toContain('personal');
        w.unmount();
    });

    it('reports authorization and validation errors without clearing unsaved input', async () => {
        const fetch=vi.fn().mockResolvedValueOnce(answer({errors:{answer:['Enter an answer.']}},422)).mockResolvedValueOnce(answer({code:'unauthenticated'},401)); vi.stubGlobal('fetch',fetch);
        const w=mount(AdminApp,{props:{page:'faq-create'}}); await flushPromises();
        await w.get('#faq-question').setValue('Hi'); await w.get('#faq-answer').setValue('Draft');
        await w.get('form[data-faq]').trigger('submit'); await flushPromises(); expect(w.get('#answer-error').text()).toContain('Enter an answer');
        await w.get('form[data-faq]').trigger('submit'); await flushPromises(); expect(w.text()).toContain('Sign in again');
        expect((w.get('#faq-answer').element as HTMLTextAreaElement).value).toBe('Draft'); w.unmount();
    });

    it('announces successful saves and prevents duplicate submissions while pending', async () => {
        let resolve!: (v: Response) => void;
        const fetch = vi.fn(() => new Promise<Response>(r => { resolve = r; })); vi.stubGlobal('fetch', fetch);
        const w = mount(AdminApp, { props: { page: 'faq-create' } }); await flushPromises();
        await w.get('#faq-question').setValue('Question'); await w.get('#faq-answer').setValue('Prepared');
        await w.get('form[data-faq]').trigger('submit'); await w.get('form[data-faq]').trigger('submit');
        expect(fetch).toHaveBeenCalledTimes(1); expect(w.get('form[data-faq] button[type="submit"]').attributes('disabled')).toBeDefined();
        resolve(answer({ data: { id: 1, question: 'Question', answer: 'Prepared', is_active: false, revision: 'a'.repeat(64) } })); await flushPromises();
        expect(w.text()).toContain('FAQ saved as inactive'); expect(w.text()).toContain('Review and activate'); w.unmount();
    });

    it('previews activation, keeps background inert, traps focus and cancels without writing', async () => {
        const fetch = vi.fn().mockResolvedValue(answer({ data: { id: 1, question: 'Q', answer: '<img src=x onerror=alert(1)>', is_active: false, revision: 'a'.repeat(64), created_at: '2026-10-09T00:00:00Z', updated_at: '2026-10-09T00:00:00Z' } })); vi.stubGlobal('fetch', fetch);
        const w = mount(AdminApp, { props: { page: 'faq-detail', resourceId: 1 }, attachTo: document.body }); await flushPromises();
        const activate = w.findAll('button').find(b => b.text() === 'Activate FAQ')!;
        (activate.element as HTMLElement).focus(); await activate.trigger('click'); await nextTick();
        const modal = w.get('[role="dialog"]');
        expect(modal.text()).toContain('<img src=x onerror=alert(1)>'); expect(w.find('img').exists()).toBe(false);
        expect(w.get('.admin-workspace').attributes('inert')).toBeDefined();
        const cancel = modal.findAll('button').find(b => b.text() === 'Cancel')!;
        (cancel.element as HTMLElement).focus(); await cancel.trigger('keydown', { key: 'Tab' });
        expect(document.activeElement).toBe(modal.get('button[type="submit"]').element);
        await modal.trigger('keydown', { key: 'Escape' }); await nextTick();
        expect(w.find('[role="dialog"]').exists()).toBe(false); expect(document.activeElement).toBe(activate.element);
        expect(fetch).toHaveBeenCalledTimes(1); w.unmount();
    });

    it('requires password confirmation before deleting and clears credentials on completion', async () => {
        const fetch = vi.fn().mockResolvedValueOnce(answer({ data: { id: 2, question: 'Private', status: 'unanswered', created_at: '2026-10-09T00:00:00Z' } }))
            .mockResolvedValueOnce(new Response(null, { status: 204 })).mockResolvedValueOnce(new Response(null, { status: 204 })); vi.stubGlobal('fetch', fetch);
        const w = mount(AdminApp, { props: { page: 'question-detail', resourceId: 2 } }); await flushPromises();
        await w.findAll('button').find(b => b.text() === 'Delete question')!.trigger('click');
        await w.get('#confirm-password').setValue('hidden-secret'); await w.get('[role="dialog"] form').trigger('submit'); await flushPromises();
        expect(fetch.mock.calls[1][0]).toBe('/admin/chatbot/password/confirm'); expect(fetch.mock.calls[2][0]).toBe('/admin/chatbot/data/questions/2');
        expect(JSON.parse(fetch.mock.calls[2][1].body)).toEqual({ confirmed: true });
        expect(w.find('#confirm-password').exists()).toBe(false); expect(w.text()).toContain('Record deleted'); w.unmount();
    });

    it('makes conversion after a vanished source explicit and preserves the draft', async () => {
        const fetch = vi.fn().mockResolvedValueOnce(answer({ data: { id: 2, question: 'Source text', status: 'unanswered' } }))
            .mockResolvedValueOnce(answer({ code: 'not_found' }, 404)).mockResolvedValueOnce(answer({ data: { id: 3, question: 'Source text', answer: 'Draft', is_active: false, revision: 'a'.repeat(64) } })); vi.stubGlobal('fetch', fetch);
        const w = mount(AdminApp, { props: { page: 'faq-create', sourceQuestionId: 2 } }); await flushPromises();
        await w.get('#faq-answer').setValue('Draft'); await w.get('form[data-faq]').trigger('submit'); await flushPromises();
        expect(w.text()).toContain('standalone draft'); expect((w.get('#faq-answer').element as HTMLTextAreaElement).value).toBe('Draft');
        await w.findAll('button').find(b => b.text() === 'Continue as standalone draft')!.trigger('click');
        await w.get('form[data-faq]').trigger('submit'); await flushPromises();
        expect(JSON.parse(fetch.mock.calls[2][1].body)).not.toHaveProperty('source_question_id'); w.unmount();
    });

    it('closes the responsive sidebar with Escape and restores its trigger focus', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(answer({ data: { total_questions: 0, originally_unanswered: 0, active_faqs: 0, inactive_faqs: 0 } })));
        const w = mount(AdminApp, { props: { page: 'dashboard' }, attachTo: document.body }); await flushPromises();
        const button = w.get('.admin-menu-button'); await button.trigger('click'); expect(button.attributes('aria-expanded')).toBe('true');
        await w.get('.admin-layout').trigger('keydown', { key: 'Escape' }); await nextTick();
        expect(button.attributes('aria-expanded')).toBe('false'); expect(document.activeElement).toBe(button.element); w.unmount();
    });

    it('never creates a replacement FAQ when an edit record fails to load', async () => {
        const fetch = vi.fn().mockResolvedValue(answer({ code: 'not_found' }, 404)); vi.stubGlobal('fetch', fetch);
        const w = mount(AdminApp, { props: { page: 'faq-edit', resourceId: 999 } }); await flushPromises();
        await w.get('#faq-question').setValue('Preserved'); await w.get('#faq-answer').setValue('Draft');
        await w.get('form[data-faq]').trigger('submit'); await flushPromises();
        expect(fetch).toHaveBeenCalledTimes(1); expect(w.get('form[data-faq] button[type="submit"]').attributes('disabled')).toBeDefined(); w.unmount();
    });
});
