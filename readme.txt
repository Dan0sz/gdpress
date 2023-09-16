=== GDPRess | Eliminate external requests to increase GDPR compliance ===
Contributors: DaanvandenBergh
Tags: gdpr, dsvgo, avg, external, 3rd party, requests, minimize
Requires at least: 5.8
Tested up to: 6.2
Stable tag: 1.2.3
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**GDPRess can be downloaded for free without any paid subscription from [the official WordPress repository](https://wordpress.org/plugins/gdpr-press/).**

In January, 2022 [a German court ruled](https://ffw.press/blog/gdpr/google-fonts-violates-gdpr-germany/) that a website owner was in breach of GDPR and should pay a € 100,- fine, because embedded Google Fonts were used, essentially transferring the user's personal data (IP address) without the user's prior consent.

= What's embedding? =

When an external (i.e. loaded from another server, besides your own) resource is embedded into a webpage, it basically means that the resource behaves as if it's loaded from the same server hosting the webpage.

= Why is using embedded resources in breach of GDPR? =

Because of [the way the internet works](https://ffw.press/blog/how-to/google-fonts-gdpr/). When a browser (i.e. computer) requests a file (e.g. an image or a font file), the server needs the IP address of that computer to send it back. All these requests (including the IP address) are logged in a so-called `access.log`.

Once this IP address leaves the European Union, your website is violating the GDPR.

= What does this plugin do? =

GDPRess scans your homepage for 3rd party scripts (JS) and stylesheets (CSS), and:

* Allows you to download or exclude them from downloading,
* Parses the stylesheets for loaded font files, downloads them, and rewrites the stylesheet to use the local copies,
* Makes sure the local copies of each script/stylesheet are used in your site's frontend.

In short, it makes sure no requests are made to external/embedded/3rd party scripts and stylesheets.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/gdpr-press` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> GDPRess screen to configure the plugin

== Frequently Asked Questions ==

= Can I remove/preload stylesheets and/or scripts with this plugin? =

No, because there are other plugins (like Asset Cleanup or Autoptimize) that are already excellent at that.

= GDPRess downloaded a stylesheet/script, but I'm still seeing requests to font files loaded by the stylesheet? =

GDPRess parses the stylesheet for defined src urls. But if it somehow missed it, I'd love to hear about that, because that might be a bug. Please head over to the support forum and submit a ticket, and include the full URL to the external stylesheet.

= Will this plugin allow me to use Google Analytics in compliance with GDPR? =

No, because much more is needed than *just* downloading analytics.js/gtag.js to your server. To [use Google Analytics in compliance with GDPR](https://ffw.press/blog/gdpr/google-analytics-compliance-gdpr/), you need [CAOS Pro](https://ffw.press/wordpress/caos-pro/).

== Screenshots ==

1. GDPRess' Start screen, simply click Scan Website to start.
2. After running the initial scan, external requests are listed. Exclude a file when e.g. you suspect it might not work properly when it's downloaded.
3. Google Analytics is automatically excluded, because simply downloading the file is not enough to use it in compliance with GDPR. Click on the link in the tooltip for more information.
4. Google Fonts is supported, but when many font families and/or font styles are detected, GDPRess will offer an alternative approach to optimize the request.
5. When the selected files are downloaded, the URLs of the local copies are listed.

== Changelog ==

= 1.2.3 =
* Fixed: call to undefined function download_url().

= 1.2.2 =
* Fixed: GDPRess now runs before OMGF/CAOS, so e.g. OMGF Pro can optimize previously externally hosted stylesheets.

= 1.2.1 =
* Fixed: Protocol (//) and Root (/) relative URLs shouldn't be interpreted as external URLs.
* Fixed: Let CAOS/OMGF handle their files, if these plugins are active.

= 1.2.0 =
* Added: Run a quick scan on each page to see if new external (3rd party) requests are present on that page!
* Added: Test Mode (enabled by Default) to allow users to first test the optimizations before releasing them to the public.
* Fixed: Don't use WP_Filesystem to get and put file contents.

= 1.1.0 =
* Added: Google Fonts support
  - When many Font Families or Font Styles are detected, GDPR Press will suggest to use OMGF to optimize the request before downloading it.
* Fixed several bugs, notices and warnings.

= 1.0.2 =
* Added: tooltip next to success message.
* Fixed: tooltip-icon line height.

= 1.0.1 =
* Fixed: Conflicts with several caching/optimization plugins:
  - Autoptimize
  - WP Rocket
  - W3 Total Cache
  - WP Optimize
* Fixed: several warnings and notices.

= 1.0 =
* First release!