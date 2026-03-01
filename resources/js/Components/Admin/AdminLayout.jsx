import { usePage, router } from '@inertiajs/react';

const NAV = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Subscribers', href: '/admin/subscribers' },
    { label: 'Analytics', href: '/admin/analytics' },
    { label: 'Scrape Logs', href: '/admin/scrape-logs' },
    { label: 'Queue', href: '/admin/queue' },
];

export default function AdminLayout({ children, title }) {
    const { url } = usePage();

    const handleLogout = () => {
        router.post('/admin/logout');
    };

    return (
        <div className="min-h-screen bg-[#0c0c14] text-[#e0e0e8] flex">
            {/* Sidebar */}
            <aside className="w-56 bg-[#15151f] border-r border-[#2a2a3a] flex flex-col shrink-0">
                <div className="px-5 py-6 border-b border-[#2a2a3a]">
                    <h1 className="font-sans text-sm font-semibold text-white tracking-tight">
                        Admin Panel
                    </h1>
                    <p className="text-[10px] text-[#555570] mt-1">AYN Thor Estimator</p>
                </div>
                <nav className="flex-1 px-3 py-4 space-y-1">
                    {NAV.map((item) => {
                        const active = url === item.href
                            || (item.href !== '/admin' && url.startsWith(item.href));
                        return (
                            <a
                                key={item.href}
                                href={item.href}
                                className={`block px-3 py-2 rounded-lg text-xs font-mono transition-colors ${active
                                        ? 'bg-[#1a1a28] text-white border border-[#2a2a3a]'
                                        : 'text-[#a8a8bc] hover:text-white hover:bg-[#1a1a28]'
                                    }`}
                            >
                                {item.label}
                            </a>
                        );
                    })}
                </nav>
                <div className="px-3 py-4 border-t border-[#2a2a3a]">
                    <button
                        onClick={handleLogout}
                        className="w-full px-3 py-2 rounded-lg text-xs font-mono text-[#a8a8bc] hover:text-white hover:bg-[#1a1a28] text-left cursor-pointer transition-colors"
                    >
                        Log out
                    </button>
                </div>
            </aside>

            {/* Main content */}
            <main className="flex-1 p-8 overflow-auto">
                {title && (
                    <h2 className="font-sans text-lg font-semibold text-white mb-6">{title}</h2>
                )}
                {children}
            </main>
        </div>
    );
}
