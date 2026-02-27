import { useState, useEffect } from 'react';

export default function Toast({ message, type = 'success', duration = 5000 }) {
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        const timer = setTimeout(() => setVisible(false), duration);
        return () => clearTimeout(timer);
    }, [duration]);

    if (!visible) return null;

    const colors = {
        success: { bg: '#0a2a15', border: '#1a5a30', text: '#4ade80' },
        error: { bg: '#2a0a0a', border: '#5a1a1a', text: '#f87171' },
    };
    const c = colors[type] || colors.success;

    return (
        <div
            className="fixed top-4 left-1/2 -translate-x-1/2 z-50 animate-fade-in"
            style={{
                background: c.bg,
                border: `1px solid ${c.border}`,
                borderRadius: 12,
                padding: '12px 24px',
                maxWidth: 420,
            }}
        >
            <div className="flex items-center gap-2">
                <span
                    className="inline-block w-2 h-2 rounded-full"
                    style={{ background: c.text }}
                />
                <span className="text-sm font-mono" style={{ color: c.text }}>
                    {message}
                </span>
                <button
                    onClick={() => setVisible(false)}
                    className="ml-3 text-xs cursor-pointer"
                    style={{ color: c.text, opacity: 0.6, background: 'none', border: 'none' }}
                >
                    ×
                </button>
            </div>
        </div>
    );
}
