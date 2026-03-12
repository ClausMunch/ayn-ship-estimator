/**
 * Build a global timeline from ALL variants' batches combined.
 * Groups by date, takes max end across all models, then computes a
 * running max so both date and end are monotonically increasing.
 */
export function buildGlobalTimeline(variants) {
    const map = new Map();
    for (const v of variants) {
        if (!v.shipping_batches) continue;
        for (const b of v.shipping_batches) {
            const date = b.ship_date;
            const end = Number(b.order_range_end);
            if (!date || Number.isNaN(end)) continue;
            if (!map.has(date) || end > map.get(date)) {
                map.set(date, end);
            }
        }
    }

    // Sort by date chronologically, then compute running max of end
    const sorted = Array.from(map, ([date, end]) => ({ date, end }));
    sorted.sort((a, b) => (a.date < b.date ? -1 : a.date > b.date ? 1 : 0));

    const points = [];
    let runningMax = 0;
    for (const p of sorted) {
        if (p.end > runningMax) {
            runningMax = p.end;
            points.push({ date: p.date, end: runningMax });
        }
    }

    return points;
}

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
 * @param {Array<{date: string, end: number}>} timeline - per-model timeline, sorted by end ascending
 * @param {number} orderPrefix - 4-digit order prefix
 * @param {Array<{date: string, end: number}>} [globalTimeline] - all-models timeline for extrapolation
 * @returns {{ type: 'shipped'|'estimated'|'extrapolated', date?: string, formatted: string } | null}
 */
export function estimateShipDate(timeline, orderPrefix, globalTimeline) {
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

    // Extrapolate beyond known data using the global timeline (all models combined).
    // Order numbers come from a single pool across all models, so the global
    // shipping rate reflects how fast AYN processes orders overall.
    const extTimeline = globalTimeline && globalTimeline.length >= 2 ? globalTimeline : timeline;

    if (extTimeline.length >= 2) {
        const last = extTimeline[extTimeline.length - 1];
        const lastTs = toTimestamp(last.date);

        // If the order is already within the global frontier, use the global
        // timeline to interpolate — AYN has shipped this order range for other
        // models, so this model should follow soon.
        if (globalTimeline && globalTimeline.length >= 2 && orderPrefix <= last.end) {
            for (let i = 1; i < globalTimeline.length; i++) {
                if (orderPrefix <= globalTimeline[i].end) {
                    const prevEnd = globalTimeline[i - 1].end;
                    const currEnd = globalTimeline[i].end;
                    const prevTs = toTimestamp(globalTimeline[i - 1].date);
                    const currTs = toTimestamp(globalTimeline[i].date);

                    if (currEnd === prevEnd) {
                        const formatted = formatDate(currTs);
                        if (!formatted) return null;
                        return { type: 'extrapolated', formatted };
                    }

                    const ratio = (orderPrefix - prevEnd) / (currEnd - prevEnd);
                    const estimatedTs = prevTs + ratio * (currTs - prevTs);
                    const formatted = formatDate(estimatedTs);
                    if (!formatted) return null;
                    return { type: 'extrapolated', formatted };
                }
            }
        }

        // Collect rates (ms per order) and order volumes from up to 5 most recent pairs
        const maxPairs = Math.min(extTimeline.length - 1, 5);
        const pairs = [];
        for (let i = extTimeline.length - 1; i >= extTimeline.length - maxPairs; i--) {
            const orderDiff = extTimeline[i].end - extTimeline[i - 1].end;
            if (orderDiff > 0) {
                const timeDiff = toTimestamp(extTimeline[i].date) - toTimestamp(extTimeline[i - 1].date);
                pairs.push({ rate: timeDiff / orderDiff, volume: orderDiff });
            }
        }

        if (pairs.length === 0) return null;

        // Weighted average: weight by recency (most recent first) × order volume
        // This prevents small cleanup batches from dominating the projection
        let weightedSum = 0;
        let weightTotal = 0;
        for (let i = 0; i < pairs.length; i++) {
            const recency = pairs.length - i;
            const weight = recency * pairs[i].volume;
            weightedSum += pairs[i].rate * weight;
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
