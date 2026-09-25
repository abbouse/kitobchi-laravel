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
  const isDiskWarning = serverHealth?.disk?.status === 'warning';

  return (
    <div>
      {/* ── 1. Sarlavha va Axelit Boshqaruv Qatori ──────────────────────── */}
      <div className="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
          <div className="d-flex align-items-center gap-2 mb-1">
            <h4 className="main-title mb-0">Kiberxavfsizlik & Server Salomatligi</h4>
            {firewallActive && (
              <span className="badge bg-light-primary text-primary" title="Real-vaqtli WAF himoyasi faol">
                <i className="ti ti-shield-check me-1"></i>Firewall WAF: Faol
              </span>
            )}
          </div>
          <p className="text-secondary mb-0">
            Real-vaqtli server monitoringi, resurslar, config butunligi va kiberhujumlar radari
          </p>
        </div>
        <div className="d-flex gap-2 align-items-center flex-wrap">
          <span className="badge bg-light-info text-info" title="Sizning hozirgi IP manzilingiz oq ro'yxatda (himoyalangan)">
            <i className="ti ti-user-check me-1"></i>Siz: <code>{currentAdminIp}</code>
          </span>

          {hasConfigError || isDiskCritical ? (
            <span className="badge bg-light-danger text-danger">
              <i className="ti ti-alert-triangle me-1"></i>Kritik holat
            </span>
          ) : isDiskWarning || threatAnalysis.threat_level === 'high' ? (
            <span className="badge bg-light-warning text-warning">
              <i className="ti ti-alert-circle me-1"></i>Diqqat talab
            </span>
          ) : (
            <span className="badge bg-light-success text-success d-inline-flex align-items-center">
              <span className="d-inline-block h-8 w-8 b-r-50 bg-success me-1"></span>
              Tizim barqaror
            </span>
          )}

          <div className="btn-group btn-group-sm">
            <button
              type="button"
              className="btn btn-outline-secondary"
              disabled={isProcessing}
              onClick={() => router.reload()}
              title="Yangilash"
            >
              <i className="ti ti-refresh me-1"></i>Yangilash
            </button>
            <button
              type="button"
              className="btn btn-outline-primary"
              disabled={isProcessing}
              onClick={handleClearCache}
              title="Barcha keshni tozalash"
            >
              <i className="ti ti-trash me-1"></i>Keshni tozalash
            </button>
            <button
              type="button"
              className="btn btn-outline-success"
              disabled={isProcessing}
              onClick={handleFixStorage}
              title="Storage va framework papkalari ruxsatini tiklash"
            >
              <i className="ti ti-folder-check me-1"></i>Storage tiklash
            </button>
            <button
              type="button"
              className="btn btn-outline-danger"
              disabled={isProcessing}
              onClick={handleTruncateLogs}
              title={`Eski loglarni tozalash (${logFileSize})`}
            >
              <i className="ti ti-eraser me-1"></i>Log tozalash ({logFileSize})
            </button>
          </div>
        </div>
      </div>

      {/* ── Kritik Konfiguratsiya Xatosi Ogohlantirishi ──────────────── */}
      {hasConfigError && (
        <div className="alert alert-light-danger d-flex align-items-start gap-3 mb-4" role="alert">
          <i className="ti ti-alert-triangle f-s-28 text-danger flex-shrink-0 mt-1"></i>
          <div>
            <h6 className="alert-heading text-danger f-w-600 mb-1">
              Diqqat: Noto'g'ri yoki bo'sh config/*.php fayl aniqlandi!
            </h6>
            <p className="mb-2 text-dark f-s-14">
              Quyidagi konfiguratsiya fayllari massiv (array) qaytarmayapti. Bu serverda butun sayt bo'ylab <code>array_merge(): Argument #2 must be of type array, int given</code> xatosiga va sayt 500 bo'lishiga sabab bo'ladi:
            </p>
            <ul className="mb-0 text-danger">
              {servicesStatus.config_integrity.corrupted.map((c, i) => (
                <li key={i}>
                  <strong>config/{c.file}</strong>: {c.reason}
                </li>
              ))}
            </ul>
          </div>
        </div>
      )}

      {/* ── Disk Bandligi Xavf Ogohlantirishi ───────────────────────── */}
      {isDiskCritical && (
        <div className="alert alert-light-danger d-flex align-items-center gap-3 mb-4" role="alert">
          <i className="ti ti-database-x f-s-24 text-danger flex-shrink-0"></i>
          <div className="flex-grow-1">
            <h6 className="alert-heading text-danger f-w-600 mb-0">
              Server diski to'lish xavfi ostida ({serverHealth.disk.used_percent}%)!
            </h6>
            <small className="text-secondary">
              Atigi {serverHealth.disk.free_formatted} bo'sh joy qoldi. PHP sessiyalar yozilmay qolishi yoki MySQL qulab tushishi mumkin.
            </small>
          </div>
          <button
            type="button"
            className="btn btn-danger btn-sm"
            onClick={handleTruncateLogs}
          >
            <i className="ti ti-trash me-1"></i>Loglarni tozalab joy ochish ({logFileSize})
          </button>
        </div>
      )}

      {/* ── 2. Axelit Marquee E-commerce Top Vidjetlari ──────────────── */}
      <div className="row mb-2">
        {/* Vidjet 1: Disk Xotira (orders-provided-card with spin animation) */}
        <div className="col-sm-6 col-xxl-3 col-md-6 mb-4">
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

        {/* Vidjet 2: RAM Xotira (bg-primary-300 product-sold-card with icon) */}
        <div className="col-sm-6 col-xxl-3 col-md-6 mb-4">
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
                <div
                  className="progress-bar bg-primary"
                  role="progressbar"
                  style={{ width: `${Math.min(100, serverHealth.ram.percent ?? 30)}%` }}
                ></div>
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

        {/* Vidjet 3: Kiber Tahdid Radar (bg-danger-300 / bg-warning-300) */}
        <div className="col-sm-6 col-xxl-3 col-md-6 mb-4">
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
                <div
                  className={`progress-bar ${threatAnalysis.threat_level === 'high' ? 'bg-danger' : 'bg-warning'}`}
                  role="progressbar"
                  style={{ width: `${Math.min(100, totalThreats > 0 ? Math.max(15, totalThreats * 8) : 5)}%` }}
                ></div>
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

        {/* Vidjet 4: Xizmatlar Salomatligi (product-store-card with live dot) */}
        <div className="col-sm-6 col-xxl-3 col-md-6 mb-4">
          <div className="card product-store-card h-100">
            <div className="card-body">
              <i className="ph-bold ph-circle circle-bg-img"></i>
              <div className="d-flex align-items-center justify-content-between">
                <p className="f-s-16 f-w-600 text-success mb-0">Xizmatlar Salomatligi</p>
                <span className="badge bg-light-success text-success">
                  {servicesStatus.database.status === 'ok' ? 'Barcha faol' : 'Xatolik'}
                </span>
              </div>
              <h2 className="text-success-dark my-2">
                {servicesStatus.database.latency_ms !== null ? `${servicesStatus.database.latency_ms} ms` : '100% OK'}
              </h2>
              <div className="progress progress-sm mb-2">
                <div
                  className="progress-bar bg-success"
                  role="progressbar"
                  style={{ width: '100%' }}
                ></div>
              </div>
              <p className="mb-0 text-secondary f-s-13 d-flex justify-content-between">
                <span>MySQL: {servicesStatus.database.status.toUpperCase()}</span>
                <span>Kesh: {servicesStatus.cache.status.toUpperCase()}</span>
                <span>Queue: {servicesStatus.queue.failed_jobs} failed</span>
              </p>
              <span className="position-absolute top-0 end-0 pa-6 bg-success b-2-white border-light rounded-circle animate__animated animate__heartBeat animate__infinite animate__fast"></span>
            </div>
          </div>
        </div>
      </div>

      {/* ── 3. Axelit "Country Card" Uslubidagi 5 ta Tahdid Vektori ─── */}
      <div className="row mb-2">
        <div className="col-12 mb-2">
          <h5 className="f-w-600">
            <i className="ti ti-radar-2 me-2 text-primary"></i>Tahdid Vektorlari va Hujum Turlari
          </h5>
        </div>

        <div className="col-6 col-md-4 col-xxl mb-3">
          <div className="card country-card-danger h-100">
            <div className="card-body">
              <span className="badge text-light-danger mb-2">
                <i className="ti ti-database-x me-1"></i>SQLi
              </span>
              <h4 className="text-danger-dark f-w-700 mb-1">{threatAnalysis.vectors.sql_injection} ta</h4>
              <p className="text-secondary f-s-12 mb-0">SQL Injection urinishlari</p>
              <i className="ti ti-code icon-bg text-danger"></i>
            </div>
          </div>
        </div>

        <div className="col-6 col-md-4 col-xxl mb-3">
          <div className="card country-card-warning h-100">
            <div className="card-body">
              <span className="badge text-light-warning mb-2">
                <i className="ti ti-folder-search me-1"></i>Path Traversal
              </span>
              <h4 className="text-warning-dark f-w-700 mb-1">{threatAnalysis.vectors.path_traversal} ta</h4>
              <p className="text-secondary f-s-12 mb-0">/etc/passwd va tizim skani</p>
              <i className="ti ti-folder-search icon-bg text-warning"></i>
            </div>
          </div>
        </div>

        <div className="col-6 col-md-4 col-xxl mb-3">
          <div className="card country-card-info h-100">
            <div className="card-body">
              <span className="badge text-light-info mb-2">
                <i className="ti ti-file-text me-1"></i>Maxfiy Fayllar
              </span>
              <h4 className="text-info-dark f-w-700 mb-1">{threatAnalysis.vectors.sensitive_files} ta</h4>
              <p className="text-secondary f-s-12 mb-0">.env, .git va admin probes</p>
              <i className="ti ti-file-text icon-bg text-info"></i>
            </div>
          </div>
        </div>

        <div className="col-6 col-md-6 col-xxl mb-3">
          <div className="card country-card-primary h-100">
            <div className="card-body">
              <span className="badge text-light-primary mb-2">
                <i className="ti ti-shield-code me-1"></i>XSS
              </span>
              <h4 className="text-primary-dark f-w-700 mb-1">{threatAnalysis.vectors.xss_attempt} ta</h4>
              <p className="text-secondary f-s-12 mb-0">Zararli script kiritish</p>
              <i className="ti ti-shield-code icon-bg text-primary"></i>
            </div>
          </div>
        </div>

        <div className="col-6 col-md-6 col-xxl mb-3">
          <div className="card country-card-secondary h-100">
            <div className="card-body">
              <span className="badge text-light-secondary mb-2">
                <i className="ti ti-lock-access me-1"></i>Bruteforce
              </span>
              <h4 className="text-dark f-w-700 mb-1">{threatAnalysis.vectors.auth_bruteforce} ta</h4>
              <p className="text-secondary f-s-12 mb-0">Noto'g'ri parol urinishlari</p>
              <i className="ti ti-lock-access icon-bg text-secondary"></i>
            </div>
          </div>
        </div>
      </div>

      {/* ── 4. Server Xizmatlari va Papkalar / Config Butunligi ──────── */}
      <div className="row mb-2">
        {/* Chap: Server va Xizmatlar Holati */}
        <div className="col-xl-6 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-server me-2 text-primary"></i>Server va Xizmatlar Holati
              </h5>
              <span className="badge bg-light-primary text-primary">
                PHP {serverHealth.system.php_version}
              </span>
            </div>
            <div className="card-body p-0">
              <div className="table-responsive">
                <table className="table table-bottom-border align-middle mb-0">
                  <tbody>
                    <tr>
                      <td className="text-secondary ps-3 py-3" style={{ width: '35%' }}>
                        <i className="ti ti-network me-2 text-primary"></i>Server IP
                      </td>
                      <td className="text-dark f-w-600 pe-3 text-end">
                        <code>{serverHealth.system.server_ip}</code>
                      </td>
                    </tr>
                    <tr>
                      <td className="text-secondary ps-3 py-3">
                        <i className="ti ti-lock me-2 text-success"></i>SSL Sertifikati
                      </td>
                      <td className="pe-3 text-end">
                        <span className={`badge ${sslStatus.is_https ? 'bg-light-success text-success' : 'bg-light-warning text-warning'}`}>
                          <i className="ti ti-shield-check me-1"></i>
                          {sslStatus.is_https ? 'HTTPS Faol' : 'HTTP'} ({sslStatus.issuer})
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td className="text-secondary ps-3 py-3">
                        <i className="ti ti-cpu me-2 text-info"></i>CPU Load Average
                      </td>
                      <td className="text-dark f-w-600 pe-3 text-end">
                        {serverHealth.system.load_average.join(' · ')}
                        <small className="text-secondary ms-2">(1m, 5m, 15m)</small>
                      </td>
                    </tr>
                    <tr>
                      <td className="text-secondary ps-3 py-3">
                        <i className="ti ti-clock me-2 text-warning"></i>Server Uptime
                      </td>
                      <td className="text-dark f-w-600 pe-3 text-end">
                        {serverHealth.system.uptime}
                      </td>
                    </tr>
                    <tr>
                      <td className="text-secondary ps-3 py-3">
                        <i className="ti ti-database me-2 text-success"></i>MySQL Database
                      </td>
                      <td className="pe-3 text-end">
                        {servicesStatus.database.status === 'ok' ? (
                          <span className="badge bg-light-success text-success">
                            <i className="ti ti-check me-1"></i>Ulandi ({servicesStatus.database.latency_ms} ms)
                          </span>
                        ) : (
                          <span className="badge bg-light-danger text-danger">
                            <i className="ti ti-x me-1"></i>Xato: {servicesStatus.database.error}
                          </span>
                        )}
                      </td>
                    </tr>
                    <tr>
                      <td className="text-secondary ps-3 py-3">
                        <i className="ti ti-bolt me-2 text-warning"></i>Kesh Tizimi
                      </td>
                      <td className="pe-3 text-end">
                        <span className="badge bg-light-primary text-primary me-2">
                          {servicesStatus.cache.driver}
                        </span>
                        {servicesStatus.cache.status === 'ok' ? (
                          <span className="badge bg-light-success text-success">
                            <i className="ti ti-check me-1"></i>Faol
                          </span>
                        ) : (
                          <span className="badge bg-light-danger text-danger">
                            {servicesStatus.cache.error}
                          </span>
                        )}
                      </td>
                    </tr>
                    <tr>
                      <td className="text-secondary ps-3 py-3">
                        <i className="ti ti-clock-pause me-2 text-secondary"></i>Queue (Navbat)
                      </td>
                      <td className="pe-3 text-end">
                        {servicesStatus.queue.failed_jobs > 0 ? (
                          <span className="badge bg-light-warning text-warning">
                            {servicesStatus.queue.failed_jobs} ta qolib ketgan vazifa
                          </span>
                        ) : (
                          <span className="badge bg-light-success text-success">
                            0 ta xatolik (Toza)
                          </span>
                        )}
                      </td>
                    </tr>
                    <tr>
                      <td className="text-secondary ps-3 py-3">
                        <i className="ti ti-brand-laravel me-2 text-danger"></i>Laravel Framework
                      </td>
                      <td className="text-dark f-w-600 pe-3 text-end">
                        v{serverHealth.system.laravel_version}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {/* O'ng: Papkalar va Konfiguratsiya Butunligi */}
        <div className="col-xl-6 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-folder-check me-2 text-success"></i>Fayl Tizimi & Config Butunligi
              </h5>
              <span className={`badge ${hasConfigError ? 'bg-light-danger text-danger' : 'bg-light-success text-success'}`}>
                {servicesStatus.config_integrity.scanned_count} ta config skanerlandi
              </span>
            </div>
            <div className="card-body p-3">
              {/* Config Integrity Banner */}
              {!hasConfigError ? (
                <div className="p-3 mb-3 b-r-10 bg-light-success d-flex align-items-center justify-content-between">
                  <div className="d-flex align-items-center gap-2">
                    <i className="ti ti-check-double f-s-22 text-success"></i>
                    <div>
                      <h6 className="text-success f-w-600 mb-0">
                        Barcha {servicesStatus.config_integrity.scanned_count} ta config/*.php fayllar sog'lom!
                      </h6>
                      <small className="text-secondary">Har bir fayl massiv qaytaradi — 500 fatal xatosi xavfi yo'q</small>
                    </div>
                  </div>
                  <span className="badge bg-success text-white">100% OK</span>
                </div>
              ) : null}

              {/* .env Exposure Protection Banner */}
              <div className="p-3 mb-3 b-r-10 bg-light-primary d-flex align-items-center justify-content-between">
                <div className="d-flex align-items-center gap-2">
                  <i className="ti ti-file-shield f-s-22 text-primary"></i>
                  <div>
                    <h6 className="text-primary f-w-600 mb-0">.env va .git Maxfiy Fayl Himoyasi</h6>
                    <small className="text-secondary">{envStatus.note}</small>
                  </div>
                </div>
                <span className="badge bg-primary text-white">Xavfsiz</span>
              </div>

              <div className="table-responsive">
                <table className="table table-bottom-border align-middle mb-0">
                  <thead>
                    <tr className="text-secondary f-s-13">
                      <th className="ps-2">Papka / Yo'nalish</th>
                      <th>Mavjudligi</th>
                      <th className="text-end pe-2">Ruxsat</th>
                    </tr>
                  </thead>
                  <tbody>
                    {Object.entries(servicesStatus.storage || {}).map(([key, item]) => (
                      <tr key={key}>
                        <td className="ps-2 py-2">
                          <code className="text-dark">{item.path.replace(/.*\/kitobchi-laravel\/?/, '')}</code>
                        </td>
                        <td>
                          {item.exists ? (
                            <span className="badge bg-light-success text-success">
                              <i className="ti ti-check me-1"></i>Mavjud
                            </span>
                          ) : (
                            <span className="badge bg-light-danger text-danger">
                              <i className="ti ti-x me-1"></i>Yo'q
                            </span>
                          )}
                        </td>
                        <td className="text-end pe-2">
                          {item.writable ? (
                            <span className="badge bg-light-success text-success">
                              <i className="ti ti-lock-open me-1"></i>Writable
                            </span>
                          ) : (
                            <span className="badge bg-light-danger text-danger">
                              <i className="ti ti-lock me-1"></i>Readonly
                            </span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── 5. Shubhali IP Manzillar va So'nggi Shubhali Xurujlar ────── */}
      <div className="row mb-2">
        {/* Shubhali IP Manzillar (Axelit customer-list style) */}
        <div className="col-xl-6 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-ban me-2 text-danger"></i>Shubhali IP Manzillar ({threatAnalysis.top_ips.length})
              </h5>
              <span className="badge bg-light-danger text-danger">
                {blockedIps.length} ta IP qora ro'yxatda
              </span>
            </div>
            <div className="card-body p-0">
              {/* Qo'lda IP bloklash formasi */}
              <form onSubmit={handleBlockIp} className="p-3 border-bottom d-flex gap-2">
                <input
                  type="text"
                  className="form-control form-control-sm"
                  placeholder="IP manzil (masalan: 185.120.45.67)"
                  value={ipToBlock}
                  onChange={(e) => setIpToBlock(e.target.value)}
                  disabled={isProcessing}
                />
                <button
                  type="submit"
                  className="btn btn-danger btn-sm text-nowrap"
                  disabled={isProcessing || !ipToBlock.trim()}
                >
                  <i className="ti ti-ban me-1"></i>IP Bloklash
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
                        <button
                          type="button"
                          className={`btn btn-sm ${item.is_blocked ? 'btn-outline-success' : 'btn-outline-danger'}`}
                          disabled={isProcessing}
                          onClick={() => handleToggleBlock(item.ip, item.is_blocked)}
                        >
                          {item.is_blocked ? (
                            <>
                              <i className="ti ti-lock-open me-1"></i>Blokdan chiqarish
                            </>
                          ) : (
                            <>
                              <i className="ti ti-ban me-1"></i>Bloklash
                            </>
                          )}
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
            </div>
          </div>
        </div>

        {/* So'nggi Shubhali Xurujlar (Axelit order-content-list style) */}
        <div className="col-xl-6 mb-4">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-activity me-2 text-info"></i>So'nggi Shubhali Xurujlar (Radar)
              </h5>
              <span className="badge bg-light-info text-info">
                Oxirgi {threatAnalysis.recent_threats.length} ta
              </span>
            </div>
            <div className="card-body p-0">
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
                          <span className={`badge text-light-${tone}`}>
                            {threat.time}
                          </span>
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
            </div>
          </div>
        </div>
      </div>

      {/* ── 6. Serverdagi So'nggi Jiddiy Xatoliklar (500 / Exception Log) ── */}
      <div className="card mb-4">
        <div className="card-header d-flex justify-content-between align-items-center">
          <h5 className="f-w-600 mb-0">
            <i className="ti ti-bug me-2 text-danger"></i>Serverdagi So'nggi Jiddiy Xatoliklar (laravel.log / 500)
          </h5>
          <span className="badge bg-light-secondary text-secondary">
            So'nggi {systemLogs.length} ta log yozuvi
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
                    <th className="pe-3" style={{ width: '25%' }}>Fayl</th>
                  </tr>
                </thead>
                <tbody>
                  {systemLogs.map((log, index) => (
                    <tr key={index}>
                      <td className="ps-3 py-2 text-secondary f-s-13">
                        {log.timestamp}
                      </td>
                      <td>
                        <span className={`badge ${log.level === 'CRITICAL' ? 'bg-danger text-white' : 'bg-light-danger text-danger'}`}>
                          {log.level}
                        </span>
                      </td>
                      <td className="text-dark f-w-500 font-monospace f-s-13 text-break">
                        {log.message}
                      </td>
                      <td className="pe-3 font-monospace f-s-12 text-secondary text-truncate" style={{ maxWidth: '280px' }}>
                        {log.file}
                      </td>
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
  );
}
