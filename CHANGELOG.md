# Changelist Garp 3
Here we keep track of backward-incompatible changes.

For every (necessary) backward-incompatible Garp update we create a new tag, with an incremented minor version number.

(not entirely semver-compatible, we know, but historically more compatible with how we came to Garp version 3 in the first place)

## Version 3.19.0

vlucas/phpdotenv has been upgraded from `v2.0.1` to `^v3.4`. An overview of parsing modifications can be found in [vlucas/phpdotenv/UPGRADING.md](https://github.com/vlucas/phpdotenv/blob/master/UPGRADING.md). Check your `.env` file for possible consequences.

To prevent conflicts between Garp3 and Laravel some global functions have been removed. Most of the functions could be replaced by their [Garp Functional](https://grrr-amsterdam.github.io/garp-functional) equivalent. Some need more attention. The original functions still exist in `application/removed-functions.php`. You could include that file (partially) to stay compatible, but to become compatible with Laravel you can't use that solution. `view()` and some other functions are also implemented by Laravel helpers. 

Removed functions:

- `array_get()`
- `array_get_subset()`
- `array_pluck()`
- `array_set()`
- `callMethod()`
- `callLeft()`
- `callRight()`
- `compose()`
- `concatAll()`
- `dump()`
- `getProperty()`
- `id()`
- `instance()`
- `model()`
- `noop()`
- `not()`
- `propertyEquals()`
- `psort()`
- `some()`
- `unary()`
- `view()`
- `when()`

## Version 3.18.1

Not a breaking change, but because of the huge impact on deploy performance interesting to mention nonetheless: as of this version you can configure Capistrano to not distribute assets to the CDN.   
Put the following in the stage's deploy configuration file, or the general `deploy.rb`:

```rb
# staging.rb, for example
:set distribute_assets, false
```

**Note**: obviously, if you rely on assets being on the CDN, this setting is not for you.  
This works when you have configured your `cdn.location` to be `local`, for all file types relevant to you. For instance:

```
cdn.css.location = "local"
cdn.js.location = "local"
```

## Version 3.18

The minimum-stability of Composer packages installed by Garp has changed from `dev` to `stable`. Because `prefer-stable` was in place the impact should be minimal. Nevertheless carefully check and test the changes after running `composer update` in your project.

## Version 3.17

Removes the `DefaultSortable` behavior. It caused more errors than it gave value.  
How to migrate models using this feature?

1. Check for models specifying the `order` property in their spawn configuration.
2. For every model, check their queries and add an `ORDER` clause manually.

Note: the CMS is not affected, since the order is still stored in the Javascript models.

## Version 3.16

The Zend Framework Amazon S3 service has been severed from Garp: it now uses the official AWS PHP SDK from Amazon to interact with the S3 service.  
Nothing has changed, all interfaces and outputs have remained the same, however, `cdn.s3.region` has become a *required configuration parameter*.

## Version 3.15

The minimum required PHP version has been updated to PHP7.1.  
Mostly to be able to support Garp\Functional version 3.

## Version 3.14

`teardown` on our unit test has been greatly optimized to allow for high-precision truncating. 
The `teardown` method will truncate exactly the tables that received inserts during the tests, no more, no less.

In order to use this functionality, you need to make sure your default database adapter has a profiler enabled:

```
[testing : development]

resources.db.params.profiler.enabled = true
resources.db.params.dbname = "my_test_database"
```

This way we can piggyback on the profiler to keep track of all `INSERT` queries. 
When no profiler is configured, the old behavior will still work.

One notable backward-compatible change is the removal of `getDatabaseAdapter()` from `Garp_Test_PHPUnit_TestCase`. 
It has been moved to `Garp_Test_PHPUnit_Helper`, so if you still want to use it, do so thru `$this->_helper->getDatabaseAdapter()`.

## Version 3.13

Using Capistrano, we write a `VERSION` file in the root of the project. An accompanying `Garp_Version` class is created to lookup the current version.
Note that this file will usually *not* exist in `development` environments, so don't write code which relies on it.
This deprecates the use of `Garp_Semver`. Since this is mostly used to aid the `git flow` helper commands, this version of Garp will be most compatible with a `One Flow` setup. See [OneFlow - a Git branching model and workflow
](http://endoflineblog.com/oneflow-a-git-branching-model-and-workflow) for more information.

In addition, some spring cleaning has been done:

- `Garp_Util_AssetUrl` has been greatly simplified. Either you use a `rev-manifest` file or you get a versioned query string added to the file (containing the version stored in `VERSION`). All code related to using versioned build paths has been removed.
- `git flow`-related code has been deprecated. This means `g feature`, `g hotfix` and `g release` are no longer valid Garp CLI Commands.
- `Garp_Semver` has been removed.

## Version 3.12

In order to update the phpunit dependency to a modern version, we finally dropped support for php 5.3 and jumped all the way up to php 7.

Most importantly for implementors: `Garp_Test_PHPUnit_ControllerTestCase` has been removed from Garp. It extended `Zend_Test_PHPUnit_ControllerTestCase`, which was the reason we couldn't upgrade phpunit.


## Version 3.11

Previously, we used a boolean in `Zend_Registry` to indicate CMS context:

```php
Zend_Registry::set('CMS', true);
```

This setting is _deprecated_. Garp now happily continues life with one less global variable to worry about.

If you want to know if a model's being used in CMS context, you can access the new `isCmsContext` method of `Garp_Model_Db`. For instance in a behavior's callback:

```php
function afterFetch($args) {
  $model = $args[0];
  $isCms = $model->isCmsContext();
}
```
If your app uses `Zend_Registry::get('CMS')` it will need to refactor that bit.

### Version 3.11.19

Since credentials for CDNs moved to `.env` files, `g cdn distribute` is no longer able to distribute to the right environment, since the credentials are not known across envs anymore.
[12g](https://github.com/grrr-amsterdam/12g) is developed to fix this. It can read configuration from other environments, among other things.  
Its output can be piped into Garp to distribute to the right environment, like this:

```
12g env list -e staging -o json | g cdn distribute
```

### Version 3.11.30

A little late to the party, but as of this version `12g` is a requirement for Garp deployment using Capistrano. The `--to` parameter of `g cdn distribute` is officially deprecated and will trigger a warning.


## Version 3.10

Asset URL generation has changed once more.
Fortunately, is has been greatly simplified. There's no more attempted intelligence in constructing an s3 URL at either HTTPS or HTTP, with or without a bucket, on a custom domain, et cetera.

All you have to do is configure:

```
cdn.baseUrl = "https://s3-eu-west-1.amazonaws.com/bucket"
```

That's it. Provide everything up to the paths that are fed to the `assetUrl` helper at runtime.  

Note that nothing changed to the path manipulation logic, so versioning or hashing still works.  
Also, local exceptions for assets are still allowed:

```
cdn.baseUrl = "https://s3-eu-west-1.amazonaws.com/bucket"
cdn.css.location = "local"
```

This still puts CSS files on a relative path (`/css/base.css`) but uploaded files will be loaded from the external domain (`https://s3-eu-west-1.amazonaws.com/bucket/uploads/images/funny-monkey.gif`).

## Version 3.9.61

When memcached is configured, it HAS to be running, otherwise an exception is thrown. Sounds fair, right? In the past however, Garp would fallback to the `BlackHole` cache if Memcache was unavailable.   
We want to be explicit in our behaviors rather than implicit, therefore this was changed. You can simply configure Memcached to be `NULL` and no connection attempts are made.

```
// @file application/configs/environment.php
$memcachedPorts = array(
    'production'  => 11211,
    'staging'     => 11211,
    'development' => null,
    'testing'     => null
);

// @file tests/TestHelper.php
define('APPLICATION_ENV', 'testing');
define('MEMCACHE_HOST', null);
define('MEMCACHE_PORT', null);
```

Note that this is not considered to be a breaking change because historically, all production and staging environments are configured correctly. It's only local development machines that are hurt by this. The fix is so easy that I trust devs to make quick work of this nuisance.

## Version 3.9.53

Garp will look for `composer.phar` in the shared folder when deployed with Capistrano. If it's there, it will execute `composer install`. This allows you to put the entire `vendor` folder inside `.gitignore`.

[Take a look at this script to find out how to install Composer on the server](https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md)

Note: in a clustered environment every webserver should have its own `composer.phar`.  
Also: development machines are pretty rad and run things like PHP 7. Webservers sometimes are kinda wack and run things like PHP 5.3.

You can configure the following in `composer.json` to make sure you never install dependencies that cannot run in the target environment:

```
"config": {
  "platform": {
    "php": "5.3.3"
  }
}

```

## Version 3.9.40

Tiny change that should not really affect you but might still: the cache directory for HTMLPurifier must be configured explicitly.

```
htmlFilterable.cachePath = APPLICATION_PATH "/data/cache/htmlpurifier"
```

Note that it's also recommended to specify something nested _inside_ `application/data/cache`. In the past its `URI`, `HTML` and `CSS` directories ended up directly inside `/application/data/cache`, but in order to namespace everything a bit more neatly, let's use a dedicated folder. (incidentally: this is also the directory that Capistrano will auto-create on the web server)

If not configured, no path will be specified to HTMLPurifier.

## Version 3.9

Translatable behavior has been refactored. (See commit https://github.com/grrr-amsterdam/garp3/commit/4d200b62d5d70f8b0a08499e683d10c47baaf6ef)

Because of this change, the database of multilingual projects needs an update. The fallback system is gone, so data needs to be migrated from the default language to all others.   
A CLI command is provided for this purpose:

```
g i18n populateLocalizedRecords
```

Note that the process reads every multilingual record in your project and migrates its content to the accompanying record in every other language. It might take a long time to finish.

Also, a tiny change is made to reflect recent Gulp setups. Projects are now required to have both a `assets.js.root` and a `assets.js.build` directory.  
It used to be just `root`, but as of now that's reserved for your Javascript source files as opposed to actual compiled files. Example:

```
assets.js.root = "/js/src"
assets.js.build = "/js/build/prod"
```

:exclamation: *Make sure that je new js build path is also updated in the gulpfile!*

That's it! You're done. ☕

## Version 3.8
Garp moved to a Composer package.   
This changes Garp a lot in that it has be able to stand on its own when tested for instance. A lot of unit tests broke because they relied on Garp being part of a bigger project. These tests have been moved out of Garp (conceptually you could say they're integration tests) and into a Garp sandbox project.

*Make sure you update Golem before updating Garp!*

[To migrate to Garp composer version, see the accompanying wiki article.](garp-composer)

That should be your step 1 in upgrading. Just make sure you require `^3.8.0`. Run `composer update` to install.   

:exclamation: Look into [this issue](empty-composer) if you're getting empty Composer packages on your web server (most often noted by an error stating `Zend_Registry` cannot be found when deploying).

Secondly:

- Update `composer.json` in the host project to autoload its own `App_` and `Model_` namespaces.

```
    "autoload": {
        "psr-0": {
            "App_": "library/",
            "Golem_": "library/",
            "Model_": "application/modules/default/"
        }
    }
```

`Garp_Loader` is *deprecated* in favor of Composer's autoloader.

- Create `tests/TestHelper.php`

```php
<?php
date_default_timezone_set('Europe/Amsterdam');
define('APPLICATION_ENV', 'testing');
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));

error_reporting(-1);
ini_set('log_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 'stderr');

$garpRoot = BASE_PATH . '/vendor/grrr-amsterdam/garp3';
require_once $garpRoot . '/application/init.php';

$application = new Garp_Application(
	APPLICATION_ENV,
	APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap();
Zend_Registry::set('application', $application);

$mem = new Garp_Util_Memory();
$mem->useHighMemory();
```

- If you're not already loading `vendor/autoload.php` in your `index.php`, make sure you do:

```php
// file: public/index.php
require_once('../vendor/autoload.php');
```

- Rename `application/modules/default/models` to `application/modules/default/Model` (to support `psr-0` style autoloading).
- Run `g models migrateGarpModels`. Garp models have moved from the `G_Model_` namespace to the `Garp_Model_Db_` namespace. Make sure your project doesn't reference the former still.
It's possible your `AuthLocal` model is not correctly configured to extend from Garp. Make sure `"module": "garp"` is in `AuthLocal.json` and its extended model extends from `Garp_Model_Db_AuthLocal`.
- You can remove `library/PHPExcel` and `library/Zend`: they're required by Composer.

## Versie 3.7
Alle tabellen worden vanaf deze versie in lowercase gegenereerd.
Het is dus zaak het volgende stappenplan aan te houden:

- hernoem je tabellen naar de lowercase variant. (volledig lowercase, ```_MovieGenre``` wordt dus ```_moviegenre```)
- draai ```g spawn```
- herschrijf alle referenties naar de oude tabelnamen. In Ack kun je de volgende query gebruiken: ```[ `\'\(]+Cinema[ `\'\.]+``` om ze te vinden.

Optioneel: je kunt in ```app.ini``` de parameter ```app.domain``` vullen, voor FullUrl helpers e.d.


## Versie 3.6 (Git)
(ik weet niet zeker of we dit 3.6 noemen!)

### cms-stylesheets.phtml partial + cms.css
@harmenjanssen (12-12-2012)

- cms.css is verplaatst naar Sass, d.w.z. dat de echte CSS file dus in ```/public/css/compiled``` staat. In het CMS wordt dit pad gebruikt. Mocht je nou geen icoontjes zien bij de datatypes kijk dan even of deze file wel goed geladen wordt.
- er is een partial bijgekomen: ```cms-stylesheets.phtml```. Hierin kun je app-specifieke stylesheets kwijt. Bovenstaande cms.css wordt daar ook in gezet. Met de komende WYSIWYG editor is dat heel handig omdat je nog wel wat custom styles zou moeten kunnen toevoegen (zoals een @font-face dingetje van Google of Typekit). Als deze partial er niet is krijg je logischerwijs een dikke error.

## Versie 3.5
svn.grrr.nl/garp3/code/branches/3_5

### Storage type Cookie
Toevoegen aan ```core.ini```:
```
store.type = "Cookie"
```


### Volgorde class hiërarchie veranderd voor Garp modellen
Let op! Modellen die afstammen van een Garp model afstammen moeten iets anders inheriten dan het geval was:
```
Model_Image > G_Model_Image > Model_Base_Image
```
in plaats van:
```
Model_Image > Model_Base_Image > G_Model_Image
```
Je zult dus je app-specifieke modellen aan moeten passen.

### App-specifieke cms icons
@davidspreekmeester
public/css/cms-icons.css heet nu
public/css/cms.css

### [application.ini] Auth config notatie
@harmenjanssen
We hanteren een nieuwe syntax voor auth variabelen, specifiek voor het auth.login.* en auth.login.register.* stukje. In de scaffoldversie van application.ini kun je het juiste formaat al vinden. Voorbeeld:
```
auth.login.view = “login.phtml”
```

### [database] Auth tabellen hernoemd
@harmenjanssen
We zijn van auth_facebook en auth_local en auth_twitter enzovoorts gegaan naar de Garp 3.4 nieuwe stijl namen AuthFacebook, AuthLocal, AuthTwitter etc.

### [database] Aanpassing benaming relatiekolommen in homofiele relaties
@davidspreekmeester
Waar een homofiele relatietabel zoals `_UserUser` voorheen de kolommen `user_id1` en `user_id2` zou hebben, gebruiken we vanaf 3.5 `user1_id` en `user2_id`.

### Consistentere configuratie voor hasAndBelongsToMany relaties
@davidspreekmeester
Voorheen hadden we het bestand `_HabtmRelations.json`, waar alle `hasAndBelongsToMany` relaties in gedefinieerd worden. Vanaf 3.5 dienen deze relaties in de configuratie van het eerste model in de relatie (alfabetisch gezien) te gebeuren. De configuratie is hetzelfde als voor andere typen relaties, maar dan met type: hasAndBelongsToMany. Zie de Spawner docs voor uitgebreidere info.

### Aanpassing kolom Video.author naar Video.video_author
@harmenjanssen
Omdat de virtuele kolom Author ook al wordt toegevoegd in de joint view.
Pas bij bestaande data eerst de kolom in de database aan (zodat je geen data kwijt raakt).
Pas daarna de Spawn config aan en draai een Spawn.

### Opzet modules is veranderd, LayoutBroker plugin is geïntroduceerd
@harmenjanssen
Pas het volgende aan in **application.ini**:
```
-resources.layout.layoutPath = APPLICATION_PATH "/modules/default/views/layouts"
+resources.layout =
+resources.frontController.plugins.LayoutBroker = "Garp_Controller_Plugin_LayoutBroker"

-resources.frontController.moduleDirectory = APPLICATION_PATH "/modules"
+resources.frontController.moduleDirectory[] = APPLICATION_PATH "/modules"
+resources.frontController.moduleDirectory[] = APPLICATION_PATH "/../garp/application/modules"
```
De symlink "g" in **APPLICATION_PATH "/modules"** moet vanaf nu wijzen naar ```garp/application/modules/g```.


## Versie 3.4
svn.grrr.nl/garp3/code/branches/3_4

### JS models / Base JS models
@peter
Geen support meer voor xtype = ‘compositefield’. Gebruik nu xtype = ‘fieldset’ met layout = ‘hbox’ om visueel de zelfde rendering te verkrijgen.


### Spawnen Base model goo (minified en geclusterd)
@davidspreekmeester
De aanroep in models.phtml is veranderd. Dit bestand wordt nu nog maar gedeeltelijk gespawnd. Er moeten wat Ext aanroepen voor en na de gespawnde aanroepen. Zie

garp/scripts/scaffold/application/modules/default/views/scripts/partials/models.phtml

voor de juiste syntax. Dit kun je gebruiken als inhoud voor models.phtml in de applicatie, een Spawn-sessie daarna zal de rest aanvullen.



## versie 3.3
svn.grrr.nl/garp3/code/branches/3_3
revision 3357
25 november 2011

### Rollen / Rechten voor CMS
@harmenjanssen
Alle modellen moeten in acl.ini genoemd worden zodat ze in ACL bekend worden en door de Content Manager gebruikt worden. Als ze er niet instaan kun je ze niet editen in het CMS.
Bijvoorbeeld: (bij resources)
acl.resources.G_Model_Video.id = "G_Model_Video"
acl.resources.Model_BlogPost.id = "Model_BlogPost"
En dan ook nog: (bij permissions)
acl.resources.G_Model_Video.allow.all.roles = "admin"
acl.resources.Model_BlogPost.allow.all.roles = "admin"
Zie n8.nl voor een recente implementatie.


### Caching refactor
@harmenjanssen
application/configs/cache.ini moet aanwezig zijn. Hoeft niet per sé gevuld te zijn, maar er moeten  wel entries voor production / staging / development in staan . Zie n8_garp3 repository voor een voorbeeld van de daadwerkelijke implementatie.




## versie 3.2

> revision ?
datum?
Asset version
@harmenjanssen
In public/.htaccess dient:
```RewriteRule ^([0-9]+)/(css|js|media)/(.*) $2/$3 [L]```
vervangen te worden door:
```RewriteRule ^(\d+)/(css|js|media)/(.*) $2/$3 [L]```



## versie 3.1

> revision 2684
20 september 2011
Image / storage refactor
@davidspreekmeester
Snippet.image is een veld met een filename, dit moet Snippet.image_id worden, die verwijst naar een image record.
De entries voor cdn in application.ini zijn veranderd.
De benodigde entries zijn:
```
cdn.type = 'local'
cdn.domain = HTTP_HOST

;cdn.type = 's3'
;cdn.domain = "grrr.nl.s3.amazonaws.com"

cdn.extensions = "jpg,jpeg,gif,png,zip,pdf,xls,xlsx,csv"

cdn.path.upload.image = "/uploads/images"
cdn.path.upload.document = "/uploads/documents"
cdn.path.static.image = "/2011/media/images"
cdn.path.static.document = "/2011/documents"

;cdn.s3.apikey = "XXXXXX"
;cdn.s3.secret = "XXXXXX"
;cdn.s3.bucket = "grrr.nl"
```

De volgende entries voor de setup van images in **application.ini** kunnen verwijderd worden:
```
image.uri.scaled = '/uploads/images/scaled/'
image.uri.upload = '/uploads/images/'
image.path.upload = APPLICATION_PATH "/../public/uploads/images/"
image.path.scaled = APPLICATION_PATH "/../public/uploads/images/scaled/"
image.host.static = "http://" HTTP_HOST
```
In plaats van CDN moet in Javascript IMAGES_CDN en DOCUMENTS_CDN gezet zijn.
