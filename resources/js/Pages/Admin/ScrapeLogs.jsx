import { router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../Components/Admin/AdminLayout';

export default function ScrapeLogs({ logs }) {
    const [expandedLogId, setExpandedLogId] = useState(null);
    const [scraping, setScraping] = useState(false);

    const toggleExpanded = (logId) => {
        setExpandedLogId((current) => (current === logId ? null : logId));
    };

    const clearLogs = () => {
        if (!confirm('Clear all scrape logs? This cannot be undone.')) {
            return;
        }

        router.delete('/admin/scrape-logs', {
            preserveScroll: true,
        });
    };

    const runScrapeNow = () => {
        setScraping(true);

        router.post('/admin/scrape', {}, {
            preserveScroll: true,
            onFinish: () => setScraping(false),
        });
    };

    return (
        <AdminLayout title="Scrape Logs">
            <div className="flex justify-end mb-4 gap-2">
                <button
                    onClick={runScrapeNow}
                    disabled={scraping}
                    className="px-3 py-1.5 rounded text-xs font-mono text-white bg-[#2a2e5a] border border-[#3a4380] hover:bg-[#313868] cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {scraping ? 'Scraping...' : 'Scrape Now'}
                </button>
                <button
                    onClick={clearLogs}
                    className="px-3 py-1.5 rounded text-xs font-mono text-red-300 bg-[#2a0f13] border border-[#5a1a24] hover:bg-[#35131a] cursor-pointer transition-colors"
                >
                    Clear Logs
                </button>
            </div>

            <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl overflow-hidden">
                <table className="w-full text-xs font-mono">
                    <thead>
                        <tr className="border-b border-[#2a2a3a]">
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Timestamp</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Status</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Found</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">New</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Duration</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Runtime</th>
                            <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.data.length === 0 ? (
                            <tr>
                                <td colSpan={7} className="px-4 py-8 text-center text-[#555570]">
                                    No scrape logs yet
                                </td>
                            </tr>
                        ) : (
                            logs.data.flatMap((log) => {
                                const isExpanded = expandedLogId === log.id;
                                const hasError = !!log.error_message;

                                const rows = [
                                    <tr key={`row-${log.id}`} className="border-b border-[#1a1a28] hover:bg-[#1a1a28]">
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
                                        <td className="px-4 py-3 text-[#a8a8bc] max-w-xs truncate" title={log.runtime_context || ''}>
                                            {log.runtime_context || '\u2014'}
                                        </td>
                                        <td className="px-4 py-3 text-red-400 max-w-xs">
                                            {hasError ? (
                                                <>
                                                    <div className="truncate">{log.error_message}</div>
                                                    <button
                                                        onClick={() => toggleExpanded(log.id)}
                                                        className="mt-1 text-[10px] text-indigo-300 hover:text-indigo-200 cursor-pointer"
                                                    >
                                                        {isExpanded ? 'Hide' : 'View full'}
                                                    </button>
                                                </>
                                            ) : '\u2014'}
                                        </td>
                                    </tr>,
                                ];

                                if (hasError && isExpanded) {
                                    rows.push(
                                        <tr key={`expanded-${log.id}`} className="border-b border-[#1a1a28] bg-[#11111a]">
                                            <td colSpan={7} className="px-4 py-3">
                                                <div className="text-[11px] text-red-300 whitespace-pre-wrap wrap-break-word">
                                                    {log.error_message}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                }

                                return rows;
                            })
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
                    className={`px-3 py-1.5 rounded text-xs font-mono cursor-pointer transition-colors ${link.active
                        ? 'bg-[#818cf8] text-white'
                        : 'bg-[#1a1a28] text-[#a8a8bc] hover:bg-[#2a2a3a]'
                        } ${!link.url ? 'opacity-30 cursor-not-allowed' : ''}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
