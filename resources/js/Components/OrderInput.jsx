export default function OrderInput({ value, onChange }) {
    const handleChange = (e) => {
        const v = e.target.value.replace(/\D/g, '').slice(0, 4);
        onChange(v);
    };

    return (
        <div>
            <span className="block text-[10px] font-semibold uppercase tracking-[1.5px] text-[#555570] mb-2.5">
                Order Number (first 4 digits)
            </span>
            <div className="relative mb-6">
                <input
                    type="text"
                    inputMode="numeric"
                    maxLength={4}
                    placeholder="1350"
                    value={value}
                    onChange={handleChange}
                    className="w-full rounded-[10px] px-[18px] py-4 pr-20 text-center font-mono text-[22px] font-semibold tracking-[4px] text-white outline-none transition-colors duration-150"
                    style={{
                        background: '#1a1a28',
                        border: '1.5px solid #2a2a3a',
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#4a4a6a')}
                    onBlur={(e) => (e.target.style.borderColor = '#2a2a3a')}
                />
                <span className="absolute right-[18px] top-1/2 -translate-y-1/2 text-[22px] font-semibold tracking-[4px] text-[#333348] pointer-events-none">
                    xx
                </span>
            </div>
        </div>
    );
}
