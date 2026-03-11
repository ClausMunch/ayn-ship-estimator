/**
 * Build the cumulative end-point timeline for a variant from its shipping batches.
 * Returns sorted array of { date: "YYYY-MM-DD", end: number }
 */
export function buildTimeline(batches) {
    const map = new Map();
    for (const b of batches) {
        const date = b.ship_date;
        const end = Number(b.order_range_end);
        if (!date || Number.isNaN(end)) {
            continue;
        }
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
    if (!dateStr) return NaN;

    if (dateStr instanceof Date) {
        if (Number.isNaN(dateStr.getTime())) return NaN;
        return Date.UTC(dateStr.getUTCFullYear(), dateStr.getUTCMonth(), dateStr.getUTCDate());
    }

    if (typeof dateStr === 'string') {
        const match = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            const year = Number(match[1]);
            const month = Number(match[2]) - 1;
            const day = Number(match[3]);
            return Date.UTC(year, month, day);
        }
    }

    const parsed = new Date(dateStr);
    if (Number.isNaN(parsed.getTime())) return NaN;

    return Date.UTC(parsed.getUTCFullYear(), parsed.getUTCMonth(), parsed.getUTCDate());
}

function formatDate(ts) {
    if (!Number.isFinite(ts)) {
        return null;
    }

    const d = new Date(ts);
    if (Number.isNaN(d.getTime())) {
        return null;
    }

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
        const ts = toTimestamp(timeline[0].date);
        const formatted = formatDate(ts);
        if (!formatted) return null;

        return {
            type: 'shipped',
            date: timeline[0].date,
            formatted,
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
                const formatted = formatDate(currTs);
                if (!formatted) return null;

                return {
                    type: 'shipped',
                    date: timeline[i].date,
                    formatted,
                };
            }

            const ratio = (orderPrefix - prevEnd) / (currEnd - prevEnd);
            const estimatedTs = prevTs + ratio * (currTs - prevTs);
            const formatted = formatDate(estimatedTs);
            if (!formatted) return null;
            return { type: 'estimated', formatted };
        }
    }

    // Extrapolate beyond known data using weighted average of recent rates
    if (timeline.length >= 2) {
        const last = timeline[timeline.length - 1];
        const lastTs = toTimestamp(last.date);

        // Collect rates (ms per order) from up to 5 most recent consecutive pairs
        const maxPairs = Math.min(timeline.length - 1, 5);
        const rates = [];
        for (let i = timeline.length - 1; i >= timeline.length - maxPairs; i--) {
            const orderDiff = timeline[i].end - timeline[i - 1].end;
            if (orderDiff > 0) {
                const timeDiff = toTimestamp(timeline[i].date) - toTimestamp(timeline[i - 1].date);
                rates.push(timeDiff / orderDiff);
            }
        }

        if (rates.length === 0) return null;

        // Weighted average: most recent rate (index 0) gets highest weight
        let weightedSum = 0;
        let weightTotal = 0;
        for (let i = 0; i < rates.length; i++) {
            const weight = rates.length - i;
            weightedSum += rates[i] * weight;
            weightTotal += weight;
        }

        const avgRate = weightedSum / weightTotal;
        const extra = (orderPrefix - last.end) * avgRate;
        const formatted = formatDate(lastTs + extra);
        if (!formatted) return null;
        return { type: 'extrapolated', formatted };
    }

    return null;
}
