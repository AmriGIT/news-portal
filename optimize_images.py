"""Optimize static images: convert to WebP and resize for web use."""
import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
from PIL import Image
import os

IMG_DIR = r"d:\amri\myweb\blogger\news-portal\public\images"

optimizations = [
    # (source, output, max_width, max_height, quality)
    ("header logo.png", "header-logo.webp", 416, 96, 80),
    ("logo-footer.png", "logo-footer.webp", 352, 112, 80),
    ("default.png", "default.webp", 960, 540, 75),
    ("favicon.png", "favicon.webp", 180, 180, 80),
]

for src_name, out_name, max_w, max_h, quality in optimizations:
    src = os.path.join(IMG_DIR, src_name)
    out = os.path.join(IMG_DIR, out_name)
    if not os.path.exists(src):
        print(f"SKIP: {src_name} not found")
        continue
    img = Image.open(src)
    orig_size = os.path.getsize(src)
    # Resize maintaining aspect ratio
    img.thumbnail((max_w, max_h), Image.LANCZOS)
    img.save(out, "WEBP", quality=quality)
    new_size = os.path.getsize(out)
    print(f"✅ {src_name} ({orig_size:,} bytes) -> {out_name} ({new_size:,} bytes) [{100 - (new_size/orig_size*100):.0f}% smaller]")

# Also create a proper ICO favicon from favicon.png
favicon_src = os.path.join(IMG_DIR, "favicon.png")
favicon_out = os.path.join(IMG_DIR, "..", "favicon.ico")
if os.path.exists(favicon_src):
    img = Image.open(favicon_src)
    img.thumbnail((48, 48), Image.LANCZOS)
    img.save(favicon_out, format="ICO", sizes=[(16,16), (32,32), (48,48)])
    print(f"✅ favicon.ico created ({os.path.getsize(favicon_out):,} bytes)")
