#!/usr/bin/env python3
"""
Boshqaruv uchun tabler-icons shrift to'plamini qayta yig'ish.

Panel faqat ishlatilgan ikonkalarni o'z ichiga olgan kichik shrift ishlatadi
(resources/css/axelit/fonts/tabler-icons.woff2). Yangi `ti ti-...` ikonka
qo'shilsa va u kvadrat bo'lib ko'rinsa — shu skriptni ishga tushiring:

    npm pack @tabler/icons-webfont@3.31.0 && tar xzf tabler-icons-webfont-3.31.0.tgz
    pip install fonttools brotli
    python3 scripts/tabler_subset.py package/dist

Skript resources/js/boshqaruv ichidagi barcha `ti-*` nomlarini topadi,
icons.css dagi tabler qoidalarini qayta yozadi va shriftni qisqartiradi.
"""
import glob
import os
import re
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DIST = sys.argv[1] if len(sys.argv) > 1 else 'package/dist'
CSS_PATH = os.path.join(ROOT, 'resources/css/axelit/icons.css')
FONT_OUT = os.path.join(ROOT, 'resources/css/axelit/fonts/tabler-icons.woff2')

full_css = open(os.path.join(DIST, 'tabler-icons.css'), encoding='utf-8').read()
full = dict(re.findall(r'\.ti-([a-z0-9-]+):before\s*\{\s*content:\s*"\\([0-9a-f]+)"', full_css))

used = set()
for path in glob.glob(os.path.join(ROOT, 'resources/js/boshqaruv/**/*.ts*'), recursive=True):
    used |= set(re.findall(r'\bti-([a-z0-9]+(?:-[a-z0-9]+)*)', open(path, encoding='utf-8').read()))
# Server ham ikonka nomini yuboradi (workQueue, menyu belgilari va h.k.)
for path in glob.glob(os.path.join(ROOT, 'app/**/*.php'), recursive=True):
    used |= set(re.findall(r'[\'" ]ti-([a-z0-9]+(?:-[a-z0-9]+)*)[\'"]', open(path, encoding='utf-8').read()))

css = open(CSS_PATH, encoding='utf-8').read()
current = set(re.findall(r'\.ti-([a-z0-9-]+):before\{content:"\\[0-9a-f]+"\}', css))
names = sorted(n for n in (used | current) if n in full)
missing = sorted(n for n in used if n not in full)

rules = '\n'.join(f'.ti-{n}:before{{content:"\\{full[n]}"}}' for n in names)
css = re.sub(r'\.ti-[a-z0-9-]+:before\{content:"\\[0-9a-f]+"\}\n?', '', css)
css = re.sub(r'/\* tabler-icons [^*]*\*/', f'/* tabler-icons 3.31.0 — {len(names)} glif (scripts/tabler_subset.py) */', css)
marker = re.search(r'\.ti\{font-family:"tabler-icons"[^}]*\}\n', css)
css = css[:marker.end()] + rules + '\n' + css[marker.end():]
open(CSS_PATH, 'w', encoding='utf-8').write(css)

unicodes = ','.join(f'U+{full[n].upper()}' for n in names)
subprocess.check_call([
    sys.executable, '-m', 'fontTools.subset', os.path.join(DIST, 'fonts/tabler-icons.ttf'),
    f'--unicodes={unicodes}', '--flavor=woff2', f'--output-file={FONT_OUT}', '--no-hinting', '--layout-features=',
])
print(f'{len(names)} ta ikonka; topilmadi: {missing or "yo‘q"}; {os.path.getsize(FONT_OUT)} bayt')
