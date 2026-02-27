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

                    return (
                        <button
                            key={v.id}
                            onClick={() => onSelect(v.id)}
                            className="rounded-lg px-2.5 py-2 text-left font-mono text-[11px] cursor-pointer transition-all duration-150"
                            style={{
                                background: active ? (colors.tagBg || '#1a1a28') : '#1a1a28',
                                border: `1.5px solid ${active ? colors.border : '#2a2a3a'}`,
                                color: active ? '#fff' : '#888',
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
