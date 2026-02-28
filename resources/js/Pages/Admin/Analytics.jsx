import AdminLayout from '../../Components/Admin/AdminLayout';
import {
    BarChart, Bar, LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer,
} from 'recharts';

const TOOLTIP_STYLE = {
    contentStyle: { background: '#1a1a28', border: '1px solid #2a2a3a', borderRadius: 8, fontSize: 11 },
    labelStyle: { color: '#a8a8bc' },
};

export default function Analytics({ dailyViews, dailyEstimations, topVariants, topPrefixes }) {
    return (
        <AdminLayout title="Analytics">
            {/* Page views chart */}
            <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl p-5 mb-6">
                <div className="text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold mb-4">
                    Page Views (30 Days)
                </div>
                {dailyViews.length > 0 ? (
                    <ResponsiveContainer width="100%" height={250}>
                        <BarChart data={dailyViews}>
                            <XAxis dataKey="date" tick={{ fill: '#555570', fontSize: 9 }}
                                tickFormatter={(d) => d.slice(5)} />
                            <YAxis tick={{ fill: '#555570', fontSize: 10 }} allowDecimals={false} />
                            <Tooltip {...TOOLTIP_STYLE} />
                            <Bar dataKey="unique" fill="#818cf8" radius={[2, 2, 0, 0]} name="Unique" />
                            <Bar dataKey="total" fill="#3a3a5a" radius={[2, 2, 0, 0]} name="Total" />
                        </BarChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="text-[#555570] text-xs text-center py-8">No data yet</div>
                )}
            </div>

            {/* Estimations chart */}
            <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl p-5 mb-6">
                <div className="text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold mb-4">
                    Estimations (30 Days)
                </div>
                {dailyEstimations.length > 0 ? (
                    <ResponsiveContainer width="100%" height={200}>
                        <LineChart data={dailyEstimations}>
                            <XAxis dataKey="date" tick={{ fill: '#555570', fontSize: 9 }}
                                tickFormatter={(d) => d.slice(5)} />
                            <YAxis tick={{ fill: '#555570', fontSize: 10 }} allowDecimals={false} />
                            <Tooltip {...TOOLTIP_STYLE} />
                            <Line type="monotone" dataKey="total" stroke="#818cf8" strokeWidth={2} dot={false} />
                        </LineChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="text-[#555570] text-xs text-center py-8">No data yet</div>
                )}
            </div>

            {/* Tables row */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl p-5">
                    <div className="text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold mb-3">
                        Top Model Variants
                    </div>
                    {topVariants.length > 0 ? (
                        <table className="w-full text-xs font-mono">
                            <tbody>
                                {topVariants.map((v, i) => (
                                    <tr key={i} className="border-b border-[#1a1a28]">
                                        <td className="py-2 text-[#a8a8bc]">{v.name}</td>
                                        <td className="py-2 text-right text-[#818cf8]">{v.count}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="text-[#555570] text-xs text-center py-4">No data yet</div>
                    )}
                </div>

                <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl p-5">
                    <div className="text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold mb-3">
                        Top Order Prefixes
                    </div>
                    {topPrefixes.length > 0 ? (
                        <table className="w-full text-xs font-mono">
                            <tbody>
                                {topPrefixes.map((p, i) => (
                                    <tr key={i} className="border-b border-[#1a1a28]">
                                        <td className="py-2 text-[#a8a8bc]">#{p.order_prefix}xx</td>
                                        <td className="py-2 text-right text-[#818cf8]">{p.count}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="text-[#555570] text-xs text-center py-4">No data yet</div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
