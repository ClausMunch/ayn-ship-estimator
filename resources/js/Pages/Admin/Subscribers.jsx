import { router } from '@inertiajs/react';
import AdminLayout from '../../Components/Admin/AdminLayout';
import DataTable from '../../Components/Admin/DataTable';
import Toast from '../../Components/Toast';
import { useState } from 'react';

const COLUMNS = [
    { key: 'email', label: 'Email' },
    {
        key: 'model_variant',
        label: 'Model',
        sortable: false,
        render: (row) => row.model_variant?.name || '\u2014',
    },
    { key: 'order_prefix', label: 'Order Prefix' },
    {
        key: 'shipping_expectation',
        label: 'Shipping status',
        sortable: false,
        render: (row) => {
            const expectation = row.shipping_expectation;
            const styles = {
                not_due: ['Not due', 'text-[#818cf8]'],
                should_have_shipped: ['Should have shipped', 'text-amber-300'],
                should_have_delivered: ['Should have delivered', 'text-emerald-300'],
                unknown: ['Unknown', 'text-[#666680]'],
            };
            const [label, className] = styles[expectation?.status] || styles.unknown;
            const title = expectation?.ship_date
                ? `Calculated ship date: ${expectation.ship_date}; expected delivery: ${expectation.delivery_date}`
                : 'Not enough shipping data to calculate an estimate.';

            return <span className={className} title={title}>{label}</span>;
        },
    },
    {
        key: 'device_confirmation',
        label: 'User confirmed',
        sortable: false,
        render: (row) => {
            if (row.delivered_confirmed_at) {
                return <span className="text-emerald-300" title={new Date(row.delivered_confirmed_at).toLocaleString()}>Delivered</span>;
            }
            if (row.shipped_confirmed_at) {
                return <span className="text-cyan-300" title={new Date(row.shipped_confirmed_at).toLocaleString()}>Shipped</span>;
            }
            if (row.delivered_confirmation_sent_at) return <span className="text-[#666680]">Asked about delivery</span>;
            if (row.shipped_confirmation_sent_at) return <span className="text-[#666680]">Asked about shipment</span>;
            return <span className="text-[#444460]">Not asked</span>;
        },
    },
    {
        key: 'email_verified_at',
        label: 'Verified',
        render: (row) => row.email_verified_at
            ? new Date(row.email_verified_at).toLocaleDateString()
            : 'No',
    },
    {
        key: 'verification_sent_at',
        label: 'Verification sent',
        render: (row) => row.verification_sent_at
            ? new Date(row.verification_sent_at).toLocaleString()
            : 'Not sent',
    },
    {
        key: 'delivery_status',
        label: 'Delivery',
        sortable: false,
        render: (row) => (
            <span title={row.delivery_error || ''} className={['bounced', 'failed'].includes(row.delivery_status) ? 'text-red-400' : row.delivery_status === 'deferred' ? 'text-amber-300' : 'text-emerald-300'}>
                {row.delivery_status === 'bounced' ? 'Rejected' : (row.delivery_status || 'active')}
            </span>
        ),
    },
    {
        key: 'created_at',
        label: 'Date',
        render: (row) => new Date(row.created_at).toLocaleDateString(),
    },
];

export default function Subscribers({ subscribers, unverifiedCount, bouncedCount, sort, direction }) {
    const [toast, setToast] = useState(null);
    const [resendingAll, setResendingAll] = useState(false);

    const handleSort = (key) => {
        router.get('/admin/subscribers', {
            sort: key,
            direction: sort === key && direction === 'asc' ? 'desc' : 'asc',
        }, { preserveState: true });
    };

    const handleDelete = (subscriber) => {
        if (!confirm(`Delete subscriber ${subscriber.email}?`)) return;
        router.delete(`/admin/subscribers/${subscriber.id}`);
    };

    const handleResendVerification = (subscriber) => {
        router.post(`/admin/subscribers/${subscriber.id}/resend-verification`, {}, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setToast({ message: `Verification email queued for ${subscriber.email}.`, type: 'success' });
            },
            onError: (errors) => {
                setToast({ message: errors.resend || 'Failed to queue verification email.', type: 'error' });
            },
        });
    };

    const handleResendAllVerifications = () => {
        if (unverifiedCount === 0) return;
        if (!confirm(`Queue verification emails for all ${unverifiedCount} unverified subscribers?`)) return;

        setResendingAll(true);
        router.post('/admin/subscribers/resend-all-verifications', {}, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setToast({ message: `${unverifiedCount} verification emails queued.`, type: 'success' });
            },
            onError: () => {
                setToast({ message: 'Failed to queue verification emails.', type: 'error' });
            },
            onFinish: () => setResendingAll(false),
        });
    };

    const handleDeleteBounced = () => {
        if (!confirm(`Permanently delete all ${bouncedCount} rejected subscribers?`)) return;
        router.delete('/admin/subscribers/bounced', {
            preserveScroll: true,
            onSuccess: () => setToast({ message: `${bouncedCount} rejected subscribers deleted.`, type: 'success' }),
        });
    };

    return (
        <AdminLayout title="Subscribers">
            {toast && (
                <Toast
                    message={toast.message}
                    type={toast.type}
                    duration={3500}
                />
            )}

            <div className="flex justify-end gap-3 mb-4">
                <button
                    type="button"
                    disabled={bouncedCount === 0}
                    onClick={handleDeleteBounced}
                    className="px-3 py-2 rounded bg-red-950 text-red-300 text-xs font-mono hover:bg-red-900 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-colors"
                >
                    Delete rejected ({bouncedCount})
                </button>
                <button
                    type="button"
                    disabled={unverifiedCount === 0 || resendingAll}
                    onClick={handleResendAllVerifications}
                    className="px-3 py-2 rounded bg-[#4f46e5] text-white text-xs font-mono hover:bg-[#6366f1] disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-colors"
                >
                    {resendingAll
                        ? 'Queueing…'
                        : `Resend all unverified (${unverifiedCount})`}
                </button>
            </div>

            <DataTable
                columns={COLUMNS}
                rows={subscribers.data}
                sort={sort}
                direction={direction}
                onSort={handleSort}
                actions={(row) => (
                    <div className="flex items-center justify-end gap-3">
                        {!row.email_verified_at && row.delivery_status !== 'bounced' && (
                            <button
                                onClick={() => handleResendVerification(row)}
                                className="text-indigo-300 hover:text-indigo-200 text-[10px] cursor-pointer"
                            >
                                Resend verification
                            </button>
                        )}
                        <button
                            onClick={() => handleDelete(row)}
                            className="text-red-400 hover:text-red-300 text-[10px] cursor-pointer"
                        >
                            Delete
                        </button>
                    </div>
                )}
            />

            <Pagination links={subscribers.links} />
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
                >
                    {link.label.replace(/&laquo;/g, '\u00AB').replace(/&raquo;/g, '\u00BB')}
                </button>
            ))}
        </div>
    );
}
