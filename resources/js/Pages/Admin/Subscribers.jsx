import { router } from '@inertiajs/react';
import AdminLayout from '../../Components/Admin/AdminLayout';
import DataTable from '../../Components/Admin/DataTable';

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

    return (
        <AdminLayout title="Subscribers">
            <DataTable
                columns={COLUMNS}
                rows={subscribers.data}
                sort={sort}
                direction={direction}
                onSort={handleSort}
                actions={(row) => (
                    <button
                        onClick={() => handleDelete(row)}
                        className="text-red-400 hover:text-red-300 text-[10px] cursor-pointer"
                    >
                        Delete
                    </button>
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
