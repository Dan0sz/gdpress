=== GDPRess | Eliminate External (3rd Party) Requests ===
Contributors: DaanvandenBergh
Tags: gdpr, dsvgo, avg, speed, minimize, external, requests
Requires at least: 5.8
Tested up to: 5.9
Stable tag: 0.9
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

In January, 2022 a German court ruled that a website owner should pay a € 100,- fine, because embedded Google Fonts were used, essentially transferring the user's personal data (IP address) without the user's prior consent.

This ruling doesn\'t go for just Google Fonts; loading any file from a server *outside* the US is in breach of GDPR.

GDPRess eliminates embedded resources (scripts (JS) and stylesheets (CSS)), downloads them and automatically rewrites the URLs in your site's frontend.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/gdpr-press` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> Optimize Google Fonts screen to configure the plugin

== Frequently Asked Questions ==

= Can I remove/preload stylesheets and/or scripts with this plugin? =

No, because there are other plugins (like Asset Cleanup or Autoptimize) that are already excellent at that.

= GDPRess downloaded a stylesheet/script, but I'm still seeing external requests to font files, e.g. WOFF2 =

It's possible that the stylesheet/script that was downloaded makes additional calls to external files. Perhaps in a future version I'll automate this process, too, but for now you should manually download the files and rewrite the URLs inside the stylesheet/script.

== Screenshots ==

== Changelog ==
