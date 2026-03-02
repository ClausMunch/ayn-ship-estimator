import { useState, useMemo, useEffect, useRef } from 'react';
import ModelSelector from '../Components/ModelSelector';
import OrderInput from '../Components/OrderInput';
import ResultDisplay from '../Components/ResultDisplay';
import SubscribeForm from '../Components/SubscribeForm';
import Toast from '../Components/Toast';
import { buildTimeline, estimateShipDate } from '../lib/estimation';

export default function Home({ variants, lastUpdated, csrfToken, signedTs }) {
    const [selectedVariantId, setSelectedVariantId] = useState(variants[0]?.id);
    const [orderInput, setOrderInput] = useState('');

    const verified = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('verified');

    const selectedVariant = variants.find((v) => v.id === selectedVariantId);

    const timeline = useMemo(() => {
        if (!selectedVariant) return [];
        return buildTimeline(selectedVariant.shipping_batches);
    }, [selectedVariant]);

    const result = useMemo(() => {
        const num = parseInt(orderInput, 10);
        if (isNaN(num) || orderInput.length !== 4) return null;
        return estimateShipDate(timeline, num);
    }, [orderInput, timeline]);

    const lastEnd = timeline.length > 0 ? timeline[timeline.length - 1].end : null;

    // Track page view on mount
    useEffect(() => {
        fetch('/api/track/view', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } }).catch(() => {});
    }, []);

    // Track estimations when result changes
    const lastTracked = useRef(null);
    useEffect(() => {
        if (!result || !result.type) return;
        const key = `${selectedVariantId}-${orderInput}-${result.type}`;
        if (key === lastTracked.current) return;
        lastTracked.current = key;
        fetch('/api/track/estimation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                model_variant_id: selectedVariantId,
                order_prefix: parseInt(orderInput, 10),
                result_type: result.type,
            }),
        }).catch(() => {});
    }, [result]);

    return (
        <main className="min-h-screen bg-[#0c0c14] text-[#e0e0e8] font-mono flex flex-col items-center justify-center p-6">
            {verified && (
                <Toast message="Email verified! You'll be notified when your estimate changes." />
            )}

            <article className="w-full max-w-[480px] bg-[#15151f] border border-[#2a2a3a] rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.5),0_0_0_1px_rgba(255,255,255,0.03)]">
                <header className="px-7 pt-7 pb-5 border-b border-[#2a2a3a] text-center">
                    <h1 className="font-sans text-xl font-semibold text-white tracking-tight mb-1">
                        AYN Thor Ship Date Estimator
                    </h1>
                    <p className="text-xs text-[#666680] font-light">
                        Based on known shipping batches
                        {lastUpdated && ` · Updated ${new Date(lastUpdated).toLocaleDateString()}`}
                    </p>
                </header>

                <section className="px-7 pt-6 pb-7">
                    <ModelSelector
                        variants={variants}
                        selectedId={selectedVariantId}
                        onSelect={setSelectedVariantId}
                    />

                    <OrderInput value={orderInput} onChange={setOrderInput} />

                    <ResultDisplay
                        result={result}
                        orderInput={orderInput}
                        modelName={selectedVariant?.name || ''}
                        lastEnd={lastEnd}
                    />

                    <SubscribeForm
                        variantId={selectedVariantId}
                        orderPrefix={orderInput}
                        signedTs={signedTs}
                        csrfToken={csrfToken}
                    />
                </section>
            </article>

            <footer className="mt-6 text-center text-[10px] text-[#444460] font-light space-y-1">
                <p>
                    Data scraped from{' '}
                    <a
                        href="https://www.ayntec.com/pages/shipment-dashboard"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="underline hover:text-[#666680]"
                    >
                        ayntec.com
                    </a>
                </p>
            </footer>
        </main>
    );
}
