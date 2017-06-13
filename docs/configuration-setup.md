# Configuration Setup

Setup is very easy and straight-forward. There are essentially only two configuration points you must concern yourself with:

1. Setting the model auto_load directory.
2. Configuring your database connections.

By setting the model auto_load directory, you are telling PHP where to look for your model classes. This means that you can have an app/folder structure of your choice as long as you have a real directory that holds your model classes. Each class should have it's own php file that is the same name of the class with a .php extension of course.

There are two ways you can initialize your configuration options. The easiest path is wrapping the calls in a closure which is sent through the Config initializer method. This is a neat and clean way to take advantage of PHP's new closure feature.

```php
# inclue the ActiveRecord library
require_once 'php-activerecord/ActiveRecord.php';

ActiveRecord\Config::initialize(function($cfg)
{
  $cfg->set_model_directory('/path/to/your/model_directory');
  $cfg->set_connections(array('development' =>
    'mysql://username:password@localhost/database_name'));
});
```

That's it! ActiveRecord takes care of the rest for you. It does not require that you map your table schema to yaml/xml files. It will query the database for this information and cache it so that it does not make multiple calls to the database for a single schema.

If you aren't feeling fancy, you can drop the closure and access the ActiveRecord\Config singleton directly.

```php
$cfg = ActiveRecord\Config::instance();
$cfg->set_model_directory('/path/to/your/model_directory');
$cfg->set_connections(array('development' =>
  'mysql://username:password@localhost/database_name'));
```

## Default connection

The development connection is the default by convention. You can change this by setting a new default connection based off of one of the connections you passed to set_connections.

```php
$connections = array(
  'development' => 'mysql://username:password@localhost/development',
  'production' => 'mysql://username:password@localhost/production',
  'test' => 'mysql://username:password@localhost/test'
);

# must issue a "use" statement in your closure if passing variables
ActiveRecord\Config::initialize(function($cfg) use ($connections)
{
  $cfg->set_model_directory('/path/to/your/model_directory');
  $cfg->set_connections($connections);

  # default connection is now production
  $cfg->set_default_connection('production');
});
```

## Multi-connections

You can easily configure ActiveRecord to accept multiple database connections. All you have to do is specify the connection in the given model that should be using a different database.

```php
$connections = array(
  'development' => 'mysql://username:password@localhost/development',
  'pgsql' => 'pgsql://username:password@localhost/development',
  'sqlite' => 'sqlite://my_database.db',
  'oci' => 'oci://username:passsword@localhost/xe'
);

# must issue a "use" statement in your closure if passing variables
ActiveRecord\Config::initialize(function($cfg) use ($connections)
{
  $cfg->set_model_directory('/path/to/your/model_directory');
  $cfg->set_connections($connections);
});
```

Your models would look like the following.

```php
# SomeOciModel.php
class SomeOciModel extends ActiveRecord\Model
{
  static $connection = 'oci';
}

# SomeSqliteModel.php
class SomeSqliteModel extends ActiveRecord\Model
{
  static $connection = 'sqlite';
}
```

You could also have a base 'connection' model so all sub-classes will inherit the db setting.

```php
# OciModels.php
abstract class OciModels extends ActiveRecord\Model
{
  static $connection = 'oci';
}

# AnotherOciModel.php
class AnotherOciModel extends OciModels
{
   # automatically inherits the oci database
}
```

## Setting the encoding

The character encoding can be specified in your connection parameters:

```php
$config->set_connections(array(
  'development' => 'mysql://user:pass@localhost/mydb?charset=utf8')
);
```
