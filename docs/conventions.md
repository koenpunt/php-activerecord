# Conventions

Because we have embraced a convention over configuration philosophy, using our library is not painful. The conventions are easy to remember which will also contribute to stream-lining your productivity as a developer.

If you've already seen the [Configuration / Setup](configuration-setup.md) guide, then you know that there isn't much to it. Therefore, using PHP ActiveRecord mainly requires you to acquaint yourself with some simple conventions. Once you've done that, you can move on to the more advanced features.

ActiveRecord assumes the following conventions:

```php
# name of your class represents the singular form of your table name.
class Book extends ActiveRecord\Model {}

# your table name would be "people"
class Person extends ActiveRecord\Model {}
e primary key of your table is named "id".

class Book extends ActiveRecord\Model {}

# SELECT * FROM `books` where id = 1
Book::find(1);
```

## Overriding conventions

Even through ActiveRecord prefers to make assumptions about your table and primary key names, you can override them. Here is a simple example showing how one could configure a specific model.

```php
class Book extends ActiveRecord\Model
{
  # explicit table name since our table is not "books"
  static $table_name = 'my_book';

  # explicit pk since our pk is not "id"
  static $primary_key = 'book_id';

  # explicit connection name since we always want our test db with this model
  static $connection = 'test';

  # explicit database name will generate sql like so => my_db.my_book
  static $db = 'my_db';
}
```
