export default function DataTable({ columns, rows, sort, direction, onSort, actions }) {
    return (
        <div className="bg-[#15151f] border border-[#2a2a3a] rounded-xl overflow-hidden">
            <table className="w-full text-xs font-mono">
                <thead>
                    <tr className="border-b border-[#2a2a3a]">
                        {columns.map((col) => (
                            <th
                                key={col.key}
                                className={`px-4 py-3 text-left text-[10px] uppercase tracking-[1.5px] text-[#555570] font-semibold ${
                                    col.sortable !== false ? 'cursor-pointer hover:text-[#a8a8bc]' : ''
                                }`}
                                onClick={() => col.sortable !== false && onSort?.(col.key)}
                            >
                                {col.label}
                                {sort === col.key && (
                                    <span className="ml-1">{direction === 'asc' ? '\u2191' : '\u2193'}</span>
                                )}
                            </th>
                        ))}
                        {actions && <th className="px-4 py-3" />}
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 ? (
                        <tr>
                            <td
                                colSpan={columns.length + (actions ? 1 : 0)}
                                className="px-4 py-8 text-center text-[#555570]"
                            >
                                No data
                            </td>
                        </tr>
                    ) : (
                        rows.map((row, i) => (
                            <tr key={row.id || i} className="border-b border-[#1a1a28] hover:bg-[#1a1a28]">
                                {columns.map((col) => (
                                    <td key={col.key} className="px-4 py-3 text-[#a8a8bc]">
                                        {col.render ? col.render(row) : row[col.key]}
                                    </td>
                                ))}
                                {actions && (
                                    <td className="px-4 py-3 text-right">
                                        {actions(row)}
                                    </td>
                                )}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
