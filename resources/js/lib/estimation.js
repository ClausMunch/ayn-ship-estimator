/**
 * Build the cumulative end-point timeline for a variant from its shipping batches.
 * Returns sorted array of { date: "YYYY-MM-DD", end: number }
 */
export function buildTimeline(batches) {
    const map = new Map();
    for (const b of batches) {
        const date = b.ship_date;
        const end = b.order_range_end;
        if (!map.has(date) || end > map.get(date)) {
            map.set(date, end);
        }
    }

    const points = Array.from(map, ([date, end]) => ({ date, end }));
    points.sort((a, b) => a.end - b.end);

    // Deduplicate: if two points have the same end, keep the earlier date
    const deduped = [];
    for (const p of points) {
        if (deduped.length === 0 || p.end > deduped[deduped.length - 1].end) {
            deduped.push(p);
        }
    }

    return deduped;
}

function toTimestamp(dateStr) {
    return new Date(dateStr + 'T00:00:00Z').getTime();
}

function formatDate(ts) {
    const d = new Date(ts);
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    return `${days[d.getUTCDay()]}, ${months[d.getUTCMonth()]} ${d.getUTCDate()}, ${d.getUTCFullYear()}`;
}

/**
 * Estimate ship date for a given order prefix using the timeline data points.
 * @param {Array<{date: string, end: number}>} timeline - sorted by end ascending
 * @param {number} orderPrefix - 4-digit order prefix
 * @returns {{ type: 'shipped'|'estimated'|'extrapolated', date?: string, formatted: string } | null}
 */
export function estimateShipDate(timeline, orderPrefix) {
    if (!timeline || timeline.length === 0) return null;

    // Already shipped
    if (orderPrefix <= timeline[0].end) {
        return {
            type: 'shipped',
            date: timeline[0].date,
            formatted: formatDate(toTimestamp(timeline[0].date)),
        };
    }

    // Interpolate within known range
    for (let i = 1; i < timeline.length; i++) {
        if (orderPrefix <= timeline[i].end) {
            const prevEnd = timeline[i - 1].end;
            const currEnd = timeline[i].end;
            const prevTs = toTimestamp(timeline[i - 1].date);
            const currTs = toTimestamp(timeline[i].date);

            if (currEnd === prevEnd) {
                return {
                    type: 'shipped',
                    date: timeline[i].date,
                    formatted: formatDate(currTs),
                };
            }

            const ratio = (orderPrefix - prevEnd) / (currEnd - prevEnd);
            const estimatedTs = prevTs + ratio * (currTs - prevTs);
            return { type: 'estimated', formatted: formatDate(estimatedTs) };
        }
    }

    // Extrapolate beyond known data
    if (timeline.length >= 2) {
        const last = timeline[timeline.length - 1];
        const prev = timeline[timeline.length - 2];
        const lastTs = toTimestamp(last.date);
        const prevTs = toTimestamp(prev.date);
        const orderRange = last.end - prev.end;

        if (orderRange <= 0) {
            // Can't extrapolate from last two, look further back
            for (let i = timeline.length - 2; i >= 1; i--) {
                const r = timeline[i].end - timeline[i - 1].end;
                if (r > 0) {
                    const t = toTimestamp(timeline[i].date) - toTimestamp(timeline[i - 1].date);
                    const rate = t / r;
                    const extra = (orderPrefix - last.end) * rate;
                    return { type: 'extrapolated', formatted: formatDate(lastTs + extra) };
                }
            }
            return null;
        }

        const rate = (lastTs - prevTs) / orderRange;
        const extra = (orderPrefix - last.end) * rate;
        return { type: 'extrapolated', formatted: formatDate(lastTs + extra) };
    }

    return null;
}
