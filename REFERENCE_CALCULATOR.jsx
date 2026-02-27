import { useState, useMemo } from "react";

const SHIPPING_DATA = {
  "Rainbow Max": [
    { date: "2026-01-15", end: 1114 },
    { date: "2026-01-19", end: 1175 },
    { date: "2026-01-23", end: 1210 },
    { date: "2026-01-25", end: 1258 },
    { date: "2026-01-27", end: 1286 },
    { date: "2026-01-28", end: 1308 },
    { date: "2026-01-30", end: 1319 },
    { date: "2026-02-05", end: 1343 },
    { date: "2026-02-09", end: 1417 },
    { date: "2026-02-12", end: 1426 },
  ],
  "Rainbow Pro": [
    { date: "2026-01-15", end: 1109 },
    { date: "2026-01-20", end: 1147 },
    { date: "2026-01-24", end: 1186 },
    { date: "2026-01-26", end: 1219 },
    { date: "2026-01-29", end: 1291 },
    { date: "2026-01-30", end: 1295 },
    { date: "2026-02-02", end: 1364 },
    { date: "2026-02-03", end: 1386 },
    { date: "2026-02-04", end: 1395 },
    { date: "2026-02-05", end: 1438 },
    { date: "2026-02-12", end: 1440 },
  ],
  "Black Pro": [
    { date: "2026-01-20", end: 1094 },
    { date: "2026-01-21", end: 1139 },
    { date: "2026-01-24", end: 1195 },
    { date: "2026-01-29", end: 1232 },
    { date: "2026-01-30", end: 1285 },
    { date: "2026-02-01", end: 1311 },
    { date: "2026-02-03", end: 1404 },
    { date: "2026-02-12", end: 1437 },
  ],
  "Black Max": [
    { date: "2026-01-20", end: 1135 },
    { date: "2026-01-21", end: 1147 },
    { date: "2026-01-24", end: 1190 },
    { date: "2026-01-27", end: 1267 },
    { date: "2026-01-29", end: 1300 },
    { date: "2026-02-01", end: 1352 },
    { date: "2026-02-09", end: 1402 },
    { date: "2026-02-12", end: 1437 },
  ],
  "Black Base": [
    { date: "2026-01-21", end: 1088 },
    { date: "2026-01-30", end: 1269 },
    { date: "2026-02-01", end: 1344 },
    { date: "2026-02-03", end: 1420 },
    { date: "2026-02-04", end: 1500 },
    { date: "2026-02-12", end: 1510 },
  ],
  "Black Lite": [
    { date: "2026-02-04", end: 1492 },
  ],
  "White Pro": [
    { date: "2026-01-21", end: 1097 },
    { date: "2026-01-26", end: 1279 },
    { date: "2026-02-02", end: 1376 },
    { date: "2026-02-05", end: 1465 },
    { date: "2026-02-12", end: 1465 },
  ],
  "White Max": [
    { date: "2026-01-22", end: 1160 },
    { date: "2026-02-09", end: 1335 },
    { date: "2026-02-12", end: 1438 },
  ],
  "Clear Purple Max": [
    { date: "2026-01-22", end: 1090 },
    { date: "2026-01-23", end: 1102 },
    { date: "2026-01-26", end: 1139 },
    { date: "2026-01-27", end: 1153 },
    { date: "2026-02-01", end: 1172 },
    { date: "2026-02-12", end: 1439 },
  ],
  "Clear Purple Pro": [
    { date: "2026-01-22", end: 1117 },
    { date: "2026-01-27", end: 1156 },
    { date: "2026-02-01", end: 1317 },
    { date: "2026-02-12", end: 1440 },
  ],
};

function toTimestamp(dateStr) {
  return new Date(dateStr + "T00:00:00Z").getTime();
}

function formatDate(ts) {
  const d = new Date(ts);
  const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  const days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
  return `${days[d.getUTCDay()]}, ${months[d.getUTCMonth()]} ${d.getUTCDate()}, ${d.getUTCFullYear()}`;
}

function estimateShipDate(model, orderNum) {
  const data = SHIPPING_DATA[model];
  if (!data || data.length === 0) return null;

  // If order already shipped (before or at first known end)
  if (orderNum <= data[0].end) {
    return { type: "shipped", date: data[0].date, formatted: formatDate(toTimestamp(data[0].date)) };
  }

  // If within known range, interpolate
  for (let i = 1; i < data.length; i++) {
    if (orderNum <= data[i].end) {
      const prevEnd = data[i - 1].end;
      const currEnd = data[i].end;
      const prevTs = toTimestamp(data[i - 1].date);
      const currTs = toTimestamp(data[i].date);

      if (currEnd === prevEnd) {
        return { type: "shipped", date: data[i].date, formatted: formatDate(currTs) };
      }

      const ratio = (orderNum - prevEnd) / (currEnd - prevEnd);
      const estimatedTs = prevTs + ratio * (currTs - prevTs);
      return { type: "estimated", formatted: formatDate(estimatedTs) };
    }
  }

  // Beyond known data — extrapolate from last two points
  if (data.length >= 2) {
    const last = data[data.length - 1];
    const prev = data[data.length - 2];
    const lastTs = toTimestamp(last.date);
    const prevTs = toTimestamp(prev.date);
    const orderRange = last.end - prev.end;

    if (orderRange <= 0) {
      // Can't extrapolate, use rate from further back
      for (let i = data.length - 2; i >= 1; i--) {
        const r = data[i].end - data[i - 1].end;
        if (r > 0) {
          const t = toTimestamp(data[i].date) - toTimestamp(data[i - 1].date);
          const rate = t / r;
          const extra = (orderNum - last.end) * rate;
          return { type: "extrapolated", formatted: formatDate(lastTs + extra) };
        }
      }
      return null;
    }

    const rate = (lastTs - prevTs) / orderRange; // ms per order unit
    const extra = (orderNum - last.end) * rate;
    return { type: "extrapolated", formatted: formatDate(lastTs + extra) };
  }

  return null;
}

const MODELS = Object.keys(SHIPPING_DATA);

const MODEL_COLORS = {
  "Rainbow Max": { bg: "linear-gradient(135deg, #ff6b6b22, #ffd93d22, #6bcb7722, #4ecdc422, #a06cd522)", border: "#e8567d", accent: "#e8567d", tag: "#fff0f3" },
  "Rainbow Pro": { bg: "linear-gradient(135deg, #ff6b6b18, #ffd93d18, #6bcb7718)", border: "#d4943a", accent: "#d4943a", tag: "#fff8ed" },
  "Black Pro": { bg: "#0a0a0a08", border: "#333", accent: "#1a1a1a", tag: "#f0f0f0" },
  "Black Max": { bg: "#0a0a0a0c", border: "#111", accent: "#000", tag: "#e8e8e8" },
  "Black Base": { bg: "#0a0a0a06", border: "#555", accent: "#444", tag: "#f5f5f5" },
  "Black Lite": { bg: "#0a0a0a04", border: "#777", accent: "#666", tag: "#f8f8f8" },
  "White Pro": { bg: "#f8f8ff", border: "#c0c0d0", accent: "#6a6a8a", tag: "#eeeef8" },
  "White Max": { bg: "#fafafe", border: "#9a9ab0", accent: "#55557a", tag: "#e8e8f5" },
  "Clear Purple Max": { bg: "linear-gradient(135deg, #9b59b612, #8e44ad12)", border: "#9b59b6", accent: "#8e44ad", tag: "#f5eef8" },
  "Clear Purple Pro": { bg: "linear-gradient(135deg, #9b59b60a, #8e44ad0a)", border: "#b07cc5", accent: "#a368b8", tag: "#f8f2fb" },
};

export default function AYNThorCalculator() {
  const [orderInput, setOrderInput] = useState("");
  const [selectedModel, setSelectedModel] = useState("Rainbow Max");

  const result = useMemo(() => {
    const num = parseInt(orderInput, 10);
    if (isNaN(num) || orderInput.length !== 4) return null;
    return estimateShipDate(selectedModel, num);
  }, [orderInput, selectedModel]);

  const colors = MODEL_COLORS[selectedModel];

  const lastShipped = SHIPPING_DATA[selectedModel]?.[SHIPPING_DATA[selectedModel].length - 1];

  return (
    <div style={{
      minHeight: "100vh",
      background: "#0c0c14",
      color: "#e0e0e8",
      fontFamily: "'JetBrains Mono', 'Fira Code', 'SF Mono', monospace",
      display: "flex",
      flexDirection: "column",
      alignItems: "center",
      justifyContent: "center",
      padding: "24px",
    }}>
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap');
        
        * { box-sizing: border-box; }
        
        .card {
          background: #15151f;
          border: 1px solid #2a2a3a;
          border-radius: 16px;
          width: 100%;
          max-width: 480px;
          overflow: hidden;
          box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.03);
        }
        
        .header {
          padding: 28px 28px 20px;
          border-bottom: 1px solid #2a2a3a;
          text-align: center;
        }
        
        .header h1 {
          font-family: 'Space Grotesk', sans-serif;
          font-size: 20px;
          font-weight: 600;
          margin: 0 0 4px;
          color: #fff;
          letter-spacing: -0.3px;
        }
        
        .header p {
          font-size: 12px;
          color: #666680;
          margin: 0;
          font-weight: 300;
        }
        
        .body { padding: 24px 28px 28px; }
        
        .label {
          font-size: 10px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 1.5px;
          color: #555570;
          margin-bottom: 10px;
          display: block;
        }
        
        .model-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 6px;
          margin-bottom: 24px;
        }
        
        .model-btn {
          background: #1a1a28;
          border: 1.5px solid #2a2a3a;
          border-radius: 8px;
          padding: 8px 10px;
          color: #888;
          font-family: 'JetBrains Mono', monospace;
          font-size: 11px;
          font-weight: 400;
          cursor: pointer;
          transition: all 0.15s ease;
          text-align: left;
        }
        
        .model-btn:hover {
          background: #20202f;
          border-color: #3a3a50;
          color: #bbb;
        }
        
        .model-btn.active {
          border-color: var(--accent);
          color: #fff;
          background: var(--tag-bg);
          font-weight: 500;
        }
        
        .order-input-wrap {
          position: relative;
          margin-bottom: 24px;
        }
        
        .order-input {
          width: 100%;
          background: #1a1a28;
          border: 1.5px solid #2a2a3a;
          border-radius: 10px;
          padding: 16px 80px 16px 18px;
          color: #fff;
          font-family: 'JetBrains Mono', monospace;
          font-size: 22px;
          font-weight: 600;
          letter-spacing: 4px;
          outline: none;
          transition: border-color 0.15s;
          text-align: center;
        }
        
        .order-input::placeholder {
          color: #333348;
          letter-spacing: 4px;
          font-weight: 300;
        }
        
        .order-input:focus {
          border-color: #4a4a6a;
        }
        
        .order-suffix {
          position: absolute;
          right: 18px;
          top: 50%;
          transform: translateY(-50%);
          color: #333348;
          font-size: 22px;
          font-weight: 600;
          letter-spacing: 4px;
          pointer-events: none;
        }
        
        .result-box {
          border-radius: 12px;
          padding: 20px;
          text-align: center;
          animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(4px); }
          to { opacity: 1; transform: translateY(0); }
        }
        
        .result-label {
          font-size: 10px;
          text-transform: uppercase;
          letter-spacing: 1.5px;
          margin-bottom: 8px;
          font-weight: 500;
        }
        
        .result-date {
          font-family: 'Space Grotesk', sans-serif;
          font-size: 22px;
          font-weight: 700;
          color: #fff;
          letter-spacing: -0.3px;
        }
        
        .result-note {
          font-size: 11px;
          margin-top: 10px;
          opacity: 0.6;
          font-weight: 300;
          line-height: 1.5;
        }
        
        .empty-state {
          text-align: center;
          color: #444460;
          font-size: 12px;
          padding: 16px;
          font-weight: 300;
          line-height: 1.6;
        }
        
        .data-range {
          font-size: 10px;
          color: #444460;
          text-align: center;
          margin-top: 8px;
          font-weight: 300;
        }
        
        .status-dot {
          display: inline-block;
          width: 7px;
          height: 7px;
          border-radius: 50%;
          margin-right: 6px;
          position: relative;
          top: -1px;
        }
      `}</style>

      <div className="card">
        <div className="header">
          <h1>AYN Thor Ship Date Estimator</h1>
          <p>Based on known shipping batches through Feb 12, 2026</p>
        </div>

        <div className="body">
          <span className="label">Model</span>
          <div className="model-grid">
            {MODELS.map((m) => {
              const c = MODEL_COLORS[m];
              return (
                <button
                  key={m}
                  className={`model-btn ${selectedModel === m ? "active" : ""}`}
                  style={{
                    "--accent": c.accent,
                    "--tag-bg": selectedModel === m ? (c.bg.includes("gradient") ? c.tag : c.bg) : undefined,
                    borderColor: selectedModel === m ? c.border : undefined,
                  }}
                  onClick={() => setSelectedModel(m)}
                >
                  {m}
                </button>
              );
            })}
          </div>

          <span className="label">Order Number (first 4 digits)</span>
          <div className="order-input-wrap">
            <input
              className="order-input"
              type="text"
              inputMode="numeric"
              maxLength={4}
              placeholder="1350"
              value={orderInput}
              onChange={(e) => {
                const v = e.target.value.replace(/\D/g, "").slice(0, 4);
                setOrderInput(v);
              }}
            />
            <span className="order-suffix">xx</span>
          </div>

          {result ? (
            <div
              className="result-box"
              style={{
                background: result.type === "shipped"
                  ? "linear-gradient(135deg, #0a2a15, #152f1a)"
                  : result.type === "estimated"
                  ? "linear-gradient(135deg, #1a1a2e, #1e1e35)"
                  : "linear-gradient(135deg, #2a1a0a, #2f2015)",
                border: `1px solid ${
                  result.type === "shipped" ? "#1a5a30" : result.type === "estimated" ? "#3a3a5a" : "#5a3a1a"
                }`,
              }}
            >
              <div
                className="result-label"
                style={{
                  color: result.type === "shipped" ? "#4ade80" : result.type === "estimated" ? "#818cf8" : "#fbbf24",
                }}
              >
                <span
                  className="status-dot"
                  style={{
                    background: result.type === "shipped" ? "#4ade80" : result.type === "estimated" ? "#818cf8" : "#fbbf24",
                  }}
                />
                {result.type === "shipped"
                  ? "Already Shipped"
                  : result.type === "estimated"
                  ? "Estimated Ship Date"
                  : "Projected Ship Date"}
              </div>
              <div className="result-date">{result.formatted}</div>
              <div className="result-note">
                {result.type === "shipped"
                  ? `Order ${orderInput}xx was in a batch shipped on or before this date.`
                  : result.type === "estimated"
                  ? `Interpolated from known shipping batches for ${selectedModel}.`
                  : `Extrapolated beyond known data — take with a grain of salt.`}
              </div>
            </div>
          ) : orderInput.length > 0 && orderInput.length < 4 ? (
            <div className="empty-state">Enter all 4 digits to see your estimate.</div>
          ) : orderInput.length === 0 ? (
            <div className="empty-state">
              Enter the first 4 digits of your order number<br />
              (e.g. <strong style={{ color: "#777" }}>1350</strong> from order #1350xx)
            </div>
          ) : null}

          {lastShipped && (
            <div className="data-range">
              {selectedModel} data covers orders up to {lastShipped.end}xx (shipped {lastShipped.date})
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
