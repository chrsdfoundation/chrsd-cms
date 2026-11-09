import re

# ---------- logo ----------
svg = open('/mnt/user-data/uploads/wAsset_1.svg').read()
tags = re.findall(r'<path\b[^>]*?/>', svg, flags=re.S)
mono_paths = "".join(re.sub(r'\sclass="[^"]*"', '', t) for t in tags)
full_logo = re.sub(r'<\?xml[^>]*\?>\s*', '', svg)
full_logo = full_logo.replace('<svg id="Layer_2" data-name="Layer 2"', '<svg role="img" aria-label="CHRSD emblem" class="block w-full h-auto"', 1)

# ---------- QR (client-supplied download.svg: 81 x 81 modules) ----------
_q = open('/mnt/user-data/uploads/download.svg').read()
_cells = {(int(x)//4, int(y)//4) for x,y in re.findall(r'<rect x="(\d+)" y="(\d+)" width="4" height="4"', _q)}
n = 81; assert len(_cells) == 3367
d = []
for y in range(n):
    x = 0
    while x < n:
        if (x,y) in _cells:
            s0 = x
            while x < n and (x,y) in _cells: x += 1
            d.append(f"M{s0} {y}h{x-s0}v1h-{x-s0}z")
        else: x += 1
qr_path = "".join(d)

# ---------- icons (24x24 line icons) ----------
def ico(inner): return f'<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{inner}</svg>'
I = {
 'tel':  ico('<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>'),
 'cell': ico('<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18.5h2"/>'),
 'wa':   ico('<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>'),
 'mail': ico('<rect x="2" y="4.5" width="20" height="15" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'),
 'web':  ico('<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'),
 'in':   ico('<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'),
 'pin':  ico('<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>'),
}
def row(icon, text, label, extra=""):
    return f'<li class="flex items-center gap-[18px] h-[42px] {extra}"><span class="text-gold-deep shrink-0" role="img" aria-label="{label}">{I[icon]}</span><span>{text}</span></li>'

html = f'''<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>CHRSD executive business card</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
tailwind.config = {{
  theme: {{ extend: {{
    fontFamily: {{
      serif: ['"Cormorant Garamond"', 'Georgia', '"Times New Roman"', 'serif'],
      sans: ['Montserrat', 'system-ui', '-apple-system', '"Segoe UI"', 'Arial', 'sans-serif'],
    }},
    colors: {{
      forest: {{ 900: '#082A1B', 800: '#0E3A27', 700: '#0D4D26' }},
      emerald: {{ 500: '#35A866' }},
      gold: {{ DEFAULT: '#C8A04A', soft: '#D8BE7C', deep: '#9C7B2C' }},
      pearl: '#F3F5F0',
      ivory: '#F4F2EA',
      ink: '#0F2A1E',
      slate2: '#56635B',
    }},
  }} }}
}}
</script>
<style>
  :root {{
    --bg:#F1F2EE; --fg:#14231B; --muted:#5A675F; --line:#D5D9D2; --stage:#E3E6E0; --btn:#FFFFFF; --btn-on:#0D4D26; --btn-on-fg:#FFFFFF;
    box-sizing:border-box; padding-top:env(safe-area-inset-top,0px); padding-bottom:env(safe-area-inset-bottom,0px);
    --foil: linear-gradient(var(--ang,110deg), #EBD79C 0%, #C8A04A 28%, #8F6F28 52%, #D9BC6B 76%, #F2E4B2 100%);
  }}
  @media (prefers-color-scheme: dark) {{
    :root:not([data-theme="light"]) {{ --bg:#0C1511; --fg:#E9EEE9; --muted:#9AA79F; --line:#25342C; --stage:#16221C; --btn:#16221C; --btn-on:#35A866; --btn-on-fg:#06150D; color-scheme:dark; }}
  }}
  :root[data-theme="dark"] {{ --bg:#0C1511; --fg:#E9EEE9; --muted:#9AA79F; --line:#25342C; --stage:#16221C; --btn:#16221C; --btn-on:#35A866; --btn-on-fg:#06150D; color-scheme:dark; }}
  html {{ scroll-padding-top:env(safe-area-inset-top,0px); }}
  body {{ background:var(--bg); color:var(--fg); font-family:Montserrat,system-ui,sans-serif; }}
  .flat {{ --foil:#C8A04A; }}
  .flat #foilGrad stop {{ stop-color:#C8A04A; }}
  .foil-bg {{ background:var(--foil); }}
  .toggle {{ background:var(--btn); color:var(--fg); border:1px solid var(--line); border-radius:999px; padding:.5rem 1rem; font-size:.875rem; font-weight:500; cursor:pointer; }}
  .toggle[aria-pressed="true"] {{ background:var(--btn-on); color:var(--btn-on-fg); border-color:var(--btn-on); }}
  .toggle:focus-visible {{ outline:3px solid #C8A04A; outline-offset:2px; }}
  .stage {{ background:var(--stage); border-radius:14px; padding:clamp(10px,2.4vw,28px); overflow:hidden; }}
  .slot {{ position:relative; margin-inline:auto; }}
  .canvas {{ position:absolute; left:0; top:0; width:1125px; height:675px; transform-origin:0 0; transform:scale(var(--k,.5)); }}
  .trim {{ position:absolute; left:37.5px; top:37.5px; width:1050px; height:600px; overflow:hidden; box-shadow:0 40px 70px -28px rgba(0,0,0,.45), 0 2px 6px rgba(0,0,0,.18); }}
  .bleed {{ position:absolute; inset:-37.5px; }}
  .g {{ display:none; position:absolute; pointer-events:none; }}
  .guides .trim {{ overflow:visible; box-shadow:none; }}
  .guides .g {{ display:block; }}
  .g-bleed {{ inset:-37.5px; border:2px solid #E5484D; }}
  .g-trim  {{ inset:0; border:2px dashed #E040FB; }}
  .g-safe  {{ inset:45px; border:2px dashed #00B8D9; }}
  .legend {{ display:none; }}
  .guides .legend {{ display:flex; }}
  @media (prefers-reduced-motion: reduce) {{ * {{ scroll-behavior:auto !important; }} }}
</style>
</head>
<body class="antialiased">

<!-- shared SVG defs: monochrome emblem + foil gradient -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <defs>
    <g id="chrsd-mono">{mono_paths}</g>
    <linearGradient id="foilGrad" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="320" y2="290">
      <stop offset="0" stop-color="#EBD79C"/><stop offset=".28" stop-color="#C8A04A"/><stop offset=".52" stop-color="#8F6F28"/><stop offset=".76" stop-color="#D9BC6B"/><stop offset="1" stop-color="#F2E4B2"/>
    </linearGradient>
  </defs>
</svg>

<main class="mx-auto max-w-[1280px] px-5 sm:px-8 py-8 sm:py-12">
  <header class="mb-8 flex flex-wrap items-end justify-between gap-5">
    <div>
      <h1 class="font-serif text-[34px] sm:text-[44px] leading-[1.05] font-semibold">Executive business card</h1>
      <p class="mt-2 text-[15px]" style="color:var(--muted)">Mohammed Abu Ramim, Executive Director. Trim 3.5 × 2 in, with 0.125 in bleed on every side.</p>
    </div>
    <div class="flex flex-wrap gap-2" role="group" aria-label="Preview options">
      <button id="btnGuides" class="toggle" type="button" aria-pressed="false">Show print guides</button>
      <button id="btnFlat" class="toggle" type="button" aria-pressed="false">Flat gold ink instead of foil</button>
    </div>
  </header>

  <div class="legend mb-4 flex-wrap gap-x-6 gap-y-2 text-[13px]" style="color:var(--muted)" aria-live="polite">
    <span class="inline-flex items-center gap-2"><i class="inline-block w-5 h-0 border-t-2 border-solid" style="border-color:#E5484D"></i>Bleed edge, artwork runs to here</span>
    <span class="inline-flex items-center gap-2"><i class="inline-block w-5 h-0 border-t-2 border-dashed" style="border-color:#E040FB"></i>Trim, where the card is cut</span>
    <span class="inline-flex items-center gap-2"><i class="inline-block w-5 h-0 border-t-2 border-dashed" style="border-color:#00B8D9"></i>Safe area, keep text inside</span>
  </div>

  <div class="grid gap-8 lg:grid-cols-2" id="cards">

    <!-- ================= FRONT ================= -->
    <figure>
      <div class="stage"><div class="slot" data-slot>
        <div class="canvas" id="front">
          <div class="trim bg-forest-900">
            <div class="bleed bg-forest-900" style="background-image:radial-gradient(900px 600px at 85% 15%, rgba(53,168,102,.10), transparent 70%)"></div>

            <!-- tone-on-tone watermark: spot UV on matte -->
            <div class="absolute overflow-hidden pointer-events-none" style="inset:-37.5px" aria-hidden="true"><svg viewBox="0 0 320 290.25" class="absolute" style="left:537.5px;top:97.5px;width:800px;height:auto"><use href="#chrsd-mono" fill="#10432D"/></svg></div>

            <!-- foil emblem -->
            <svg viewBox="0 0 320 290.25" class="absolute" style="left:60px;top:58px;width:236px;height:auto" role="img" aria-label="CHRSD emblem"><use href="#chrsd-mono" fill="url(#foilGrad)"/></svg>

            <!-- organisation name -->
            <p class="absolute font-serif font-semibold text-ivory text-[44px] leading-[1.14]" style="left:340px;top:88px;width:640px">Centre for Humanitarian Research<br>and Social Development<br>Foundation (CHRSD)</p>

            <!-- hairline -->
            <div class="absolute foil-bg h-[2px]" style="left:60px;right:60px;top:336px"></div>

            <!-- person -->
            <p class="absolute font-sans font-semibold text-ivory text-[52px] leading-none tracking-[0.005em]" style="left:60px;top:384px">Mohammed Abu Ramim</p>
            <p class="absolute font-sans font-medium text-[28px] leading-none tracking-[0.14em]" style="left:60px;top:462px;color:#D8BE7C">Executive Director</p>

            <div class="g g-safe"></div><div class="g g-trim"></div><div class="g g-bleed"></div>
          </div>
        </div>
      </div></div>
      <figcaption class="mt-3 text-[14px]" style="color:var(--muted)">Front. Gold foil emblem and hairline on deep forest green, with a tone-on-tone spot UV watermark.</figcaption>
    </figure>

    <!-- ================= BACK ================= -->
    <figure>
      <div class="stage"><div class="slot" data-slot>
        <div class="canvas">
          <div class="trim bg-pearl text-ink">
            <div class="bleed bg-pearl"></div>

            <!-- contacts -->
            <ul class="absolute font-sans text-[28px] leading-none text-ink" style="left:60px;top:56px;width:590px">
              {row('tel','+880 2 47122566','Office phone')}
              {row('cell','+880 1602-778984','Mobile')}
              {row('wa','+880 1334-383341','WhatsApp')}
              {row('mail','info@chrsd.org','General email','mt-[16px]')}
              {row('mail','ed@chrsd.org','Executive Director email')}
              {row('web','chrsd.org','Website','mt-[16px]')}
              {row('in','linkedin.com/in/maramim','LinkedIn')}
            </ul>

            <!-- address -->
            <div class="absolute" style="left:60px;top:420px;width:580px">
              <div class="h-[2px] foil-bg mb-[18px]"></div>
              <div class="flex items-start gap-[18px]">
                <span class="text-gold-deep shrink-0 pt-[3px]" role="img" aria-label="Address">{I['pin']}</span>
                <p class="font-sans text-[25px] leading-[1.38] text-slate2">29 Toyenbee Circular Road<br>(5th Floor), Motijheel C/A<br>Dhaka-1000, Bangladesh</p>
              </div>
            </div>

            <!-- vertical divider -->
            <div class="absolute foil-bg w-[2px]" style="left:672px;top:56px;height:482px"></div>

            <!-- full-colour emblem + QR -->
            <div class="absolute" style="left:698px;top:52px;width:292px">
              <div class="mx-auto" style="width:92px">{full_logo}</div>
            </div>
            <div class="absolute bg-white" style="left:698px;top:168px;width:292px;height:292px;border:2px solid #C8A04A;padding:18px">
              <svg viewBox="0 0 {n} {n}" width="252" height="252" shape-rendering="crispEdges" role="img" aria-label="QR code that saves Mohammed Abu Ramim as a contact"><path d="{qr_path}" fill="#082A1B"/></svg>
            </div>
            <p class="absolute font-sans text-[25px] leading-none text-slate2 text-center" style="left:698px;top:482px;width:292px">Scan to save contact</p>

            <div class="g g-safe"></div><div class="g g-trim"></div><div class="g g-bleed"></div>
          </div>
        </div>
      </div></div>
      <figcaption class="mt-3 text-[14px]" style="color:var(--muted)">Back. Contact channels grouped as phones, emails and web, with the contact QR in a gold frame.</figcaption>
    </figure>
  </div>

  <p class="mt-8 max-w-[70ch] text-[14px] leading-relaxed" style="color:var(--muted)">The foil is a screen simulation. Move the pointer across the front to see the sheen shift. Final colour and foil depth depend on the printer’s stock and press, so approve a physical proof before the full run.</p>
</main>

<script>
(function(){{
  var root=document.documentElement, slots=[].slice.call(document.querySelectorAll('[data-slot]'));
  function fit(){{ slots.forEach(function(s){{
    var cs=getComputedStyle(s.parentElement), w=s.parentElement.clientWidth-parseFloat(cs.paddingLeft)-parseFloat(cs.paddingRight), k=Math.min(1,w/1125);
    s.style.width=(1125*k)+'px'; s.style.height=(675*k)+'px'; s.firstElementChild.style.setProperty('--k',k);
  }}); }}
  if(window.ResizeObserver) new ResizeObserver(fit).observe(document.getElementById('cards')); window.addEventListener('resize',fit); fit();

  var bg=document.getElementById('btnGuides'), bf=document.getElementById('btnFlat');
  var stages=document.querySelectorAll('.stage');
  bg.addEventListener('click',function(){{ var on=bg.getAttribute('aria-pressed')!=='true'; bg.setAttribute('aria-pressed',on); document.body.classList.toggle('guides',on); }});
  bf.addEventListener('click',function(){{ var on=bf.getAttribute('aria-pressed')!=='true'; bf.setAttribute('aria-pressed',on); root.classList.toggle('flat',on); }});

  var front=document.getElementById('front'), grad=document.getElementById('foilGrad');
  var reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function sheen(a){{ root.style.setProperty('--ang',a+'deg'); grad.setAttribute('gradientTransform','rotate('+(a-110)+' 160 145)'); }}
  front.addEventListener('pointermove',function(e){{ if(reduce||root.classList.contains('flat')) return; var r=front.getBoundingClientRect(); sheen(Math.round(110+((e.clientX-r.left)/r.width)*80)); }});
  front.addEventListener('pointerleave',function(){{ sheen(110); }});
}})();
</script>
</body>
</html>'''
open('/mnt/user-data/outputs/chrsd-executive-card.html','w').write(html)
print("bytes", len(html))
