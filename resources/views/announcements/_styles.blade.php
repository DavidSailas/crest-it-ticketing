{{-- Announcements UI. Self-contained on purpose (no Tailwind rebuild needed). --}}
<style>
    /* ── Shared bits ──────────────────────────────────────── */
    .ann-ico { --fg:#475569; --bg:#f1f5f9; flex:none; display:flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:.8rem; background:var(--bg); color:var(--fg); }
    .ann-ico svg { width:1.3rem; height:1.3rem; }
    .ann-chip { --fg:#475569; --bg:#f1f5f9; display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:999px; background:var(--bg); color:var(--fg); font-size:.7rem; font-weight:600; letter-spacing:.01em; line-height:1.3; white-space:nowrap; }
    .ann-chip svg { width:.8rem; height:.8rem; }
    .ann-chip--plain { --bg:#f3f4f6; --fg:#6b7280; font-weight:500; }
    .ann-new { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; background:#1a6b3c; color:#fff; font-size:.62rem; font-weight:700; letter-spacing:.07em; }
    .ann-new::before { content:''; width:.35rem; height:.35rem; border-radius:999px; background:#bbf7d0; }
    .ann-muted { color:#9ca3af; }
    .ann-avatar { flex:none; display:inline-flex; align-items:center; justify-content:center; width:1.6rem; height:1.6rem; border-radius:999px; background:#e7f3ec; color:#14532d; font-size:.62rem; font-weight:700; }

    /* rich message body */
    .ann-body { color:#374151; font-size:.93rem; line-height:1.65; overflow-wrap:anywhere; }
    .ann-body p { margin:.6rem 0 0; }
    .ann-body p:first-child, .ann-body ul:first-child { margin-top:0; }
    .ann-body ul { margin:.6rem 0 0; padding-left:0; list-style:none; }
    .ann-body li { position:relative; margin:.3rem 0; padding-left:1.35rem; }
    .ann-body li::before { content:''; position:absolute; left:.25rem; top:.62em; width:.42rem; height:.42rem; border-radius:999px; background:var(--fg,#1a6b3c); opacity:.85; }
    .ann-body a { color:#15803d; text-decoration:underline; text-underline-offset:2px; }
    .ann-body strong { color:#111827; font-weight:600; }

    /* ── Pop-up ───────────────────────────────────────────── */
    @keyframes annFade { from { opacity:0 } to { opacity:1 } }
    @keyframes annRise { from { opacity:0; transform:translateY(14px) scale(.985) } to { opacity:1; transform:none } }
    .ann-modal { position:fixed; inset:0; z-index:70; display:flex; align-items:center; justify-content:center; padding:1rem; background:rgba(10,24,17,.58); backdrop-filter:blur(3px); animation:annFade .2s ease-out; }
    .ann-dialog { --fg:#15803d; --bg:#ecf7f0; width:100%; max-width:36rem; max-height:min(88vh, 42rem); max-height:min(88dvh, 42rem); display:flex; flex-direction:column; overflow:hidden; border-radius:1.4rem; background:#fff; box-shadow:0 30px 70px -15px rgba(0,0,0,.45); animation:annRise .28s cubic-bezier(.2,.8,.2,1); }
    .ann-dialog__hero { position:relative; flex:none; padding:1.3rem 1.5rem 1.15rem; background:linear-gradient(135deg, var(--bg) 0%, #fff 85%); border-bottom:1px solid rgba(0,0,0,.05); }
    .ann-dialog__hero::before { content:''; position:absolute; inset:0 0 auto 0; height:.3rem; background:var(--fg); }
    .ann-dialog__eyebrow { display:flex; align-items:center; gap:.5rem; margin-bottom:.8rem; font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--fg); }
    .ann-dialog__eyebrow .ann-x { margin-left:auto; }
    .ann-dialog__row { display:flex; align-items:flex-start; gap:.95rem; }
    .ann-dialog__row .ann-ico { width:3.1rem; height:3.1rem; border-radius:1rem; background:#fff; box-shadow:0 0 0 1px rgba(0,0,0,.05), 0 6px 14px -6px rgba(0,0,0,.18); }
    .ann-dialog__row .ann-ico svg { width:1.6rem; height:1.6rem; }
    .ann-dialog__title { margin:.4rem 0 0; font-size:1.3rem; line-height:1.28; font-weight:700; letter-spacing:-.01em; color:#0f172a; overflow-wrap:anywhere; }
    .ann-dialog__meta { margin-top:.35rem; font-size:.76rem; color:#6b7280; }
    .ann-x { flex:none; width:2.25rem; height:2.25rem; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; color:#6b7280; background:transparent; border:0; cursor:pointer; transition:background .12s; }
    .ann-x:hover { background:rgba(0,0,0,.06); color:#111827; }
    .ann-dialog__scroll { overflow-y:auto; padding:1.15rem 1.5rem 1.2rem; -webkit-overflow-scrolling:touch; --fg:inherit; }
    .ann-dialog__foot { flex:none; display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; padding:.9rem 1.5rem 1.15rem; border-top:1px solid #f1f5f9; background:#fafbfa; }
    .ann-dots { display:flex; align-items:center; gap:.35rem; margin-right:auto; font-size:.75rem; color:#6b7280; }
    .ann-dot { width:.45rem; height:.45rem; border-radius:999px; background:#d1d5db; transition:width .2s, background .2s; }
    .ann-dot.is-on { background:#1a6b3c; width:1.2rem; }
    .ann-link { font-size:.8rem; color:#15803d; text-decoration:underline; text-underline-offset:2px; background:none; border:0; cursor:pointer; padding:0; margin-right:auto; }
    .ann-dialog__foot .btn { min-height:2.65rem; padding-left:1.4rem; padding-right:1.4rem; }
    @media (max-width:640px) {
        .ann-modal { align-items:flex-end; padding:0; }
        .ann-dialog { max-width:none; border-radius:1.4rem 1.4rem 0 0; max-height:92vh; max-height:92dvh; padding-bottom:env(safe-area-inset-bottom); }
        .ann-dialog__hero { padding:1.1rem 1.15rem 1rem; }
        .ann-dialog__scroll { padding:1rem 1.15rem; }
        .ann-dialog__foot { padding:.8rem 1.15rem 1rem; }
        .ann-dialog__foot .btn { flex:1 1 auto; min-height:2.9rem; }
        .ann-dots { flex:1 0 100%; margin-right:0; }
        .ann-dialog__title { font-size:1.15rem; }
    }

    /* ── Dashboard banner ─────────────────────────────────── */
    .ann-banner { border:1px solid #d9eadf; background:#fff; border-radius:1rem; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden; }
    .ann-banner__head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.6rem 1rem; background:linear-gradient(90deg,#f1f9f4,#fff); border-bottom:1px solid #e3f1e8; font-size:.7rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; color:#14532d; }
    .ann-banner__head a { text-transform:none; letter-spacing:0; font-size:.78rem; font-weight:600; color:#15803d; text-decoration:none; }
    .ann-banner__head a:hover { text-decoration:underline; }
    .ann-banner__item { display:flex; align-items:flex-start; gap:.85rem; padding:.85rem 1rem; text-decoration:none; color:inherit; border-left:3px solid var(--fg,#1a6b3c); transition:background .12s; }
    .ann-banner__item + .ann-banner__item { border-top:1px solid #f1f5f9; }
    .ann-banner__item:hover { background:#fafdfb; }
    .ann-banner__item .ann-ico { width:2.1rem; height:2.1rem; border-radius:.65rem; }
    .ann-banner__item .ann-ico svg { width:1.1rem; height:1.1rem; }
    .ann-banner__title { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; font-size:.9rem; font-weight:600; color:#111827; }
    .ann-banner__text { margin:.15rem 0 0; font-size:.82rem; color:#6b7280; overflow-wrap:anywhere; }
    .ann-banner__date { margin-left:auto; flex:none; font-size:.72rem; color:#9ca3af; white-space:nowrap; }
    @media (max-width:520px) { .ann-banner__date { display:none; } }

    /* ── Hero (feed + admin) ──────────────────────────────── */
    .ann-hero { position:relative; overflow:hidden; border-radius:1rem; background:#123f24; color:#fff; padding:1.35rem 1.4rem; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem 1.5rem; }
    .ann-hero::after { content:''; position:absolute; right:-3.5rem; top:-4rem; width:15rem; height:15rem; border-radius:999px; background:radial-gradient(closest-side, rgba(255,255,255,.1), rgba(255,255,255,0)); pointer-events:none; }
    .ann-hero > * { position:relative; z-index:1; }
    .ann-hero h1 { margin:0; font-size:1.25rem; font-weight:700; letter-spacing:-.01em; }
    .ann-hero p { margin:.25rem 0 0; font-size:.86rem; color:rgba(220,252,231,.82); max-width:34rem; }
    .ann-hero__actions { display:flex; flex-wrap:wrap; gap:.6rem; }
    .ann-hero .btn-light { background:#fff; color:#14532d; font-weight:600; }
    .ann-hero .btn-light:hover { background:#f0fdf4; }
    .ann-hero .btn-ghost { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.25); }
    .ann-hero .btn-ghost:hover { background:rgba(255,255,255,.2); }

    /* ── Filters / tabs ───────────────────────────────────── */
    .ann-filters { display:flex; gap:.5rem; overflow-x:auto; padding:.15rem .1rem .35rem; margin:0 -.1rem; scrollbar-width:none; -webkit-overflow-scrolling:touch; }
    .ann-filters::-webkit-scrollbar { display:none; }
    .ann-filter { flex:none; display:inline-flex; align-items:center; gap:.45rem; min-height:2.3rem; padding:.3rem .85rem; border-radius:999px; border:1px solid #e5e7eb; background:#fff; color:#4b5563; font-size:.8rem; font-weight:500; cursor:pointer; text-decoration:none; white-space:nowrap; transition:border-color .12s, background .12s, color .12s; }
    .ann-filter:hover { border-color:#bbc4cf; color:#111827; }
    .ann-filter b { min-width:1.25rem; padding:0 .35rem; border-radius:999px; background:#f1f5f9; color:#64748b; font-size:.68rem; font-weight:700; text-align:center; line-height:1.15rem; }
    .ann-filter.is-on { background:#14532d; border-color:#14532d; color:#fff; font-weight:600; }
    .ann-filter.is-on b { background:rgba(255,255,255,.2); color:#fff; }

    /* ── Feed ─────────────────────────────────────────────── */
    .ann-feed { display:flex; flex-direction:column; gap:1rem; }
    .ann-card { --fg:#1a6b3c; --bg:#ecf7f0; position:relative; background:#fff; border:1px solid #e5e7eb; border-radius:1.1rem; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden; scroll-margin-top:5rem; transition:box-shadow .15s, border-color .15s; }
    .ann-card::before { content:''; position:absolute; inset:0 auto 0 0; width:.3rem; background:var(--fg); }
    .ann-card:hover { box-shadow:0 8px 22px -12px rgba(0,0,0,.25); }
    .ann-card.is-unread { border-color:#b7dcc4; background:linear-gradient(180deg,#f7fcf9 0,#fff 5rem); }
    .ann-card__inner { display:flex; gap:1rem; padding:1.2rem 1.35rem 1.1rem 1.5rem; }
    .ann-card__main { min-width:0; flex:1; }
    .ann-card__top { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; }
    .ann-card__when { margin-left:auto; font-size:.74rem; color:#9ca3af; white-space:nowrap; }
    .ann-card__title { margin:.55rem 0 0; font-size:1.1rem; line-height:1.35; font-weight:700; letter-spacing:-.005em; color:#0f172a; overflow-wrap:anywhere; }
    .ann-card__text { position:relative; margin-top:.6rem; }
    .ann-card__text.is-clamped { max-height:8.5rem; overflow:hidden; }
    .ann-card__text.is-clamped::after { content:''; position:absolute; inset:auto 0 0 0; height:3.2rem; background:linear-gradient(to bottom, rgba(255,255,255,0), #fff 85%); pointer-events:none; }
    .ann-card.is-unread .ann-card__text.is-clamped::after { background:linear-gradient(to bottom, rgba(255,255,255,0), #fff 85%); }
    .ann-more { margin-top:.4rem; padding:0; border:0; background:none; cursor:pointer; font-size:.82rem; font-weight:600; color:#15803d; }
    .ann-more:hover { text-decoration:underline; }
    .ann-card__foot { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem .9rem; margin-top:.9rem; padding-top:.75rem; border-top:1px solid #f1f5f9; font-size:.76rem; color:#6b7280; }
    .ann-card__foot .who { display:inline-flex; align-items:center; gap:.45rem; }
    @media (max-width:520px) { .ann-card__inner { padding:1rem 1rem 1rem 1.15rem; gap:.75rem; } .ann-card__inner > .ann-ico { display:none; } .ann-card__when { flex:1 0 100%; margin-left:0; } }
    .ann-empty { text-align:center; padding:3.2rem 1.25rem; background:#fff; border:1px dashed #cfd6de; border-radius:1.1rem; color:#6b7280; }
    .ann-empty .ann-ico { margin:0 auto .9rem; width:3.4rem; height:3.4rem; border-radius:1.1rem; }
    .ann-empty .ann-ico svg { width:1.7rem; height:1.7rem; }
    .ann-empty h3 { margin:0; font-size:1rem; font-weight:700; color:#374151; }
    .ann-empty p { margin:.35rem auto 0; max-width:24rem; font-size:.86rem; }

    /* ── Admin: stats + list ──────────────────────────────── */
    .ann-stats { display:grid; gap:.75rem; grid-template-columns:repeat(2,minmax(0,1fr)); }
    @media (min-width:768px) { .ann-stats { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    .ann-stat { display:flex; align-items:center; gap:.8rem; padding:.9rem 1rem; background:#fff; border:1px solid #e5e7eb; border-radius:1rem; text-decoration:none; transition:border-color .12s, box-shadow .12s; }
    a.ann-stat:hover { border-color:#b7dcc4; box-shadow:0 4px 14px -8px rgba(0,0,0,.25); }
    .ann-stat.is-on { border-color:#14532d; box-shadow:0 0 0 2px rgba(20,83,45,.12); }
    .ann-stat__n { font-size:1.45rem; line-height:1; font-weight:700; color:#0f172a; }
    .ann-stat__l { margin-top:.2rem; font-size:.74rem; color:#6b7280; }
    .ann-row { --fg:#1a6b3c; position:relative; display:flex; align-items:flex-start; gap:1rem; padding:1.05rem 1.2rem 1.05rem 1.35rem; background:#fff; border:1px solid #e5e7eb; border-radius:1.1rem; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden; }
    .ann-row::before { content:''; position:absolute; inset:0 auto 0 0; width:.28rem; background:var(--fg); }
    .ann-row.is-muted { background:#fafafa; }
    .ann-row.is-muted::before { background:#d1d5db; }
    .ann-row__main { min-width:0; flex:1; }
    .ann-row__title { display:flex; flex-wrap:wrap; align-items:center; gap:.45rem; font-weight:600; color:#111827; }
    .ann-row__title a { color:inherit; text-decoration:none; overflow-wrap:anywhere; }
    .ann-row__title a:hover { color:#15803d; }
    .ann-row__meta { margin:.3rem 0 0; font-size:.75rem; color:#6b7280; display:flex; flex-wrap:wrap; gap:.2rem .5rem; align-items:center; }
    .ann-row__meta span + span::before { content:'·'; margin-right:.5rem; color:#cbd5e1; }
    .ann-row__text { margin:.45rem 0 0; font-size:.84rem; color:#6b7280; overflow-wrap:anywhere; }
    .ann-row__side { display:flex; flex-direction:column; align-items:flex-end; gap:.7rem; flex:none; min-width:12rem; }
    .ann-row__actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:.4rem; }
    .ann-row__actions form { display:inline; }
    .ann-reach { width:100%; font-size:.72rem; color:#6b7280; text-align:right; }
    .ann-reach__bar { height:.42rem; margin-top:.35rem; border-radius:999px; background:#e5e7eb; overflow:hidden; }
    .ann-reach__bar > span { display:block; height:100%; background:linear-gradient(90deg,#1a6b3c,#4ade80); border-radius:999px; }
    .ann-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.14rem .6rem; border-radius:999px; font-size:.68rem; font-weight:700; letter-spacing:.02em; }
    .ann-pill::before { content:''; width:.4rem; height:.4rem; border-radius:999px; background:currentColor; }
    .ann-pill--live { background:#ecf7f0; color:#15803d; }
    .ann-pill--scheduled { background:#eff6ff; color:#1d4ed8; }
    .ann-pill--draft { background:#f3f4f6; color:#6b7280; }
    .ann-pill--expired { background:#fef2f2; color:#b91c1c; }
    @media (max-width:760px) {
        .ann-row { flex-wrap:wrap; padding:1rem 1rem 1rem 1.2rem; }
        .ann-row > .ann-ico { display:none; }
        .ann-row__side { flex:1 0 100%; flex-direction:column-reverse; align-items:stretch; min-width:0; gap:.6rem; }
        .ann-row__actions { justify-content:flex-start; }
        .ann-reach { text-align:left; }
    }

    /* ── Admin form ───────────────────────────────────────── */
    .ann-form-grid { display:grid; gap:1.5rem; grid-template-columns:minmax(0,1fr); align-items:start; }
    @media (min-width:1024px) { .ann-form-grid { grid-template-columns:minmax(0,1fr) 23rem; } .ann-sticky { position:sticky; top:1.25rem; } }
    .ann-panel { background:#fff; border:1px solid #e5e7eb; border-radius:1.1rem; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden; }
    .ann-section { padding:1.3rem 1.25rem; }
    .ann-section + .ann-section { border-top:1px solid #f1f5f9; }
    @media (min-width:640px) { .ann-section { padding:1.5rem 1.6rem; } }
    .ann-section__head { display:flex; align-items:center; gap:.7rem; margin-bottom:1.1rem; }
    .ann-section__n { flex:none; display:inline-flex; align-items:center; justify-content:center; width:1.7rem; height:1.7rem; border-radius:999px; background:#14532d; color:#fff; font-size:.78rem; font-weight:700; }
    .ann-section__t { font-size:.98rem; font-weight:700; color:#0f172a; }
    .ann-section__s { font-size:.76rem; color:#9ca3af; }
    .ann-field + .ann-field { margin-top:1.15rem; }
    .ann-label { display:block; margin-bottom:.4rem; font-size:.8rem; font-weight:600; color:#374151; }
    .ann-hint { margin-top:.4rem; font-size:.74rem; color:#9ca3af; line-height:1.45; }
    .ann-hint code { background:#f1f5f9; padding:0 .3rem; border-radius:.25rem; color:#475569; font-size:.72rem; }
    .ann-err { margin-top:.4rem; font-size:.78rem; color:#dc2626; }
    .ann-input { display:block; width:100%; box-sizing:border-box; min-height:2.8rem; border:1px solid #d1d5db; border-radius:.7rem; padding:.55rem .85rem; font-size:1rem; background:#fff; color:#111827; transition:border-color .12s, box-shadow .12s; }
    .ann-input:focus { outline:none; border-color:#1a6b3c; box-shadow:0 0 0 3px rgba(26,107,60,.15); }
    textarea.ann-input { min-height:12rem; resize:vertical; line-height:1.6; }
    .ann-two { display:grid; gap:1rem; grid-template-columns:minmax(0,1fr); }
    @media (min-width:640px) { .ann-two { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .ann-cats { display:grid; gap:.55rem; grid-template-columns:repeat(2,minmax(0,1fr)); }
    @media (min-width:640px) { .ann-cats { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    .ann-cat { --fg:#475569; --bg:#f1f5f9; display:flex; align-items:center; gap:.6rem; min-height:3rem; padding:.45rem .75rem; border:1.5px solid #e5e7eb; border-radius:.85rem; background:#fff; cursor:pointer; font-size:.82rem; font-weight:500; color:#374151; transition:border-color .12s, background .12s, box-shadow .12s; }
    .ann-cat:hover { border-color:#cbd5e1; }
    .ann-cat.is-on { border-color:var(--fg); background:var(--bg); color:var(--fg); font-weight:600; box-shadow:0 0 0 3px color-mix(in srgb, var(--fg) 14%, transparent); }
    .ann-cat .ann-ico { width:1.8rem; height:1.8rem; border-radius:.55rem; }
    .ann-cat .ann-ico svg { width:1rem; height:1rem; }
    .ann-cat.is-on .ann-ico { background:#fff; }
    .ann-sr { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
    .ann-cat:focus-within { outline:2px solid #1a6b3c; outline-offset:2px; }
    .ann-check { display:flex; align-items:flex-start; gap:.75rem; padding:.8rem .95rem; border:1px solid #e5e7eb; border-radius:.85rem; cursor:pointer; background:#fff; transition:border-color .12s, background .12s; }
    .ann-check:hover { border-color:#cbd5e1; }
    .ann-check:has(input:checked) { border-color:#9fd2b3; background:#f5fbf7; }
    .ann-check + .ann-check { margin-top:.55rem; }
    .ann-check input { width:1.15rem; height:1.15rem; margin-top:.15rem; accent-color:#1a6b3c; color:#1a6b3c; flex:none; border-radius:.3rem; }
    .ann-check input:focus { outline:none; box-shadow:0 0 0 3px rgba(26,107,60,.2); }
    .ann-check b { display:block; font-size:.86rem; font-weight:600; color:#111827; }
    .ann-check span { display:block; font-size:.76rem; color:#6b7280; margin-top:.15rem; line-height:1.4; }
    .ann-actions { display:flex; flex-wrap:wrap; align-items:center; gap:.6rem; padding:1rem 1.25rem; background:#fafbfa; border-top:1px solid #eef1f4; }
    @media (min-width:640px) { .ann-actions { padding:1.1rem 1.6rem; } }
    .ann-actions .btn { min-height:2.8rem; padding-left:1.35rem; padding-right:1.35rem; }
    .ann-actions .ann-cancel { margin-left:auto; font-size:.85rem; color:#6b7280; text-decoration:none; }
    .ann-actions .ann-cancel:hover { color:#111827; }
    @media (max-width:640px) { .ann-actions { position:sticky; bottom:0; z-index:5; box-shadow:0 -8px 20px -12px rgba(0,0,0,.2); } .ann-actions .btn { flex:1 1 auto; } .ann-actions .ann-cancel { flex:1 0 100%; text-align:center; margin:0; } }
    .ann-preview-label { display:flex; align-items:center; gap:.5rem; margin:0 0 .6rem; font-size:.7rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; color:#6b7280; }
    .ann-preview-label::before { content:''; width:.5rem; height:.5rem; border-radius:999px; background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2); }
    .ann-preview { --fg:#15803d; --bg:#ecf7f0; border-radius:1.2rem; overflow:hidden; background:#fff; box-shadow:0 18px 40px -18px rgba(0,0,0,.35), 0 0 0 1px rgba(0,0,0,.05); }
    .ann-preview__hero { position:relative; padding:1.1rem 1.1rem .95rem; background:linear-gradient(135deg, var(--bg) 0%, #fff 85%); border-bottom:1px solid rgba(0,0,0,.05); }
    .ann-preview__hero::before { content:''; position:absolute; inset:0 0 auto 0; height:.28rem; background:var(--fg); }
    .ann-preview__row { display:flex; gap:.8rem; align-items:flex-start; margin-top:.35rem; }
    .ann-preview__row .ann-ico { width:2.7rem; height:2.7rem; border-radius:.9rem; background:#fff; box-shadow:0 0 0 1px rgba(0,0,0,.05), 0 5px 12px -6px rgba(0,0,0,.18); }
    .ann-preview__title { margin:.35rem 0 0; font-size:1.08rem; line-height:1.3; font-weight:700; color:#0f172a; overflow-wrap:anywhere; }
    .ann-preview__body { padding:1rem 1.1rem 1.1rem; max-height:20rem; overflow:auto; }
    .ann-tips { margin:1rem 0 0; padding:.9rem 1rem; border-radius:.9rem; background:#f8fafc; border:1px solid #eef2f7; font-size:.76rem; color:#64748b; line-height:1.55; }
    .ann-tips b { color:#334155; }
    @media (prefers-reduced-motion:reduce) { .ann-modal, .ann-dialog { animation:none; } .ann-cat, .ann-card, .ann-filter, .ann-dot { transition:none; } }
</style>
