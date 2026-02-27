import { useState } from 'react';

export default function SubscribeForm({ variantId, orderPrefix, signedTs, csrfToken }) {
    const [email, setEmail] = useState('');
    const [status, setStatus] = useState(null); // 'success' | 'error'
    const [message, setMessage] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [expanded, setExpanded] = useState(false);

    if (!orderPrefix || orderPrefix.length !== 4) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setStatus(null);

        try {
            const res = await fetch('/api/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    email,
                    model_variant_id: variantId,
                    order_prefix: parseInt(orderPrefix, 10),
                    website: '', // honeypot
                    _ts: signedTs,
                }),
            });

            const data = await res.json();

            if (res.ok) {
                setStatus('success');
                setMessage(data.message);
            } else {
                setStatus('error');
                const errors = data.errors;
                setMessage(errors ? Object.values(errors).flat()[0] : (data.message || 'Something went wrong.'));
            }
        } catch {
            setStatus('error');
            setMessage('Network error. Please try again.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="mt-6">
            {!expanded ? (
                <button
                    onClick={() => setExpanded(true)}
                    className="w-full rounded-xl py-3 text-xs font-medium cursor-pointer transition-all duration-150 hover:border-[#3a3a5a]"
                    style={{
                        background: '#1a1a28',
                        border: '1.5px solid #2a2a3a',
                        color: '#818cf8',
                    }}
                >
                    Get notified when your estimate changes
                </button>
            ) : (
                <form onSubmit={handleSubmit} className="rounded-xl p-4" style={{ background: '#1a1a28', border: '1.5px solid #2a2a3a' }}>
                    <div className="text-[10px] font-semibold uppercase tracking-[1.5px] text-[#555570] mb-2.5">
                        Email notifications
                    </div>

                    <input
                        type="email"
                        required
                        placeholder="your@email.com"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full rounded-lg px-3 py-2.5 text-sm text-white outline-none mb-3 font-mono"
                        style={{ background: '#15151f', border: '1px solid #2a2a3a' }}
                        onFocus={(e) => (e.target.style.borderColor = '#4a4a6a')}
                        onBlur={(e) => (e.target.style.borderColor = '#2a2a3a')}
                    />

                    {/* Honeypot — hidden from real users */}
                    <div style={{ position: 'absolute', left: '-9999px', opacity: 0 }} aria-hidden="true">
                        <input type="text" name="website" tabIndex={-1} autoComplete="off" />
                    </div>

                    <button
                        type="submit"
                        disabled={submitting || !email}
                        className="w-full rounded-lg py-2.5 text-xs font-semibold cursor-pointer transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed"
                        style={{ background: '#818cf8', color: '#fff', border: 'none' }}
                    >
                        {submitting ? 'Subscribing...' : 'Notify me'}
                    </button>

                    {status && (
                        <div
                            className="mt-3 text-xs text-center font-light"
                            style={{ color: status === 'success' ? '#4ade80' : '#f87171' }}
                        >
                            {message}
                        </div>
                    )}

                    <div className="mt-2 text-[10px] text-[#444460] text-center font-light">
                        We'll email you when the estimate for order #{orderPrefix}xx changes.
                    </div>
                </form>
            )}
        </div>
    );
}
