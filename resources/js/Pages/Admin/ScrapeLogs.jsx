import { router } from '@inertiajs/react';
import AdminLayout from '../../Components/Admin/AdminLayout';

export default function ScrapeLogs({ logs }) {
    return (
        <AdminLayout title="Scrape Logs">
            <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl overflow-hidden">
                <table className="w-full text-xs font-mono">
                    <thead>
                        <tr className="border-b border-[#2a2a3a]">
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Timestamp</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Status</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Found</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">New</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Duration</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.data.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="px-4 py-8 text-center text-[#555570]">
                                    No scrape logs yet
                                </td>
                            </tr>
                        ) : (
                            logs.data.map((log) => (
                                <tr key={log.id} className="border-b border-[#1a1a28] hover:bg-[#1a1a28]">
                                    <td className="px-4 py-3 text-[#a8a8bc]">
                                        {new Date(log.created_at).toLocaleString()}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={log.status === 'success' ? 'text-green-400' : 'text-red-400'}>
                                            {log.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-[#a8a8bc]">{log.records_found}</td>
                                    <td className="px-4 py-3 text-[#a8a8bc]">{log.records_new}</td>
                                    <td className="px-4 py-3 text-[#a8a8bc]">
                                        {log.duration_ms ? `${log.duration_ms}ms` : '\u2014'}
                                    </td>
                                    <td className="px-4 py-3 text-red-400 max-w-xs truncate">
                                        {log.error_message || '\u2014'}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <Pagination links={logs.links} />
        </AdminLayout>
    );
}

function Pagination({ links }) {
    if (!links || links.length <= 3) return null;

    return (
        <div className="flex gap-2 mt-4 justify-center">
            {links.map((link, i) => (
                <button
                    key={i}
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                    className={`px-3 py-1.5 rounded text-xs font-mono cursor-pointer transition-colors ${
                        link.active
                            ? 'bg-[#818cf8] text-white'
                            : 'bg-[#1a1a28] text-[#a8a8bc] hover:bg-[#2a2a3a]'
                    } ${!link.url ? 'opacity-30 cursor-not-allowed' : ''}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
