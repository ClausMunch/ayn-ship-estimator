import AdminLayout from '../../Components/Admin/AdminLayout';
import StatCard from '../../Components/Admin/StatCard';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';

const TOOLTIP_STYLE = {
    contentStyle: { background: '#1a1a28', border: '1px solid #2a2a3a', borderRadius: 8, fontSize: 11 },
    labelStyle: { color: '#a8a8bc' },
};

export default function Dashboard({ todayTotal, todayUnique, weeklyViews, totalVerified, topVariant, lastScrape }) {
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
                <StatCard
                    label="Last Scrape"
                    value={lastScrape?.status === 'success' ? 'OK' : (lastScrape?.status || 'Never')}
                    sublabel={lastScrape ? new Date(lastScrape.created_at).toLocaleString() : null}
                />
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
