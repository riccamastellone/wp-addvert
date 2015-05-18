=== WP-Addvert ===
Contributors: riccardo.mastellone, Iazel
Donate link: 
Tags: addvert, ecommerce
Requires at least: 3.3
Tested up to: 4.0
Stable tag: 1.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows your WooCommerce to communicate with Addvert affiliation website

== Description ==

Easily integrate [Addvert](http://addvert.it) to your WooCommerce website without any coding!

What does this plugin do :

* Add some Open Graph meta tags to the single products page
* Make a callback at the and of and order sending the id of the order and the total amount to Addvert

Refer to [Addvert Documentation](http://addvert.it/api/doc) for more details


== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Click 'Addvert' in the left menu and type in the E-Commerce ID and the Secret Key provided by Addvert

== Frequently asked questions ==

= How can I join Addvert? =

Visit [http://addvert.it/ecommerce](http://addvert.it/ecommerce) and signup!

== Screenshots ==

1. WP-Addvert settings page


== Changelog ==

= 1.8 =
Add more options and button themes sync
 
= 1.7 =
Introducing the [addvert] shortcode to place the button where you need
Bugfixing
  
= 1.6 =
Cambiata la posizione dell'add button 

= 1.5 =
A lot of bug fixing 

= 1.4.1 =
Debug tools to help us help you!

= 1.4 =
* Let the browser choose which connection to use for the button (http / https) to avoid unsecure elements on secure pages
* We now check if curl is available and prefer it over file_get_contents()
* If https/open_ssl are available, we now use a secure connection to send the order details to Addvert

= 1.3.1 =
* Updated compatibility for WooCommerce up to 2.1.2
  
= 1.3 =
* New, more secure, server-side tracking method

= 1.2.1 =
* Fix: Order total now escludes shipping fees 
 
= 1.2 =
* New feature: choose the Addvert button layout you prefer!
* Improvement: Updated for newer version of Addvert documentation
 
= 1.1 =
* First public release
