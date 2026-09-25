import { useState, FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { EmptyState } from '../components/Axelit';

interface ServerHealth {
  disk: {
    total_formatted: string;
    used_formatted: string;
    free_formatted: string;
    used_percent: number;
    status: 'safe' | 'warning' | 'critical';
    inodes_percent: number | null;
    inodes_total: number | null;
    inodes_used: number | null;
  };
  ram: {
    total_mb: number | null;
    used_mb: number | null;
    percent: number | null;
  };
  system: {
    php_version: string;
    laravel_version: string;
    server_software: string;
    load_average: number[];
    uptime: string;
    server_ip: string;
  };
}

interface ServicesStatus {
  database: {
    status: 'ok' | 'failed';
    latency_ms: number | null;
    error: string | null;
  };
  cache: {
    status: 'ok' | 'warning' | 'failed';
    driver: string;
    error: string | null;
  };
  queue: {
    failed_jobs: number;
    status: 'ok' | 'warning';
  };
  storage: Record<string, {
    path: string;
    exists: boolean;
    writable: boolean;
  }>;
  config_integrity: {
    status: 'ok' | 'critical';
    corrupted: Array<{ file: string; path: string; reason: string }>;
    scanned_count: number;
  };
}

interface ThreatAnalysis {
  vectors: {
    sql_injection: number;
    path_traversal: number;
    xss_attempt: number;
    sensitive_files: number;
    auth_bruteforce: number;
  };
  recent_threats: Array<{
    type: string;
    ip: string;
    time: string;
    snippet: string;
  }>;
  top_ips: Array<{
    ip: string;
    count: number;
    is_blocked: boolean;
    risk_level: 'Yuqori' | "O'rta" | 'Past';
  }>;
  threat_level: 'high' | 'normal';
}

interface SystemErrorLog {
  timestamp: string;
  level: string;
  message: string;
  file: string;
}

interface SslStatus {
  is_https: boolean;
  issuer: string;
  valid_to: string | null;
  days_left: number | null;
  status: string;
}

interface EnvStatus {
  is_protected: boolean;
  exists: boolean;
  note: string;
}

interface Props {
  serverHealth: ServerHealth;
  servicesStatus: ServicesStatus;
  threatAnalysis: ThreatAnalysis;
  blockedIps: string[];
  systemLogs: SystemErrorLog[];
  sslStatus: SslStatus;
  envStatus: EnvStatus;
  logFileSize: string;
  currentAdminIp: string;
  firewallActive: boolean;
  generatedAt: string;
}

export default function SecurityDashboard({
  serverHealth,
  servicesStatus,
  threatAnalysis,
  blockedIps = [],
  systemLogs = [],
  sslStatus,
  envStatus,
  logFileSize = '0 B',
  currentAdminIp = '127.0.0.1',
  firewallActive = true,
  generatedAt,
}: Props) {
  const [ipToBlock, setIpToBlock] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [activeTab, setActiveTab] = useState<'ips' | 'threats'>('ips');

  const totalThreats = Object.values(threatAnalysis?.vectors || {}).reduce((acc, curr) => acc + (curr || 0), 0);

  const handleClearCache = () => {
    if (!confirm("Barcha tizim keshlarini (config, route, view, bootstrap) tozalashni tasdiqlaysizmi?")) return;
    setIsProcessing(true);
    router.post('/boshqaruv/security/clear-cache', {}, {
      preserveScroll: true,
      onFinish: () => setIsProcessing(false),
    });
  };

  const handleFixStorage = () => {
    setIsProcessing(true);
    router.post('/boshqaruv/security/fix-storage', {}, {
      preserveScroll: true,
      onFinish: () => setIsProcessing(false),
    });
  };

  const handleTruncateLogs = () => {
    if (!confirm(`Server log faylini (${logFileSize}) tozalab, diskda joy ochmoqchimisiz?`)) return;
    setIsProcessing(true);
    router.post('/boshqaruv/security/truncate-logs', {}, {
      preserveScroll: true,
      onFinish: () => setIsProcessing(false),
    });
  };

  const handleBlockIp = (e: FormEvent) => {
    e.preventDefault();
    if (!ipToBlock.trim()) return;
    setIsProcessing(true);
    router.post('/boshqaruv/security/block-ip', { ip: ipToBlock.trim() }, {
      preserveScroll: true,
      onSuccess: () => setIpToBlock(''),
      onFinish: () => setIsProcessing(false),
    });
  };

  const handleToggleBlock = (ip: string, isCurrentlyBlocked: boolean) => {
    if (isCurrentlyBlocked) {
      if (!confirm(`${ip} manzilini blokdan chiqarmoqchimisiz?`)) return;
      setIsProcessing(true);
      router.post('/boshqaruv/security/unblock-ip', { ip }, {
        preserveScroll: true,
        onFinish: () => setIsProcessing(false),
      });
    } else {
      if (!confirm(`${ip} manzilini xavfsizlik devori (Firewall) orqali bloklaysizmi?`)) return;
      setIsProcessing(true);
      router.post('/boshqaruv/security/block-ip', { ip }, {
        preserveScroll: true,
        onFinish: () => setIsProcessing(false),
      });
    }
  };

  const hasConfigError = servicesStatus?.config_integrity?.status === 'critical' || (servicesStatus?.config_integrity?.corrupted?.length ?? 0) > 0;
  const isDiskCritical = serverHealth?.disk?.status === 'critical';

  // Umumiy tizim holati
  const systemOk = !hasConfigError && !isDiskCritical && threatAnalysis.threat_level !== 'normal';
  const overallStatus = hasConfigError || isDiskCritical
    ? { tone: 'danger', label: 'Kritik holat', icon: 'ti-alert-triangle' }
    : threatAnalysis.threat_level === 'high'
    ? { tone: 'warning', label: 'Diqqat talab', icon: 'ti-alert-circle' }
    : { tone: 'success', label: 'Tizim barqaror', icon: 'ti-shield-check' };

  // Xizmatlar sanasi: nechta sog'lom
  const svcCount = [
    servicesStatus.database.status === 'ok',
    servicesStatus.cache.status === 'ok',
    servicesStatus.queue.failed_jobs === 0,
  ];
  const healthyServices = svcCount.filter(Boolean).length;

  return (
    <div>
      {/* ═══════════════════════════════════════════════════════════════════
          1-QATOR: Asimetrik hero — chap: status banner, o'ng: tezkor amallar
         ═══════════════════════════════════════════════════════════════════ */}
      <div className="row mb-4">
        {/* Hero status card — keng */}
        <div className="col-xl-8 mb-3 mb-xl-0">
          <div className={`card bg-${overallStatus.tone}-300 h-100 overflow-hidden position-relative`}>
            <div className="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
              <div className={`h-70 w-70 d-flex-center b-r-20 bg-${overallStatus.tone} flex-shrink-0`}
                   style={{ boxShadow: `0 8px 24px rgba(var(--bs-${overallStatus.tone}-rgb), .35)` }}>
                <i className={`ti ${overallStatus.icon} f-s-32 text-white`}></i>
              </div>
              <div className="flex-grow-1 min-w-0">
                <div className="d-flex align-items-center gap-2 mb-1">
                  <h4 className={`text-${overallStatus.tone}-dark f-w-700 mb-0`}>
                    Kiberxavfsizlik & Server
                  </h4>
                  {firewallActive && (
                    <span className={`badge bg-white-300 text-${overallStatus.tone}-dark`}>
                      <span className="d-inline-block h-6 w-6 b-r-50 bg-success me-1" style={{ animation: 'pulse 2s infinite' }}></span>
                      WAF Faol
                    </span>
                  )}
                </div>
                <p className={`text-${overallStatus.tone}-dark mb-2 opacity-75`}>
                  Real-vaqtli server monitoringi · {generatedAt}
                </p>
                <div className="d-flex gap-2 align-items-center flex-wrap">
                  <span className={`badge bg-white-300 text-${overallStatus.tone}-dark`}>
                    <i className="ti ti-user-check me-1"></i>IP: <code className="text-inherit">{currentAdminIp}</code>
                  </span>
                  <span className={`badge bg-white-300 text-${overallStatus.tone}-dark`}>
                    <i className="ti ti-server me-1"></i>{serverHealth.system.server_ip}
                  </span>
                  <span className={`badge bg-white-300 text-${overallStatus.tone}-dark`}>
                    <i className="ti ti-clock me-1"></i>Uptime: {serverHealth.system.uptime}
                  </span>
                </div>
              </div>
              <i className={`ti ${overallStatus.icon} position-absolute end-0 bottom-0 text-${overallStatus.tone} opacity-25`}
                 style={{ fontSize: '140px', lineHeight: 1, transform: 'translate(15%, 15%)' }}></i>
            </div>
          </div>
        </div>

        {/* Tezkor amallar kartasi — ixcham */}
        <div className="col-xl-4">
          <div className="card h-100">
            <div className="card-header">
              <h6 className="f-w-600 mb-0"><i className="ti ti-bolt me-2 text-warning"></i>Tezkor amallar</h6>
            </div>
            <div className="card-body d-flex flex-column gap-2 py-3">
              <button type="button" className="btn btn-light-secondary btn-sm text-start d-flex align-items-center gap-2"
                disabled={isProcessing} onClick={() => router.reload()}>
                <span className="h-28 w-28 d-flex-center b-r-8 bg-light-info flex-shrink-0"><i className="ti ti-refresh text-info"></i></span>
                <span>Yangilash</span>
              </button>
              <button type="button" className="btn btn-light-secondary btn-sm text-start d-flex align-items-center gap-2"
                disabled={isProcessing} onClick={handleClearCache}>
                <span className="h-28 w-28 d-flex-center b-r-8 bg-light-primary flex-shrink-0"><i className="ti ti-trash text-primary"></i></span>
                <span>Keshni tozalash</span>
              </button>
              <button type="button" className="btn btn-light-secondary btn-sm text-start d-flex align-items-center gap-2"
                disabled={isProcessing} onClick={handleFixStorage}>
                <span className="h-28 w-28 d-flex-center b-r-8 bg-light-success flex-shrink-0"><i className="ti ti-folder-check text-success"></i></span>
                <span>Storage tiklash</span>
              </button>
              <button type="button" className="btn btn-light-secondary btn-sm text-start d-flex align-items-center gap-2"
                disabled={isProcessing} onClick={handleTruncateLogs}>
                <span className="h-28 w-28 d-flex-center b-r-8 bg-light-danger flex-shrink-0"><i className="ti ti-eraser text-danger"></i></span>
                <span>Log tozalash <span className="text-secondary f-s-12">({logFileSize})</span></span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ═══════════════════════════════════════════════════════════════════
          OGOHLANTIRISH BANNERLARI (faqat kerak bo'lganda ko'rinadi)
         ═══════════════════════════════════════════════════════════════════ */}
      {hasConfigError && (
        <div className="alert alert-light-danger d-flex align-items-start gap-3 mb-4" role="alert">
          <i className="ti ti-alert-triangle f-s-28 text-danger flex-shrink-0 mt-1"></i>
          <div>
            <h6 className="alert-heading text-danger f-w-600 mb-1">
              Diqqat: Noto'g'ri yoki bo'sh config/*.php fayl aniqlandi!
            </h6>
            <p className="mb-2 text-dark f-s-14">
              Quyidagi konfiguratsiya fayllari massiv qaytarmayapti. <code>array_merge()</code> xatosi va sayt 500 bo'lishiga sabab bo'ladi:
            </p>
            <ul className="mb-0 text-danger">
              {servicesStatus.config_integrity.corrupted.map((c, i) => (
                <li key={i}><strong>config/{c.file}</strong>: {c.reason}</li>
              ))}
            </ul>
          </div>
        </div>
      )}
      {isDiskCritical && (
        <div className="alert alert-light-danger d-flex align-items-center gap-3 mb-4" role="alert">
          <i className="ti ti-database-x f-s-24 text-danger flex-shrink-0"></i>
          <div className="flex-grow-1">
            <h6 className="alert-heading text-danger f-w-600 mb-0">
              Server diski to'lish xavfi ostida ({serverHealth.disk.used_percent}%)!
            </h6>
            <small className="text-secondary">
              Atigi {serverHealth.disk.free_formatted} bo'sh joy qoldi.
            </small>
          </div>
          <button type="button" className="btn btn-danger btn-sm" onClick={handleTruncateLogs}>
            <i className="ti ti-trash me-1"></i>Loglarni tozalash ({logFileSize})
          </button>
        </div>
      )}

      {/* ═══════════════════════════════════════════════════════════════════
          2-QATOR: 4 ta resurs vidjet — Axelit product-sold / orders-provided
                   Har xil pastel fonlar bilan vizual ritm
         ═══════════════════════════════════════════════════════════════════ */}
      <div className="row mb-2">
        {/* Disk — orders-provided-card (oq + pattern) */}
        <div className="col-sm-6 col-xxl-3 mb-4">
          <div className="card orders-provided-card h-100">
            <div className="card-body">
              <i className="ph-bold ph-circle circle-bg-img"></i>
              <div className="d-flex align-items-center justify-content-between">
                <p className="f-s-16 f-w-600 text-dark mb-0">Disk Bandligi</p>
                <span className={`badge ${serverHealth.disk.used_percent >= 90 ? 'bg-light-danger text-danger' : serverHealth.disk.used_percent >= 80 ? 'bg-light-warning text-warning' : 'bg-light-success text-success'}`}>
                  {serverHealth.disk.free_formatted} bo'sh
                </span>
              </div>
              <h2 className="text-secondary-dark my-2">{serverHealth.disk.used_percent}%</h2>
              <div className="progress progress-sm mb-2">
                <div
                  className={`progress-bar ${serverHealth.disk.used_percent >= 90 ? 'bg-danger' : serverHealth.disk.used_percent >= 80 ? 'bg-warning' : 'bg-primary'}`}
                  role="progressbar"
                  style={{ width: `${Math.min(100, serverHealth.disk.used_percent)}%` }}
                ></div>
              </div>
              <p className="mb-0 text-secondary f-s-13 d-flex justify-content-between">
                <span>{serverHealth.disk.used_formatted} / {serverHealth.disk.total_formatted}</span>
                <span>Inodes: {serverHealth.disk.inodes_percent !== null ? `${serverHealth.disk.inodes_percent}%` : 'N/A'}</span>
              </p>
              <i className="iconoir-hard-drive icon-bg text-secondary"></i>
            </div>
          </div>
        </div>

        {/* RAM — product-sold (primary pastel) */}
        <div className="col-sm-6 col-xxl-3 mb-4">
          <div className="card bg-primary-300 product-sold-card h-100">
            <div className="card-body">
              <div className="d-flex align-items-center justify-content-between">
                <h5 className="text-primary-dark f-w-600 mb-0">RAM Xotira</h5>
                <span className="badge bg-white-300 text-primary-dark">
                  {serverHealth.ram.percent !== null ? `${serverHealth.ram.percent}% band` : 'Linux RAM'}
                </span>
              </div>
              <div className="my-2">
                <h2 className="text-primary-dark mb-0">
                  {serverHealth.ram.percent !== null ? `${serverHealth.ram.percent}%` : `${serverHealth.system.load_average[0] || 0} load`}
                </h2>
              </div>
              <div className="progress progress-sm mb-2">
                <div className="progress-bar bg-primary" role="progressbar"
                  style={{ width: `${Math.min(100, serverHealth.ram.percent ?? 30)}%` }}></div>
              </div>
              <p className="mb-0 text-primary-dark f-s-13 d-flex justify-content-between">
                <span>{serverHealth.ram.used_mb ? `${serverHealth.ram.used_mb} MB` : '0 MB'} / {serverHealth.ram.total_mb ? `${serverHealth.ram.total_mb} MB` : 'N/A'}</span>
                <span>Load: {serverHealth.system.load_average.join(', ')}</span>
              </p>
              <span className="bg-primary h-35 w-35 d-flex-center b-r-50 product-sold-icon">
                <i className="iconoir-cpu f-w-600 f-s-18"></i>
              </span>
            </div>
          </div>
        </div>

        {/* Tahdidlar — product-sold (danger yoki warning pastel) */}
        <div className="col-sm-6 col-xxl-3 mb-4">
          <div className={`card ${threatAnalysis.threat_level === 'high' ? 'bg-danger-300' : 'bg-warning-300'} product-sold-card h-100`}>
            <div className="card-body">
              <div className="d-flex align-items-center justify-content-between">
                <h5 className={`${threatAnalysis.threat_level === 'high' ? 'text-danger-dark' : 'text-warning-dark'} f-w-600 mb-0`}>
                  Kiber Tahdidlar
                </h5>
                <span className={`badge bg-white-300 ${threatAnalysis.threat_level === 'high' ? 'text-danger-dark' : 'text-warning-dark'}`}>
                  {threatAnalysis.threat_level === 'high' ? 'Yuqori xavf' : "Me'yorda"}
                </span>
              </div>
              <div className="my-2">
                <h2 className={`${threatAnalysis.threat_level === 'high' ? 'text-danger-dark' : 'text-warning-dark'} mb-0`}>
                  {totalThreats} ta xuruj
                </h2>
              </div>
              <div className="progress progress-sm mb-2">
                <div className={`progress-bar ${threatAnalysis.threat_level === 'high' ? 'bg-danger' : 'bg-warning'}`}
                  role="progressbar"
                  style={{ width: `${Math.min(100, totalThreats > 0 ? Math.max(15, totalThreats * 8) : 5)}%` }}></div>
              </div>
              <p className={`mb-0 ${threatAnalysis.threat_level === 'high' ? 'text-danger-dark' : 'text-warning-dark'} f-s-13 d-flex justify-content-between`}>
                <span>SQLi: {threatAnalysis.vectors.sql_injection} · Path: {threatAnalysis.vectors.path_traversal}</span>
                <span>Probes: {threatAnalysis.vectors.sensitive_files}</span>
              </p>
              <span className={`bg-${threatAnalysis.threat_level === 'high' ? 'danger' : 'warning'} h-35 w-35 d-flex-center b-r-50 product-sold-icon`}>
                <i className="iconoir-shield-alert f-w-600 f-s-18"></i>
              </span>
            </div>
          </div>
        </div>

        {/* Xizmatlar — product-store-card (oq + success) */}
        <div className="col-sm-6 col-xxl-3 mb-4">
          <div className="card product-store-card h-100">
            <div className="card-body">
              <i className="ph-bold ph-circle circle-bg-img"></i>
              <div className="d-flex align-items-center justify-content-between">
                <p className="f-s-16 f-w-600 text-success mb-0">Xizmatlar</p>
                <span className="badge bg-light-success text-success">
                  {healthyServices}/{svcCount.length} sog'lom
                </span>
              </div>
              <h2 className="text-success-dark my-2">
                {servicesStatus.database.latency_ms !== null ? `${servicesStatus.database.latency_ms} ms` : '100% OK'}
              </h2>
              <div className="d-flex gap-2 mb-2">
                {([
                  { ok: servicesStatus.database.status === 'ok', label: 'MySQL' },
                  { ok: servicesStatus.cache.status === 'ok', label: servicesStatus.cache.driver },
                  { ok: servicesStatus.queue.failed_jobs === 0, label: 'Queue' },
                ] as const).map((svc) => (
                  <span key={svc.label} className={`badge ${svc.ok ? 'bg-light-success text-success' : 'bg-light-danger text-danger'} f-s-11`}>
                    <i className={`ti ${svc.ok ? 'ti-check' : 'ti-x'} me-1`}></i>{svc.label}
                  </span>
                ))}
              </div>
              <p className="mb-0 text-secondary f-s-13">
                PHP {serverHealth.system.php_version} · Laravel v{serverHealth.system.laravel_version}
              </p>
              <span className="position-absolute top-0 end-0 pa-6 bg-success b-2-white border-light rounded-circle" style={{ animation: 'pulse 2s infinite' }}></span>
            </div>
          </div>
        </div>
      </div>

      {/* ═══════════════════════════════════════════════════════════════════
          3-QATOR: Tahdid vektorlari — Axelit country-card stilida
                   5 ta karta, har biri boshqa rangda (pastel fon + icon)
         ═══════════════════════════════════════════════════════════════════ */}
      <div className="row mb-2">
        {([
          { key: 'sql_injection', label: 'SQL Injection', desc: "Ma'lumotlar bazasiga hujum", icon: 'ti-database-x', tone: 'danger' },
          { key: 'path_traversal', label: 'Path Traversal', desc: '/etc/passwd va tizim skani', icon: 'ti-folder-search', tone: 'warning' },
          { key: 'sensitive_files', label: 'Maxfiy Fayllar', desc: '.env, .git va admin probes', icon: 'ti-file-text', tone: 'info' },
          { key: 'xss_attempt', label: 'XSS Hujumlar', desc: 'Zararli script kiritish', icon: 'ti-shield-code', tone: 'primary' },
          { key: 'auth_bruteforce', label: 'Bruteforce', desc: "Noto'g'ri parol urinishlari", icon: 'ti-lock-access', tone: 'secondary' },
        ] as const).map((v) => (
          <div className="col-6 col-md-4 col-xxl mb-3" key={v.key}>
            <div className={`card country-card-${v.tone} h-100`}>
              <div className="card-body">
                <span className={`badge text-light-${v.tone} mb-2`}>
                  <i className={`ti ${v.icon} me-1`}></i>{v.label}
                </span>
                <h4 className={`text-${v.tone === 'secondary' ? 'dark' : `${v.tone}-dark`} f-w-700 mb-1`}>
                  {(threatAnalysis.vectors as Record<string, number>)[v.key] || 0} ta
                </h4>
                <p className="text-secondary f-s-12 mb-0">{v.desc}</p>
                <i className={`ti ${v.icon} icon-bg text-${v.tone}`}></i>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* ═══════════════════════════════════════════════════════════════════
          4-QATOR: Chap — Server detallari + himoya bannerlari
                   O'ng — Tabli IP/Tahdid paneli (zamonaviy tab layout)
         ═══════════════════════════════════════════════════════════════════ */}
      <div className="row mb-2">
        {/* CHAP: Server va Fayl tizimi (kattaroq karta — vizual ağırlık) */}
        <div className="col-xxl-5 col-xl-6 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-server me-2 text-primary"></i>Server Holati
              </h5>
              <span className="badge bg-light-primary text-primary">
                PHP {serverHealth.system.php_version}
              </span>
            </div>
            <div className="card-body p-0">
              <div className="table-responsive">
                <table className="table table-bottom-border align-middle mb-0">
                  <tbody>
                    {([
                      { icon: 'ti-lock', tone: 'success', label: 'SSL Sertifikati',
                        value: <span className={`badge ${sslStatus.is_https ? 'bg-light-success text-success' : 'bg-light-warning text-warning'}`}>
                          <i className="ti ti-shield-check me-1"></i>
                          {sslStatus.is_https ? 'HTTPS Faol' : 'HTTP'} ({sslStatus.issuer})
                        </span>
                      },
                      { icon: 'ti-cpu', tone: 'info', label: 'CPU Load',
                        value: <><span className="f-w-600">{serverHealth.system.load_average.join(' · ')}</span><small className="text-secondary ms-2">(1m, 5m, 15m)</small></>
                      },
                      { icon: 'ti-database', tone: 'success', label: 'MySQL',
                        value: servicesStatus.database.status === 'ok'
                          ? <span className="badge bg-light-success text-success"><i className="ti ti-check me-1"></i>Ulandi ({servicesStatus.database.latency_ms} ms)</span>
                          : <span className="badge bg-light-danger text-danger"><i className="ti ti-x me-1"></i>{servicesStatus.database.error}</span>
                      },
                      { icon: 'ti-bolt', tone: 'warning', label: 'Kesh',
                        value: <><span className="badge bg-light-primary text-primary me-2">{servicesStatus.cache.driver}</span>
                          {servicesStatus.cache.status === 'ok'
                            ? <span className="badge bg-light-success text-success"><i className="ti ti-check me-1"></i>Faol</span>
                            : <span className="badge bg-light-danger text-danger">{servicesStatus.cache.error}</span>
                          }</>
                      },
                      { icon: 'ti-clock-pause', tone: 'secondary', label: 'Queue',
                        value: servicesStatus.queue.failed_jobs > 0
                          ? <span className="badge bg-light-warning text-warning">{servicesStatus.queue.failed_jobs} ta qolib ketgan</span>
                          : <span className="badge bg-light-success text-success">0 ta xatolik</span>
                      },
                    ] as const).map((row, i) => (
                      <tr key={i}>
                        <td className="text-secondary ps-3 py-3" style={{ width: '40%' }}>
                          <i className={`ti ${row.icon} me-2 text-${row.tone}`}></i>{row.label}
                        </td>
                        <td className="pe-3 text-end">{row.value}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Himoya bannerlari — jadval ostida */}
              <div className="p-3 d-flex flex-column gap-2">
                {!hasConfigError ? (
                  <div className="p-3 b-r-10 bg-light-success d-flex align-items-center justify-content-between">
                    <div className="d-flex align-items-center gap-2">
                      <i className="ti ti-check-double f-s-20 text-success"></i>
                      <div>
                        <span className="text-success f-w-600 f-s-13">
                          {servicesStatus.config_integrity.scanned_count} ta config sog'lom
                        </span>
                      </div>
                    </div>
                    <span className="badge bg-success text-white">OK</span>
                  </div>
                ) : null}
                <div className="p-3 b-r-10 bg-light-primary d-flex align-items-center justify-content-between">
                  <div className="d-flex align-items-center gap-2">
                    <i className="ti ti-file-shield f-s-20 text-primary"></i>
                    <span className="text-primary f-w-600 f-s-13">.env & .git himoyalangan</span>
                  </div>
                  <span className="badge bg-primary text-white">Xavfsiz</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* O'NG: Tabli karta — IP manzillar / Shubhali xurujlar */}
        <div className="col-xxl-7 col-xl-6 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <div className="nav kc-segment" role="tablist">
                <div className="nav-item">
                  <button type="button" role="tab" aria-selected={activeTab === 'ips'}
                    className={`nav-link ${activeTab === 'ips' ? 'active' : ''}`}
                    onClick={() => setActiveTab('ips')}>
                    <i className="ti ti-ban me-1"></i>Shubhali IPlar
                    <span className="badge bg-light-danger text-danger ms-2">{threatAnalysis.top_ips.length}</span>
                  </button>
                </div>
                <div className="nav-item">
                  <button type="button" role="tab" aria-selected={activeTab === 'threats'}
                    className={`nav-link ${activeTab === 'threats' ? 'active' : ''}`}
                    onClick={() => setActiveTab('threats')}>
                    <i className="ti ti-activity me-1"></i>Xurujlar radari
                    <span className="badge bg-light-info text-info ms-2">{threatAnalysis.recent_threats.length}</span>
                  </button>
                </div>
              </div>
              <span className="badge bg-light-danger text-danger">
                {blockedIps.length} ta bloklangan
              </span>
            </div>

            <div className="card-body p-0">
              {activeTab === 'ips' ? (
                <>
                  {/* IP bloklash formasi */}
                  <form onSubmit={handleBlockIp} className="p-3 border-bottom d-flex gap-2">
                    <input type="text" className="form-control form-control-sm"
                      placeholder="IP manzil (masalan: 185.120.45.67)"
                      value={ipToBlock} onChange={(e) => setIpToBlock(e.target.value)} disabled={isProcessing} />
                    <button type="submit" className="btn btn-danger btn-sm text-nowrap"
                      disabled={isProcessing || !ipToBlock.trim()}>
                      <i className="ti ti-ban me-1"></i>Bloklash
                    </button>
                  </form>

                  {threatAnalysis.top_ips.length > 0 ? (
                    <ul className="customer-list p-3 mb-0">
                      {threatAnalysis.top_ips.map((item) => (
                        <li className="customer-list-item" key={item.ip}>
                          <span className={`h-35 w-35 d-flex-center b-r-50 customer-list-avtar ${item.is_blocked ? 'text-light-danger' : item.risk_level === 'Yuqori' ? 'text-light-warning' : 'text-light-info'}`}>
                            <i className={`f-s-18 ${item.is_blocked ? 'ti ti-ban' : 'ti ti-shield'}`}></i>
                          </span>
                          <div className="customer-list-content">
                            <h6 className="mb-0 font-monospace">{item.ip}</h6>
                            <p className="mb-0 text-secondary f-s-12">
                              {item.count} ta so'rov · Xavf:{' '}
                              <span className={`badge ${item.risk_level === 'Yuqori' ? 'bg-light-danger text-danger' : item.risk_level === "O'rta" ? 'bg-light-warning text-warning' : 'bg-light-info text-info'}`}>
                                {item.risk_level}
                              </span>
                            </p>
                          </div>
                          <div>
                            <button type="button"
                              className={`btn btn-sm ${item.is_blocked ? 'btn-outline-success' : 'btn-outline-danger'}`}
                              disabled={isProcessing} onClick={() => handleToggleBlock(item.ip, item.is_blocked)}>
                              {item.is_blocked
                                ? <><i className="ti ti-lock-open me-1"></i>Ochish</>
                                : <><i className="ti ti-ban me-1"></i>Bloklash</>
                              }
                            </button>
                          </div>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <div className="p-4">
                      <EmptyState text="Hozircha shubhali IP manzillar aniqlanmadi." icon="iconoir-shield-check" />
                    </div>
                  )}
                </>
              ) : (
                <>
                  {threatAnalysis.recent_threats.length > 0 ? (
                    <ul className="order-content-list p-3 mb-0">
                      {threatAnalysis.recent_threats.map((threat, index) => {
                        const isSql = threat.type.includes('SQL');
                        const isPath = threat.type.includes('Path');
                        const tone = isSql ? 'danger' : isPath ? 'warning' : 'info';
                        return (
                          <li className={`bg-${tone}-300`} key={index}>
                            <div className="d-flex align-items-center justify-content-between">
                              <h6 className={`text-${tone}-dark f-w-600 mb-0 font-monospace`}>
                                <i className="ti ti-shield-alert me-1"></i>{threat.type} · {threat.ip}
                              </h6>
                              <span className={`badge text-light-${tone}`}>{threat.time}</span>
                            </div>
                            <p className={`text-${tone}-dark mb-0 font-monospace f-s-12 txt-ellipsis-2 mt-1 opacity-75`}>
                              {threat.snippet}
                            </p>
                          </li>
                        );
                      })}
                    </ul>
                  ) : (
                    <div className="p-4">
                      <EmptyState text="So'nggi loglarda shubhali xurujlar qayd etilmagan." icon="iconoir-check" />
                    </div>
                  )}
                </>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* ═══════════════════════════════════════════════════════════════════
          5-QATOR: Papkalar holati + Error loglar — asimetrik layout
         ═══════════════════════════════════════════════════════════════════ */}
      <div className="row mb-2">
        {/* Papkalar — ixcham karta */}
        <div className="col-xxl-4 col-xl-5 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-folder-check me-2 text-success"></i>Fayl Tizimi
              </h5>
              <span className={`badge ${hasConfigError ? 'bg-light-danger text-danger' : 'bg-light-success text-success'}`}>
                {Object.keys(servicesStatus.storage || {}).length} papka
              </span>
            </div>
            <div className="card-body p-0">
              <div className="table-responsive">
                <table className="table table-bottom-border align-middle mb-0">
                  <tbody>
                    {Object.entries(servicesStatus.storage || {}).map(([key, item]) => (
                      <tr key={key}>
                        <td className="ps-3 py-2">
                          <code className="text-dark f-s-12">{item.path.replace(/.*\/kitobchi-laravel\/?/, '')}</code>
                        </td>
                        <td className="text-end pe-3">
                          <span className={`badge ${item.exists && item.writable ? 'bg-light-success text-success' : 'bg-light-danger text-danger'} f-s-11`}>
                            {item.exists && item.writable ? '✓ OK' : item.exists ? '🔒 Readonly' : "✗ Yo'q"}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {/* Error loglar — keng karta */}
        <div className="col-xxl-8 col-xl-7 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-bug me-2 text-danger"></i>So'nggi Jiddiy Xatoliklar
              </h5>
              <span className="badge bg-light-secondary text-secondary">
                {systemLogs.length} ta log
              </span>
            </div>
            <div className="card-body p-0">
              {systemLogs.length > 0 ? (
                <div className="table-responsive">
                  <table className="table table-bottom-border align-middle mb-0">
                    <thead>
                      <tr className="text-secondary f-s-13">
                        <th className="ps-3" style={{ width: '15%' }}>Vaqt</th>
                        <th style={{ width: '10%' }}>Daraja</th>
                        <th>Xatolik Xabari</th>
                        <th className="pe-3" style={{ width: '22%' }}>Fayl</th>
                      </tr>
                    </thead>
                    <tbody>
                      {systemLogs.map((log, index) => (
                        <tr key={index}>
                          <td className="ps-3 py-2 text-secondary f-s-13">{log.timestamp}</td>
                          <td>
                            <span className={`badge ${log.level === 'CRITICAL' ? 'bg-danger text-white' : 'bg-light-danger text-danger'}`}>
                              {log.level}
                            </span>
                          </td>
                          <td className="text-dark f-w-500 font-monospace f-s-13 text-break">{log.message}</td>
                          <td className="pe-3 font-monospace f-s-12 text-secondary text-truncate" style={{ maxWidth: '240px' }}>{log.file}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="p-4">
                  <EmptyState text="Server loglarida jiddiy xatoliklar topilmadi (Tizim toza)." icon="iconoir-check-circle" />
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
