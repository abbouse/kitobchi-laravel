import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

interface RegionStat {
  name: string;
  value: number;
  color: string;
  coords: { x: number; y: number };
  revenue: number;
  profit: number;
}

const initialRegions: RegionStat[] = [
  { name: 'Toshkent', value: 34, color: '#a855f7', coords: { x: 72, y: 35 }, revenue: 18_450_200, profit: 7_380_080 },
  { name: 'Samarqand', value: 22, color: '#6366f1', coords: { x: 50, y: 55 }, revenue: 6_210_400, profit: 2_484_160 },
  { name: 'Buxoro', value: 18, color: '#3b82f6', coords: { x: 35, y: 60 }, revenue: 4_480_500, profit: 1_792_200 },
  { name: "Farg'ona", value: 15, color: '#10b981', coords: { x: 85, y: 45 }, revenue: 5_420_600, profit: 2_168_240 },
  { name: 'Andijon', value: 12, color: '#f59e0b', coords: { x: 92, y: 40 }, revenue: 3_680_200, profit: 1_472_080 },
  { name: 'Namangan', value: 14, color: '#ec4899', coords: { x: 82, y: 32 }, revenue: 3_190_800, profit: 1_276_320 },
  { name: 'Xorazm', value: 10, color: '#06b6d4', coords: { x: 20, y: 45 }, revenue: 2_360_700, profit: 944_280 },
  { name: 'Qashqadaryo', value: 16, color: '#f43f5e', coords: { x: 45, y: 70 }, revenue: 2_740_300, profit: 1_096_120 },
];

const appPlatforms = [
  { name: 'Android', icon: '🤖', color: '#10b981', activeUsers: 2684, orders: 904, revenue: 34_290_000, conv: 3.7, crash: 0.42 },
  { name: 'iOS', icon: '🍎', color: '#3b82f6', activeUsers: 728, orders: 380, revenue: 14_430_500, conv: 4.1, crash: 0.18 },
];

const segments = [
  { name: 'VIP', color: '#f59e0b', share: 5.0, revenue: 14_840_000, icon: '⭐' },
  { name: 'Loyal', color: '#10b981', share: 26.1, revenue: 16_920_000, icon: '💚' },
  { name: 'Occasional', color: '#3b82f6', share: 41.9, revenue: 11_420_000, icon: '💙' },
  { name: 'New', color: '#a855f7', share: 11.3, revenue: 3_840_000, icon: '🆕' },
  { name: 'Churned', color: '#ef4444', share: 15.7, revenue: 1_890_000, icon: '⚠️' },
];

const payments = [
  { name: 'Ulangan karta', share: 65.6, color: '#4f46e5', success: 95.7, failed: 38 },
  { name: 'Naqd', share: 34.4, color: '#f59e0b', success: 100, failed: 0 },
];

export default function LiveDashboard() {
  const [isPaused, setIsPaused] = useState(false);
  const [speed, setSpeed] = useState(2500);
  const [soundEnabled, setSoundEnabled] = useState(false);

  // Asosiy
  const [rev, setRev] = useState(48_720_500);
  const [ord, setOrd] = useState(1284);
  const [vis, setVis] = useState(3412);
  const [velocity, setVelocity] = useState(12);
  const [profit, setProfit] = useState(20_270_300);
  const loss = 2_082_500;
  const [netProfit, setNetProfit] = useState(5_990_300);
  const [grossMargin, setGrossMargin] = useState(41.6);
  const netMargin = 12.3;
  const [conversion, setConversion] = useState(3.42);
  const [aov, setAov] = useState(37940);
  const cac = 18500;
  const roas = 4.8;
  const retention = 72.5;
  const ltv = 485000;

  // Dinamik
  const [chart, setChart] = useState(() => Array.from({ length: 30 }, (_, i) => ({ t: i, v: 300 + Math.random() * 400, p: 120 + Math.random() * 180 })));
  const [funnel, setFunnel] = useState([56840, 53620, 34120, 18440, 8420, 4280, 2580, 1944]);
  const [feed, setFeed] = useState<{ id: number; text: string; amount: number; region: string; profit: number; platform: string; time: string }[]>([]);
  const [regionData, setRegionData] = useState<RegionStat[]>(initialRegions);
  const [platformData, setPlatformData] = useState(appPlatforms);
  const [activePulse, setActivePulse] = useState<string | null>('Toshkent');
  const [clock, setClock] = useState(new Date());
  const [insights, setInsights] = useState<string[]>([
    'Android 2.7.9 versiyasida crash yuqori — forced update tavsiya',
    '3 ta kitob zaxirasi kam — 12 kunga yetadi',
    'VIP segmenti daromadi +8.4% o\'sdi',
  ]);

  // Soat
  useEffect(() => {
    const t = setInterval(() => setClock(new Date()), 1000);
    return () => clearInterval(t);
  }, []);

  // Simulyator
  useEffect(() => {
    if (isPaused) return;

    const names = ['Aziza K.', 'Bobur A.', 'Dilnoza R.', 'Jamshid T.', 'Malika U.', 'Shaxlo Y.', 'Sardor M.', 'Kamola N.', 'Farhod T.', 'Diyor R.'];
    const products = [
      { n: '"O\'tkan kunlar"', p: 85000, c: 52000, t: 'book' },
      { n: '"Mehrobdan chayon"', p: 72000, c: 45000, t: 'book' },
      { n: '"Sarob"', p: 65000, c: 41000, t: 'book' },
      { n: 'Parker Ruchka', p: 245000, c: 155000, t: 'stationery' },
      { n: 'Moleskine Daftar', p: 185000, c: 110000, t: 'stationery' },
      { n: 'Stabilo Marker 10pk', p: 95000, c: 62000, t: 'stationery' },
      { n: '"Kecha va kunduz"', p: 78000, c: 48000, t: 'book' },
      { n: '"Yulduzli tunlar"', p: 95000, c: 58000, t: 'book' },
      { n: 'Faber-Castell qalam', p: 42000, c: 26000, t: 'stationery' },
      { n: "A4 Qog'oz 500v", p: 78000, c: 55000, t: 'stationery' },
    ];

    let id = Date.now();

    const addFeed = () => {
      const nm = names[Math.floor(Math.random() * names.length)];
      const pr = products[Math.floor(Math.random() * products.length)];
      const qty = 1 + Math.floor(Math.random() * 3);
      const amt = pr.p * qty;
      const prf = (pr.p - pr.c) * qty;
      const regObj = initialRegions[Math.floor(Math.random() * initialRegions.length)];
      const platform = appPlatforms[Math.floor(Math.random() * appPlatforms.length)];

      if (soundEnabled && amt > 200000) {
        try {
          const ctx = new (window.AudioContext || (window as any).webkitAudioContext)();
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          osc.type = 'sine';
          osc.frequency.setValueAtTime(587.33, ctx.currentTime);
          gain.gain.setValueAtTime(0.1, ctx.currentTime);
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.start();
          gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + 0.5);
          osc.stop(ctx.currentTime + 0.5);
        } catch (e) { /* ignore */ }
      }

      setFeed((f) => [
        { id: id++, text: `${nm} ${qty}x ${pr.n} sotib oldi`, amount: amt, profit: prf, region: regObj.name, platform: platform.name, time: new Date().toLocaleTimeString('uz-UZ') },
        ...f
      ].slice(0, 15));

      // Asosiy metrikalar
      setRev((r) => r + amt);
      setOrd((o) => o + 1);
      setProfit((p) => p + prf);
      setNetProfit((np) => np + Math.floor(prf * 0.4));
      setVis((v) => Math.max(0, v + Math.floor(Math.random() * 11 - 5)));
      setVelocity((v) => Math.min(45, Math.max(5, v + Math.floor(Math.random() * 5 - 2))));
      setAov(Math.floor((rev + amt) / (ord + 1)));
      setGrossMargin(+((profit + prf) / (rev + amt) * 100).toFixed(1));
      setConversion(+(((ord + 1) / vis) * 100).toFixed(2));

      // Chart
      setChart((c) => [...c.slice(1), { t: c[c.length - 1].t + 1, v: 200 + Math.random() * 600, p: 100 + Math.random() * 250 }]);

      // Funnel
      setFunnel((fn) => fn.map((v, i) => Math.max(1, v + (i === 0 ? 15 : i === 1 ? 12 : i === 2 ? 8 : i === 3 ? 5 : i === 4 ? 3 : i === 5 ? 2 : i === 6 ? 2 : 1))));

      // Region
      setRegionData((rs) => rs.map((r) => r.name === regObj.name ? { ...r, value: r.value + 1, revenue: r.revenue + amt, profit: r.profit + prf } : r));
      setActivePulse(regObj.name);

      // Platform
      setPlatformData((ps) => ps.map((p) => p.name === platform.name ? { ...p, orders: p.orders + 1, revenue: p.revenue + amt } : p));

      setTimeout(() => setActivePulse(null), 1200);
    };

    const iv = setInterval(addFeed, speed);
    return () => clearInterval(iv);
  }, [isPaused, speed, soundEnabled]);

  // Insight rotatsiyasi
  useEffect(() => {
    const insightsPool = [
      'Android 2.7.9 versiyasida crash 1.42% — majburiy yangilash tavsiya',
      '3 ta kitob zaxirasi kam — 12 kunga yetadi',
      'VIP segmenti daromadi +8.4% o\'sdi',
      'Savat tashlash 68.2% — checkout optimizatsiya kerak',
      'Shanba eng yuqori savdo kuni (+75% vs Dushanba)',
      'Mobile qurilmalar 64% — UX test tavsiya',
      'Karta ulash bosqichida 39.7% user yo\'qolmoqda — UX va bank xabarlarini yaxshilang',
      'LTV/CAC 26.2x — mukammal koeffitsient',
      'Naqd buyurtmalarda admin tasdiq SLA 26 minut — 20 minutdan pastga tushirish kerak',
    ];
    const iv = setInterval(() => {
      setInsights([insightsPool[Math.floor(Math.random() * insightsPool.length)]]);
    }, 8000);
    return () => clearInterval(iv);
  }, []);

  const goFull = () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen();
  };

  return (
    <div className="fs-live p-3 p-xl-4" style={{ minHeight: '100vh' }}>
      {/* ===== TOP BAR ===== */}
      <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 pb-2 border-bottom" style={{ borderColor: 'rgba(255,255,255,0.08)' }}>
        <div className="d-flex align-items-center gap-2">
          <div style={{ width: 44, height: 44, borderRadius: 12, background: 'linear-gradient(135deg, #4f46e5, #ec4899)', display: 'grid', placeItems: 'center', fontSize: 22, color: 'white', boxShadow: '0 0 20px rgba(236, 72, 153, 0.4)' }}>
            <i className="bi bi-broadcast"></i>
          </div>
          <div>
            <div className="d-flex align-items-center gap-2">
              <h3 style={{ color: '#f8fafc', fontWeight: 800, margin: 0, letterSpacing: '-0.5px' }}>Live Command Center</h3>
              <span className="chip" style={{ background: isPaused ? 'rgba(245,158,11,0.2)' : 'rgba(16,185,129,0.2)', color: isPaused ? '#fcd34d' : '#6ee7b7', border: `1px solid ${isPaused ? '#f59e0b' : '#10b981'}`, fontSize: 10 }}>
                {!isPaused && <span className="live-pulse"></span>}
                {isPaused ? 'PAUSED' : 'LIVE'}
              </span>
            </div>
            <div style={{ color: '#94a3b8', fontSize: 11 }}>Real vaqt marketplace analitikasi · O'zbekiston bo'ylab</div>
          </div>
        </div>

        <div className="d-flex gap-1 align-items-center flex-wrap">
          <button className={`btn btn-sm ${soundEnabled ? 'btn-success' : 'btn-outline-secondary'}`} onClick={() => setSoundEnabled(!soundEnabled)} title="Ovozli signal">
            <i className={`bi ${soundEnabled ? 'bi-volume-up-fill' : 'bi-volume-mute-fill'}`}></i>
          </button>
          <div className="btn-group btn-group-sm">
            <button className={`btn ${speed === 3500 ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSpeed(3500)}>x1</button>
            <button className={`btn ${speed === 2000 ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSpeed(2000)}>x2</button>
            <button className={`btn ${speed === 800 ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSpeed(800)}>x5</button>
          </div>
          <button className={`btn btn-sm ${isPaused ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => setIsPaused(!isPaused)}>
            <i className={`bi ${isPaused ? 'bi-play-fill' : 'bi-pause-fill'}`}></i>
          </button>
          <div className="px-2 py-1 rounded" style={{ background: '#1e293b', color: '#e2e8f0', fontSize: 12, border: '1px solid #334155' }}>
            <i className="bi bi-clock text-primary me-1"></i>{clock.toLocaleTimeString('uz-UZ')}
          </div>
          <button className="btn btn-outline-light btn-sm" onClick={goFull}><i className="bi bi-arrows-fullscreen"></i></button>
          <button className="btn btn-primary-gradient btn-sm" onClick={() => router.visit('/boshqaruv')}><i className="bi bi-house-door"></i></button>
        </div>
      </div>

      {/* ===== AI INSIGHT BANNER ===== */}
      <div className="mb-3 p-2 rounded d-flex align-items-center gap-2" style={{ background: 'linear-gradient(90deg, rgba(99,102,241,0.15), rgba(236,72,153,0.15))', border: '1px solid rgba(139,92,246,0.3)' }}>
        <i className="bi bi-stars" style={{ fontSize: 18, color: '#a855f7' }}></i>
        <span className="fw-bold small" style={{ color: '#a855f7' }}>AI Insight:</span>
        <span style={{ color: '#e2e8f0', fontSize: 13, animation: 'fadeIn 0.5s' }}>{insights[0]}</span>
      </div>

      {/* ===== TOP KPIs (12 ta) ===== */}
      <div className="row g-2 mb-3">
        {[
          { l: 'Daromad', v: fmt(rev) + ' so\'m', icon: 'bi-cash-stack', c: '#a855f7' },
          { l: 'Sof Foyda', v: fmt(netProfit) + ' so\'m', icon: 'bi-graph-up-arrow', c: '#10b981' },
          { l: 'Yalpi Marja', v: grossMargin + '%', icon: 'bi-percent', c: '#4f46e5' },
          { l: 'Net Marja', v: netMargin + '%', icon: 'bi-graph-up', c: '#059669' },
          { l: 'Buyurtmalar', v: fmt(ord), icon: 'bi-bag-check', c: '#10b981' },
          { l: 'AOV', v: fmt(aov) + ' so\'m', icon: 'bi-receipt', c: '#f59e0b' },
          { l: 'Konversiya', v: conversion + '%', icon: 'bi-bullseye', c: '#ec4899' },
          { l: 'Tezlik', v: velocity + '/daq', icon: 'bi-lightning-charge', c: '#06b6d4' },
          { l: 'CAC', v: fmt(cac) + ' so\'m', icon: 'bi-person-plus', c: '#7c3aed' },
          { l: 'LTV', v: fmt(ltv) + ' so\'m', icon: 'bi-heart', c: '#ec4899' },
          { l: 'ROAS', v: roas + 'x', icon: 'bi-megaphone', c: '#4f46e5' },
          { l: 'Retention', v: retention + '%', icon: 'bi-people', c: '#10b981' },
        ].map((k) => (
          <div className="col-xl-2 col-lg-3 col-md-4 col-6" key={k.l}>
            <div className="p-2 rounded" style={{ background: 'rgba(30, 41, 59, 0.5)', borderLeft: `3px solid ${k.c}` }}>
              <div className="d-flex justify-content-between align-items-start">
                <span style={{ color: '#94a3b8', fontSize: 10, textTransform: 'uppercase', letterSpacing: 0.5 }}>{k.l}</span>
                <i className={`bi ${k.icon}`} style={{ fontSize: 14, color: k.c }}></i>
              </div>
              <div style={{ fontSize: 18, fontWeight: 800, color: k.c, margin: '2px 0' }}>{k.v}</div>
            </div>
          </div>
        ))}
      </div>

      {/* ===== P&L MINI PANEL ===== */}
      <div className="row g-2 mb-3">
        <div className="col-12">
          <div className="p-2 rounded" style={{ background: 'linear-gradient(90deg, rgba(16,185,129,0.08), rgba(79,70,229,0.08))', border: '1px solid rgba(139,92,246,0.2)' }}>
            <div className="row g-2 text-center">
              {[
                { l: 'Daromad', v: fmt(rev), c: '#10b981' },
                { l: '- COGS', v: fmt(rev - profit), c: '#ef4444' },
                { l: 'Yalpi Foyda', v: fmt(profit), c: '#4f46e5' },
                { l: '- Marketing', v: fmt(Math.floor(rev * 0.087)), c: '#f59e0b' },
                { l: '- Shipping', v: fmt(Math.floor(rev * 0.039)), c: '#f59e0b' },
                { l: '- Operating', v: fmt(Math.floor(rev * 0.167)), c: '#f59e0b' },
                { l: 'Zararlar', v: fmt(loss), c: '#ef4444' },
                { l: 'SOF FOYDA', v: fmt(netProfit), c: '#059669' },
              ].map((m) => (
                <div className="col" key={m.l}>
                  <div style={{ fontSize: 9, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 0.5 }}>{m.l}</div>
                  <div style={{ fontSize: 14, fontWeight: 800, color: m.c }}>{m.v}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* ===== MAIN ROW: MAP + CHART + FEED + FUNNEL ===== */}
      <div className="row g-3 mb-3">
        {/* MAP */}
        <div className="col-xl-4">
          <div className="card-panel live-map-grid h-100">
            <div className="d-flex justify-content-between align-items-center mb-2">
              <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc' }}>📍 Geografik segmentlar</div>
              <span className="chip chip-purple" style={{ fontSize: 9 }}>{regionData.length} viloyat</span>
            </div>
            <div style={{ position: 'relative', height: 180 }}>
              <svg viewBox="0 0 100 100" style={{ width: '100%', height: '100%' }}>
                <path d="M 15 45 Q 30 25 60 30 T 95 35 Q 90 55 75 60 T 40 75 Q 20 65 15 45 Z" fill="rgba(79, 70, 229, 0.05)" stroke="rgba(255,255,255,0.1)" strokeWidth="0.5" />
                {regionData.map((reg) => {
                  const isPulse = activePulse === reg.name;
                  return (
                    <g key={reg.name}>
                      <line x1="60" y1="45" x2={reg.coords.x} y2={reg.coords.y} stroke="rgba(255,255,255,0.05)" strokeWidth="0.3" strokeDasharray="0.5 0.5" />
                      {isPulse && (
                        <circle cx={reg.coords.x} cy={reg.coords.y} r="6" fill="none" stroke={reg.color} strokeWidth="1">
                          <animate attributeName="r" from="3" to="12" dur="1s" begin="0s" repeatCount="1" />
                          <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0s" repeatCount="1" />
                        </circle>
                      )}
                      <circle cx={reg.coords.x} cy={reg.coords.y} r={isPulse ? "4" : "2.5"} fill={reg.color} style={{ filter: isPulse ? `drop-shadow(0 0 6px ${reg.color})` : 'none' }} />
                      <text x={reg.coords.x} y={reg.coords.y - 4} fill={isPulse ? '#fff' : '#94a3b8'} fontSize="5" textAnchor="middle" fontWeight={isPulse ? "bold" : "normal"}>{reg.name}</text>
                    </g>
                  );
                })}
              </svg>
            </div>
            <div className="d-flex flex-wrap gap-1 pt-1 border-top" style={{ borderColor: 'rgba(255,255,255,0.05)', fontSize: 9 }}>
              {regionData.slice(0, 8).map((r) => (
                <span key={r.name} className="d-inline-flex align-items-center gap-1 me-1" style={{ color: '#cbd5e1' }}>
                  <span style={{ width: 6, height: 6, borderRadius: '50%', background: r.color, display: 'inline-block' }}></span>
                  {r.name}: <strong style={{ color: '#fff' }}>{fmt(r.revenue).slice(0, 3)}k</strong>
                </span>
              ))}
            </div>
          </div>
        </div>

        {/* CHART */}
        <div className="col-xl-5">
          <div className="card-panel h-100">
            <div className="d-flex justify-content-between align-items-center mb-2">
              <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc' }}>📈 Daromad & Foyda trendi</div>
              <div className="d-flex gap-2 small">
                <span style={{ color: '#ec4899', fontSize: 10 }}>● Daromad</span>
                <span style={{ color: '#10b981', fontSize: 10 }}>● Foyda</span>
              </div>
            </div>
            <ResponsiveContainer width="100%" height={180}>
              <AreaChart data={chart}>
                <defs>
                  <linearGradient id="liveRev" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#ec4899" stopOpacity={0.6} />
                    <stop offset="100%" stopColor="#4f46e5" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="liveProf" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#10b981" stopOpacity={0.5} />
                    <stop offset="100%" stopColor="#059669" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <XAxis dataKey="t" hide />
                <YAxis hide />
                <Tooltip contentStyle={{ background: '#0f172a', border: '1px solid #334155', borderRadius: 6, fontSize: 11 }} />
                <Area type="monotone" dataKey="v" stroke="#ec4899" strokeWidth={2} fill="url(#liveRev)" />
                <Area type="monotone" dataKey="p" stroke="#10b981" strokeWidth={2} fill="url(#liveProf)" />
              </AreaChart>
            </ResponsiveContainer>
            <div className="d-flex justify-content-between text-muted pt-1" style={{ fontSize: 10, borderTop: '1px solid rgba(255,255,255,0.05)' }}>
              <span>← 60s oldin</span>
              <span style={{ color: '#6ee7b7' }}>● Real-time</span>
              <span>Hozir →</span>
            </div>
          </div>
        </div>

        {/* FUNNEL */}
        <div className="col-xl-3">
          <div className="card-panel h-100">
            <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc', marginBottom: 8 }}>🎯 Live Funnel</div>
            {['Launch', 'Home', 'List', 'Detail', 'Cart', 'Checkout', 'Pay select', 'Order'].map((s, i) => {
              const percent = (funnel[i] / funnel[0] * 100).toFixed(1);
              return (
                <div key={s} className="mb-2">
                  <div className="d-flex justify-content-between" style={{ fontSize: 10, color: '#cbd5e1' }}>
                    <span>{s}</span>
                    <span><strong>{fmt(funnel[i])}</strong> ({percent}%)</span>
                  </div>
                  <div className="progress" style={{ height: 12, background: 'rgba(255,255,255,0.05)' }}>
                    <div className="progress-bar" style={{ width: `${percent}%`, background: `linear-gradient(90deg, #4f46e5, #ec4899)`, fontSize: 9, fontWeight: 700 }}>
                      {percent}%
                    </div>
                  </div>
                </div>
              );
            })}
            <div className="mt-2 p-2 rounded text-center" style={{ background: 'rgba(16,185,129,0.1)', border: '1px solid rgba(16,185,129,0.3)' }}>
              <div style={{ fontSize: 10, color: '#94a3b8' }}>Final Konversiya</div>
              <div style={{ fontSize: 20, fontWeight: 800, color: '#6ee7b7' }}>{conversion}%</div>
            </div>
          </div>
        </div>
      </div>

      {/* ===== 2nd ROW: APP PLATFORM + SEGMENTS + PAYMENT ===== */}
      <div className="row g-3 mb-3">
        {/* APP PLATFORMS */}
        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc', marginBottom: 8 }}>📱 Android / iOS real-time</div>
            {platformData.map((p) => (
              <div key={p.name} className="d-flex align-items-center gap-2 py-2 border-bottom" style={{ borderColor: 'rgba(255,255,255,0.05)', fontSize: 11 }}>
                <span style={{ fontSize: 16 }}>{p.icon}</span>
                <span style={{ flex: 1, color: '#e2e8f0', fontWeight: 600 }}>{p.name}</span>
                <span className="text-muted" style={{ fontSize: 10 }}>Conv {p.conv}%</span>
                <span style={{ color: '#6ee7b7', fontWeight: 700 }}>{fmt(p.orders)}</span>
                <span style={{ color: '#a855f7', fontWeight: 700, fontSize: 10 }}>{fmt(p.revenue).slice(0, 4)}k</span>
              </div>
            ))}
            <div className="mt-2 pt-2 border-top" style={{ borderColor: 'rgba(255,255,255,0.05)' }}>
              <div className="d-flex justify-content-between" style={{ fontSize: 10, color: '#94a3b8' }}>
                <span>App active users:</span>
                <span style={{ color: '#6ee7b7', fontWeight: 700 }}>{fmt(platformData.reduce((a, p) => a + p.activeUsers, 0))}</span>
              </div>
              <div className="d-flex justify-content-between" style={{ fontSize: 10, color: '#94a3b8' }}>
                <span>Weighted crash:</span>
                <span style={{ color: '#fcd34d', fontWeight: 700 }}>0.37%</span>
              </div>
            </div>
          </div>
        </div>

        {/* SEGMENTS */}
        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc', marginBottom: 8 }}>👥 Mijoz segmentlari (RFM)</div>
            {segments.map((s) => (
              <div key={s.name} className="d-flex align-items-center gap-2 py-1 border-bottom" style={{ borderColor: 'rgba(255,255,255,0.05)', fontSize: 11 }}>
                <span style={{ fontSize: 14 }}>{s.icon}</span>
                <span style={{ flex: 1, color: '#e2e8f0', fontWeight: 600 }}>{s.name}</span>
                <span className="chip" style={{ background: s.color + '22', color: s.color, fontSize: 9, padding: '1px 6px' }}>{s.share}%</span>
                <span style={{ color: '#6ee7b7', fontWeight: 700, fontSize: 10 }}>{fmt(s.revenue).slice(0, 3)}k</span>
              </div>
            ))}
            <div className="mt-2 pt-2 border-top" style={{ borderColor: 'rgba(255,255,255,0.05)' }}>
              <div style={{ fontSize: 10, color: '#94a3b8' }}>Umumiy segment daromadi:</div>
              <div style={{ fontSize: 14, fontWeight: 800, color: '#6ee7b7' }}>{fmt(segments.reduce((a, s) => a + s.revenue, 0))} so'm</div>
            </div>
          </div>
        </div>

        {/* PAYMENTS */}
        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="d-flex justify-content-between mb-2">
              <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc' }}>💳 Karta / Naqd</div>
            </div>
            {payments.map((p) => (
              <div key={p.name} className="mb-2">
                <div className="d-flex justify-content-between" style={{ fontSize: 10, color: '#cbd5e1' }}>
                  <span>{p.name}</span>
                  <span><strong>{p.share}%</strong> · success {p.success}%</span>
                </div>
                <div className="progress" style={{ height: 6, background: 'rgba(255,255,255,0.05)' }}>
                  <div className="progress-bar" style={{ width: `${p.share}%`, background: p.color }}></div>
                </div>
                <div className="text-end" style={{ fontSize: 9, color: p.failed > 0 ? '#fca5a5' : '#6ee7b7' }}>Failed: {p.failed}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* ===== 3rd ROW: TOP/LIVE FEED + SYSTEM ===== */}
      <div className="row g-3">
        {/* LIVE FEED */}
        <div className="col-xl-5">
          <div className="card-panel" style={{ height: 280, display: 'flex', flexDirection: 'column' }}>
            <div className="d-flex justify-content-between align-items-center mb-2">
              <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc' }}>⚡ Jonli buyurtmalar oqimi</div>
              <span className="live-pulse"></span>
            </div>
            <div style={{ flex: 1, overflowY: 'auto' }}>
              {feed.length === 0 ? (
                <div className="text-center py-4" style={{ color: '#64748b', fontSize: 12 }}>
                  <div className="spinner-border spinner-border-sm text-primary mb-2"></div>
                  <br />Buyurtmalar kutilmoqda...
                </div>
              ) : feed.map((f, index) => (
                <div key={f.id} className="d-flex align-items-start gap-2 py-1 border-bottom" style={{ borderColor: 'rgba(255,255,255,0.05)', fontSize: 11, animation: index === 0 ? 'fadeIn 0.3s ease' : 'none' }}>
                  <div style={{ width: 6, height: 6, borderRadius: '50%', background: index === 0 ? '#10b981' : '#475569', flexShrink: 0, marginTop: 4 }}></div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ color: '#e2e8f0', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{f.text}</div>
                    <div className="d-flex justify-content-between" style={{ fontSize: 9, color: '#94a3b8' }}>
                      <span>📍{f.region} · 📱{f.platform}</span>
                      <span>{f.time}</span>
                    </div>
                  </div>
                  <div className="text-end">
                    <div style={{ color: '#6ee7b7', fontWeight: 700 }}>+{fmt(f.amount)}</div>
                    <div style={{ color: '#a855f7', fontSize: 9 }}>+{fmt(f.profit)}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* TOP PERFORMERS */}
        <div className="col-xl-4">
          <div className="card-panel" style={{ height: 280 }}>
            <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc', marginBottom: 8 }}>🏆 Real-time Top 5</div>
            {[
              { n: "O'tkan kunlar", s: 124, r: 24_140_000 },
              { n: 'Moleskine Daftar', s: 98, r: 22_940_000 },
              { n: 'Parker Ruchka', s: 85, r: 21_805_000 },
              { n: 'Stabilo Marker', s: 64, r: 19_950_000 },
              { n: 'Mehrobdan chayon', s: 58, r: 15_768_000 },
            ].map((p, i) => (
              <div key={p.n} className="d-flex align-items-center gap-2 py-1 border-bottom" style={{ borderColor: 'rgba(255,255,255,0.05)', fontSize: 11 }}>
                <div style={{ width: 22, height: 22, borderRadius: 5, background: i === 0 ? '#fbbf24' : i === 1 ? '#d1d5db' : i === 2 ? '#d97706' : '#334155', color: i > 2 ? '#94a3b8' : 'white', display: 'grid', placeItems: 'center', fontSize: 10, fontWeight: 800 }}>{i + 1}</div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ color: '#f1f5f9', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{p.n}</div>
                  <small style={{ color: '#94a3b8' }}>{p.s} ta sotildi</small>
                </div>
                <div style={{ color: '#6ee7b7', fontWeight: 700, fontSize: 10 }}>{fmt(p.r).slice(0, 4)}k</div>
              </div>
            ))}
          </div>
        </div>

        {/* SYSTEM HEALTH */}
        <div className="col-xl-3">
          <div className="card-panel" style={{ height: 280 }}>
            <div style={{ fontWeight: 700, fontSize: 13, color: '#f8fafc', marginBottom: 8 }}>⚡ System Health</div>
            {[
              { n: 'Server', s: 'Online', c: '#10b981', l: '28%' },
              { n: 'Payment GW', s: 'Fast', c: '#10b981', l: '14%' },
              { n: 'SMS GW', s: 'OK', c: '#10b981', l: '45%' },
              { n: 'Database', s: 'Optimal', c: '#10b981', l: '32%' },
              { n: 'Cache', s: 'Online', c: '#10b981', l: '18%' },
              { n: 'API', s: 'Online', c: '#10b981', l: '22%' },
            ].map((it) => (
              <div key={it.n} className="d-flex justify-content-between align-items-center py-1 border-bottom" style={{ borderColor: 'rgba(255,255,255,0.05)', fontSize: 11 }}>
                <span style={{ color: '#cbd5e1' }}>{it.n}</span>
                <span className="d-flex align-items-center gap-1">
                  <span style={{ fontSize: 9, color: '#94a3b8' }}>{it.l}</span>
                  <span style={{ width: 6, height: 6, borderRadius: '50%', background: it.c }}></span>
                </span>
              </div>
            ))}
            <div className="mt-2 pt-2 border-top text-center" style={{ borderColor: 'rgba(255,255,255,0.05)' }}>
              <small style={{ color: '#94a3b8' }}>Uptime: <span style={{ color: '#6ee7b7', fontWeight: 700 }}>99.98%</span></small>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
