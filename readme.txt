=== Qtranslate Slug ===
Contributors: Carlos Sanz García
Donate link: none
Tags: qtranslate, slug, multilanguage
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows to define a slug for each language and some qTranslate bug fixes

== Description ==

This plugin allows translated slugs in Qtranslate.
You need install and activate previously Qtranslate, if not, Qtranslate Slug will not work.

Tested with Qtranslate versions 2.5.8 and 2.5.9

== Installation ==

This plugins requires qTranslate installed previously, if not, it will not activate.

1. Upload `qtranslate-slug` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. That's all!

== Frequently Asked Questions ==

= It works with posts and pages, but with other content type ? =

This plugin allows to translate slugs of: posts, pages, custom post types, categories, tags and custom taxonomies.

= How can I insert a language selector in my theme ? =

Place `<?php if (function_exists('qTranslateSlug_generateLanguageSelectCode') ) qTranslateSlug_generateLanguageSelectCode('text'); ?>` in your template.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==

= 0.9 =
* some wordpress qTranslate bug fixes
* adds a javascript solution for qTranslate Nav Menus

= 0.8 =
* added support por Categories
* added support por Tags
* added support por Taxonomies
* added support por Custom Post Types

= 0.7 ( enhanced by Zapo (http://www.qianqin.de/qtranslate/forum/viewtopic.php?f=4&t=1049&start=50#p7499) ) =
* added suport for qTranslate TLD domain mode (en: domain.com | fr: domain.fr) visit 

= 0.5 and 0.6 enhanched by Marco Del Percio =

== Upgrade Notice ==

= 0.9 =
This version fix some bugs and allow multilanguage in nav-menus.

= 0.8 =
A lot of slugs content allowed

= 0.7 =
This version allows TLD domain option for a different Qtranslate fork maded by Zappo


== Todo ==

 * admin options page for setup the taxonomies names
 * change the database system ( post meta ), without installing tables
 * generate translated slug automatically from the translated title
 * check if the slug is already used, and add a progressive number in this case
 * force the use of the translated slug if defined
 * force to show a page/post only at the correct URL
 * try to redirect from a wrong URL to a correct URL
 * keep track of renamed slugs, redirecting from old to new slugs 
 * translate categories and tags slugs.
