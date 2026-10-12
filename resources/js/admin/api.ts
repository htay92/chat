export type AdminPage = 'dashboard' | 'questions' | 'question-detail' | 'faqs' | 'faq-create' | 'faq-detail' | 'faq-edit';
export interface VisitorQuestion { id: number; question: string; status: 'answered' | 'unanswered'; created_at: string; faq_id?: number | null }
export interface PreparedFaq { id: number; question: string; answer: string; is_active: boolean; created_at: string; updated_at: string; revision: string }
export interface Listing<T> { data: T[]; next_cursor: string | null; previous_cursor: string | null }
export interface Metrics { total_questions: number; originally_unanswered: number; active_faqs: number; inactive_faqs: number }

export class AdminError extends Error {
    constructor(public status: number, public code: string, public errors: Record<string, string[]> = {}) {
        super('The administration request could not be completed.');
    }
}

// One request only. A timed-out write may have committed; never retry automatically.
export async function adminRequest<T = unknown>(path: string, method = 'GET', body?: unknown, signal?: AbortSignal): Promise<T> {
    if (! /^\/(?:data\/[a-z0-9\/-]+|password\/confirm)$/.test(path)) throw new AdminError(0, 'invalid_path');
    const controller = new AbortController();
    const abort = () => controller.abort();
    signal?.addEventListener('abort', abort, { once: true });
    if (signal?.aborted) controller.abort();
    const timer = setTimeout(abort, 15000);
    try {
        const response = await fetch('/admin/chatbot' + path, {
            method, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
            ...(body === undefined ? {} : { body: JSON.stringify(body) }),
        });
        if (response.status === 204) return undefined as T;
        let payload: Record<string, unknown>;
        try { payload = await response.json(); }
        catch { throw new AdminError(response.status, response.ok ? 'invalid_response' : 'service_error'); }
        if (! payload || typeof payload !== 'object' || Array.isArray(payload)) throw new AdminError(0, 'invalid_response');
        if (! response.ok) {
            const errors: Record<string, string[]> = {};
            if (payload.errors && typeof payload.errors === 'object') {
                for (const [field, messages] of Object.entries(payload.errors)) {
                    if (['question', 'answer', 'search', 'password', 'form', 'source_question_id'].includes(field) && Array.isArray(messages)) {
                        errors[field] = messages.filter((m): m is string => typeof m === 'string').slice(0, 3);
                    }
                }
            }
            throw new AdminError(response.status, typeof payload.code === 'string' ? payload.code : 'service_error', errors);
        }
        return payload as T;
    } catch (error) {
        if (error instanceof AdminError) throw error;
        throw new AdminError(0, controller.signal.aborted ? 'interrupted' : 'network_error');
    } finally {
        clearTimeout(timer);
        signal?.removeEventListener('abort', abort);
    }
}
