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
  const overallStatus = hasConfigError || isDiskCritical
    ? { tone: 'danger', label: 'Kritik holat', icon: 'ti-alert-triangle' }
    : threatAnalysis.threat_level === 'high'
    ? { tone: 'warning', label: 'Diqqat talab', icon: 'ti-alert-triangle' }
    : { tone: 'success', label: 'Tizim barqaror', icon: 'ti-shield-check' };

  // Xizmatlar sanasi: nechta sog'lom
  const svcCount = [
    servicesStatus.database.status === 'ok',
    servicesStatus.cache.status === 'ok',
    servicesStatus.queue.failed_jobs === 0,
  ];
  const healthyServices = svcCount.filter(Boolean).length;
  const diskTone = serverHealth.disk.used_percent >= 90 ? 'danger' : serverHealth.disk.used_percent >= 80 ? 'warning' : 'primary';

  return (
    <div>
      {/* Sarlavha: umumiy holat, server ma'lumoti va tezkor amallar */}
      <div className="kb-page-head">
        <div className="min-w-0">
          <div className="d-flex align-items-center gap-2 flex-wrap">
            <h4 className="main-title mb-0">Kiberxavfsizlik va server</h4>
            <span className={`badge text-light-${overallStatus.tone}`}><i className={`ti ${overallStatus.icon}`}></i>{overallStatus.label}</span>
            {firewallActive ? <span className="badge text-light-success"><i className="ti ti-shield-check"></i>WAF faol</span> : <span className="badge text-light-danger">WAF o‘chiq</span>}
          </div>
          <p className="mb-0 text-secondary f-s-13 mt-1">
            Jonli monitoring · {generatedAt}
            <span className="mx-2">·</span>Sizning IP: <code>{currentAdminIp}</code>
            <span className="mx-2">·</span>Server: {serverHealth.system.server_ip}
            <span className="mx-2">·</span>Ishlash vaqti: {serverHealth.system.uptime}
          </p>
        </div>
        <div className="kb-page-actions">
          <button type="button" className="btn btn-sm btn-light-secondary" disabled={isProcessing} onClick={() => router.reload()}><i className="ti ti-refresh"></i>Yangilash</button>
          <button type="button" className="btn btn-sm btn-light-secondary" disabled={isProcessing} onClick={handleClearCache}><i className="ti ti-eraser"></i>Keshni tozalash</button>
          <button type="button" className="btn btn-sm btn-light-secondary" disabled={isProcessing} onClick={handleFixStorage}><i className="ti ti-folder"></i>Storage’ni tiklash</button>
          <button type="button" className="btn btn-sm btn-light-danger" disabled={isProcessing} onClick={handleTruncateLogs}><i className="ti ti-trash"></i>Log tozalash ({logFileSize})</button>
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
          <i className="ti ti-database f-s-24 text-danger flex-shrink-0"></i>
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

      {/* Resurslar: disk, RAM, tahdidlar, xizmatlar */}
      <div className="row">
        <div className="col-sm-6 col-xxl-3">
          <div className="card h-100 kb-res">
            <div className="card-body">
              <div className="kb-res-head"><span>Disk</span><span className={`badge text-light-${diskTone === 'primary' ? 'success' : diskTone}`}>{serverHealth.disk.free_formatted} bo‘sh</span></div>
              <div className="kb-res-value">{serverHealth.disk.used_percent}%</div>
              <div className="progress progress-sm my-2"><div className={`progress-bar bg-${diskTone}`} role="progressbar" style={{ width: `${Math.min(100, serverHealth.disk.used_percent)}%` }}></div></div>
              <div className="kb-res-foot"><span>{serverHealth.disk.used_formatted} / {serverHealth.disk.total_formatted}</span><span>Inodes: {serverHealth.disk.inodes_percent !== null ? `${serverHealth.disk.inodes_percent}%` : 'N/A'}</span></div>
            </div>
          </div>
        </div>
        <div className="col-sm-6 col-xxl-3">
          <div className="card h-100 kb-res">
            <div className="card-body">
              <div className="kb-res-head"><span>RAM</span><span className="badge text-light-secondary">{serverHealth.ram.percent !== null ? `${serverHealth.ram.percent}% band` : 'Linux RAM'}</span></div>
              <div className="kb-res-value">{serverHealth.ram.percent !== null ? `${serverHealth.ram.percent}%` : `${serverHealth.system.load_average[0] || 0} load`}</div>
              <div className="progress progress-sm my-2"><div className={`progress-bar bg-${(serverHealth.ram.percent ?? 0) >= 90 ? 'danger' : (serverHealth.ram.percent ?? 0) >= 80 ? 'warning' : 'primary'}`} role="progressbar" style={{ width: `${Math.min(100, serverHealth.ram.percent ?? 30)}%` }}></div></div>
              <div className="kb-res-foot"><span>{serverHealth.ram.used_mb ? `${serverHealth.ram.used_mb} MB` : '0 MB'} / {serverHealth.ram.total_mb ? `${serverHealth.ram.total_mb} MB` : 'N/A'}</span><span>Load: {serverHealth.system.load_average.join(', ')}</span></div>
            </div>
          </div>
        </div>
        <div className="col-sm-6 col-xxl-3">
          <div className="card h-100 kb-res">
            <div className="card-body">
              <div className="kb-res-head"><span>Kiber tahdidlar</span><span className={`badge ${threatAnalysis.threat_level === 'high' ? 'text-light-danger' : 'text-light-success'}`}>{threatAnalysis.threat_level === 'high' ? 'Yuqori xavf' : "Me'yorda"}</span></div>
              <div className="kb-res-value">{totalThreats} <span className="kb-res-unit">ta urinish</span></div>
              <div className="progress progress-sm my-2"><div className={`progress-bar ${threatAnalysis.threat_level === 'high' ? 'bg-danger' : 'bg-warning'}`} role="progressbar" style={{ width: `${Math.min(100, totalThreats > 0 ? Math.max(15, totalThreats * 8) : 5)}%` }}></div></div>
              <div className="kb-res-foot"><span>SQLi: {threatAnalysis.vectors.sql_injection} · Path: {threatAnalysis.vectors.path_traversal}</span><span>Probes: {threatAnalysis.vectors.sensitive_files}</span></div>
            </div>
          </div>
        </div>
        <div className="col-sm-6 col-xxl-3">
          <div className="card h-100 kb-res">
            <div className="card-body">
              <div className="kb-res-head"><span>Xizmatlar</span><span className={`badge ${healthyServices === svcCount.length ? 'text-light-success' : 'text-light-warning'}`}>{healthyServices}/{svcCount.length} sog‘lom</span></div>
              <div className="kb-res-value">{servicesStatus.database.latency_ms !== null ? <>{servicesStatus.database.latency_ms} <span className="kb-res-unit">ms · DB</span></> : '100% OK'}</div>
              <div className="d-flex gap-1 flex-wrap my-2">
                {([
                  { ok: servicesStatus.database.status === 'ok', label: 'MySQL' },
                  { ok: servicesStatus.cache.status === 'ok', label: servicesStatus.cache.driver },
                  { ok: servicesStatus.queue.failed_jobs === 0, label: 'Queue' },
                ] as const).map((svc) => (
                  <span key={svc.label} className={`badge ${svc.ok ? 'text-light-success' : 'text-light-danger'}`}><i className={`ti ${svc.ok ? 'ti-check' : 'ti-x'}`}></i>{svc.label}</span>
                ))}
              </div>
              <div className="kb-res-foot"><span>PHP {serverHealth.system.php_version} · Laravel v{serverHealth.system.laravel_version}</span></div>
            </div>
          </div>
        </div>
      </div>

      {/* Tahdid vektorlari */}
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between"><h5 className="mb-0">Tahdid vektorlari</h5><span className="f-s-13 text-secondary">Jami {totalThreats} ta urinish</span></div>
        <div className="card-body pt-0">
          <div className="kb-vectors">
            {([
              { key: 'sql_injection', label: 'SQL injection', desc: "Ma'lumotlar bazasiga hujum", icon: 'ti-database' },
              { key: 'path_traversal', label: 'Path traversal', desc: '/etc/passwd va tizim skani', icon: 'ti-search' },
              { key: 'sensitive_files', label: 'Maxfiy fayllar', desc: '.env, .git va admin probes', icon: 'ti-file-text' },
              { key: 'xss_attempt', label: 'XSS', desc: 'Zararli script kiritish', icon: 'ti-shield-lock' },
              { key: 'auth_bruteforce', label: 'Bruteforce', desc: "Noto'g'ri parol urinishlari", icon: 'ti-lock-access' },
            ] as const).map((v) => {
              const count = (threatAnalysis.vectors as Record<string, number>)[v.key] || 0;
              return (
                <div key={v.key}>
                  <div className="kb-res-head"><span><i className={`ti ${v.icon} me-1`}></i>{v.label}</span></div>
                  <div className={`kb-res-value ${count > 0 ? '' : 'text-secondary'}`}>{count}</div>
                  <div className="f-s-12 text-secondary">{v.desc}</div>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      {/* ═══════════════════════════════════════════════════════════════════
          4-QATOR: Chap — Server detallari + himoya bannerlari
                   O'ng — Tabli IP/Tahdid paneli (zamonaviy tab layout)
         ═══════════════════════════════════════════════════════════════════ */}
      <div className="row">
        {/* CHAP: Server va Fayl tizimi (kattaroq karta — vizual ağırlık) */}
        <div className="col-xxl-5 col-xl-6">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-database me-2 text-primary"></i>Server holati
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
                      { icon: 'ti-settings', tone: 'info', label: 'CPU Load',
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
                      { icon: 'ti-hourglass', tone: 'secondary', label: 'Queue',
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
                      <i className="ti ti-checks f-s-20 text-success"></i>
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
                    <i className="ti ti-file-certificate f-s-20 text-primary"></i>
                    <span className="text-primary f-w-600 f-s-13">.env & .git himoyalangan</span>
                  </div>
                  <span className="badge bg-primary text-white">Xavfsiz</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* O'NG: Tabli karta — IP manzillar / Shubhali xurujlar */}
        <div className="col-xxl-7 col-xl-6">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <div className="nav kc-segment" role="tablist">
                <div className="nav-item">
                  <button type="button" role="tab" aria-selected={activeTab === 'ips'}
                    className={`nav-link ${activeTab === 'ips' ? 'active' : ''}`}
                    onClick={() => setActiveTab('ips')}>
                    <i className="ti ti-ban me-1"></i>Shubhali IP’lar
                    <span className="badge bg-light-danger text-danger ms-2">{threatAnalysis.top_ips.length}</span>
                  </button>
                </div>
                <div className="nav-item">
                  <button type="button" role="tab" aria-selected={activeTab === 'threats'}
                    className={`nav-link ${activeTab === 'threats' ? 'active' : ''}`}
                    onClick={() => setActiveTab('threats')}>
                    <i className="ti ti-chart-line me-1"></i>Xurujlar radari
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
                            <i className={`f-s-18 ${item.is_blocked ? 'ti ti-ban' : 'ti ti-shield-check'}`}></i>
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
                                <i className="ti ti-shield-x me-1"></i>{threat.type} · {threat.ip}
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
      <div className="row">
        {/* Papkalar — ixcham karta */}
        <div className="col-xxl-4 col-xl-5">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-folder me-2 text-success"></i>Fayl tizimi
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
        <div className="col-xxl-8 col-xl-7">
          <div className="card h-100">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="f-w-600 mb-0">
                <i className="ti ti-alert-triangle me-2 text-danger"></i>So'nggi jiddiy xatoliklar
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
                        <th>Xatolik matni</th>
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
