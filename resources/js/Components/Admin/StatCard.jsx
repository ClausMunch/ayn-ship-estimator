export default function StatCard({ label, value, sublabel }) {
    return (
        <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl p-5">
            <div className="text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold mb-2">
                {label}
            </div>
            <div className="font-sans text-2xl font-bold text-white">
                {value}
            </div>
            {sublabel && (
                <div className="text-[11px] text-[#666680] mt-1 font-light">
                    {sublabel}
                </div>
            )}
        </div>
    );
}
