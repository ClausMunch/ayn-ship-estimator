import { useEffect } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/Admin/AdminLayout';

export default function GuideEditor({ guide, saved }) {
    // Start from the persisted values when a save changes the server record.
    return <Editor key={guide ? JSON.stringify(guide) : 'new'} guide={guide} saved={saved} />;
}

function Editor({ guide, saved }) {
    const form = useForm({
        title: guide?.title ?? '',
        slug: guide?.slug ?? '',
        excerpt: guide?.excerpt ?? '',
        category: guide?.category ?? 'Getting started',
        author: guide?.author ?? 'AYN Thor Guides',
        body: guide?.body ?? '',
        status: guide?.status ?? 'draft',
    });

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
                            <textarea id="body" name="body" required maxLength={200000} rows={24} spellCheck={false} value={form.data.body} onChange={(event) => form.setData('body', event.target.value)} placeholder={'## Before you begin\n\nExplain what the reader needs.\n\n## Step by step\n\n1. Start here.\n\n## Troubleshooting\n\nDescribe known issues and link to original sources.'} className="guide-input w-full resize-y font-mono text-xs leading-7" aria-invalid={!!form.errors.body} aria-describedby="body-help body-error" />
                            <p id="body-error" className="mt-2 text-xs text-red-300">{form.errors.body}</p>
                            <p id="body-help" className="mt-2 text-xs leading-6 text-[#a8a8bc]">Use ## headings and ### subheadings for the automatic contents list. Supports lists, tables, code, [links](https://example.com) and ![image descriptions](/images/example.webp). Raw HTML is stripped.</p>
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
                            <button type="submit" disabled={form.processing} className="guide-button w-full disabled:opacity-50">{form.processing ? 'Saving…' : 'Save guide'}</button>
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
