<script setup lang="ts">
import { computed, nextTick, onMounted, onBeforeUnmount, ref } from 'vue';
import { adminRequest, AdminError, type AdminPage, type PreparedFaq, type VisitorQuestion, type Listing, type Metrics } from './api';

const props = defineProps<{ page: AdminPage; resourceId?: number; sourceQuestionId?: number }>();
const loading = ref(true), busy = ref(false), message = ref(''), failure = ref<AdminError | null>(null);
const metrics = ref<Metrics | null>(null), rows = ref<(PreparedFaq | VisitorQuestion)[]>([]);
const faq = ref<PreparedFaq | null>(null), question = ref<VisitorQuestion | null>(null);
const questionText = ref(''), answerText = ref(''), search = ref(''), filter = ref(''), perPage = ref(25);
const nextCursor = ref<string | null>(null), previousCursor = ref<string | null>(null);
const source = ref(props.sourceQuestionId), sidebarOpen = ref(false), navButton = ref<HTMLButtonElement | null>(null);
const dialog = ref<'activate' | 'deactivate' | 'delete-faq' | 'delete-question' | null>(null);
const dialogElement = ref<HTMLDivElement | null>(null), password = ref('');
let returnFocus: HTMLElement | null = null;
let active = true;
let controller = new AbortController();
const isList = computed(() => props.page === 'questions' || props.page === 'faqs');
const isEditor = computed(() => props.page === 'faq-create' || props.page === 'faq-edit');
const titles: Record<AdminPage, string> = { dashboard: 'Overview', questions: 'Visitor questions', 'question-detail': 'Question details', faqs: 'Prepared FAQs', 'faq-create': 'Create FAQ draft', 'faq-detail': 'FAQ details', 'faq-edit': 'Edit FAQ' };
const title = computed(() => titles[props.page]);
const expired = computed(() => failure.value?.status === 401 || failure.value?.status === 419);
const errorMessage = computed(() => {
    const e = failure.value;
    if (!e) return '';
    if (expired.value) return 'Your session has expired. Sign in again in a new tab, then reload when your draft is safe.';
    if (e.status === 403) return 'You do not have permission to access this information.';
    if (e.status === 409) return e.code === 'duplicate_question' ? 'An FAQ with this normalized question already exists. Search FAQs before creating another.' : 'This FAQ changed. Your unsaved draft is preserved. Reload the current version before continuing.';
    if (e.status === 404) return 'This record is no longer available. Refresh the list or deliberately create a standalone draft.';
    if (e.status === 422) return 'Check the highlighted fields and try again.';
    if (e.status === 429) return 'Too many requests. Wait a minute before trying again.';
    if (e.status === 428) return 'Confirm your password again before deleting.';
    return 'Could not complete the request. A write may have been saved; check the current record before trying again.';
});
function resetFeedback() { failure.value = null; message.value = ''; }
function fail(error: unknown) { if (active) failure.value = error instanceof AdminError ? error : new AdminError(0, 'invalid_response'); }
function date(value: string) { const time = new Date(value); return Number.isNaN(time.getTime()) ? 'Unknown time' : time.toISOString().replace('T', ' ').replace('.000Z', ' UTC'); }
function faqRow(row: PreparedFaq | VisitorQuestion): row is PreparedFaq { return 'is_active' in row; }
function token() { return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''; }

async function load(cursor?: string | null) {
    if (busy.value) return;
    controller.abort(); controller = new AbortController();
    const signal = controller.signal;
    loading.value = true; resetFeedback();
    try {
        if (props.page === 'dashboard') {
            const response = await adminRequest<{ data: Metrics }>('/data/metrics', 'GET', undefined, signal);
            if (!signal.aborted) metrics.value = response.data;
        } else if (isList.value) {
            const body: Record<string, unknown> = { search: search.value, per_page: perPage.value };
            if (filter.value) body[props.page === 'faqs' ? 'active' : 'status'] = filter.value;
            if (cursor) body.cursor = cursor;
            const response = await adminRequest<Listing<PreparedFaq | VisitorQuestion>>(`/data/${props.page}/search`, 'POST', body, signal);
            if (!Array.isArray(response.data)) throw new AdminError(0, 'invalid_response');
            if (!signal.aborted) { rows.value = response.data; nextCursor.value = response.next_cursor; previousCursor.value = response.previous_cursor; }
        } else if (props.page === 'question-detail' || (props.page === 'faq-create' && source.value)) {
            const response = await adminRequest<{ data: VisitorQuestion }>(`/data/questions/${props.resourceId ?? source.value}`, 'GET', undefined, signal);
            if (!signal.aborted) { question.value = response.data; if (props.page === 'faq-create') { questionText.value = response.data.question; answerText.value = ''; } }
        } else if (props.page !== 'faq-create') {
            const response = await adminRequest<{ data: PreparedFaq }>(`/data/faqs/${props.resourceId}`, 'GET', undefined, signal);
            if (!signal.aborted) { faq.value = response.data; questionText.value = response.data.question; answerText.value = response.data.answer; }
        }
    } catch (error) { if (!signal.aborted) fail(error); }
    finally { if (!signal.aborted) loading.value = false; }
}
async function save() {
    if (busy.value || loading.value || failure.value?.code === 'stale_revision' || (props.page === 'faq-edit' && !faq.value)) return;
    busy.value = true; resetFeedback();
    try {
        const body: Record<string, unknown> = { question: questionText.value, answer: answerText.value };
        if (faq.value) body.revision = faq.value.revision;
        else if (source.value) body.source_question_id = source.value;
        const response = await adminRequest<{ data: PreparedFaq }>(faq.value ? `/data/faqs/${faq.value.id}` : '/data/faqs', faq.value ? 'PATCH' : 'POST', body, controller.signal);
        if (active) { faq.value = response.data; message.value = response.data.is_active ? 'FAQ saved. Content was unchanged; it remains active.' : 'FAQ saved as inactive. Review its content before activation.'; }
    } catch (error) { fail(error); }
    finally { if (active) busy.value = false; }
}
async function openDialog(action: NonNullable<typeof dialog.value>) {
    if (busy.value || loading.value) return;
    returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    password.value = ''; resetFeedback(); dialog.value = action;
    await nextTick(); dialogElement.value?.querySelector<HTMLElement>('input, button')?.focus();
}
function closeDialog() {
    if (busy.value) return;
    dialog.value = null; password.value = ''; returnFocus?.focus();
}
function trap(event: KeyboardEvent) {
    if (event.key === 'Escape') { event.preventDefault(); closeDialog(); return; }
    if (event.key !== 'Tab') return;
    const elements = Array.from(dialogElement.value?.querySelectorAll<HTMLElement>('button:not(:disabled), input:not(:disabled), a[href]') ?? []);
    if (!elements.length) { event.preventDefault(); dialogElement.value?.focus(); return; }
    const first = elements[0], last = elements[elements.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
}
async function confirmAction() {
    if (busy.value || !dialog.value) return;
    const action = dialog.value;
    busy.value = true; resetFeedback();
    try {
        if (action.startsWith('delete')) {
            await adminRequest('/password/confirm', 'POST', { password: password.value }, controller.signal);
            password.value = '';
            await adminRequest(action === 'delete-faq' ? `/data/faqs/${faq.value?.id}` : `/data/questions/${question.value?.id}`, 'DELETE', action === 'delete-faq' ? { revision: faq.value?.revision, confirmed: true } : { confirmed: true }, controller.signal);
            if (active) { faq.value = null; question.value = null; message.value = 'Record deleted. Retained question history is never reclassified.'; }
        } else {
            const response = await adminRequest<{ data: PreparedFaq }>(`/data/faqs/${faq.value?.id}/activation`, 'POST', { revision: faq.value?.revision, is_active: action === 'activate', confirmed: true }, controller.signal);
            if (active) { faq.value = response.data; message.value = action === 'activate' ? 'FAQ activated. Matching questions can now receive this answer.' : 'FAQ deactivated.'; }
        }
        if (active) { dialog.value = null; await nextTick(); returnFocus?.isConnected ? returnFocus.focus() : document.querySelector<HTMLElement>('#admin-main')?.focus(); }
    } catch (error) { fail(error); }
    finally { password.value = ''; if (active) busy.value = false; }
}
async function closeSidebar() { sidebarOpen.value = false; await nextTick(); navButton.value?.focus(); }
onMounted(() => { void load(); });
onBeforeUnmount(() => { active = false; controller.abort(); password.value = ''; });
</script>

<template>
    <div class="admin-layout" @keydown.esc="sidebarOpen && closeSidebar()">
        <a class="admin-skip" href="#admin-main" :inert="!!dialog">Skip to content</a>
        <aside id="admin-sidebar" class="admin-sidebar" :class="{ 'is-open': sidebarOpen }" :inert="!!dialog">
            <a class="admin-brand" href="/admin/chatbot"><span class="admin-brand-icon" aria-hidden="true">Q</span> Chatbot admin</a>
            <p class="admin-nav-label">WORKSPACE</p>
            <nav aria-label="Chatbot administration">
                <a href="/admin/chatbot" :aria-current="page === 'dashboard' ? 'page' : undefined">Overview</a>
                <a href="/admin/chatbot/questions" :aria-current="page.startsWith('question') ? 'page' : undefined">Visitor questions</a>
                <a href="/admin/chatbot/faqs" :aria-current="page.startsWith('faq') ? 'page' : undefined">Prepared FAQs</a>
            </nav>
            <div class="admin-sidebar-footer"><p>Prepared answers. No AI.</p><a href="/">Visit website</a></div>
        </aside>
        <div class="admin-workspace" :inert="!!dialog">
            <header class="admin-topbar">
                <button ref="navButton" class="admin-menu-button" type="button" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen" @click="sidebarOpen = !sidebarOpen">Menu</button>
                <span>Chatbot workspace</span>
                <form action="/admin/logout" method="post"><input type="hidden" name="_token" :value="token()"><button class="admin-button secondary" type="submit">Sign out</button></form>
            </header>
            <main id="admin-main" tabindex="-1" class="admin-content">
                <div class="admin-page-heading"><div><p class="admin-eyebrow">CHATBOT ADMINISTRATION</p><h1>{{ title }}</h1><p class="admin-muted">Manage prepared answers and review retained visitor questions.</p></div><a v-if="page === 'faqs'" class="admin-button" href="/admin/chatbot/faqs/create">Create FAQ</a></div>
                <p v-if="loading || busy" role="status" aria-live="polite" class="admin-notice">{{ busy ? 'Saving changes…' : 'Loading…' }}</p>
                <p v-if="message" role="status" class="admin-notice success">{{ message }}</p>
                <div v-if="failure" role="alert" class="admin-notice error"><p>{{ errorMessage }}</p><p v-for="(messages, field) in failure.errors" v-show="!['question', 'answer', 'password'].includes(String(field))" :key="field">{{ messages.join(' ') }}</p><a v-if="expired" href="/admin/login" target="_blank" rel="noopener">Sign in again</a><button v-if="failure.code === 'stale_revision'" type="button" class="admin-button secondary" @click="load()">Reload current version (discard unsaved draft)</button><button v-if="failure.status === 404 && source" type="button" class="admin-button secondary" @click="source = undefined; failure = null">Continue as standalone draft</button><button v-if="!isEditor && !dialog && !expired" type="button" class="admin-button secondary" @click="load()">Refresh</button></div>

                <template v-if="page === 'dashboard' && metrics">
                    <div class="admin-metrics"><article v-for="(card, index) in [{ label: 'Collected questions', value: metrics.total_questions }, { label: 'Originally unanswered', value: metrics.originally_unanswered }, { label: 'Active FAQs', value: metrics.active_faqs }, { label: 'Inactive / draft FAQs', value: metrics.inactive_faqs }]" :key="index" class="admin-metric"><p>{{ card.label }}</p><strong>{{ card.value.toLocaleString() }}</strong><span>{{ index < 2 ? 'Currently retained questions' : 'Prepared answer library' }}</span></article></div>
                    <section class="admin-card admin-guide"><h2>Turn unanswered questions into prepared answers</h2><p>Review a question, create an inactive draft, remove personal information, then publish an accurate answer. Original question classifications remain unchanged.</p><a class="admin-button" href="/admin/chatbot/questions">Review visitor questions</a><a class="admin-button secondary" href="/admin/chatbot/faqs">Manage FAQs</a></section>
                </template>

                <section v-if="isList" class="admin-card" :aria-busy="loading">
                    <form data-search class="admin-filters" @submit.prevent="load()"><div class="admin-field"><label for="admin-search">Search question text</label><input id="admin-search" v-model="search" type="search" :aria-invalid="!!failure?.errors.search" :aria-describedby="failure?.errors.search ? 'search-error' : undefined"><p v-if="failure?.errors.search" id="search-error" class="admin-field-error">{{ failure.errors.search.join(' ') }}</p></div><div class="admin-field"><label for="admin-filter">{{ page === 'questions' ? 'Original classification' : 'FAQ status' }}</label><select id="admin-filter" v-model="filter"><option value="">All statuses</option><template v-if="page === 'questions'"><option value="answered">Originally answered</option><option value="unanswered">Originally unanswered</option></template><template v-else><option value="active">Active</option><option value="inactive">Inactive / draft</option></template></select></div><div class="admin-field"><label for="admin-per-page">Rows</label><select id="admin-per-page" v-model.number="perPage"><option :value="25">25</option><option :value="50">50</option></select></div><button class="admin-button" :disabled="loading || busy" type="submit">Search</button></form>
                    <div class="admin-table-scroll"><table v-if="rows.length"><caption class="admin-sr-only">{{ title }} results</caption><thead><tr><th scope="col">Question</th><th scope="col">{{ page === 'questions' ? 'Original classification' : 'Status' }}</th><th scope="col">Created (UTC)</th><th scope="col">Actions</th></tr></thead><tbody><tr v-for="row in rows" :key="row.id"><td class="admin-question-cell">{{ row.question }}</td><td><span class="admin-badge" :class="faqRow(row) ? (row.is_active ? 'active' : '') : (row.status === 'answered' ? 'active' : '')">{{ faqRow(row) ? (row.is_active ? 'Active' : 'Inactive / draft') : (row.status === 'answered' ? 'Originally answered' : 'Originally unanswered') }}</span></td><td><time :datetime="row.created_at">{{ date(row.created_at) }}</time></td><td><a :href="`/admin/chatbot/${page}/${row.id}`" :aria-label="`View ${page === 'faqs' ? 'FAQ' : 'question'} ${row.id}`">View</a><a v-if="page === 'faqs'" :href="`/admin/chatbot/faqs/${row.id}/edit`" :aria-label="`Edit FAQ ${row.id}`">Edit</a></td></tr></tbody></table><div v-else-if="!loading" class="admin-empty"><h2>{{ page === 'questions' ? 'No questions found' : 'No FAQs found' }}</h2><p>Try another search or filter. Newly prepared FAQs start inactive.</p></div></div>
                    <div class="admin-pagination"><span>{{ rows.length }} records on this page</span><button type="button" class="admin-button secondary" :disabled="!previousCursor || loading" @click="load(previousCursor)">Previous</button><button type="button" class="admin-button secondary" :disabled="!nextCursor || loading" @click="load(nextCursor)">Next</button></div>
                </section>

                <section v-if="page === 'question-detail' && question" class="admin-card admin-details"><h2>Collected question</h2><p class="admin-plain">{{ question.question }}</p><dl><dt>Original classification</dt><dd>{{ question.status === 'answered' ? 'Originally answered' : 'Originally unanswered' }}</dd><dt>Created (UTC)</dt><dd><time :datetime="question.created_at">{{ date(question.created_at) }}</time></dd></dl><p class="admin-muted">Reviewing or preparing an answer never changes this historical classification or retention date.</p><div class="admin-actions"><a class="admin-button" :href="`/admin/chatbot/faqs/create?source_question_id=${question.id}`">Create FAQ draft</a><button type="button" class="admin-button danger" @click="openDialog('delete-question')">Delete question</button><a href="/admin/chatbot/questions">Back to questions</a></div></section>

                <section v-if="isEditor" class="admin-card admin-details">
                    <p class="admin-notice">Remove personal or sensitive information copied from a visitor question. New FAQs and content edits remain inactive until explicitly reviewed and activated.</p>
                    <form data-faq @submit.prevent="save()"><div class="admin-field"><label for="faq-question">Question</label><textarea id="faq-question" v-model="questionText" rows="4" required :disabled="loading || busy" :aria-invalid="!!failure?.errors.question" :aria-describedby="failure?.errors.question ? 'question-error' : 'question-help'"></textarea><p id="question-help" class="admin-muted">Up to 1,000 Unicode characters. Matching uses the existing exact normalization rules.</p><p v-if="failure?.errors.question" id="question-error" class="admin-field-error">{{ failure.errors.question.join(' ') }}</p></div><div class="admin-field"><label for="faq-answer">Prepared answer</label><textarea id="faq-answer" v-model="answerText" rows="7" required :disabled="loading || busy" :aria-invalid="!!failure?.errors.answer" :aria-describedby="failure?.errors.answer ? 'answer-error' : 'answer-help'"></textarea><p id="answer-help" class="admin-muted">Up to 4,000 Unicode characters. Plain text only.</p><p v-if="failure?.errors.answer" id="answer-error" class="admin-field-error">{{ failure.errors.answer.join(' ') }}</p></div><div class="admin-actions"><button type="submit" class="admin-button" :disabled="loading || busy || failure?.code === 'stale_revision' || expired || (page === 'faq-edit' && !faq)">Save FAQ</button><a v-if="faq" class="admin-button secondary" :href="`/admin/chatbot/faqs/${faq.id}`">Review and activate</a><a href="/admin/chatbot/faqs">Cancel</a></div></form>
                </section>

                <section v-if="page === 'faq-detail' && faq" class="admin-card admin-details"><div class="admin-actions"><span class="admin-badge" :class="{ active: faq.is_active }">{{ faq.is_active ? 'Active' : 'Inactive / draft' }}</span><a :href="`/admin/chatbot/faqs/${faq.id}/edit`">Edit content</a></div><h2>Question</h2><p class="admin-plain">{{ faq.question }}</p><h2>Prepared answer</h2><p class="admin-plain">{{ faq.answer }}</p><dl><dt>Created (UTC)</dt><dd>{{ date(faq.created_at) }}</dd><dt>Updated (UTC)</dt><dd>{{ date(faq.updated_at) }}</dd></dl><div class="admin-actions"><button type="button" class="admin-button" @click="openDialog(faq.is_active ? 'deactivate' : 'activate')">{{ faq.is_active ? 'Deactivate FAQ' : 'Activate FAQ' }}</button><button type="button" class="admin-button danger" @click="openDialog('delete-faq')">Delete FAQ</button><a href="/admin/chatbot/faqs">Back to FAQs</a></div></section>
            </main>
        </div>
        <div v-if="dialog" class="admin-modal-backdrop" @keydown.stop="trap"><div ref="dialogElement" role="dialog" aria-modal="true" aria-labelledby="admin-dialog-title" aria-describedby="admin-dialog-description" tabindex="-1" class="admin-modal"><h2 id="admin-dialog-title">{{ dialog.startsWith('delete') ? 'Confirm permanent deletion' : dialog === 'activate' ? 'Activate this prepared answer?' : 'Deactivate this FAQ?' }}</h2><p id="admin-dialog-description">{{ dialog === 'delete-question' ? 'This removes the retained question and its deduplication record. Backup restoration must reapply this deletion.' : dialog === 'delete-faq' ? 'This removes the FAQ. Collected questions keep their original classification; replies may fall back.' : 'Review the current question and answer. This action does not change collected question history.' }}</p><template v-if="faq"><h3>Question</h3><p class="admin-plain">{{ faq.question }}</p><h3>Prepared answer</h3><p class="admin-plain">{{ faq.answer }}</p></template><form @submit.prevent="confirmAction()"><div v-if="dialog.startsWith('delete')" class="admin-field"><label for="confirm-password">Confirm your password</label><input id="confirm-password" v-model="password" type="password" autocomplete="current-password" required :disabled="busy" :aria-invalid="!!failure?.errors.password" :aria-describedby="failure?.errors.password ? 'password-error' : undefined"><p v-if="failure?.errors.password" id="password-error" class="admin-field-error">{{ failure.errors.password.join(' ') }}</p></div><p v-if="failure" role="alert">{{ errorMessage }}</p><p v-if="busy" role="status">Saving changes…</p><div class="admin-actions"><button class="admin-button" :class="{ danger: dialog.startsWith('delete') }" type="submit" :disabled="busy || failure?.code === 'stale_revision' || expired">{{ dialog.startsWith('delete') ? 'Permanently delete' : 'Confirm' }}</button><button type="button" class="admin-button secondary" :disabled="busy" @click="closeDialog">Cancel</button></div></form></div></div>
    </div>
</template>
