import { router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../Components/Admin/AdminLayout';
import Toast from '../../Components/Toast';

export default function Queue({ pendingJobs, failedJobs, queueConnection, failedConnection }) {
    const [toast, setToast] = useState(null);
    const [retryingId, setRetryingId] = useState(null);

    const handleRetry = (jobId) => {
        setRetryingId(jobId);

        router.post(`/admin/queue/failed/${jobId}/retry`, {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setToast({ message: `Failed job #${jobId} queued for retry.`, type: 'success' });
            },
            onError: () => {
                setToast({ message: `Failed to retry job #${jobId}.`, type: 'error' });
            },
            onFinish: () => {
                setRetryingId(null);
            },
        });
    };

    return (
        <AdminLayout title="Queue">
            {toast && (
                <Toast
                    message={toast.message}
                    type={toast.type}
                    duration={3500}
                />
            )}

            <section className="mb-8">
                <h3 className="font-sans text-sm font-semibold text-white mb-1">Pending Jobs</h3>
                <p className="text-[10px] text-[#666680] mb-3">DB connection: {queueConnection}</p>
                <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl overflow-hidden">
                    <table className="w-full text-xs font-mono">
                        <thead>
                            <tr className="border-b border-[#2a2a3a]">
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">ID</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Queue</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Attempts</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Reserved</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Available</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pendingJobs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-[#555570]">
                                        No pending jobs
                                    </td>
                                </tr>
                            ) : (
                                pendingJobs.data.map((job) => (
                                    <tr key={job.id} className="border-b border-[#1a1a28] hover:bg-[#1a1a28]">
                                        <td className="px-4 py-3 text-[#a8a8bc]">{job.id}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{job.queue}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{job.attempts}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{formatUnix(job.reserved_at)}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{formatUnix(job.available_at)}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{formatUnix(job.created_at)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                <Pagination links={pendingJobs.links} />
            </section>

            <section>
                <h3 className="font-sans text-sm font-semibold text-white mb-1">Failed Jobs</h3>
                <p className="text-[10px] text-[#666680] mb-3">DB connection: {failedConnection}</p>
                <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl overflow-hidden">
                    <table className="w-full text-xs font-mono">
                        <thead>
                            <tr className="border-b border-[#2a2a3a]">
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">ID</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Queue</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Connection</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Failed At</th>
                                <th className="px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold">Exception</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {failedJobs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-[#555570]">
                                        No failed jobs
                                    </td>
                                </tr>
                            ) : (
                                failedJobs.data.map((job) => (
                                    <tr key={job.id} className="border-b border-[#1a1a28] hover:bg-[#1a1a28]">
                                        <td className="px-4 py-3 text-[#a8a8bc]">{job.id}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{job.queue}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{job.connection}</td>
                                        <td className="px-4 py-3 text-[#a8a8bc]">{new Date(job.failed_at).toLocaleString()}</td>
                                        <td className="px-4 py-3 text-red-400 max-w-md truncate">{firstLine(job.exception)}</td>
                                        <td className="px-4 py-3 text-right">
                                            <button
                                                onClick={() => handleRetry(job.id)}
                                                disabled={retryingId === job.id}
                                                className="text-indigo-300 hover:text-indigo-200 text-[10px] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {retryingId === job.id ? 'Retrying...' : 'Retry'}
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                <Pagination links={failedJobs.links} />
            </section>
        </AdminLayout>
    );
}

function firstLine(value) {
    if (!value) return '\u2014';
    return String(value).split('\n')[0];
}

function formatUnix(seconds) {
    if (!seconds) return '\u2014';
    return new Date(seconds * 1000).toLocaleString();
}

function Pagination({ links }) {
    if (!links || links.length <= 3) return null;

    return (
        <div className="flex gap-2 mt-4 justify-center">
            {links.map((link, i) => (
                <button
                    key={i}
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
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
