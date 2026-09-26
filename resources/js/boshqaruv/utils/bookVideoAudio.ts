/**
 * KITOB VIDEOLARI — ovoz: brauzerning o'zida sintez qilinadigan quvnoq trek
 * (110 BPM, C–G–Am–F) + sahna kesimlariga mos ovoz effektlari, yoki admin
 * yuklagan musiqa fayli. Natija — AudioBuffer, video bilan birga yoziladi.
 */
import { BEAT, TIMING, durationOf, type VideoScene } from './bookVideoRenderer';

export type MusicMode = 'fresh' | 'custom' | 'none';

export interface SoundOptions {
  music: MusicMode;
  custom?: AudioBuffer | null;
  sfx: boolean;
}

const SR = 44100;
const midi = (n: number) => 440 * Math.pow(2, (n - 69) / 12);

/** Admin yuklagan musiqa faylini o'qiydi. */
export async function decodeMusicFile(file: File): Promise<AudioBuffer> {
  const ac = new AudioContext();
  try {
    return await ac.decodeAudioData(await file.arrayBuffer());
  } finally {
    void ac.close();
  }
}

/** Sahna uchun to'liq ovoz yo'lagini tayyorlaydi (musiqa ham, SFX ham yo'q bo'lsa — null). */
export async function renderSoundtrack(scene: VideoScene, opts: SoundOptions): Promise<AudioBuffer | null> {
  if (opts.music === 'none' && !opts.sfx) return null;
  if (opts.music === 'custom' && !opts.custom && !opts.sfx) return null;
  const total = durationOf(scene.template, scene.books.length);
  const ac = new OfflineAudioContext(2, Math.ceil(SR * total), SR);
  const comp = ac.createDynamicsCompressor();
  comp.threshold.value = -14;
  comp.ratio.value = 4;
  const master = ac.createGain();
  master.gain.setValueAtTime(0.9, 0);
  master.gain.setValueAtTime(0.9, Math.max(0, total - 1.2));
  master.gain.linearRampToValueAtTime(0, total);
  master.connect(comp).connect(ac.destination);

  const noise = ac.createBuffer(1, SR, SR);
  const nd = noise.getChannelData(0);
  let seed = 11;
  for (let i = 0; i < nd.length; i++) { seed = (seed * 16807) % 2147483647; nd[i] = (seed / 2147483647) * 2 - 1; }

  const bus = (gain: number) => { const g = ac.createGain(); g.gain.value = gain; g.connect(master); return g; };
  const musicBus = bus(0.55);
  const sfxBus = bus(0.5);

  const env = (g: GainNode, t: number, peak: number, attack: number, decay: number) => {
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(peak, t + attack);
    g.gain.exponentialRampToValueAtTime(0.0001, t + attack + decay);
  };
  const osc = (type: OscillatorType, f: number, t: number, dur: number, out: AudioNode) => {
    const o = ac.createOscillator();
    o.type = type;
    o.frequency.setValueAtTime(f, t);
    o.connect(out);
    o.start(t);
    o.stop(t + dur + 0.05);
    return o;
  };
  const noiseSrc = (t: number, dur: number, out: AudioNode) => {
    const n = ac.createBufferSource();
    n.buffer = noise;
    n.connect(out);
    n.start(t, (t * 7.3) % 0.5);
    n.stop(t + dur);
  };
  const filter = (type: BiquadFilterType, f: number, out: AudioNode, q = 0.8) => {
    const b = ac.createBiquadFilter();
    b.type = type;
    b.frequency.value = f;
    b.Q.value = q;
    b.connect(out);
    return b;
  };

  // ── cholg'ular ──
  const kick = (t: number) => {
    const g = ac.createGain(); g.connect(musicBus); env(g, t, 1, 0.003, 0.32);
    const o = osc('sine', 150, t, 0.35, g); o.frequency.exponentialRampToValueAtTime(45, t + 0.12);
  };
  const clap = (t: number) => {
    const g = ac.createGain(); g.connect(filter('bandpass', 1600, musicBus, 0.9)); env(g, t, 0.55, 0.002, 0.16);
    noiseSrc(t, 0.2, g);
  };
  const hat = (t: number, open: boolean) => {
    const g = ac.createGain(); g.connect(filter('highpass', 8000, musicBus)); env(g, t, open ? 0.2 : 0.14, 0.001, open ? 0.14 : 0.04);
    noiseSrc(t, open ? 0.2 : 0.06, g);
  };
  const bass = (t: number, note: number, dur: number) => {
    const g = ac.createGain(); g.connect(filter('lowpass', 480, musicBus)); env(g, t, 0.5, 0.005, dur);
    osc('sawtooth', midi(note), t, dur, g);
  };
  const pad = (t: number, notes: number[], dur: number) => {
    const g = ac.createGain(); g.connect(filter('lowpass', 1500, musicBus));
    g.gain.setValueAtTime(0.0001, t); g.gain.linearRampToValueAtTime(0.06, t + 0.08);
    g.gain.setValueAtTime(0.06, t + dur - 0.15); g.gain.linearRampToValueAtTime(0.0001, t + dur);
    notes.forEach((n) => { osc('sawtooth', midi(n) * 1.003, t, dur, g); osc('sawtooth', midi(n) * 0.997, t, dur, g); });
  };
  const pluck = (t: number, note: number, v = 0.22) => {
    const g = ac.createGain(); g.connect(musicBus); env(g, t, v, 0.003, 0.32);
    osc('triangle', midi(note), t, 0.4, g); osc('sine', midi(note + 12), t, 0.25, g);
  };

  // ── SFX ──
  const whoosh = (t: number, dur = 0.45) => {
    const g = ac.createGain(); g.connect(sfxBus);
    g.gain.setValueAtTime(0.0001, t); g.gain.linearRampToValueAtTime(0.5, t + dur * 0.6); g.gain.linearRampToValueAtTime(0.0001, t + dur);
    const b = filter('bandpass', 400, g, 1.2); b.frequency.setValueAtTime(400, t); b.frequency.exponentialRampToValueAtTime(5000, t + dur);
    noiseSrc(t, dur, b);
  };
  const pop = (t: number, f0 = 500, f1 = 1100, v = 0.45) => {
    const g = ac.createGain(); g.connect(sfxBus); env(g, t, v, 0.002, 0.09);
    const o = osc('sine', f0, t, 0.1, g); o.frequency.exponentialRampToValueAtTime(f1, t + 0.08);
  };
  const ding = (t: number, f = 1568, v = 0.3) => {
    [1, 2.01, 3.02].forEach((m, i) => { const g = ac.createGain(); g.connect(sfxBus); env(g, t, v / (i + 1), 0.003, 1.1 / (i + 1)); osc('sine', f * m, t, 1.2, g); });
  };

  const { INTRO, PER_BOOK, OUTRO } = TIMING;
  const outroAt = total - OUTRO;

  // ── musiqa ──
  if (opts.music === 'custom' && opts.custom) {
    const src = ac.createBufferSource();
    src.buffer = opts.custom;
    const g = ac.createGain(); g.gain.value = 0.9; g.connect(master);
    src.connect(g);
    src.start(0);
  } else if (opts.music === 'fresh') {
    const prog: [number, number[]][] = [[48, [60, 64, 67]], [43, [59, 62, 67]], [45, [60, 64, 69]], [41, [60, 65, 69]]]; // C G Am F
    const hook = [76, 79, 81, 79, 76, 74, 72, 74, 76, 79, 84, 81, 79, 76, 79, 81];
    const beats = Math.ceil(total / BEAT);
    const drop = Math.round(INTRO / BEAT); // birinchi kitob bilan ritm to'liq kiradi
    const stop = Math.floor(outroAt / BEAT);
    for (let k = 0; k < beats; k++) {
      const t = k * BEAT;
      const [root, chord] = prog[Math.floor(k / 4) % 4];
      if (k % 4 === 0 && t < total) pad(t, chord, Math.min(BEAT * 4, total - t));
      if (k < stop) {
        hat(t + BEAT / 2, k % 4 === 3);
        if (k >= drop - 2 || k % 2 === 0) hat(t, false);
        if (k >= drop) {
          kick(t);
          if (k % 2 === 1) clap(t);
          bass(t, root - 12, BEAT * 0.45);
          bass(t + BEAT / 2, root, BEAT * 0.4);
          pluck(t, hook[(k * 2) % hook.length]);
          if (k % 2 === 0) pluck(t + BEAT / 2, hook[(k * 2 + 1) % hook.length], 0.16);
        } else if (k === drop - 1) {
          whoosh(t, BEAT); // zarb oldidan "ko'tarilish"
        }
      }
    }
    // yakuniy akkord
    const fin = stop * BEAT;
    kick(fin);
    pad(fin, [60, 64, 67, 72], Math.max(0.5, total - fin));
    bass(fin, 36, Math.max(0.5, total - fin - 0.1));
  }

  // ── sahna effektlari ──
  if (opts.sfx) {
    for (let i = 0; i < 3; i++) pop(0.35 + i * 0.12, 400 + i * 120, 900 + i * 150, 0.25);
    const n = scene.books.length;
    if (scene.template === 'grid') {
      whoosh(INTRO - 1.2, 0.8);
      for (let i = 0; i < Math.min(n, 6); i++) pop(INTRO + 0.25 + i * 0.08, 600 + i * 90, 1300 + i * 120, 0.3);
      pop(INTRO + 1.0, 300, 900, 0.5);
    } else {
      whoosh(INTRO - 1.2, 0.8);
      for (let k = 1; k < n; k++) whoosh(INTRO + k * PER_BOOK - 0.05, 0.5);
      for (let k = 0; k < n; k++) pop(INTRO + k * PER_BOOK + (k === 0 ? 0.35 : 0.8), 300, 900, 0.4);
      if (scene.template === 'countdown') {
        const f = INTRO + (n - 1) * PER_BOOK + 0.8;
        ding(f, 1568, 0.35); ding(f + 0.12, 2093, 0.3);
        for (let i = 0; i < 6; i++) ding(f + 0.25 + i * 0.05, 2093 * Math.pow(2, (i * 5 % 12) / 12), 0.07);
      }
    }
    whoosh(outroAt - 0.5, 0.5);
    ding(outroAt + 0.35, 1760, 0.3);
    pop(outroAt + 1.0, 500, 1000, 0.3);
    pop(outroAt + 1.1, 600, 1200, 0.3);
  }

  return ac.startRendering();
}
