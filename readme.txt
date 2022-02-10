=== GDPRess | Eliminate External (3rd Party) Requests ===
Contributors: DaanvandenBergh
Tags: gdpr, dsvgo, avg, speed, minimize, external, requests
Requires at least: 5.8
Tested up to: 5.9
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

In January, 2022 a German court ruled that a website owner should pay a € 100,- fine, because embedded Google Fonts were used, essentially transferring the user's personal data (IP address) without the user's prior consent.

= What's embedding? =

When an external (i.e. loaded from another server, besides your own) resource is embedded into a webpage, it basically means that the resource behaves as if it's loaded from the same server hosting the webpage.

= Why is using embedded resources in breach of GDPR? =

Because of the way the internet works. When a browser (i.e. computer) requests a file (e.g. an image or a font file), the server needs the IP address of that computer to send it back. All these requests (including the IP address) are logged in a so-called `access.log`.

Once this IP address leaves the European Union, your website is violating the GDPR.

= What does this plugin do? =

GDPRess scans your homepage for 3rd party scripts (JS) and stylesheets (CSS), and:

* Allows you to download or exclude them from downloading,
* Parses the stylesheets for loaded font files, downloads them, and rewrites the stylesheet to use the local copies,
* Makes sure the local copies of each script/stylesheet are used in your site's frontend.

Effectively removing any requests to embedded scripts and stylesheets.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/gdpr-press` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> Optimize Google Fonts screen to configure the plugin

== Frequently Asked Questions ==

= Can I remove/preload stylesheets and/or scripts with this plugin? =

No, because there are other plugins (like Asset Cleanup or Autoptimize) that are already excellent at that.

= GDPRess downloaded a stylesheet/script, but I'm still seeing requests to font files loaded by the stylesheet? =

GDPRess parses the stylesheet for defined src urls. But if it somehow missed it, I'd love to hear about that, because that might be a bug. Please head over to the support forum and submit a ticket, and include the full URL to the external stylesheet.

== Screenshots ==

1. GDPRess' Start screen, simply click Scan Website to start.
2. After running the initial scan, external requests are listed. Exclude a file when e.g. you suspect it might not work properly when it's downloaded.
3. When the selected files are downloaded, the URLs of the local copies are listed.
4. Google Analytics is automatically excluded, because simply downloading the file is not enough to use it in compliance with GDPR. Click on the link in the tooltip for more information.
5. Google Fonts is automatically excluded, because simply downloading the file is not enough to use it in compliance with GDPR. Click on the link in the tooltip for more information.

== Changelog ==

= 1.0 =
* First release!