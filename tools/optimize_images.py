"""
Make web-ready copies of the site photos.

Reads every .jpg/.jpeg/.png in assets/images/ and writes, for each one,
WebP and JPEG copies at two widths into assets/images/web/:

    IMG_0790-800.webp   IMG_0790-800.jpg
    IMG_0790-1600.webp  IMG_0790-1600.jpg

Originals are never changed. Run again whenever you add photos:

    pip install pillow
    python tools/optimize_images.py
"""
from pathlib import Path
from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "assets" / "images"
OUT = SRC / "web"
WIDTHS = (800, 1600)
WEBP_QUALITY = 76
JPEG_QUALITY = 78


def save_versions(path: Path) -> None:
    with Image.open(path) as im:
        im = ImageOps.exif_transpose(im)  # keep phone photos the right way up
        im = im.convert("RGB")
        for w in WIDTHS:
            target = min(w, im.width)  # never upscale; small images keep their size
            h = round(im.height * target / im.width)
            resized = im.resize((target, h), Image.LANCZOS) if target != im.width else im
            stem = f"{path.stem}-{w}"
            resized.save(OUT / f"{stem}.webp", "WEBP", quality=WEBP_QUALITY, method=6)
            resized.save(OUT / f"{stem}.jpg", "JPEG", quality=JPEG_QUALITY,
                         optimize=True, progressive=True)


def main() -> None:
    OUT.mkdir(exist_ok=True)
    files = [p for p in SRC.iterdir() if p.suffix.lower() in {".jpg", ".jpeg", ".png"}]
    before = sum(p.stat().st_size for p in files)
    for p in sorted(files):
        save_versions(p)
        print("done", p.name)
    after = sum(p.stat().st_size for p in OUT.glob("*-800.webp"))
    print(f"originals: {before/1e6:.1f} MB  ->  phone-size WebP set: {after/1e6:.1f} MB")


if __name__ == "__main__":
    main()
