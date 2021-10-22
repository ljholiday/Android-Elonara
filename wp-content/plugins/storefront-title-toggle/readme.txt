=== Title Toggle for Storefront Theme ===
Contributors: wooassist
Tags: title, remove, storefront, toggle
Requires at least: 4.0.0
Tested up to: 5.2.3
Stable tag: 5.2.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hide titles on a per post/page basis. Must be using the Storefront theme.

== Description ==

https://www.youtube.com/watch?v=pCFSc7xditY

This plugin lets you easily remove the page title from specific pages or posts. The basic case scenario is to be
able to hide the title that you will be setting for your "Home" page.

This plugin is built to work only for the [Storefront theme](https://wordpress.org/themes/storefront)

**How to use:**

1. To hide the title for a certain page, you need to login to your WordPress dashboard, and edit the page that you want to hide the title.
2. On the edit page, scroll down until you see the Title and Toggle meta box. Check "Hide Title" checkbox and update your page.
3. After the page has been updated, check your page to see if the title has been successfully removed.

There is also an option to remove the post meta (tags and comment count) for posts.


== Installation ==

1. Extract the zip file and upload the storefront-title-toggle folder to your /wp-content/plugins/ directory
2. In your WordPress dashboard, go to plugins and activate "Storefront Title Toggle" plugin.
3. When editing a post or a page, scroll down until you see the Title Toggle metabox. Check "hide title" to hide the page you are editing.


== Frequently Asked Questions ==

**Will this plugin work for themes other than Storefront?**
Unfortunately, No. This plugin was designed to work for the Storefront theme, utilizing Storefront's action hooks and filters. Activating the plugin while using a different theme will trigger a warning.

**I've activated the plugin, where can I access the settings?**
The settings for this plugin is a per page/post basis. The functionality can be accessed in the bottom part of every page/post and needs to be set individually.

**How to toggle all titles at once?**
This is not possible currently, but we will be adding the functionality on the next version.

**Does this work for Products under the WooCommerce plugin?**
Yes.

== Screenshots ==

1. Metabox that will be displayed in the edit screen. This should be located below the content editor.

== Changelog ==

= 1.2.4 =
* tested with WP version 4.8.2, WooCommerce 3.1.2 and Storefront 2.2.5
* fixed hiding title on the static frontpage

= 1.2.3 =
* tested with WP version 4.7.3, WooCommerce 2.6.14 and Storefront 2.1.8
* added filter to set the default values of the title and meta toggles by code

= 1.2.2 =
* fixed - notice showing on 404 pages
* fixed - notice showing on edit page when saving it
* added function to display featured image in page when title is hidden

= 1.2.1 =
* fixed - only show "hide meta" checkbox option for posts
* fixed margin bug when hiding product title

= 1.2.0 =
* added language support
* updated plugin to use storefront extension boilerplate

= 1.1.0 =
* fixed bug - cannot hide shop page title
* fixed bug - cannot hide product page title

= 1.0.0 =
* Initial release
