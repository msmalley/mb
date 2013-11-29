# Welcome to [MongoBase](http://mongobase.org) v0.8.1
### Yet another PHP Framework! :-)
---

But this one is MIT licensed and strictly uses MongoDB.

It also does it's best to make it easy for even non-technical users, especially
those already familiar with systems such as WordPress.

It does this by utilizing auto-cascading inclusion but is a little different to
your average framework in that this is a full-stack platform that offers almost
as much front-end functionality as it does behind the scenes.

However, please note that no support of any kind is currently provided as this
is being developed live, rapidly and drastically changing and not getting
updated here or even properly documented in time (until reaching v1.0).

---

It comes with several useful libraries and wrapper functions, including:

* HTML Purifier - http://htmlpurifier.org/
* PHPMailer - http://code.google.com/a/apache-extras.org/p/phpmailer/
* Mustache - http://mustache.github.com/
* Bootstrap 3 - http://getbootstrap.com/
* jQuery.js - http://jquery.com/
* LESS.js - http://lesscss.org/
* DataTables - http://datatables.net/
* Isotope.js - http://isotope.metafizzy.co/

It also features the following modules (auto-included if and when required):

* Administration Panels (not yet publicly available on GitHub)
* REST APIs (not yet publicly available on GitHub)
* Authentication
* Comments (not yet publicly available on GitHub)
* MongoDB ORM
* IMAP Mapping (not yet publicly available on GitHub)
* Front-End Forms (not yet publicly available on GitHub)
* GridFS Stored & Served Media
* Mustache Templating (by default)
* Routes
* RSS (not yet publicly available on GitHub)
* Search (not yet publicly available on GitHub)
* Stats (not yet publicly available on GitHub)

## Quick-Start Instructions
### Installation

Assuming you want an isolated default installation, simply upload the contents
of MongoBase to the root of your domain:

* /mb_app/
* /mb_config/
* /mb_core/
* /core.php
* /index.php

For added security, it is also possible to move the configuration files to a
safer location upto (by default) 3 recursive parent folders.

More information on this and other technical settings will be available soon.

In order for the framework to function properly, you must first provide an ID
for your application from within the config.ini file.

This ID must match the name of your application class found by default at
/mb_app/classes/{{app id}}.php

---

### Auto-Cascading

Auto-cascading of file inclusion is the key to simplicity with MongoBase.

If your Application ID is my-app and someone visits your-domain.com/example,
by default, MongoBase will try to do the following things:

* Include and initialize Application Class :: mb_app/classes/my-app.php
* Extend Application Class with Example :: mb_app/classes/example.php
* Include Application Functions :: mb_app/functions/my-app.php
* Include Application Data :: mb_app/data/mp-app.php
* Extend Application Data with Example :: mb_app/data/example.php
* Construct HTML Page using JSON Template :: mb_app/templates/example.php
* Compile CSS from LESS (whilst local) :: mb_app/assets/less/my-app.less
* Compile URL CSS from LESS (whilst local) :: mb_app/assets/less/example.less
* Include Application CSS :: mb_app/assets/css/my-app.css
* Include URL Specific CSS :: mb_app/assets/css/example.css
* Include Application JS :: mb_app/assets/css/my-app.js
* Include URL Specific JS :: mb_app/assets/css/example.js

All without writing a single line of code or establishing any configuration.

This allows you to use MongoBase out of the box as a flat file CMS.

Especially useful for prototyping.

## Customized Routing
### Using config.ini

Coming soon.