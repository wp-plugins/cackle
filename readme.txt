=== Plugin Name ===
Contributors: Denis Golovachev, Cackle
Tags: comments, social, social comments, вконтакте, facebook, yandex
Requires at least: 2.9.1
Tested up to: 3.5
Stable tag: 2.1

This plugin integrates Cackle Comment System (Google+, Twitter, Facebook, Vkontakte, Odnoklassniki and other) right into your website.

== Description ==

This plugin allows users to use social networks accounts to leave comments.

Supported social networks and OpenID providers:

Google, Google+, Facebook, Twitter, Vkontakte, Mail.ru, Odnoklassniki, Yandex, Rambler, LinkedIn, Yahoo, MyOpenID, Live Journal, Flickr, Wordpress, Blogger, Verisign

= Cackle for Wordpress =

* Uses Cackle API
* Comments indexable by search engines (SEO-friendly)
* Auto-sync (backup) of comments with Cackle and WordPress database
* Export local comments to cackle
* Comments administration through wordpress
* Manual desynchronizing comments
* Custom html for seo
* Comments counter for each post
* Support disable comments for each post or page through wordpress's "Quick edit"
* Support Single Sign-On (SSO)

= Cackle Features =

*   Unlimited sites
*   Unlimited moderators
*   Share on Vkontakte, Mail.ru, Facebook, Twitter, LinkedIn
*   Multimedia attachments (PNG, JPG, YouTube, Vimeo, SlideShare and other...)
*   Moderation comments through widget, without leaving your site
*   Threaded comments and replies
*   Notifications and reply by email
*   Anonymous commenting
*   Powerful admin tools
*   Ban by IP, User
*   Nasty words filter
*   Customization of widget text labels
*   Easy to install


== Installation ==

1. Upload  folder `cackle` to the `/wp-content/plugins/` directory
2. Register and obtain your Site ID, Site API Key and Account API Key: go to the Cackle Administration Panel -> Widget -> CMSs Plugins -> Wordpress. (See the second screenshot at http://wordpress.org/extend/plugins/cackle/screenshots/ )
3. Save it to 'cackle plugin's' page.
4. Activate the plugin through the 'cackle plugin's' menu in WordPress
5. In wordpress's comments menu you can find "Cackle" submenu with panel administration and settings.
6. If you need export local comments to Cackle you should click "export comments" button in settings.


== Screenshots ==

1. Cackle widget preview

2. API key highlighted in red

3. Administration menu

== Frequently Asked Questions ==
All questions send to support@cackle.me and we will reply for 5 hours

== Changelog ==

= 1.00 =
* Initial release

= 1.02 =
* Fixed layout bug

= 1.10 =
* Description rewritten
* Extra whitespaces removed
* Screenshots added
* Small style fixes

= 1.11 =
* Extra screenshots removed

= 1.12 =
* Readme fixes

= 1.13 =
* Supported version updated

= 1.14 =
* Bugfix: Remove extra </div>

= 1.15 =
* Update version to 1.15

= 1.16 =
* Support comments indexation by search engine
* Support original WordPress comments
* Auto synchronization comments from cackle

= 1.17 =
* Bug fix and update version to 1.17

= 1.18 =
* Bug fix and update version to 1.18

= 1.19 =
* Bug fix and update version to 1.19

= 1.20 =
* Bug fix and update version to 1.20

= 1.21 =
* Bug fix for custom templates and update version to 1.21

= 2.0 =
* Export local comments to cackle
* Added comments administration through wordpress
* Added manual desynchronizing comments
* Added custom html for seo
* Added comments counter for each post
* Update version to 2.0

= 2.01 =
* Added features description

= 2.02 =
* Bugfix for import anonym comments from Cackle. ReSynchronise needed to update anonym author and email in local database
* Support disable comments for each post or page through wordpress's "Quick edit"

= 2.03 =
* Bugfix for wordpress's debug mode
* Adding timeout for export function to solve bad networks problem
* Support new Cackle Api for getting & exporting comments

= 2.04 =
* Support Single Sign-On (SSO) which allows the registered (on your site) users work with widget and post comments.

= 2.05 =
* Bugfix for exporting comments to Cackle: correct sorting (order by comment_date asc) comments for generation WXR

= 2.1 =
* Updating comment statuses (APPROVED, REJECTED, SPAM, TRASH) from Cackle to local database every 5 minutes. So, when you update comment's status in cackle's admin panel then it will be updated in your local database.