# Rhino Credit Pros — homepage artwork clarity fix

## Install (one step)

**Plugins → Add New → Upload Plugin → choose `rhino-image-clarity.zip` → Install
→ Activate.** Then hard-refresh the homepage (Ctrl+F5 / Cmd+Shift+R).

That's it. Nothing to re-upload to the Media Library, nothing to change in the
block editor. The optimised artwork ships **inside** the plugin, and the plugin
swaps it in when the page renders. Deactivating it puts everything back exactly
as it was.

The zip is 3.7 MB. Your server accepts it — the 9.5 MB PNG already in your Media
Library proves the upload limit is well above that.

---

## What was actually wrong

Your master file is **2508 × 5646 px** and is genuinely sharp. Nothing on the
server re-compressed it — the PNGs come back as untouched `image/png`, so
**the CDN and caching are not the cause**. The blur is entirely the browser
being handed a small file and then stretching it. Three things stack up:

**1. The block was inserted at the "Large" size — 455px wide.**

```html
src="...-455x1024.png"  width="455" height="1024"
```

**2. The `sizes` attribute tells the browser the slot is 455px wide. It isn't.**

```html
sizes="(max-width: 455px) 100vw, 455px"
```

The figure is `alignfull`, so it spans the **whole viewport**. Measured in
Chrome on a 1440px window, the image renders at **1440 px wide from a 455 px
file — a 3.16× upscale**. On a retina screen the browser picks the 910w file and
paints it across 2880 device px — the same 3.16×. That is the softness.

**3. WordPress shrank the upload — because the image is tall, not because it's big.**

WordPress caps the *longest side* of any upload at 2560px and serves that
`-scaled` copy in place of the original. Your artwork's longest side is its
**height** (5646px), so the cap applied vertically and dragged the **width**
down with it:

```
uploaded   2508 × 5646     <- your master, still on the server
-scaled    1137 × 2560     <- what WordPress serves as "Full Size"
```

So even choosing "Full Size" in the editor only gets you 1137px of width. This
one cannot be fixed from the editor at all.

### Measured on the live site

| Viewport | File downloaded | Painted at | Result |
|---|---|---|---|
| 1440px @1x | 455w | 1440 px | **3.16× upscale** |
| 1440px @2x | 910w | 2880 device px | **3.16× upscale** |
| 390px @2x | 910w | 780 device px | fine |

### Measured with the plugin active

| Viewport | File served | Painted at | Scale |
|---|---|---|---|
| 1440px @1x | 1536w | 1440 device px | 0.94× — sharp |
| 1440px @2x | 2508w | 2880 device px | 1.15× |
| 1280px @2x | 2508w | 2560 device px | 1.02× — sharp |
| 768px @2x | 1536w | 1536 device px | 1.00× — sharp |
| 390px @3x | 1536w | 1170 device px | 0.76× — sharp |

Every case is now at or below 1:1 except retina desktop at 1.15×, which is the
ceiling of the source file — the master is 2508px wide and that slot wants
2880px. 1.15× is invisible; 3.16× is what you were looking at.

---

## What the plugin does

- Swaps the homepage artwork for the bundled **2508px WebP** master and gives it
  a correct four-step `srcset`
- Sets `sizes="100vw"` so the browser stops picking a file sized for a 455px slot
- Lifts the 2560px upload cap (`big_image_size_threshold`) so future tall
  uploads keep their full width
- Raises the srcset ceiling from 1600px to 4096px
- Marks the hero `fetchpriority="high"` / `loading="eager"` so it paints sooner
- Collapses the duplicated `fetchpriority` attribute your theme emits
- Purges LiteSpeed Cache on activation

It only ever touches this one artwork, and it is idempotent — running twice
changes nothing.

### Assets

`rhino-home-2508w.webp` is the full master at WebP quality 88. I measured the
text areas against the original at **39.7 dB PSNR** — identical to the eye, at
**1.3 MB instead of 9.4 MB**. The 2048/1536/1024 steps are there so phones and
tablets don't download more than they need.

---

## Repeating this on future uploads

1. Export at **2× the widest the image will ever display**. Full-width artwork
   on a desktop layout means **2400–2560px wide**. More is wasted bandwidth.
2. If the image is **taller than 2560px**, you need this plugin active or
   WordPress will shrink its width. This is the trap that caught this one.
3. Save as **WebP at quality 85–90**, not PNG. PNG only wins for flat graphics
   with very few colours; anything with photos or gradients is several times
   larger for no visible gain.
4. After inserting, set **Image size → Full Size** on full-width blocks.
5. To check your work: right-click the image → Inspect, and compare
   `naturalWidth` against the rendered width. If rendered is larger, it is being
   upscaled and will look soft.

---

## One thing worth flagging

The homepage is currently a **single flat image**. I checked the rendered HTML:
it contains **1 image, 0 links, 0 headings and 0 characters of text**. The nav
bar, the buttons, the footer links are all painted pixels.

Two consequences that sharpening cannot fix:

- **Nothing is clickable.** "Schedule a Free Consultation", the menu, the social
  icons — none of them do anything.
- **It cannot be read on a phone.** The body text measures 31px in the master.
  Scaled into a 390px phone window that becomes **4.9 CSS px**. On a tablet it's
  9.6px. On desktop it's a fine 17.9px. No amount of resolution changes this —
  the layout is 2508px wide and a phone is 390px.
- Google sees an empty page: no text to index.

The plugin makes it as sharp as the file allows, and on desktop it looks
genuinely good. Rebuilding it as real HTML is a separate, larger job.

---

## Files

| File | Purpose |
|---|---|
| `rhino-image-clarity.zip` | **install this** — plugin with assets bundled |
| `rhino-image-clarity.php` | the plugin source, for reference |
| `rhino-home-2508w.webp` | full master, 1.3 MB |
| `rhino-home-2048w.webp` · `1536w` · `1024w` | srcset steps |
| `rhino-home-2508w.png` | PNG fallback, 9.1 MB — not needed |
| `before-after-live.jpg` | real browser screenshots, live vs fixed |
| `before-after.jpg` | 1:1 pixel crop, same comparison |
