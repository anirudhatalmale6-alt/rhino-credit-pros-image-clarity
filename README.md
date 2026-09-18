# Rhino Credit Pros — homepage artwork clarity fix

## What is actually happening

Your master file is **2508 × 5646 px** and is genuinely sharp. Nothing on the
server has re-compressed it. The blur is caused entirely by **the browser being
handed a small file and then stretching it**. Three separate things stack up:

**1. WordPress shrank the upload — because the image is tall, not because it's big.**
WordPress caps the *longest side* of any upload at 2560px and then serves that
`-scaled` copy in place of the original. Your artwork's longest side is its
**height** (5646px), so the cap was applied vertically and dragged the **width**
down with it:

```
uploaded   2508 × 5646     <- your master, still on the server
-scaled    1137 × 2560     <- what WordPress actually serves as "Full Size"
```

So even choosing "Full Size" in the editor only gets you 1137px of width.

**2. The block was inserted at the "Large" size — 455px wide.**

```html
src="...-455x1024.png"  width="455" height="1024"
```

**3. The `sizes` attribute tells the browser the slot is 455px wide — it isn't.**

```html
sizes="(max-width: 455px) 100vw, 455px"
```

The figure is `alignfull`, so it spans the **whole viewport**. Measured in a real
browser on a 1440px window, the image renders at **1440 px wide from a 455 px
file — a 3.16× upscale**. On a retina laptop the browser picks the 910w file and
paints it across 2880 device px — the same 3.16× upscale. That is the softness
you are seeing.

Verified in Chrome against the live site:

| Viewport | File downloaded | Painted at | Result |
|---|---|---|---|
| 1440px @1x | 455w | 1440 px | 3.16× upscale |
| 1440px @2x | 910w | 2880 device px | 3.16× upscale |
| 390px @2x | 910w | 780 device px | fine |

Note the CDN/cache is **not** the culprit — the PNGs come back as `image/png`,
uncompressed and untouched.

## The fix

### Step 1 — upload the new file

Use `rhino-home-2508w.webp` (1.3 MB). It is the full 2508px master, WebP
quality 88. I measured the text areas against the original at **39.7 dB PSNR** —
visually identical, but 9.4 MB → 1.3 MB.

`rhino-home-2508w.png` is included as a fallback if you'd rather stay on PNG,
but it is 9.1 MB and there is no visible benefit.

Delete the old attachment from the Media Library first, so WordPress doesn't
keep serving the old `-455x1024` copy from its sizes table.

### Step 2 — install the plugin

Upload `rhino-image-clarity.php` via **Plugins → Add New → Upload Plugin**
(zip it first), or drop it straight into `wp-content/mu-plugins/` where it
activates by itself. It does three things:

- turns off the 2560px cap, so tall uploads keep their full width
- rewrites `sizes` to `100vw` on any `alignfull` image block
- raises the srcset ceiling to 4096px

### Step 3 — re-insert the image

In the block editor select the image, and in the right-hand panel set
**Image size → Full Size**. Save, then purge your cache.

### Verified result

With the fix in place, tested in Chrome:

| Viewport | File downloaded | Painted at | Result |
|---|---|---|---|
| 1440px @1x | 1536w | 1440 px | slight downscale — sharp |
| 1440px @2x | 2508w | 2880 device px | 1.15× — sharp |
| 390px @2x | 1024w | 780 device px | downscale — sharp |

## Repeating this on future uploads

1. Export at **2× the widest the image will ever be displayed**. Full-width
   artwork on a desktop layout means **2400–2560px wide**. More than that is
   wasted bandwidth.
2. If the image is **taller than 2560px**, you need the plugin installed or
   WordPress will shrink its width. This is the trap that caught this one.
3. Save as **WebP at quality 85–90**, not PNG. PNG only wins for flat graphics
   with very few colours; anything with photos or gradients (like this one) is
   several times larger for no visible gain.
4. After inserting, always set **Image size → Full Size** on full-width blocks.
5. To check your work: open the page, right-click the image → Inspect, and
   compare `naturalWidth` against the rendered width. If rendered is larger, it
   is being upscaled and will look soft.

## One thing worth flagging

The homepage is currently a **single flat image** — I checked the rendered HTML
and it contains 1 image, 0 links, 0 headings and 0 characters of text. The nav
bar, the buttons, the phone numbers, the footer links are all painted pixels.

That has two consequences sharpening cannot fix:

- **Nothing is clickable.** "Schedule a Free Consultation", the menu, the social
  icons — none of them do anything.
- **It cannot be read on a phone.** I measured the body text at 31px in the
  master. Scaled into a 390px phone window that becomes **4.9 CSS px**. On a
  tablet it is 9.6px. On desktop it is a fine 17.9px. No amount of resolution
  changes this — the layout is 2508px wide and the phone is 390px wide.
- Google sees an empty page: no text to index.

The fix above will make it as sharp as the file allows, and on desktop it will
look genuinely good. Rebuilding it as real HTML is a separate, larger job —
happy to quote it if you want to go that way.

## Files

| File | Purpose |
|---|---|
| `rhino-home-2508w.webp` | **upload this one** — full master, 1.3 MB |
| `rhino-home-2048w.webp` | optional srcset step |
| `rhino-home-1536w.webp` | optional srcset step |
| `rhino-home-1024w.webp` | optional srcset step |
| `rhino-home-2508w.png` | PNG fallback, 9.1 MB |
| `rhino-image-clarity.php` | the WordPress fix |
| `before-after.jpg` | same crop, current site vs fixed |
