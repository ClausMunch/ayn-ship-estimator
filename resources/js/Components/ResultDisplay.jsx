const STYLES = {
    shipped: {
        background: 'linear-gradient(135deg, #0a2a15, #152f1a)',
        border: '1px solid #1a5a30',
        color: '#4ade80',
        dot: '#4ade80',
        label: 'Already Shipped',
    },
    estimated: {
        background: 'linear-gradient(135deg, #1a1a2e, #1e1e35)',
        border: '1px solid #3a3a5a',
        color: '#818cf8',
        dot: '#818cf8',
        label: 'Estimated Ship Date',
    },
    extrapolated: {
        background: 'linear-gradient(135deg, #2a1a0a, #2f2015)',
        border: '1px solid #5a3a1a',
        color: '#fbbf24',
        dot: '#fbbf24',
        label: 'Projected Ship Date',
    },
};

export default function ResultDisplay({ result, orderInput, modelName, lastEnd }) {
    if (result) {
        const s = STYLES[result.type];

        const note =
            result.type === 'shipped'
                ? `Order ${orderInput}xx was in a batch shipped on or before this date.`
                : result.type === 'estimated'
                  ? `Interpolated from known shipping batches for ${modelName}.`
                  : 'Extrapolated beyond known data — take with a grain of salt.';

        return (
            <>
                <div
                    className="rounded-xl p-5 text-center animate-fade-in"
                    style={{ background: s.background, border: s.border }}
                >
                    <div
                        className="text-[10px] uppercase tracking-[1.5px] mb-2 font-medium"
                        style={{ color: s.color }}
                    >
                        <span
                            className="inline-block w-[7px] h-[7px] rounded-full mr-1.5 relative -top-px"
                            style={{ background: s.dot }}
                        />
                        {s.label}
                    </div>
                    <div className="font-sans text-[22px] font-bold text-white tracking-tight">
                        {result.formatted}
                    </div>
                    <div className="text-[11px] mt-2.5 opacity-60 font-light leading-relaxed">
                        {note}
                    </div>
                </div>
                {lastEnd && (
                    <div className="text-[10px] text-[#444460] text-center mt-2 font-light">
                        {modelName} data covers orders up to {lastEnd}xx
                    </div>
                )}
            </>
        );
    }

    if (orderInput.length > 0 && orderInput.length < 4) {
        return (
            <div className="text-center text-[#444460] text-xs p-4 font-light leading-relaxed">
                Enter all 4 digits to see your estimate.
            </div>
        );
    }

    return (
        <div className="text-center text-[#444460] text-xs p-4 font-light leading-relaxed">
            Enter the first 4 digits of your order number
            <br />
            (e.g. <strong className="text-[#777]">1350</strong> from order #1350xx)
        </div>
    );
}
