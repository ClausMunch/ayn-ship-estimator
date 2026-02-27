function getReadableTextColor(hex) {
    if (!hex || typeof hex !== 'string' || !hex.startsWith('#')) {
        return '#ffffff';
    }

    const raw = hex.slice(1);
    const normalized = raw.length === 3
        ? raw.split('').map((c) => c + c).join('')
        : raw;

    if (normalized.length !== 6) {
        return '#ffffff';
    }

    const r = parseInt(normalized.slice(0, 2), 16);
    const g = parseInt(normalized.slice(2, 4), 16);
    const b = parseInt(normalized.slice(4, 6), 16);

    if ([r, g, b].some(Number.isNaN)) {
        return '#ffffff';
    }

    const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;
    return luminance > 150 ? '#111827' : '#ffffff';
}

export default function ModelSelector({ variants, selectedId, onSelect }) {
    return (
        <div>
            <span className="block text-[10px] font-semibold uppercase tracking-[1.5px] text-[#555570] mb-2.5">
                Model
            </span>
            <div className="grid grid-cols-2 gap-1.5 mb-6">
                {variants.map((v) => {
                    const active = v.id === selectedId;
                    const colors = v.color_config;
                    const activeBackground = colors.tagBg || '#1a1a28';
                    const activeTextColor = getReadableTextColor(activeBackground);

                    return (
                        <button
                            key={v.id}
                            onClick={() => onSelect(v.id)}
                            className="rounded-lg px-2.5 py-2 text-left font-mono text-[11px] cursor-pointer transition-all duration-150"
                            style={{
                                background: active ? activeBackground : '#1a1a28',
                                border: `1.5px solid ${active ? colors.border : '#2a2a3a'}`,
                                color: active ? activeTextColor : '#a8a8bc',
                                fontWeight: active ? 500 : 400,
                            }}
                        >
                            {v.name}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
