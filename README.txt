=== Qtranslate Slug ===
Contributors: Carlos Sanz García
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SYC46KSLRC4Q8
Tags: qtranslate, slug, multilanguage
Requires at least: 3.3
Tested up to: 3.5.1
Version: 1.1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds support for permalink translations and fix some QTranslate deficiencies since wordpress 3.0

== Description ==

[Qtranslate](http://wordpress.org/extend/plugins/qtranslate/) is a nice plugin but unfortunately today is **outdated**. **Qtranslate Slug** is an addon to QTranslate, which adds support for permalinks translations and fix some QTranslate deficiencies since wordpress 3.0.

**Version 1.0** has been written from scratch using OOP. The code has been structured better, the functions have been marked and commented and everything is better integrated with Wordpress API.

= Requirements: =

* Wordpress 3.3 (PHP 5.2 and MySQL 5)
* Qtranslate 2.5.8

= New in version 1.1.5 =

* bugfixes

**Advice: If you're using a multisite installation, you will must activate qtranslate plugins by sepparetly on each site.**


You can also check the [project website](http://not-only-code.github.com/qtranslate-slug/) hosted on [GitHub](http://not-only-code.github.com).
Thanks for use this plugin!

== Installation ==

**This plugins requires [Qtranslate](http://wordpress.org/extend/plugins/qtranslate/) installed previously, if not, it will not activate.**

1. Upload `qtranslate-slug` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. That's all!

= Changing base permastructs =

1. In admin: navigate to *Settings/Slug options*.
1. Set the base permastructs for **post types** and **taxonomies** (If you setup a base permastruct for *categories* or *tags* in *Settings/Permalinks*, these will be overwritten by the translated ones).
1. Save settings and that's all!

== Frequently Asked Questions ==

= It works with posts and pages, but with other content type? =

This plugin allows to translate slugs of: posts, pages, custom post types, categories, tags and custom taxonomies.

= Do I have to configure anything? =

If you want to translate also the base permastructs (ex. *category*, *tag*, etc). Visit the plugin settings page in the admin *Settings/Slug options*

= How can i insert a language selector in my theme ? =

You can choose to:
* use **Qtranslate Slug Widget** in your sidebar.
* place in your template `<?php if (function_exists('qts_language_menu') ) qts_language_menu('text'); ?>`. Options are: `dropdown`, `text`, `image`, and `both`.

= Appears an error 404, what can i do? =

In the admin go to *Settings/Permalinks* or *Settings/Slug options* and save.

= I can't manage translations in Nav Menus. = 

That's because language selector metabox is hidden, if you are in admin *nav menus* screen, press the button **Screen options** (on top and right) and after, check the option *Languages*. It will appear a **Language** meta box on top of the left sidebar.

== Screenshots ==

1. Edit page for: post / page / post_type, you can see the meta box for translated slugs on top and right.
2. Add new taxonomy page
3. Edit taxonomy page
4. Qtranslate Slug options page for translate base permastructs of post_types and taxonomies.

== Changelog ==

= 1.1.5 = 
* bugfixes

= 1.1 = 
* added multisite support
* fixed some parse url bugs
* fixed slug bases validation 

= 1.0 =
* **works** with any permalink combination and qtranslate mode.
* new branch, the plugin has been rewritten: now the code is commented and wrapped inside a class, much code has change and the performance has been increased (use caches).
* data system changed, no ID for slug type, then it don't needs install `qtrasnlate_slug` table. That means slugs now are stored on meta tables and installation creates a termmeta table with some new *core functions* to access/save data, based on [simple term meta](http://wordpress.org/extend/plugins/simple-term-meta/). Upgrade process when the plugin updates from older versions.
* the plugin generates translated slug automatically from title in empty cases.
* the plugin checks if the slug already exists (per each language and `post_type`/`taxonomy`), and adds a progressive number in this case. Works on ajax requests for example when new taxonomies are created in edit post page.
* possibility to translate the base of permastructs for *post_types* and *taxonomies*, uses [$wp_rewrite](http://codex.wordpress.org/Class_Reference/WP_Rewrite). New admin options page for save the base permastructs.
* added some filters, see in [other notes](http://wordpress.org/extend/plugins/qtranslate-slug/other_notes/).
* added plugin language textdomain (.pot file).
* updated **Language selector Widget**, and some new conventions like accessible functions for templating.
* some bug fixes.
* some Qtranslate patches.

= 0.9 =
* some wordpress qTranslate bug fixes
* adds a javascript solution for qTranslate Nav Menus

= 0.8 =
* added support por Categories
* added support por Tags
* added support por Taxonomies
* added support por Custom Post Types

= 0.7 = [Zapo](http://www.qianqin.de/qtranslate/forum/viewtopic.php?f=4&t=1049&start=50#p7499)
* added suport for qTranslate TLD domain mode (en: domain.com | fr: domain.fr) visit 

= 0.5 and 0.6 enhanched by Marco Del Percio =

== Upgrade Notice ==

= 1.0 =
Major version, the plugin has been rewritten. Better performance, and some enhancements.

= 0.9 =
This version fix some bugs and allow multilanguage in nav-menus.

= 0.8 =
A lot of slugs content allowed

= 0.7 =
This version allows TLD domain option for a different Qtranslate fork maded by Zappo


== Other notes ==

Plugin filters reference:

= qts_validate_post_slug =  
filter to process the post slug before is saved on the database.  
`args: $post (object), $slug (string), $lang (string)`

= qts_validate_term_slug =  
filter to process the term slug before is saved on the database.  
`args: $term (object), $slug (string), $lang (string)`

= qts_url_args =  
filter to process the entire url after it has been generated.  
`args: $url (string), $lang (string)`
 
= qts_permastruct =  
filter to process the permastruct, used for change the base.  
`args: $permastruct (string), $name (string)`


= Todo =

* detect Slug for each language and redirect accordingly in parse_query.
* expand qtranslate for translate attachment names and descriptions ( useful for galleries )
* translate other slugs like attachments.
* qtranslate integration with other plugins like Jigoshop, e-commerce, etc. Addapt **$wp_rewrite**.