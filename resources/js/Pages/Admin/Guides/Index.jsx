import { Link } from '@inertiajs/react';
import AdminLayout from '../../../Components/Admin/AdminLayout';

export default function GuidesIndex({ guides }) {
    return (
        <AdminLayout title="Guides">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <p className="text-sm text-[#a8a8bc]">Write, preview and publish guides for Thor owners.</p>
                <div className="flex items-center gap-4">
                    <a href="/guides" className="text-xs text-indigo-300">View public guides ↗</a>
                    <Link href="/admin/guides/create" className="guide-button">New guide</Link>
                </div>
            </div>
            <div className="overflow-x-auto rounded-xl border border-[#2a2a3a] bg-[#15151f]">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-[#2a2a3a] text-xs text-[#a8a8bc]">
                        <tr><th className="p-4">Guide</th><th className="p-4">Topic</th><th className="p-4">Status</th><th className="p-4">Updated</th><th className="p-4"><span className="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody>
                        {guides.data.map((guide) => (
                            <tr key={guide.id} className="border-b border-[#2a2a3a] last:border-0">
                                <td className="p-4"><Link href={`/admin/guides/${guide.id}/edit`} className="font-medium text-white hover:text-indigo-300">{guide.title}</Link><p className="mt-1 text-xs text-[#a8a8bc]">/guides/{guide.slug}</p></td>
                                <td className="p-4 text-[#a8a8bc]">{guide.category}</td>
                                <td className="p-4"><span className={`rounded px-2 py-1 text-xs ${guide.status === 'published' ? 'bg-emerald-950 text-emerald-300' : 'bg-amber-950 text-amber-200'}`}>{guide.status === 'published' && guide.published_at && new Date(guide.published_at) > new Date() ? 'Scheduled' : guide.status}</span></td>
                                <td className="whitespace-nowrap p-4 text-xs text-[#a8a8bc]">{new Date(guide.updated_at).toLocaleDateString()}</td>
                                <td className="p-4"><a href={`/admin/guides/${guide.id}/preview`} target="_blank" rel="noopener noreferrer" className="text-xs text-indigo-300" aria-label={`Preview ${guide.title}`}>Preview ↗</a></td>
                            </tr>
                        ))}
                        {!guides.data.length && <tr><td colSpan={5} className="px-6 py-14 text-center text-[#a8a8bc]">No guides yet. Create your first draft to get started.</td></tr>}
                    </tbody>
                </table>
            </div>
            {guides.last_page > 1 && (
                <nav aria-label="Guide pages" className="mt-6 flex items-center justify-center gap-5 text-sm">
                    {guides.prev_page_url && <Link href={guides.prev_page_url} className="text-indigo-300">← Previous</Link>}
                    <span className="text-[#a8a8bc]">Page {guides.current_page} of {guides.last_page}</span>
                    {guides.next_page_url && <Link href={guides.next_page_url} className="text-indigo-300">Next →</Link>}
                </nav>
            )}
        </AdminLayout>
    );
}
