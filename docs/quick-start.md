# Quick Start

This guide will show you the bare essentials to get up and running with php-activerecord. I will assume you have downloaded the library into your include_path in a directory called php-activerecord.

The first steps are to include the library and define our database connection:

```php
ActiveRecord\Config::initialize(function($cfg)
{
    $cfg->set_model_directory('models');
    $cfg->set_connections(array(
        'development' => 'mysql://username:password@localhost/database_name'
    ));
});
```

Next, lets create a model for a table called users. We'll save this class in the file models/User.php

```php
class User extends ActiveRecord\Model
{
}
```

That's it! Now you can access the users table thru the User model.

```php
# create Tito
$user = User::create(array('name' => 'Tito', 'state' => 'VA'));

# read Tito
$user = User::find_by_name('Tito');

# update Tito
$user->name = 'Tito Jr';
$user->save();

# delete Tito
$user->delete();
```

That's it! Pretty simple. Check out the [Frameworks](frameworks.md) page for more in depth guides on setting up php-activerecord in specific frameworks.

