import type { ReactNode } from 'react';

/**
 * Axelit "profile" sahifasi komponentlari (apps/profile):
 *   ProfileCard — muqova + aylana avatar + ism + statistikalar ("profile-container")
 *   AboutList   — "About me" kartasidagi ikonka + nom + qiymat qatorlari ("about-list")
 *   Avatar      — Axelit ro'yxatlaridagi aylana avatar (h-40 w-40 d-flex-center b-r-50 …)
 */
const AVATAR_TONES = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'] as const;

export function initialsOf(name?: string | null): string {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return '?';
  return (parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0].slice(0, 2)).toUpperCase();
}

export function toneOfName(name?: string | null): (typeof AVATAR_TONES)[number] {
  const s = String(name || '');
  let h = 0;
  for (let i = 0; i < s.length; i += 1) h = (h * 31 + s.charCodeAt(i)) >>> 0;
  return AVATAR_TONES[h % AVATAR_TONES.length];
}

const AVATAR_SIZE = { xs: 'h-30 w-30 f-s-12', sm: 'h-35 w-35 f-s-13', md: 'h-40 w-40 f-s-14', lg: 'h-45 w-45 f-s-16', xl: 'h-60 w-60 f-s-20', xxl: 'h-80 w-80 f-s-26' } as const;

export function Avatar({ src, name, size = 'md', square, icon, className = '' }: { src?: string | null; name?: string | null; size?: keyof typeof AVATAR_SIZE; square?: boolean; icon?: string; className?: string }) {
  const tone = toneOfName(name);
  return (
    <span className={`${AVATAR_SIZE[size]} d-flex-center ${square ? 'b-r-10' : 'b-r-50'} overflow-hidden flex-shrink-0 f-w-600 text-light-${tone} ${className}`} title={name || undefined}>
      {src ? <img className="w-100 h-100 object-fit-cover" src={src} alt={name || ''} /> : icon ? <i className={icon}></i> : initialsOf(name)}
    </span>
  );
}

export interface ProfileStat { label: ReactNode; value: ReactNode }

export function ProfileCard({ image, name, subtitle, badges, stats, actions, verified, square, icon }: {
  image?: string | null;
  name: ReactNode;
  subtitle?: ReactNode;
  badges?: ReactNode;
  stats?: ProfileStat[];
  actions?: ReactNode;
  verified?: boolean;
  square?: boolean;
  icon?: string;
}) {
  const plain = typeof name === 'string' ? name : '';
  return (
    <div className="card">
      <div className="card-body">
        <div className="profile-container">
          <div className="image-details">
            <div className="profile-image kc-profile-cover"></div>
            <div className="profile-pic">
              <div className="avatar-upload">
                <div className="avatar-preview">
                  <div className={`kc-avatar ${square ? 'kc-avatar-square' : ''}`} style={image ? { backgroundImage: `url("${image}")` } : undefined}>
                    {image ? null : (
                      <span className={`w-100 h-100 d-flex-center ${square ? 'b-r-18' : 'b-r-50'} text-light-${toneOfName(plain)}`}>
                        {icon ? <i className={icon}></i> : initialsOf(plain)}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="person-details">
            <h5 className="f-w-600 mb-1 text-break">
              {name}
              {verified ? <i className="ti ti-discount-check-filled text-info ms-1 align-middle"></i> : null}
            </h5>
            {subtitle ? <p className="text-secondary mb-2 text-break">{subtitle}</p> : null}
            {badges ? <div className="d-flex flex-wrap justify-content-center gap-1">{badges}</div> : null}
            {stats && stats.length ? (
              <div className="details">
                {stats.map((stat, index) => (
                  <div key={index}>
                    <h4 className="text-primary mb-0 f-s-20">{stat.value}</h4>
                    <p className="text-secondary mb-0 f-s-13">{stat.label}</p>
                  </div>
                ))}
              </div>
            ) : null}
            {actions ? <div className="d-flex flex-wrap justify-content-center gap-2 mt-3">{actions}</div> : null}
          </div>
        </div>
      </div>
    </div>
  );
}

export function AboutList({ title, rows, children }: { title: ReactNode; rows: Array<{ icon?: string; label: ReactNode; value: ReactNode }>; children?: ReactNode }) {
  return (
    <div className="card">
      <div className="card-header">
        <h5 className="mb-0">{title}</h5>
      </div>
      <div className="card-body">
        {children}
        <div className="about-list pt-0">
          {rows.map((row, index) => (
            <div key={index} className="d-flex justify-content-between align-items-start gap-3">
              <span className="f-w-500 text-dark text-nowrap">{row.icon ? <i className={`ti ${row.icon} me-1 text-secondary`}></i> : null}{row.label}</span>
              <span className="f-s-13 text-secondary text-end text-break">{row.value === null || row.value === undefined || row.value === '' ? '—' : row.value}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

/** Mahsulot / kitob "show" kartasi: katta muqova, nom, izoh, belgilar va ko'rsatkichlar. */
export function MediaCard({ image, icon = 'ti ti-book', title, subtitle, badges, stats, portrait = true }: {
  image?: string | null;
  icon?: string;
  title: ReactNode;
  subtitle?: ReactNode;
  badges?: ReactNode;
  stats?: ProfileStat[];
  portrait?: boolean;
}) {
  return (
    <div className="card">
      <div className="card-body text-center">
        <div className={`${portrait ? 'w-160 h-215' : 'w-200 h-150'} b-r-22 overflow-hidden d-flex-center bg-light-primary mx-auto mb-3`}>
          {image ? <img className="w-100 h-100 object-fit-cover" src={image} alt="" /> : <i className={`${icon} f-s-50 text-primary`}></i>}
        </div>
        <h5 className="f-w-600 mb-1 text-break">{title}</h5>
        {subtitle ? <p className="text-secondary mb-2 text-break">{subtitle}</p> : null}
        {badges ? <div className="d-flex gap-1 justify-content-center flex-wrap">{badges}</div> : null}
        {stats && stats.length ? (
          <div className="d-flex justify-content-center flex-wrap gap-4 mt-3 pt-3 b-t-1-light">
            {stats.map((stat, index) => (
              <div key={index}>
                <h4 className="text-primary mb-0 f-s-18">{stat.value}</h4>
                <p className="text-secondary mb-0 f-s-13">{stat.label}</p>
              </div>
            ))}
          </div>
        ) : null}
      </div>
    </div>
  );
}

/** Axelit "profile-friends" qatori: avatar + ism + izoh (+ amal tugmasi). */
export function PersonRow({ src, name, meta, action, icon, className = '' }: { src?: string | null; name: ReactNode; meta?: ReactNode; action?: ReactNode; icon?: string; className?: string }) {
  return (
    <div className={`d-flex align-items-center ${className}`}>
      <Avatar src={src} name={typeof name === 'string' ? name : ''} icon={icon} />
      <div className="flex-grow-1 ps-2 min-w-0">
        <div className="f-w-500 txt-ellipsis-1">{name}</div>
        {meta ? <div className="text-muted f-s-12 txt-ellipsis-1">{meta}</div> : null}
      </div>
      {action}
    </div>
  );
}
