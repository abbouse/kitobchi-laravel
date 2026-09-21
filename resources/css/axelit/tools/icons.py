"""Axelit ikonka to'plamlaridan (iconoir, tabler, phosphor) faqat ishlatilgan
ikonkalarni ajratib oladi: kichik CSS + qisqartirilgan woff2 shriftlar.

Foydalanish: python3 icons.py <classes.txt> <out_dir>
classes.txt — TSX'dan yig'ilgan klass nomlari (bo'sh joy bilan ajratilgan).
"""
import re, sys, os, subprocess
from fontTools import subset
from fontTools.ttLib import TTFont

AX = os.environ.get('AXELIT_VENDOR', 'vendor')  # Axelit'ning assets/vendor/ionio-icon/css/iconoir.css joylashgan papka
NM = os.environ.get('ICONS_NODE_MODULES', 'node_modules')  # @tabler/icons-webfont@2.4.0 va @phosphor-icons/web@2.0.3
classes = set(open(sys.argv[1]).read().split())
out = sys.argv[2]
os.makedirs(os.path.join(out, 'fonts'), exist_ok=True)
css_parts = []

# ── iconoir (mask-image data URI) ───────────────────────────────────────
ico = open(f'{AX}/iconoir.css').read()
base_end = ico.index('.iconoir-accessibility-sign::before')
base = ico[ico.index("*[class^='iconoir-']::before"):base_end].strip()
used_io = sorted(c for c in classes if c.startswith('iconoir-'))
rules = []
missing = []
for name in used_io:
    m = re.search(r'\.' + re.escape(name) + r'::before\{[^}]*\}', ico)
    if m: rules.append(m.group(0))
    else: missing.append(name)
css_parts.append('/* iconoir (Axelit versiyasi) — faqat ishlatilganlari */\n' + base + '\n' + '\n'.join(rules))

# ── tabler 2.4.0 (shrift) ───────────────────────────────────────────────
tcss = open(f'{NM}/@tabler/icons-webfont/tabler-icons.css').read()
ALWAYS_TI = {0xea5f, 0xea7a, 0xea5e, 0xf671, 0xea61, 0xf60d, 0xf704}
used_ti = sorted(c for c in classes if c.startswith('ti-'))
ti_rules, ti_cps = [], set(ALWAYS_TI)
for name in used_ti:
    m = re.search(r'\.' + re.escape(name) + r':before\s*\{\s*content:\s*"\\([0-9a-f]+)";?\s*\}', tcss)
    if m:
        ti_cps.add(int(m.group(1), 16)); ti_rules.append(f'.{name}:before{{content:"\\{m.group(1)}"}}')
    else: missing.append(name)
def mk_subset(src, dst, cps):
    opts = subset.Options(); opts.flavor = 'woff2'; opts.layout_features = []; opts.notdef_outline = True
    opts.name_IDs = ['*']; opts.drop_tables += ['FFTM']
    f = TTFont(src)
    for t in ('GSUB', 'GPOS', 'GDEF'):
        if t in f: del f[t]
    s = subset.Subsetter(opts); s.populate(unicodes=sorted(cps)); s.subset(f)
    f.flavor = 'woff2'; f.save(dst)
    return os.path.getsize(dst)
sz = mk_subset(f'{NM}/@tabler/icons-webfont/fonts/tabler-icons.ttf', f'{out}/fonts/tabler-icons.woff2', ti_cps)
css_parts.append(f'''/* tabler-icons 2.4.0 — {len(ti_cps)} glif */
@font-face{{font-family:"tabler-icons";font-style:normal;font-weight:400;font-display:block;src:url("./fonts/tabler-icons.woff2") format("woff2")}}
.ti{{font-family:"tabler-icons"!important;speak:none;font-style:normal;font-weight:normal;font-variant:normal;text-transform:none;line-height:1;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}}
''' + '\n'.join(ti_rules))

# ── phosphor 2.0.3 (regular / bold / fill / duotone) ────────────────────
WEIGHTS = [('ph', 'regular', 'Phosphor'), ('ph-bold', 'bold', 'Phosphor-Bold'), ('ph-fill', 'fill', 'Phosphor-Fill'), ('ph-duotone', 'duotone', 'Phosphor-Duotone')]
ALWAYS_PH = {'bold': {0xe9fe, 0xea00, 0xec86, 0xebf8, 0xea37}}
ph_names = sorted(c for c in classes if c.startswith('ph-') and c not in ('ph-bold', 'ph-fill', 'ph-duotone', 'ph-light', 'ph-thin'))
for cls, w, fam in WEIGHTS:
    if cls not in classes and w != 'bold':
        continue
    pcss = open(f'{NM}/@phosphor-icons/web/src/{w}/style.css').read()
    cps = set(ALWAYS_PH.get(w, set())); rules = []
    for name in ph_names:
        for m in re.finditer(r'\.' + re.escape(cls) + r'\.' + re.escape(name) + r'(:before|:after)\s*\{([^}]*)\}', pcss):
            body = m.group(2)
            for cp in re.findall(r'content:\s*"\\([0-9a-f]+)"', body): cps.add(int(cp, 16))
            rules.append(f'.{cls}.{name}{m.group(1)}{{{" ".join(body.split())}}}')
    if not cps: continue
    # asosiy qoidalar (.ph-bold {font-family...}, duotone :before/:after tartibi)
    hm = re.search(r'(^|\n)\.' + re.escape(cls) + r'\s*\{([^}]*)\}', pcss)
    body = re.sub(r'/\*.*?\*/', '', hm.group(2), flags=re.S)
    head = [f'.{cls}{{' + ' '.join(body.split()) + '}']
    src = f'{NM}/@phosphor-icons/web/src/{w}/{fam}.ttf'
    fname = f'{fam}.woff2'
    mk_subset(src, f'{out}/fonts/{fname}', cps)
    css_parts.append(f'/* phosphor {w} — {len(cps)} glif */\n@font-face{{font-family:"{fam}";src:url("./fonts/{fname}") format("woff2");font-weight:normal;font-style:normal;font-display:block}}\n' + '\n'.join(head) + '\n' + '\n'.join(rules))

open(f'{out}/icons.css', 'w').write('/* Axelit ikonkalari — avtomatik yig\'ilgan qism to\'plam (scripts/axelit-icons.py). Qo\'lda tahrirlamang. */\n' + '\n\n'.join(css_parts) + '\n')
print('iconoir', len(used_io), 'tabler', len(used_ti), 'phosphor names', len(ph_names), 'missing', missing)
for f in os.listdir(f'{out}/fonts'): print(f, os.path.getsize(f'{out}/fonts/{f}'))
