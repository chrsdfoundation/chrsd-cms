import re, io, sys
import xml.etree.ElementTree as ET
import uharfbuzz as hb
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
from fontTools.pens.basePen import BasePen
from fontTools.svgLib.path import parse_path
from reportlab.pdfgen import canvas
from reportlab.lib.colors import CMYKColor, CMYKColorSep

# ------------------------------------------------------------------ geometry
PX = 0.24                      # 1 design px (300 dpi) in pt
TRIM_W, TRIM_H = 252.0, 144.0  # 3.5 x 2 in
BLEED = 9.0                    # 0.125 in
SLUG = 27.0                    # margin each side (bleed + 18 pt for marks/label)
PAGE_W, PAGE_H = TRIM_W + 2*SLUG, TRIM_H + 2*SLUG
TX0, TY0 = SLUG, SLUG          # trim lower-left
TY1 = TY0 + TRIM_H
def X(px): return TX0 + px*PX
def Y(py): return TY1 - py*PX

# ------------------------------------------------------------------ colours (CMYK, 0-1)
def cmyk(c,m,y,k): return CMYKColor(c/100,m/100,y/100,k/100)
FOREST   = cmyk(88,52,86,68)
FOREST_L = cmyk(88,45,86,52)     # tone-on-tone watermark
PEARL    = cmyk(4,2,6,0)
PAPER    = cmyk(0,0,0,0)         # knock-out white
BLACK    = cmyk(0,0,0,100)
SLATE    = cmyk(0,0,0,70)
ICON     = cmyk(0,0,0,65)
FOIL     = CMYKColorSep(.10,.06,.06,.30, spotName='Silver_Foil', density=1)  # alt colour only for on-screen preview
REG      = CMYKColorSep(1,1,1,1, spotName='All', density=1)                  # registration (crop marks)
STOPS = {  # logo gradient stops -> CMYK estimates (proof before approving)
 '#0d4d26':(93,45,100,50),'#105a2e':(92,42,100,42),'#156c3a':(90,36,100,32),
 '#258b50':(84,20,95,12),'#229356':(84,14,92,8),'#30a060':(78,10,88,4),'#35a866':(75,5,85,0)}

# ------------------------------------------------------------------ fonts -> outlines
def static_font(path, wght):
    f = instancer.instantiateVariableFont(TTFont(path), {'wght': wght}, inplace=False)
    buf = io.BytesIO(); f.save(buf); data = buf.getvalue()
    return TTFont(io.BytesIO(data)), data
class Face:
    def __init__(self, path, wght):
        self.tt, self.data = static_font(path, wght)
        self.upem = self.tt['head'].unitsPerEm
        self.gs = self.tt.getGlyphSet(); self.order = self.tt.getGlyphOrder()
        self.hb = hb.Font(hb.Face(self.data))
        os2 = self.tt['OS/2']
        if os2.fsSelection & (1<<7): self.asc, self.desc = os2.sTypoAscender, -os2.sTypoDescender
        else: self.asc, self.desc = self.tt['hhea'].ascent, -self.tt['hhea'].descent
    def shape(self, text, size, tracking=0.0):
        buf = hb.Buffer(); buf.add_str(text); buf.guess_segment_properties()
        hb.shape(self.hb, buf, {"kern": True, "liga": True})
        s = size/self.upem; out=[]; x=0.0
        for info,pos in zip(buf.glyph_infos, buf.glyph_positions):
            out.append((self.order[info.codepoint], x + pos.x_offset*s, pos.y_offset*s))
            x += pos.x_advance*s + tracking*size
        return out, x - tracking*size
    def width(self, text, size, tracking=0.0): return self.shape(text,size,tracking)[1]
    def baseline(self, line_top, line_h, size):
        s = size/self.upem
        return line_top + (line_h - (self.asc+self.desc)*s)/2 + self.asc*s

class RLPen(BasePen):
    def __init__(self, gs, path, f): super().__init__(gs); self.p=path; self.f=f
    def _moveTo(self,pt): self.p.moveTo(*self.f(pt))
    def _lineTo(self,pt): self.p.lineTo(*self.f(pt))
    def _curveToOne(self,a,b,c): self.p.curveTo(*self.f(a),*self.f(b),*self.f(c))
    def _closePath(self): self.p.close()
    def _endPath(self): pass

MONT400 = Face('fonts/Montserrat-VF.ttf', 400)
MONT600 = Face('fonts/Montserrat-VF.ttf', 600)
CORM600 = Face('fonts/Cormorant-VF.ttf', 600)

# ------------------------------------------------------------------ logo data
svg_text = open('/mnt/user-data/uploads/wAsset_1.svg').read()
NS = {'s':'http://www.w3.org/2000/svg','x':'http://www.w3.org/1999/xlink'}
root = ET.fromstring(svg_text.encode())
cls_fill = dict(re.findall(r'\.(cls-\d+)\s*\{\s*fill:\s*url\(#([\w-]+)\);', svg_text))
grads = {}
for g in root.iter('{%s}linearGradient'%NS['s']):
    gid = g.get('id'); href = g.get('{%s}href'%NS['x'])
    stops = [(float(s.get('offset')), s.get('stop-color').lower()) for s in g.findall('s:stop',NS)]
    grads[gid] = dict(x1=float(g.get('x1')),y1=float(g.get('y1')),x2=float(g.get('x2')),y2=float(g.get('y2')),stops=stops,href=href)
for gid,g in grads.items():
    if not g['stops'] and g['href']: g['stops'] = grads[g['href'].lstrip('#')]['stops']
logo_paths = [(p.get('d'), p.get('class')) for p in root.iter('{%s}path'%NS['s'])]
assert len(logo_paths)==31 and all(g['stops'] for g in grads.values())

def logo_mapper(left_px, top_px, width_px):
    s = width_px*PX/320.0
    return lambda pt: (X(left_px)+pt[0]*s, Y(top_px)-pt[1]*s), s
def build_path(c, d, f, gs=None):
    p = c.beginPath(); parse_path(d, RLPen(gs, p, f)); return p
FILL_NZ = 1

def draw_logo_flat(c, left, top, width, colour, overprint=False, clip=None):
    f,_ = logo_mapper(left, top, width)
    c.saveState()
    if clip: c.clipPath(clip, stroke=0, fill=0)
    c.setFillColor(colour); c.setFillOverprint(overprint)
    for d,_cls in logo_paths: c.drawPath(build_path(c,d,f), stroke=0, fill=1, fillMode=FILL_NZ)
    c.restoreState()

def draw_logo_color(c, left, top, width):
    f,_ = logo_mapper(left, top, width)
    for d,cls in logo_paths:
        g = grads[cls_fill[cls]]
        c.saveState()
        c.clipPath(build_path(c,d,f), stroke=0, fill=0, fillMode=FILL_NZ)
        x1,y1 = f((g['x1'],g['y1'])); x2,y2 = f((g['x2'],g['y2']))
        cols = [cmyk(*STOPS[col]) for _,col in g['stops']]; pos = [o for o,_ in g['stops']]
        c.linearGradient(x1,y1,x2,y2, cols, pos, extend=True)
        c.restoreState()

# ------------------------------------------------------------------ text, icons, QR
def draw_text(c, face, text, size_px, x_px, baseline_px, colour, tracking=0.0, overprint=False):
    glyphs,_ = face.shape(text, size_px, tracking); s = size_px/face.upem
    c.setFillColor(colour); c.setFillOverprint(overprint)
    for name, gx, gy in glyphs:
        p = c.beginPath()
        f = lambda pt, gx=gx, gy=gy: (X(x_px + gx + pt[0]*s), Y(baseline_px - gy - pt[1]*s))
        face.gs[name].draw(RLPen(face.gs, p, f))
        c.drawPath(p, stroke=0, fill=1, fillMode=FILL_NZ)

def rect_px(c, x0, y0, x1, y1, colour, overprint=False):
    c.setFillColor(colour); c.setFillOverprint(overprint)
    c.rect(X(x0), Y(y1), (x1-x0)*PX, (y1-y0)*PX, stroke=0, fill=1)

ICONS = {
 'tel': ['M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z'],
 'cell':['M8.5 2h7A2.5 2.5 0 0 1 18 4.5v15a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 6 19.5v-15A2.5 2.5 0 0 1 8.5 2z','M11 18.5h2'],
 'wa':  ['M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z'],
 'mail':['M4 4.5h16a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2z','m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'],
 'web': ['M12 2a10 10 0 1 0 0 20 10 10 0 1 0 0-20z','M2 12h20','M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z'],
 'in':  ['M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z','M2 9h4v12H2z','M4 2a2 2 0 1 0 0 4 2 2 0 1 0 0-4z'],
 'pin': ['M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z','M12 7a3 3 0 1 0 0 6 3 3 0 1 0 0-6z'],
}
def draw_icon(c, name, left_px, top_px, colour, size_px=30):
    s = size_px/24.0
    f = lambda pt: (X(left_px+pt[0]*s), Y(top_px+pt[1]*s))
    c.setStrokeColor(colour); c.setLineWidth(1.6*s*PX); c.setLineCap(1); c.setLineJoin(1)
    for d in ICONS[name]: c.drawPath(build_path(c,d,f), stroke=1, fill=0)

# QR supplied by the client (download.svg): 81 x 81 modules, 4-unit squares, no quiet zone in the file
_qr_svg = open('/mnt/user-data/uploads/download.svg').read()
_cells = {(int(x)//4, int(y)//4) for x,y in re.findall(r'<rect x="(\d+)" y="(\d+)" width="4" height="4"', _qr_svg)}
QR_N = 81
QR_M = [[(x,y) in _cells for x in range(QR_N)] for y in range(QR_N)]
assert len(_cells) == 3367 and max(x for x,_ in _cells) < QR_N and max(y for _,y in _cells) < QR_N

def draw_qr(c, left_px, top_px, size_px):
    m = size_px/QR_N; c.setFillColor(BLACK); c.setFillOverprint(True)
    p = c.beginPath()
    for y,row in enumerate(QR_M):
        x=0
        while x<QR_N:
            if row[x]:
                s=x
                while x<QR_N and row[x]: x+=1
                p.rect(X(left_px+s*m), Y(top_px+(y+1)*m), (x-s)*m*PX, m*PX)
            else: x+=1
    c.drawPath(p, stroke=0, fill=1, fillMode=FILL_NZ)

# ------------------------------------------------------------------ marks + slug
def crop_marks(c):
    c.setStrokeColor(REG); c.setLineWidth(0.25); c.setStrokeOverprint(False)
    off, ln = BLEED, 13.5
    for (x,y,dx,dy) in [(TX0,TY0,-1,-1),(TX0+TRIM_W,TY0,1,-1),(TX0,TY1,-1,1),(TX0+TRIM_W,TY1,1,1)]:
        c.line(x+dx*off, y, x+dx*(off+ln), y)       # horizontal mark
        c.line(x, y+dy*off, x, y+dy*(off+ln))       # vertical mark
def slug_label(c, text):
    # outlined 4.5 pt label, bottom slug, in registration-safe K100
    size_px = 4.5/PX
    draw_text(c, MONT400, text, size_px, 0, TRIM_H/PX + 17/PX*1.0 + 0, BLACK, overprint=True)

def bleed_clip(c):
    p = c.beginPath(); p.rect(TX0-BLEED, TY0-BLEED, TRIM_W+2*BLEED, TRIM_H+2*BLEED); return p

# ------------------------------------------------------------------ FRONT
def front(c, mode):
    art = (mode=='art')
    if art:
        c.setFillColor(FOREST); c.setFillOverprint(False)
        c.rect(TX0-BLEED, TY0-BLEED, TRIM_W+2*BLEED, TRIM_H+2*BLEED, stroke=0, fill=1)
        draw_logo_flat(c, 500, 60, 800, FOREST_L, clip=bleed_clip(c))          # tone-on-tone watermark
    emblem_col, ov = (FOIL, True) if art else (BLACK, False)
    draw_logo_flat(c, 60, 58, 236, emblem_col, overprint=ov)                    # silver foil emblem
    rect_px(c, 60, 337-1.5625/PX*PX, 990, 337+1.5625/PX*PX, emblem_col, ov) if False else None
    t = 0.75/PX                                                                 # 0.75 pt foil hairline
    rect_px(c, 60, 337-t/2, 990, 337+t/2, emblem_col, ov)
    if art:
        lines = ["Centre for Humanitarian Research","and Social Development","Foundation (CHRSD)"]
        for i,l in enumerate(lines):
            draw_text(c, CORM600, l, 44, 340, CORM600.baseline(88+i*44*1.14, 44*1.14, 44), PAPER)
        draw_text(c, MONT600, "Mohammed Abu Ramim", 52, 60, MONT600.baseline(384,52,52), PAPER, tracking=0.005)
        draw_text(c, MONT600, "Executive Director", 28, 60, MONT600.baseline(462,28,28), PAPER, tracking=0.14)

# ------------------------------------------------------------------ BACK
ROWS = [('tel','+880 2 47122566',56),('cell','+880 1602-778984',98),('wa','+880 1334-383341',140),
        ('mail','info@chrsd.org',198),('mail','ed@chrsd.org',240),('web','chrsd.org',298),('in','linkedin.com/in/maramim',340)]
def back(c, mode):
    art = (mode=='art')
    if art:
        c.setFillColor(PEARL); c.setFillOverprint(False)
        c.rect(TX0-BLEED, TY0-BLEED, TRIM_W+2*BLEED, TRIM_H+2*BLEED, stroke=0, fill=1)
        for icon,text,top in ROWS:
            draw_icon(c, icon, 60, top+6, ICON)
            draw_text(c, MONT400, text, 28, 108, MONT400.baseline(top+7,28,28), BLACK, overprint=True)
        draw_icon(c, 'pin', 60, 443, ICON)
        for i,l in enumerate(["29 Toyenbee Circular Road","(5th Floor), Motijheel C/A","Dhaka-1000, Bangladesh"]):
            draw_text(c, MONT400, l, 25, 108, MONT400.baseline(440+i*25*1.38, 25*1.38, 25), SLATE, overprint=True)
        w = MONT400.width("Scan to save contact", 25)
        draw_text(c, MONT400, "Scan to save contact", 25, 698+(292-w)/2, MONT400.baseline(482,25,25), SLATE, overprint=True)
        draw_logo_color(c, 798, 52, 92)
        # QR panel: paper-white knock-out, then K100 modules
        rect_px(c, 698, 168, 990, 460, PAPER)
        draw_qr(c, 718, 188, 252)
    col, ov = (FOIL, True) if art else (BLACK, False)
    t = 0.75/PX
    rect_px(c, 60, 421-t/2, 640, 421+t/2, col, ov)                              # address hairline
    rect_px(c, 673-t/2, 56, 673+t/2, 538, col, ov)                              # vertical divider
    for (x0,y0,x1,y1) in [(698,168,990,168+t),(698,460-t,990,460),(698,168,698+t,460),(990-t,168,990,460)]:
        rect_px(c, x0,y0,x1,y1, col, ov)                                        # QR frame (foil)

# ------------------------------------------------------------------ document
def make(path, mode):
    c = canvas.Canvas(path, pagesize=(PAGE_W,PAGE_H), pageCompression=1)
    c.setTitle("CHRSD executive card - Mohammed Abu Ramim (%s)" % ("print artwork" if mode=='art' else "silver foil plate"))
    c.setAuthor("CHRSD"); c.setSubject("Business card 3.5 x 2 in, 0.125 in bleed, crop marks")
    for name, fn in (("FRONT",front),("BACK",back)):
        fn(c, mode); crop_marks(c)
        label = ("%s | trim 3.5x2 in | bleed 0.125 in | CMYK + spot Silver_Foil" % name) if mode=='art' else ("%s | SILVER FOIL PLATE | black = foil | trim 3.5x2 in" % name)
        draw_text(c, MONT400, label, 4.5/PX, 25, (PAGE_H-8)/PX*0 + (TRIM_H/PX + (SLUG-9)/PX + 1) , BLACK, overprint=False)
        c.setTrimBox((TX0,TY0,TX0+TRIM_W,TY0+TRIM_H)); c.setArtBox((TX0,TY0,TX0+TRIM_W,TY0+TRIM_H))
        c.setBleedBox((TX0-BLEED,TY0-BLEED,TX0+TRIM_W+BLEED,TY0+TRIM_H+BLEED))
        c.showPage()
    c.save()

make('/mnt/user-data/outputs/CHRSD_Card_Print_Artwork_Silver.pdf','art')
make('/mnt/user-data/outputs/CHRSD_Card_Silver_Foil_Plate.pdf','foil')
print("done")

# ------------------------------------------------------------------ post-process: strip reportlab's unused Helvetica
from pypdf import PdfReader, PdfWriter
from pypdf.generic import DecodedStreamObject, NameObject
def clean(path):
    r = PdfReader(path); w = PdfWriter(clone_from=r)
    for pg in w.pages:
        data = pg.get_contents().get_data()
        assert data.startswith(b'1 0 0 1 0 0 cm  BT /F1 12 Tf 14.4 TL ET\n')
        data = data.replace(b'BT /F1 12 Tf 14.4 TL ET\n', b'', 1)
        assert b'/F1' not in data and b' Tf' not in data
        s = DecodedStreamObject(); s.set_data(data); pg[NameObject('/Contents')] = w._add_object(s)
        del pg['/Resources']['/Font']
        pg.compress_content_streams()
    w.add_metadata({k:v for k,v in (r.metadata or {}).items()})
    with open(path,'wb') as fh: w.write(fh)
for p in ('/mnt/user-data/outputs/CHRSD_Card_Print_Artwork_Silver.pdf','/mnt/user-data/outputs/CHRSD_Card_Silver_Foil_Plate.pdf'): clean(p)
print("cleaned")
