"""Dev-machine only (NOT for the cPanel server).
Creates static TTF instances from the Google variable fonts (both SIL OFL; verify licence files when you download).
  pip install fonttools
  Download: Montserrat[wght].ttf and CormorantGaramond[wght].ttf from the google/fonts repo (ofl/montserrat, ofl/cormorantgaramond)
Outputs go to storage/app/fonts/ in the Laravel project. Commit the OFL licence texts next to them.
"""
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
import sys, os
jobs = [("Montserrat[wght].ttf",          400, "Montserrat-Regular.ttf"),
        ("Montserrat[wght].ttf",          600, "Montserrat-SemiBold.ttf"),
        ("CormorantGaramond[wght].ttf",   600, "CormorantGaramond-SemiBold.ttf")]
src_dir, out_dir = sys.argv[1], sys.argv[2]
os.makedirs(out_dir, exist_ok=True)
for src, w, out in jobs:
    f = instancer.instantiateVariableFont(TTFont(os.path.join(src_dir, src)), {"wght": w}, inplace=False)
    f.save(os.path.join(out_dir, out)); print("wrote", out)
