# Joomla 1.5 reCaptcha Plugin

This plugin integrates reCaptcha into Joomla 1.5 and should also can be used in J1.0 (not tested).
I know, better updating to newer Joomla versions, but there are some existing J1.5 sites, which may find this usefull to prevent SPAM.
The plugin is based on https://code.google.com/p/joomla-recaptcha/.

* https://www.milkycode.com
* Like us on Facebook: https://www.facebook.com/milkycode

## Installation

Install via the standard Joomla installer.

## Configuration

In order to properly use Recaptcha, you will need to create an account at the recaptcha site. Once you have finished, copy the public and private keys for your site and then visit the Joomla Administrator.

### Joomla 1.5

* Go to Extensions > Plugin Manager
* Click the System - Recaptcha plugin.

### Joomla 1.0

* Go to Mambots > Site Mambots.
* Click the System - Recaptcha plugin (you may have to go the last page)

The parameters on the right provide textfields for the public and private keys.

### Ajax mode

Ajax mode is enabled by default. It helps avoid problems with "Operation Aborted" errors in IE6 and IE7. You can try to change it to Off and see what happens.

### Add to Contact Page J1.5

Automatically adds recaptcha to the default contact page. Can be deactivated in plugin settings.

### Add to Contact Page J1.0

In order to add a captcha to the Contact page in Joomla 1.0, you must add an event in your template file.
The 1.0 implementation depends on a custom event 'onTemplateDisplay'. If you would like a captcha on a standard Joomla contact page, you must first enable the feature in the plugin parameters, and then add the following line at the beginning of your templates index.php file:

```
<?php $_MAMBOTS->trigger('onTemplateDisplay'); ?>
```

## Use in own Components

The Recaptcha plugin can be used in your own components easily. The only requirement is that the plugin is installed and enabled.

The API is exposed through a singleton object, 'ReCaptcha'. The processing happens automatically, so all that you need to do as a programmer is access its properties. All properties are accessed through the get method.

### Methods

#### get

This method must be called statically. An example would be:

``` php
ReCaptcha::get('html');
```

### Properties

#### html

The html string to add to your form.

#### submitted

A boolean that indicates whether or not the form which the ReCaptcha is in has been submitted.

#### success

A boolean that indicates if the user entered the phrase correctly.

### Full example

``` php
// inside you form (e.g. registration.html.php):
<?php echo ReCaptcha?::get('html'); ?>
```

``` php
// Then your registration.php use the following near the top of your save() function;
if (ReCaptcha::get('submitted')) {
    if (!ReCaptcha::get('success')) {
        echo '<p>The Captcha was entered incorrectly. Please try again.</p>';
        return;
    }
}
```
