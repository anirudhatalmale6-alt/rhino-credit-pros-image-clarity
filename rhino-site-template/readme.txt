=== Rhino Site Template ===
Author: Anirudha Talmale
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
License: GPL-2.0-or-later

Adds a "Rhino Standard" page template with a site-wide header and footer.

== Install ==

1. Plugins > Add New > Upload Plugin > rhino-site-template.zip > Install Now > Activate.
2. Go to Settings > Rhino Template and paste in your social media links and the
   Schedule a Consultation link.
3. A blue notice will offer to apply the template to every page. Click it once.

New pages get the template automatically from version 1.2.0 onwards, so you
can just build and not think about it. If you deliberately pick a different
template for a page, that choice is respected and never overwritten.

To switch a single page on or off, go to Pages. There is a "Rhino Template"
column with a button on every row. You can also select several pages and use
the Bulk actions dropdown.

== The front page is never touched ==

Your homepage is a single flat artwork with its own painted header and your
hand-built hotspot links. Putting a real header on top of it gives you two
menus. So the plugin skips the front page completely: bulk apply ignores it,
and even if the template is somehow assigned to it, the plugin refuses to
render there. The Pages list shows it as "Front page - skipped".

== Why not the Template dropdown in the editor ==

Because your theme is a block theme. That editor's "Choose a template" screen
only lists the theme's own block templates, and this is a classic PHP template,
so it will not appear there. The Pages list column above does the same job.

== Why a plugin and not Elementor ==

Every page on this site uses Elementor's blank canvas template, which strips out
the theme's header and footer. That is why the site had no navigation anywhere.
The usual fix is Elementor's Theme Builder, but that is a Pro feature. This
plugin does the same job without it, and works no matter which theme is active.

== What you can change without me ==

* The menu itself: Appearance > Menus. Add, remove, reorder, nest for dropdowns.
  The header and footer both render whatever is assigned to "Rhino Header &
  Footer". Note that block themes hide the Menus screen by default; this plugin
  registers a menu location, which brings it back.
* Social links, button text and link, tagline, copyright: Settings > Rhino Template.
* Social icons stay hidden until you paste a link in, so the footer never looks
  half-finished. Same for the button.

== Defaults ==

On first activation the plugin builds a menu from the pages that exist:

  Home
  Credit / Funding
      Personal Funding
      Business Funding
  All Rhino Credit Services
  About
  Contact

It only does this once, and it never touches a menu you have already edited.

== Uninstalling ==

Deactivate and the header and footer disappear; pages fall back to whatever
template they used before. Nothing is deleted. Your menu and settings stay put
in case you turn it back on.

== Changelog ==

= 1.2.0 =
* New pages now get the template automatically - no need to remember to apply it
* An explicit template choice on a page is never overridden

= 1.1.0 =
* Front page is now excluded automatically - it can no longer end up with two headers
* Per-page on/off from the Pages list, plus bulk actions (the block-theme editor
  will not list classic templates, so this replaces that route)

= 1.0.0 =
* Registered "Rhino Standard" page template
* Sticky header: logo, menu with dropdowns, CTA button
* Mobile hamburger menu, closes on Escape
* Footer: logo, menu, social icons, copyright, tagline
* Settings screen and one-click apply to all pages
