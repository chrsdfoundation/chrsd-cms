#!/usr/bin/env python3
"""Dev-only: turn the logo SVG and the icon paths into plain cubic path data.

Runs on a developer machine (needs fonttools), never on the server. The output,
resources/business-card/geometry.json, is read by the PHP layout engine, so the
runtime needs no SVG parser.

Usage:
    python export_card_geometry.py <logo.svg> <out.json>
"""
import json
import re
import sys
import xml.etree.ElementTree as ET

from fontTools.pens.recordingPen import RecordingPen
from fontTools.svgLib.path import parse_path

SVG_NS = "http://www.w3.org/2000/svg"
XLINK_NS = "http://www.w3.org/1999/xlink"

# Icon paths from reference/reference_build_pdf_reportlab.py (24-unit Feather-style icons).
ICONS = {
    "tel": ["M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"],
    "cell": ["M8.5 2h7A2.5 2.5 0 0 1 18 4.5v15a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 6 19.5v-15A2.5 2.5 0 0 1 8.5 2z", "M11 18.5h2"],
    "wa": ["M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"],
    "mail": ["M4 4.5h16a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2z", "m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"],
    "web": ["M12 2a10 10 0 1 0 0 20 10 10 0 1 0 0-20z", "M2 12h20", "M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"],
    "in": ["M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z", "M2 9h4v12H2z", "M4 2a2 2 0 1 0 0 4 2 2 0 1 0 0-4z"],
    "pin": ["M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z", "M12 7a3 3 0 1 0 0 6 3 3 0 1 0 0-6z"],
}


def pen_commands(d):
    """SVG path data -> list of ['M'|'L'|'C'|'Z', coords...], cubic only."""
    pen = RecordingPen()
    parse_path(d, pen)
    out = []
    for op, args in pen.value:
        if op == "moveTo":
            out.append(["M", *args[0]])
        elif op == "lineTo":
            out.append(["L", *args[0]])
        elif op == "curveTo":
            out.append(["C", *args[0], *args[1], *args[2]])
        elif op == "qCurveTo":
            # Quadratic segments: promote to cubic. The logo uses none, but keep the converter total.
            (cx, cy), (x, y) = args[0], args[1]
            px, py = out[-1][-2], out[-1][-1]
            out.append(["C", px + 2 / 3 * (cx - px), py + 2 / 3 * (cy - py), x + 2 / 3 * (cx - x), y + 2 / 3 * (cy - y), x, y])
        elif op in ("closePath", "endPath"):
            out.append(["Z"])
    return out


def parse_logo(path):
    text = open(path, encoding="utf-8").read()
    root = ET.fromstring(text.encode("utf-8"))

    # CSS classes map to gradient ids, e.g. .cls-1 { fill: url(#linear-gradient-2); }
    cls_fill = dict(re.findall(r"\.(cls-\d+)\s*\{\s*fill:\s*url\(#([\w-]+)\);", text))

    gradients = {}
    for g in root.iter(f"{{{SVG_NS}}}linearGradient"):
        gid = g.get("id")
        href = g.get(f"{{{XLINK_NS}}}href")
        stops = [
            (float(s.get("offset")), s.get("stop-color").lower())
            for s in g.findall(f"{{{SVG_NS}}}stop")
        ]
        gradients[gid] = {
            "x1": float(g.get("x1")), "y1": float(g.get("y1")),
            "x2": float(g.get("x2")), "y2": float(g.get("y2")),
            "stops": stops, "href": href,
        }
    for g in gradients.values():
        if not g["stops"] and g["href"]:
            g["stops"] = gradients[g["href"].lstrip("#")]["stops"]

    paths = []
    for p in root.iter(f"{{{SVG_NS}}}path"):
        cls = p.get("class")
        paths.append({
            "d": pen_commands(p.get("d")),
            "gradient": cls_fill.get(cls) if cls else None,
        })

    viewbox = [float(v) for v in root.get("viewBox").split()]
    return {
        "viewBox": viewbox,
        "paths": paths,
        "gradients": {
            gid: {"x1": g["x1"], "y1": g["y1"], "x2": g["x2"], "y2": g["y2"], "stops": g["stops"]}
            for gid, g in gradients.items()
        },
    }


def main():
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    logo_svg, out_path = sys.argv[1], sys.argv[2]

    logo = parse_logo(logo_svg)
    assert len(logo["paths"]) == 31, f"expected 31 logo paths, got {len(logo['paths'])}"
    assert all(g["stops"] for g in logo["gradients"].values()), "gradient without stops"

    icons = {name: [pen_commands(d) for d in ds] for name, ds in ICONS.items()}

    data = {"logo": logo, "icons": icons}
    with open(out_path, "w", encoding="utf-8") as fh:
        json.dump(data, fh, separators=(",", ":"))
    print(f"wrote {out_path}: {len(logo['paths'])} logo paths, {len(icons)} icons")


if __name__ == "__main__":
    main()
