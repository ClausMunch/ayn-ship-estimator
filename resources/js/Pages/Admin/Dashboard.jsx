import { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '../../Components/Admin/AdminLayout';
import StatCard from '../../Components/Admin/StatCard';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';

const TOOLTIP_STYLE = {
    contentStyle: { background: '#1a1a28', border: '1px solid #2a2a3a', borderRadius: 8, fontSize: 11 },
    labelStyle: { color: '#a8a8bc' },
};

export default function Dashboard({ todayTotal, todayUnique, weeklyViews, totalVerified, topVariant, lastScrape }) {
    const [scraping, setScraping] = useState(false);

    const handleScrape = () => {
        setScraping(true);
        router.post('/admin/scrape', {}, {
            onFinish: () => setScraping(false),
        });
    };

    return (
        <AdminLayout title="Dashboard">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <StatCard label="Today's Visitors" value={todayUnique} sublabel={`${todayTotal} total views`} />
                <StatCard label="Verified Subscribers" value={totalVerified} />
                <StatCard
                    label="Top Model (7d)"
                    value={topVariant?.name || 'N/A'}
                    sublabel={topVariant ? `${topVariant.count} lookups` : null}
                />
                <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl p-5 flex flex-col justify-between">
                    <div>
                        <div className="text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold mb-2">
                            Last Scrape
                        </div>
                        <div className="font-sans text-2xl font-bold text-white">
                            {lastScrape?.status === 'success' ? 'OK' : (lastScrape?.status || 'Never')}
                        </div>
                        {lastScrape && (
                            <div className="text-[11px] text-[#666680] mt-1 font-light">
                                {new Date(lastScrape.created_at).toLocaleString()}
                            </div>
                        )}
                    </div>
                    <button
                        onClick={handleScrape}
                        disabled={scraping}
                        className="mt-3 w-full rounded-lg py-1.5 text-[10px] font-semibold cursor-pointer transition-all duration-150 bg-[#818cf8] text-white border-none disabled:opacity-40 hover:bg-[#6d78e4]"
                    >
                        {scraping ? 'Scraping...' : 'Run Scrape Now'}
                    </button>
                </div>
            </div>

            <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl p-5">
                <div className="text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold mb-4">
                    Visitors (Last 7 Days)
                </div>
                {weeklyViews.length > 0 ? (
                    <ResponsiveContainer width="100%" height={200}>
                        <BarChart data={weeklyViews}>
                            <XAxis
                                dataKey="date"
                                tick={{ fill: '#555570', fontSize: 10 }}
                                tickFormatter={(d) => new Date(d + 'T00:00:00').toLocaleDateString('en', { weekday: 'short' })}
                            />
                            <YAxis tick={{ fill: '#555570', fontSize: 10 }} allowDecimals={false} />
                            <Tooltip {...TOOLTIP_STYLE} />
                            <Bar dataKey="unique" fill="#818cf8" radius={[4, 4, 0, 0]} name="Unique" />
                            <Bar dataKey="total" fill="#3a3a5a" radius={[4, 4, 0, 0]} name="Total" />
                        </BarChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="text-[#555570] text-xs text-center py-8">No visitor data yet</div>
                )}
            </div>
        </AdminLayout>
    );
}
