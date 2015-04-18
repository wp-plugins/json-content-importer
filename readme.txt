=== JSON Content Importer ===
Contributors: berkux
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=APWXWK3DF2E22
Tags: json,template,engine,template engine,markup,import,import json, importer,content,cache,load,opendata,opendata import,advanced json import,json import,content import,import json to wordpress,json to content,display json
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.2.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Plugin to import, cache and display a JSON-Feed. Display is done with wordpress-shortcode.


== Description ==

= JSON Content Importer - Powerful and Simple JSON-Import Plugin =

Use a template engine to display the data of an JSON-Feed.

Define the url of the JSON-Feed, a template for it and other options like number of displayed items, cachetime etc..

The template engine inserts the JSON-data in the template provided in the wordpress-shortcode inside a page -  whereby some extras like urlencoding can be invoked.

= Simple Example of wordpress-shortcode =
[jsoncontentimporter
  url="http://...json"
  numberofdisplayeditems="number: how many items of level 1 should be displayed? display all: leave empty or set -1"
  urlgettimeout="number: who many seconds for loading url till timeout?"
  basenode="starting point of datasets, the base-node in the JSON-Feed where the data is"
  oneofthesewordsmustbein="default empty, if not empty keywords spearated by ','. At least one of these keywords must be in the created text (here: text=code without html-tags)"
  oneofthesewordsmustbeindepth="default: 1, number:where in the JSON-tree oneofthesewordsmustbein must be?"
]
Any HTML-Code plus "basenode"-datafields wrapped in "{}"
{subloop:"basenode_subloop":"number of subloop-datasets to be displayed"}
Any HTML-Code plus "basenode_subloop"-datafields wrapped in "{}". If JSON-data is HTML add "html" flag like "{fieldname:html}"
{/subloop:"basenode_subloop"}
[/jsoncontentimporter]

* If the subloop is not an object but an array, e.g.:
"{subloop-array:type:5}{1:ifNotEmptyAddRight:aa&lt;br&gt;bb}{2:ifNotEmptyAddLeft:AA}{3:ifNotEmptyAddRight:BB}{/subloop-array}"
shows the first, second and third entry of that array, modified by ifNotEmptyAddLeft and ifNotEmptyAddRight (see below).

* New in Version 1.2.0: filter & hook and the shortcode-parameter "oneofthesewordsmustbein" and "oneofthesewordsmustbeindepth" are added

* templates like "{subloop-array:AAAA:10}{text}{subloop:AAAA.image:10}{id}{/subloop:AAAA.image}{/subloop-array:AAAA}" are possible:
one is the recursive usage of "subloop-array" and "subloop".
the other is "{subloop:AAAA.image:10}" where "AAAA.image" is the path to an object. This is fine for some JSON-data.

= Recursive usage of "subloop-array" and "subloop" of wordpress-shortcode =
If the JSON-Tree is deep, the template has to be deep. Then "subloop" and "subloop-array" can be used inside the other.

= Some special add-ons for datafields =
* "{street:html}": Default-display of a datafield is NOT HTML: "&lt;" etc. are converted to "&amp,lt;". Add "html" to display the HTML-Code as Code.
* "{street:htmlAndLinefeed2htmlLinefeed}": Same as "{street:html}" plus "\n"-Linefeeds are converted to HTML-Linebreak "<br>"
* "{street:ifNotEmptyAddRight:,}": If datafield "street" is not empty, add "," right of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "
* "{street:html,ifNotEmptyAddRight:extratext}": you can combine "html" and "ifNotEmptyAdd..." like this
* "{street:ifNotEmptyAdd:,}": same as "ifNotEmptyAddRight"
* "{street:ifNotEmptyAddLeft:,}": If datafield "street" is not empty, add "," left of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "
* "{locationname:urlencode}": Insert the php-urlencoded value of the datafield "locationname". Needed when building URLs
* "{locationname:unique}": only display the first instance of a datafield. Needed when JSON delivers data more than once


== Installation ==

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

1. Login to your WordPress installation
2. Install plugin by uploading json-content-importer.zip to `/wp-content/plugins/`.
2. Activate the plugin through the _Plugins_ menu.
3. Klick on "JSON Content Importer" menuentry in the left bar: basic caching-settings and more instructions about usage.
4. Cache folder: WP_CONTENT_DIR.'/cache/jsoncontentimporter'. So "WP_CONTENT_DIR.'/cache/'" must be writable for the http-daemon. The plugin checks this and might aborts with an error-message like dir is missing or not writeable. if so: check permissions of the directories.


== Frequently Asked Questions ==

= What does this plugin do? =

This plugin gives a wp-shortcode for use in a page/blog to import, cache and display JSON-data. Inside wp-shortcode some markups (and attributes like urlencode) are defined to define how to display the data.

= How can I make sure the plugin works? =

Create a sample-page and use the wordpress-shortcode "jsoncontentimporter". An example is given in the plugin-configpage and in the "Description"-Section.

= Who do I find the proper template for my JSON? =
Check the description. [If you're lost: open ticket at wordPress.org](https://wordpress.org/support/plugin/json-content-importer) please provide the JSON-code or link to it (either in the posting or in a mail to the plugin author).

Don't forget: [Donate whatever this plugin is worth for you](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=APWXWK3DF2E22)

= What does this plugin NOT do? =
The plugins template engine ist focussed on some basic JSON-imports. Other template engines like H2O or stuff like node.js / handlebars.js can process JSON much more powerful - but they come with a much bigger overhead.
Your options if this plugin does not work:
* use correct code for this plugin ;-)
* if the above is ok, change the JSON-Input
* [open ticket at wordPress.org](https://wordpress.org/support/plugin/json-content-importer) provide the JSON-code there or an email to the plugin-author.

= Where is this plugin from? =
This plugin is made in munich, bavaria, germany!
Famous for Oktoberfest, FC Bayern Munich, AllianzArena, DLD, TUM, BMW, Siemens, seas, mountains and much more...

== Screenshots ==

1. This screen shows the description and settings-page of the "JSON Content Importer"-Plugin
2. This screen shows the Wordpress-Editor with some [jsoncontentimporter]-code

== Changelog ==

= 1.2.1 =
* new feature "{street:htmlAndLinefeed2htmlLinefeed}": Text-Linefeeds of JSON-data are converted to HTML-Linefeeds
* Boolean JSON-Values were ignored before this version. Now the text "true" or "False" is displayed
* Bugfixing Cacher: Timeout-Parameter of cache was not handled right
* Fixed a bug with JSON-Value containing "$"

= 1.2.0 =
* new shortcode-parameter: "oneofthesewordsmustbein" and "oneofthesewordsmustbeindepth"
* filter & hook for third party extensions added: hook "json_content_importer_extension" and filter "json_content_importer_result_root"
* Sourcecode: Classes rearranged
* minor bugfix: number of items in subloop/subloop-array was sometimes ignored and all was displayed
* "made in munich" added (see faq)

= 1.1.2 =
* Bugfix: tags like "{aa/aa}" are ok (previous: error)
* Display JSON-HTML-Data really as HTML. Default: JSON-HTML-Data is displayed not as HTML but as HTML-Text. New in this version: tag-sytax like "{tag:html}" or "{street:html,ifNotEmptyAddRight:extratext}" allows real HTML-display.
* New parameter in "[jsoncontentimporter]"-shortcode: set http-timeout "urlgettimeout". default is 5 seconds (ueful if source-website of JSON is slow)
* Logo of plugin: Wordpress-Logo inserted
* Update of screenshots

= 1.1.1 =
Bugfixes

= 1.1.0 =
Completely rewritten template engine for even better JSON-handling:

* "subloop-array": key should also be in the closing tag, e.g. "{subloop-array:KEY:10}{some_array_field}{/subloop-array:KEY}".
The "subloop-array" without KEY in the closing tag is ok if there is only one "subloop-array" in the template. But if there are more than one "subloop-array" in the template insert the KEY in the closing tag!
Then the template engine can identify the correct JSON-data.

* "subloop": what is above for "subloop-array" is also for "subloop", e.g.  "{subloop:KEY:10}{some_object_field}{/subloop:KEY}"

* templates like "{subloop-array:AAAA:10}{text}{subloop:AAAA.image:10}{id}{/subloop:AAAA.image}{/subloop-array:AAAA}" are possible:
one is the recursive usage of "subloop-array" and "subloop".
the other is "{subloop:AAAA.image:10}" where "AAAA.image" is the path to an object.

* JSON-data with multiple use of arrays can be handled by the template engine

= 1.0.5 =
* Added Screenshots
* Enhanced "subloop-array", new processing of pure string/numeric-array data (before: only string/numeric-data in an object)
* Enhanced FAQs: Added Link to Website for better creating shortcode-markups

= 1.0.4 =
Bugfixes

= 1.0.3 =
Enhanced the template engine for better JSON-handling.

= 1.0.2 =
Initial release on WordPress.org. Any comments and feature-requests are welcome: blog@kux.de



== Upgrade Notice ==

Version 1.2.1 fixed two bugs (cacher, $) and comes with two new features: handling of boolean-values and linefeeds