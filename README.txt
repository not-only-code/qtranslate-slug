=== Qtranslate Slug ===
Contributors: Carlos Sanz García
Donate link: http://example.com/
Tags: qtranslate, slug, multilanguage
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows to define a slug for each language and some qTranslate bug fixes

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This plugins requires qTranslate installed previously, if not, it will not activate.

This section describes how to install the plugin and get it working.

e.g.
1. Upload `qtranslate-slug` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

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
