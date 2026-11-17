import { useEffect, useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/Admin/AdminLayout';

export default function GuideEditor({ guide, saved, csrfToken }) {
    // Start from the persisted values when a save changes the server record.
    return <Editor key={guide ? JSON.stringify(guide) : 'new'} guide={guide} saved={saved} csrfToken={csrfToken} />;
}

function Editor({ guide, saved, csrfToken }) {
    const form = useForm({
        title: guide?.title ?? '',
        slug: guide?.slug ?? '',
        excerpt: guide?.excerpt ?? '',
        category: guide?.category ?? 'Getting started',
        author: guide?.author ?? 'AYN Thor Guides',
        body: guide?.body ?? '',
        status: guide?.status ?? 'draft',
    });

    const [view, setView] = useState('write');
    const [preview, setPreview] = useState({ html: '', headings: [] });
    const [previewPending, setPreviewPending] = useState(false);
    const [previewError, setPreviewError] = useState('');
    const [cleaning, setCleaning] = useState(false);
    const [pasteResult, setPasteResult] = useState(null);
    const [pasteError, setPasteError] = useState('');

    useEffect(() => {
        if (view !== 'preview') return;
        const controller = new AbortController();
        setPreviewPending(true);
        setPreviewError('');
        const timer = setTimeout(async () => {
            try {
                const response = await fetch('/admin/guides/render', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ body: form.data.body }),
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Unable to load preview. Check your connection or sign in again.');
                const content = await response.json();
                if (!controller.signal.aborted) setPreview(content);
            } catch (error) {
                if (error.name !== 'AbortError') setPreviewError(error.message);
            } finally {
                if (!controller.signal.aborted) setPreviewPending(false);
            }
        }, 650);
        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [view, form.data.body, csrfToken]);

    const cleanPaste = async () => {
        setCleaning(true);
        setPasteError('');
        const original = form.data.body;
        try {
            const response = await fetch('/admin/guides/clean-paste', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ body: original }),
            });
            if (!response.ok) throw new Error('Unable to clean pasted text. Check your connection or sign in again.');
            const result = await response.json();
            form.setData('body', result.body);
            setPasteResult({ ...result, original });
        } catch (error) {
            setPasteError(error.message);
        } finally {
            setCleaning(false);
        }
    };

    useEffect(() => {
        const beforeUnload = (event) => {
            if (!form.isDirty) return;
            event.preventDefault();
            event.returnValue = '';
        };
        window.addEventListener('beforeunload', beforeUnload);
        const removeListener = router.on('before', (event) => {
            if (form.isDirty && event.detail.visit.method === 'get' && !window.confirm('Leave this page and discard unsaved changes?')) {
                event.preventDefault();
            }
        });
        return () => {
            window.removeEventListener('beforeunload', beforeUnload);
            removeListener();
        };
    }, [form.isDirty]);

    const save = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true };
        if (guide) form.put(`/admin/guides/${guide.id}`, options);
        else form.post('/admin/guides', options);
    };

    const input = (name, label, options = {}) => (
        <div>
            <label htmlFor={name} className="mb-2 block text-xs font-medium text-[#c1c1d2]">{label}</label>
            <input id={name} name={name} value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)} aria-invalid={!!form.errors[name]} aria-describedby={form.errors[name] ? `${name}-error` : undefined} className="guide-input w-full" {...options} />
            {form.errors[name] && <p id={`${name}-error`} className="mt-2 text-xs text-red-300">{form.errors[name]}</p>}
        </div>
    );

    return (
        <AdminLayout title={guide ? 'Edit guide' : 'New guide'}>
            <Link href="/admin/guides" className="mb-6 inline-block text-xs text-indigo-300">← All guides</Link>
            <form onSubmit={save} className="max-w-6xl">
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
                    <div className="space-y-5 rounded-xl border border-[#2a2a3a] bg-[#15151f] p-6">
                        {input('title', 'Title', { required: true, maxLength: 180, placeholder: 'What will this guide help people do?' })}
                        <div>
                            <label htmlFor="excerpt" className="mb-2 block text-xs font-medium text-[#c1c1d2]">Summary</label>
                            <textarea id="excerpt" name="excerpt" required maxLength={500} rows={3} value={form.data.excerpt} onChange={(event) => form.setData('excerpt', event.target.value)} className="guide-input w-full" aria-invalid={!!form.errors.excerpt} aria-describedby="excerpt-help excerpt-error" />
                            <p id="excerpt-help" className="mt-2 text-xs text-[#a8a8bc]">Used on guide cards, in search results and when sharing. {form.data.excerpt.length}/500</p>
                            <p id="excerpt-error" className="mt-2 text-xs text-red-300">{form.errors.excerpt}</p>
                        </div>
                        <div>
                            <label htmlFor="body" className="mb-2 block text-xs font-medium text-[#c1c1d2]">Guide content · Markdown</label>
                            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <div role="group" aria-label="Editor view" className="flex gap-1 rounded-lg border border-[#343449] p-1 text-xs">
                                    <button type="button" aria-pressed={view === 'write'} onClick={() => setView('write')} className={`rounded px-3 py-2 ${view === 'write' ? 'bg-[#30304b] text-white' : 'text-[#a8a8bc]'}`}>Write</button>
                                    <button type="button" aria-pressed={view === 'preview'} onClick={() => setView('preview')} className={`rounded px-3 py-2 ${view === 'preview' ? 'bg-[#30304b] text-white' : 'text-[#a8a8bc]'}`}>Preview current text</button>
                                </div>
                                <button type="button" onClick={cleanPaste} disabled={cleaning || form.processing || !form.data.body.trim()} className="text-xs text-indigo-300 disabled:opacity-50">{cleaning ? 'Cleaning…' : 'Clean pasted text'}</button>
                            </div>
                            <p className="mb-3 text-xs leading-6 text-[#a8a8bc]">Clean pasted text removes copied citation badges and repairs image-search links. Review the changes before saving.</p>
                            {pasteError && <p role="alert" className="mb-3 text-xs text-red-300">{pasteError}</p>}
                            {pasteResult && <div className="mb-4 rounded-lg border border-[#343449] bg-[#10101a] p-4 text-xs leading-6">
                                <p className="font-medium text-white">{pasteResult.messages.length ? 'Pasted text cleaned. Changes are not saved yet.' : 'No copied formatting artifacts found.'}</p>
                                {pasteResult.messages.map((message) => <p key={message} className="mt-1 text-[#a8a8bc]">{message}</p>)}
                                {pasteResult.warnings.map((warning) => <p key={warning} className="mt-2 text-amber-200">{warning}</p>)}
                                {pasteResult.original !== pasteResult.body && form.data.body === pasteResult.body && <button type="button" disabled={cleaning} onClick={() => { form.setData('body', pasteResult.original); setPasteResult(null); }} className="mt-3 text-indigo-300 underline">Undo cleanup</button>}
                            </div>}
                            {view === 'write' ? <textarea id="body" disabled={cleaning} name="body" required maxLength={200000} rows={24} spellCheck={false} value={form.data.body} onChange={(event) => form.setData('body', event.target.value)} placeholder={'## Before you begin\n\nExplain what the reader needs.\n\n## Step by step\n\n1. Start here.\n\n## Troubleshooting\n\nDescribe known issues and link to original sources.'} className="guide-input w-full resize-y font-mono text-xs leading-7" aria-invalid={!!form.errors.body} aria-describedby="body-help body-error" /> : <div className="min-h-96 rounded-lg border border-[#343449] bg-[#0c0c14] p-5 sm:p-7" aria-label="Current guide preview" aria-busy={previewPending}>
                                <p className="mb-5 text-xs text-[#a8a8bc]">{previewPending ? 'Updating preview…' : 'Preview of current text · Not saved'}</p>
                                {previewError ? <p role="alert" className="text-sm text-red-300">{previewError}</p> : <>
                                    {form.data.title && <h3 className="mb-4 text-2xl font-semibold leading-tight text-white">{form.data.title}</h3>}
                                    {form.data.excerpt && <p className="mb-6 text-sm leading-7 text-[#a8a8bc]">{form.data.excerpt}</p>}
                                    {preview.html ? <article className="guide-prose" dangerouslySetInnerHTML={{ __html: preview.html }} /> : !previewPending && <p className="text-sm text-[#a8a8bc]">Start writing to see your guide here.</p>}
                                </>}
                            </div>}
                            <p id="body-error" className="mt-2 text-xs text-red-300">{form.errors.body}</p>
                            <p id="body-help" className="mt-2 text-xs leading-6 text-[#a8a8bc]">Use ## headings and ### subheadings for the automatic contents list. Supports lists, tables, code, [links](https://example.com) and ![image descriptions](/images/example.webp). Images need an exclamation mark: ![description](URL). Screenshot notes are text until you add an image. Raw HTML is stripped.</p>
                        </div>
                    </div>
                    <aside className="space-y-5 rounded-xl border border-[#2a2a3a] bg-[#15151f] p-6">
                        <div>
                            <label htmlFor="status" className="mb-2 block text-xs font-medium text-[#c1c1d2]">Visibility</label>
                            <select id="status" name="status" value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} className="guide-input w-full">
                                <option value="draft">Draft · private</option>
                                <option value="published">Published · public</option>
                            </select>
                            {form.errors.status && <p className="mt-2 text-xs text-red-300">{form.errors.status}</p>}
                            <p className="mt-2 text-xs leading-6 text-[#a8a8bc]">{form.data.status === 'published' ? 'Saving makes this guide available on the public site.' : 'Only signed-in administrators can preview a draft.'}</p>
                        </div>
                        {input('slug', 'URL slug', { maxLength: 180, placeholder: 'Generated from title if empty' })}
                        {guide && <p className="text-xs leading-6 text-amber-200/90">Changing the slug changes the public URL. Existing links to the previous URL will stop working.</p>}
                        {input('category', 'Topic', { required: true, maxLength: 50, list: 'guide-topics' })}
                        <datalist id="guide-topics"><option value="Getting started" /><option value="Gaming" /><option value="Troubleshooting" /><option value="Accessories" /></datalist>
                        {input('author', 'Author', { required: true, maxLength: 100 })}
                        <div className="border-t border-[#2a2a3a] pt-5">
                            <button type="submit" disabled={form.processing || cleaning} className="guide-button w-full disabled:opacity-50">{form.processing ? 'Saving…' : 'Save guide'}</button>
                            <p role="status" className="mt-3 text-center text-xs text-emerald-300">{form.recentlySuccessful || (saved && !form.isDirty) ? 'Guide saved.' : ''}</p>
                            {guide && <a href={`/admin/guides/${guide.id}/preview`} target="_blank" rel="noopener noreferrer" className="mt-3 block text-center text-xs text-indigo-300">Preview saved version ↗</a>}
                            {form.isDirty && <p className="mt-3 text-center text-xs text-amber-200">You have unsaved changes.</p>}
                        </div>
                    </aside>
                </div>
            </form>
        </AdminLayout>
    );
}
