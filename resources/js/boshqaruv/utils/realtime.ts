import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/*
 * Boshqaruv uchun real-vaqt ulanishi (Reverb / Pusher protokoli).
 * Kalit va manzil serverdan keladi (SupportInboxController::realtimeConfig),
 * kanal avtorizatsiyasi — panel sessiyasi orqali /boshqaruv/broadcasting/auth.
 */

export type RealtimeConfig = {
  driver: 'reverb' | 'pusher';
  key: string;
  cluster?: string;
  host: string;
  port: number;
  tls: boolean;
};

export type RealtimeState = 'connecting' | 'connected' | 'unavailable' | 'offline';

let echo: Echo<'pusher'> | null = null;
let echoKey = '';

export function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';
}

export function getEcho(config: RealtimeConfig | null | undefined, authEndpoint: string): Echo<'pusher'> | null {
  if (!config || !config.key) return null;

  const signature = `${config.key}@${config.host}:${config.port}`;
  if (echo && echoKey === signature) return echo;

  try {
    (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;
    echo = new Echo({
      broadcaster: 'pusher',
      key: config.key,
      cluster: config.cluster || 'mt1',
      wsHost: config.host,
      wsPort: config.port,
      wssPort: config.port,
      forceTLS: config.tls,
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
      authEndpoint,
      auth: {
        headers: {
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      },
    } as never);
    echoKey = signature;
  } catch (error) {
    console.warn('[realtime] ulanib bo‘lmadi', error);
    echo = null;
  }

  return echo;
}

/** Pusher ulanish holatini kuzatish. Qaytgan funksiya — obunani bekor qilish. */
export function watchState(instance: Echo<'pusher'> | null, onChange: (state: RealtimeState) => void): () => void {
  const pusher = (instance?.connector as unknown as { pusher?: Pusher })?.pusher;
  if (!pusher) {
    onChange('offline');
    return () => undefined;
  }

  const map = (state: string): RealtimeState => (
    state === 'connected' ? 'connected' : state === 'connecting' || state === 'initialized' ? 'connecting' : state === 'unavailable' ? 'unavailable' : 'offline'
  );
  const handler = ({ current }: { current: string }) => onChange(map(current));
  pusher.connection.bind('state_change', handler);
  onChange(map(pusher.connection.state));

  return () => pusher.connection.unbind('state_change', handler);
}

/** JSON so'rov (CSRF bilan). Xatoda `message` bilan Error tashlaydi. */
export async function apiJson<T = unknown>(url: string, options: { method?: string; body?: unknown; signal?: AbortSignal } = {}): Promise<T> {
  const response = await fetch(url, {
    method: options.method || 'GET',
    credentials: 'same-origin',
    signal: options.signal,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.body !== undefined ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() } : { 'X-CSRF-TOKEN': csrfToken() }),
    },
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const first = data?.errors ? Object.values(data.errors as Record<string, string[]>)[0]?.[0] : null;
    throw new Error(first || data?.message || `Xatolik (${response.status})`);
  }

  return data as T;
}
