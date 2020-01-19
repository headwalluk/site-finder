# Site Finder

Scan local directories for website installations and present them as a list with meta data and site/admin links.

![Site Finder Results Sample](https://github.com/headwalluk/site-finder/blob/master/site-finder-results-sample-01.png?raw=true)

## What It Actually Does

Place the files in a document root (e.g. /var/www/html or /home/USER_NAME/public_html) and Site Finder will scan sub-directories looking for websites/apps. It will display all discovered sites in a list, with summary information for each site.

If you have a development machine with lots of websites on it then this is a useful tool for presenting them in a simple list.

## Installation

Copy all the files from within www-root into your document root.

Point your browser at your server that's hosting your document root.

### Configuring with site-finder-settings.json

**Note** The current working directory will automatically have its child directories scanned for sites so there is not need to add it to site-finder-settings.json.

Example *site-finder-settings.json* using Apache's userdir module.

```json
{
  "number_of_columns": 2,
  "directories": [
    {
      "path": "/home/USER_NAME/public_html",
      "url_suffix": "~USER_NAME",
      "is_scanned": false,
      "are_children_scanned": true
    }
  ]
}
```

Example *site-finder-settings.json* with no additional directories configured, and with a 3 column output.

```json
{
  "number_of_columns": 3,
  "directories": []
}
```

## Dependancies

* Tested with Apache 2.4.38 from Debian Buster.
* PHP for the back-end - tested with version **PHP 7.3.11** from Debian Buster.
* The WordPress will use php-mysql to connect to WordPress installations and extract simple meta data.

## Extending Site Finder with Plug-Ins

Site Finder uses simple plug-ins to scan directories for sites/apps. Site Finder is still in development, so the plug-in structure is subject to change.

Look at the WordPress plug-in for an example of how to create your own plug-in.
