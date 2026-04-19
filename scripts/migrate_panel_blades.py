#!/usr/bin/env python3
"""
Panel Blade — UI migratsiya: jadval o‘rami, bo‘sh kataklar, sarlavha → <x-panel.page-header>.
layouts/, partials/, auth/login — o‘tkazilmaydi.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "resources" / "views" / "panel"
SKIP_SUBSTR = ("layouts/", "partials/", "auth/login.blade.php")


def skip(path: Path) -> bool:
    rel = str(path.relative_to(ROOT)).replace("\\", "/")
    return any(s in rel for s in SKIP_SUBSTR)


def add_kc_twrap(html: str) -> str:
    out = html.replace('<div class="table-responsive">', '<div class="table-responsive kc-twrap">')
    while "kc-twrap kc-twrap" in out:
        out = out.replace("kc-twrap kc-twrap", "kc-twrap")
    return out


def empty_cells(html: str) -> str:
    html = re.sub(
        r'\sstyle="text-align:center;padding:48px;color:var\(--p-hint\)"',
        ' class="p-empty-cell"',
        html,
    )
    return html


def books_tab_count(html: str, path: Path) -> str:
    if path.name != "index.blade.php" or "books" not in str(path):
        return html
    return html.replace(
        '<span class="tab-count" style="{{ $tab===$key ? \'background:var(--p-accent);color:#fff\' : \'\' }}">',
        '<span class="tab-count">',
    )


def reels_footer(html: str, path: Path) -> str:
    if path.name != "index.blade.php" or "reels" not in str(path):
        return html
    return html.replace(
        '<div class="flex items-center justify-between px-3 py-2"\n       style="border-top:1px solid var(--p-border)">\n    <div style="font-size:12px;color:var(--p-hint)">',
        '<div class="p-card-footer">\n    <div class="p-card-footer-meta">',
    )


def _skip_ws(s: str, i: int) -> int:
    while i < len(s) and s[i] in " \t\n\r":
        i += 1
    return i


def _close_opening_div(s: str, div_lt: int) -> int | None:
    """div_lt — `<` dan boshlanadi. Mos `</div>` dan keyingi indeks."""
    depth = 0
    i = div_lt
    while i < len(s):
        if s.startswith("<div", i):
            depth += 1
            i = s.find(">", i) + 1
            if i == 0:
                return None
        elif s.startswith("</div>", i):
            depth -= 1
            i += len("</div>")
            if depth == 0:
                return i
        else:
            i += 1
    return None


def extract_simple_header(s: str, start: int, prefix: str) -> tuple[str, str, str | None, str, int] | None:
    """
    <div PREFIX>
      <div>
        <h1 class="page-title">TITLE</h1>
        <p class="page-sub">SUB</p>  (optional)
      </div>
      ACTIONS
    </div>
    Returns (title, sub_or_none, actions, back_href_or_none, end_exclusive)
    """
    if not s.startswith(prefix, start):
        return None
    p_end = start + len(prefix)
    i = _skip_ws(s, p_end)
    if not s.startswith("<div>", i):
        return None
    inner_open = i
    inner_gt = s.find(">", inner_open) + 1
    h1o = s.find('<h1 class="page-title">', inner_gt)
    if h1o == -1:
        return None
    t0 = h1o + len('<h1 class="page-title">')
    h1c = s.find("</h1>", t0)
    title = s[t0:h1c].strip()
    ps = s.find('<p class="page-sub">', h1c)
    sub: str | None
    pos_after_inner_div: int
    if ps != -1 and ps < h1c + 800:
        ps += len('<p class="page-sub">')
        pec = s.find("</p>", ps)
        if pec == -1:
            return None
        sub = s[ps:pec].strip()
        pos = pec + len("</p>")
        pos = _skip_ws(s, pos)
        if not s.startswith("</div>", pos):
            return None
        pos_after_inner_div = pos + len("</div>")
    else:
        sub = None
        pos = h1c + len("</h1>")
        pos = _skip_ws(s, pos)
        if not s.startswith("</div>", pos):
            return None
        pos_after_inner_div = pos + len("</div>")
    pos_after_inner_div = _skip_ws(s, pos_after_inner_div)
    depth = 1
    m = pos_after_inner_div
    act0 = m
    while m < len(s):
        if s.startswith("<div", m):
            depth += 1
            m = s.find(">", m) + 1
            if m == 0:
                return None
        elif s.startswith("</div>", m):
            depth -= 1
            if depth == 0:
                actions = s[act0:m].strip()
                return title, sub, actions, None, m + len("</div>")
            m += len("</div>")
        else:
            m += 1
    return None


def extract_back_left_header(s: str, start: int, prefix: str) -> tuple[str, str | None, str, str, int] | None:
    """
    Outer PREFIX, first child:
      <div class="flex items-center gap-3">
        <a href="URL" class="btn-p ghost icon">...</a>
        <div> <h1> <p>? </div>
      </div>
    ACTIONS
    """
    if not s.startswith(prefix, start):
        return None
    p_end = start + len(prefix)
    i = _skip_ws(s, p_end)
    row_open = '<div class="flex items-center gap-3">'
    if not s.startswith(row_open, i):
        return None
    row_start = i
    row_end = _close_opening_div(s, row_start)
    if row_end is None:
        return None
    row_inner = s[row_start + len(row_open) : row_end - len("</div>")]
    a_open = row_inner.find('<a href="')
    if a_open == -1:
        return None
    h0 = a_open + len('<a href="')
    h1 = row_inner.find('"', h0)
    back_url = row_inner[h0:h1]
    a_close = row_inner.find("</a>", a_open) + len("</a>")
    j = _skip_ws(row_inner, a_close)
    if not row_inner.startswith("<div>", j):
        return None
    inner_gt = row_inner.find(">", j) + 1
    h1o = row_inner.find('<h1 class="page-title">', inner_gt)
    if h1o == -1:
        return None
    t0 = h1o + len('<h1 class="page-title">')
    h1c = row_inner.find("</h1>", t0)
    title = row_inner[t0:h1c].strip()
    ps = row_inner.find('<p class="page-sub">', h1c)
    sub: str | None
    if ps != -1 and ps < h1c + 800:
        ps += len('<p class="page-sub">')
        pec = row_inner.find("</p>", ps)
        if pec == -1:
            return None
        sub = row_inner[ps:pec].strip()
    else:
        sub = None
    act0 = _skip_ws(s, row_end)
    depth = 1
    m = act0
    a_start = m
    while m < len(s):
        if s.startswith("<div", m):
            depth += 1
            m = s.find(">", m) + 1
            if m == 0:
                return None
        elif s.startswith("</div>", m):
            depth -= 1
            if depth == 0:
                actions = s[a_start:m].strip()
                return title, sub, actions, back_url, m + len("</div>")
            m += len("</div>")
        else:
            m += 1
    return None


HEADER_PREFIXES = (
    '<div class="flex items-start justify-between mb-4 fade-up">',
    '<div class="flex items-start justify-between mb-3 fade-up">',
    '<div class="page-header fade-up flex items-start justify-between">',
    '<div class="page-header fade-up flex items-start justify-between mb-3">',
    '<div class="page-header fade-up flex items-start justify-between mb-4">',
)


def build_header(title: str, sub: str | None, actions: str, back: str | None) -> str:
    lines: list[str] = ["<x-panel.page-header"]
    if back:
        lines[0] += f' back-href="{back}"'
    lines[0] += ">"
    lines.append(f'  <x-slot name="heading">{title}</x-slot>')
    if sub:
        lines.append(f'  <x-slot name="meta">{sub}</x-slot>')
    if actions:
        lines.append('  <x-slot name="actions">')
        for ln in actions.splitlines():
            lines.append("    " + ln)
        lines.append("  </x-slot>")
    lines.append("</x-panel.page-header>")
    return "\n".join(lines) + "\n"


def convert_page_headers(html: str) -> tuple[str, int]:
    n = 0
    for prefix in HEADER_PREFIXES:
        guard = 0
        while guard < 500:
            guard += 1
            idx = html.find(prefix)
            if idx == -1:
                break
            got = extract_back_left_header(html, idx, prefix)
            if got:
                title, sub, actions, back, end = got
                html = html[:idx] + build_header(title, sub, actions, back) + html[end:]
                n += 1
                continue
            got2 = extract_simple_header(html, idx, prefix)
            if got2:
                title, sub, actions, back, end = got2
                html = html[:idx] + build_header(title, sub, actions, back) + html[end:]
                n += 1
                continue
            break
    return html, n


def convert_flex_gap_mb4_back(html: str) -> tuple[str, int]:
    """<div class=\"flex items-center gap-3 mb-4 fade-up\"> + back + title + sub.</div>"""
    pat = re.compile(
        r'<div class="flex items-center gap-3 mb-4 fade-up">\s*'
        r'<a href="([^"]+)"[\s\S]*?class="btn-p ghost icon"[\s\S]*?>[\s\S]*?</a>\s*'
        r'<div>\s*'
        r'<h1 class="page-title">([\s\S]*?)</h1>\s*'
        r'(?:<p class="page-sub"[^>]*>([\s\S]*?)</p>\s*)?'
        r"</div>\s*</div>",
        re.M,
    )
    n = 0
    out: list[str] = []
    last = 0
    for m in pat.finditer(html):
        url, title, sub = m.group(1), m.group(2).strip(), (m.group(3) or "").strip()
        block = f'<x-panel.page-header back-href="{url}">\n  <x-slot name="heading">{title}</x-slot>\n'
        if sub:
            block += f'  <x-slot name="meta">{sub}</x-slot>\n'
        block += "</x-panel.page-header>\n"
        out.append(html[last : m.start()])
        out.append(block)
        last = m.end()
        n += 1
    if not n:
        return html, 0
    out.append(html[last:])
    return "".join(out), n


def convert_standalone_back_header(html: str) -> tuple[str, int]:
    """page-header + items-center gap-3 + back (faqat chap blok)."""
    pat = re.compile(
        r'<div class="page-header fade-up flex items-center gap-3">\s*'
        r'<a href="([^"]+)" class="btn-p ghost icon"[^>]*>\s*<i class="bi bi-arrow-left"></i>\s*</a>\s*'
        r'<div>\s*'
        r'<h1 class="page-title">([\s\S]*?)</h1>\s*'
        r'(?:<p class="page-sub">([\s\S]*?)</p>\s*)?'
        r"</div>\s*</div>",
        re.M,
    )
    n = 0
    out = []
    last = 0
    for m in pat.finditer(html):
        url, title, sub = m.group(1), m.group(2).strip(), (m.group(3) or "").strip()
        block = f'<x-panel.page-header back-href="{url}">\n  <x-slot name="heading">{title}</x-slot>\n'
        if sub:
            block += f'  <x-slot name="meta">{sub}</x-slot>\n'
        block += "</x-panel.page-header>\n"
        out.append(html[last : m.start()])
        out.append(block)
        last = m.end()
        n += 1
    if not n:
        return html, 0
    out.append(html[last:])
    return "".join(out), n


def process_file(path: Path) -> dict[str, int]:
    raw = path.read_text(encoding="utf-8")
    t = raw
    st = {"kc_twrap": 0, "empty": 0, "headers": 0, "solo_back": 0}
    t2 = add_kc_twrap(t)
    if t2 != t:
        st["kc_twrap"] = t2.count("kc-twrap") - t.count("kc-twrap")
    t = t2
    t0 = t
    t = empty_cells(t)
    if t != t0:
        st["empty"] = 1
    t = books_tab_count(t, path)
    t = reels_footer(t, path)
    t, h1 = convert_page_headers(t)
    st["headers"] += h1
    t, h2 = convert_standalone_back_header(t)
    st["solo_back"] += h2
    t, h3 = convert_flex_gap_mb4_back(t)
    st["solo_back"] += h3
    if t != raw:
        path.write_text(t, encoding="utf-8")
    return st


def main() -> int:
    agg = {"files": 0, "kc_twrap": 0, "empty": 0, "headers": 0, "solo_back": 0}
    for path in sorted(ROOT.rglob("*.blade.php")):
        if skip(path):
            continue
        st = process_file(path)
        if sum(st.values()) > 0:
            agg["files"] += 1
        for k in st:
            agg[k] = agg.get(k, 0) + st[k]
    print("migrate_panel_blades:", agg)
    return 0


if __name__ == "__main__":
    sys.exit(main())
