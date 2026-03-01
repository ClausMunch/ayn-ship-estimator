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
        key: 'email_verified_at',
        label: 'Verified',
        render: (row) => row.email_verified_at
            ? new Date(row.email_verified_at).toLocaleDateString()
            : 'No',
    },
    {
        key: 'created_at',
        label: 'Date',
        render: (row) => new Date(row.created_at).toLocaleDateString(),
    },
];

export default function Subscribers({ subscribers, sort, direction }) {
    const [toast, setToast] = useState(null);

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

    return (
        <AdminLayout title="Subscribers">
            {toast && (
                <Toast
                    message={toast.message}
                    type={toast.type}
                    duration={3500}
                />
            )}

            <DataTable
                columns={COLUMNS}
                rows={subscribers.data}
                sort={sort}
                direction={direction}
                onSort={handleSort}
                actions={(row) => (
                    <div className="flex items-center justify-end gap-3">
                        {!row.email_verified_at && (
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
