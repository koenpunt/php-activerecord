# Validations

Validations offer a simple and powerful pattern to ensure the integrity of your data. By declaring validations on your models, you can be certain that only valid data will be saved to your database. No longer will you need to recall where you put that function which verifies the legitimacy of an e-mail and whether or not it will stop the record fom being saved. With validations, if your data is invalid, ActiveRecord will take care of marking the record as invalid instead of writing it to the database.

Validations will run for the following methods normally:

```php
$book->save();
Book::create();
$book->update_attributes(array('title' => 'new title'));
```

The following will skip validations and save the record:

```php
$book->update_attribute();
$book->save(false); # anytime you pass false to save it will skip validations
```

## Is my model valid or not?

You can determine whether or not your model is valid and can be saved to the database by issuing one of these methods: Model::is_valid or Model::is_invalid. Both of these methods will run the validations for your model when invoked.

```php
class Book extends ActiveRecord\Model
{
  static $validates_presence_of = array(
    array('title')
  );
}

# our book won't pass validates_presence_of
$book = new Book(array('title' => ''));
echo $book->is_valid(); # false
echo $book->is_invalid(); # true
```

If validation(s) fails for your model, then you can access the error message(s) like so. Let's assume that our validation was validates_presence_of.

```php
class Book extends ActiveRecord\Model
{
  static $validates_presence_of = array(
    array('title')
  );
}

$book = new Book(array('title' => ''));
$book->save();
$book->errors->is_invalid('title'); # => true

# if the attribute fails more than 1 validation,
# you would get an array of errors below

echo $book->errors->on('title'); # => can't be blank
```

Now let's assume our model failed two validations: validates_presence_of and validates_size_of.

```php
class Book extends ActiveRecord\Model
{
  static $validates_presence_of = array(
    array('title')
  );

  static $validates_size_of = array(
    array('title', 'within' => array(1, 20))
  );
}

$book = new Book(array('title' => ''));
$book->save();
$book->errors->is_invalid('title'); # true

print_r($book->errors->on('title'));

# which would give us:

# Array
# (
#   [0] => can't be blank
#   [1] => is too short (minimum is 1 characters)
# )

# to access errors for all attributes, not just 'title'
print_r($book->errors->full_messages());
```

## Commonalities

Validations are defined with a common set of options and some of them have specific options. As you've seen above, creating a validation is as simple as declaring a static validation variable in your model class as a multi-dimensional array (to validate multiple attributes). Each validation will require you to put the attribute name in the 0 index of the array. You can configure the error message by creating a message key with the message string as the value. You can also add an option which will only run the validation on either creation or update. By default, your validation will run everytime Model#save() is called.

```php
class Book extends ActiveRecord\Model
{
  # 0 index is title, the attribute to test against
  # message is our custom error msg
  # only run this validation on creation - not when updating
  static $validates_presence_of = array(
    array('title', 'message' => 'cannot be blank on a book!', 'on' => 'create')
  );
}
```php

In some validations you may use: in, is within. In/within designate a range whereby you use an array with the first and second elements representing the beginning and end of the range respectively. Is represents equality.

Common options available to all validators:

- `on`: run the validator during "save", "update"
- `allow_null`: allow null to satisfy the validation
- `allow_blank`: allow a blank string to satisfy the validation
- `message`: specify a custom error message

## Available validations

There are a number of pre-defined validation routines that you can declare on your model for specific attributes.

`validates_presence_of`
`validates_size_of`
`validates_length_of`
`validates_(in|ex)clusion_of`
`validates_format_of`
`validates_numericality_of`
`validates_uniqueness_of`
`validate *custom`

### validates_presence_of

This is probably the simplest of all the validations. It will make sure that the value of the attribute is not null or a blank string. Available options:

- `message`: default: can't be blank

```php
class Book extends ActiveRecord\Model
{
  static $validates_presence_of = array(
    array('title'),
    array('cover_blurb', 'message' => 'must be present and witty')
  );
}

$book = new Book(array('title' => ''));
$book->save();

echo $book->errors->on('cover_blurb'); # => must be present and witty
echo $book->errors->on('title'); # => can't be blank
```

### validates_size_of / validates_length_of

These two validations are one and the same. The purpose is to validate the length in characters of a given attribute. Available options:

- `is`: attribute should be exactly n characters long
- `in/within`: attribute should be within an range array(n, m)
- `maximum/minimum`: attribute should not be above/below respectively

Each of the options has a particular message and can be changed.

- `is`: uses key `wrong_length`
- `in/within`: uses keys `too_long` & `too_short`
- `maximum/minimum`: uses keys `too_long` & `too_short`

```php
class Book extends ActiveRecord\Model
{
  static $validates_size_of = array(
    array('title', 'within' => array(1, 5), 'too_short' => 'too short!'),
    array('cover_blurb', 'is' => 20),
    array('description', 'maximum' => 10, 'too_long' => 'should be short and sweet')
  );
}

$book = new Book;
$book->title = 'War and Peace';
$book->cover_blurb = 'not 20 chars';
$book->description = 'this description is longer than 10 chars';
$ret = $book->save();

# validations failed so we get a false return
if ($ret == false)
{
  # too short!
  echo $book->errors->on('title');

  # is the wrong length (should be 20 chars)
  echo $book->errors->on('cover_blurb');

  # should be short and sweet
  echo $book->errors->on('description');
}
```

### validates_(in|ex)clusion_of

As you can see from the names, these two are similar. In fact, this is just a white/black list approach to your validations. Inclusion is a whitelist that will require a value to be within a given set. Exclusion is the opposite: a blacklist that requires a value to not be within a given set. Available options:

- `in/within`: attribute should/shouldn't be a value within an array
- `message`: custom error message

```php
class Car extends ActiveRecord\Model
{
  static $validates_inclusion_of = array(
    array('fuel_type', 'in' => array('petroleum', 'hydrogen', 'electric')),
  );
}

# this will pass since it's in the above list
$car = new Car(array('fuel_type' => 'electric'));
$ret = $car->save();
echo $ret # => true

class User extends ActiveRecord\Model
{
  static $validates_exclusion_of = array(
    array('password', 'in' => array('god', 'sex', 'password', 'love', 'secret'),
      'message' => 'should not be one of the four most used passwords')
  );
}

$user = new User;
$user->password = 'god';
$user->save();

# => should not be one of the four most used passwords
echo $user->errors->on('password');
```

### validates_format_of

This validation uses preg_match to verify the format of an attribute. You can create a regular expression to test against. Available options:

- `with`: regular expression
- `message`: custom error message

```php
class User extends ActiveRecord\Model
{
  static $validates_format_of = array(
    array('email', 'with' =>
      '/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/')
    array('password', 'with' =>
      '/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message' => 'is too weak')
  );
}

$user = new User;
$user->email = 'not_a_real_email.com';
$user->password = 'notstrong';
$user->save();

echo $user->errors->on('email'); # => is invalid
echo $user->errors->on('password'); # => is too weak
```

### validates_numericality_of

As the name suggests, this gem tests whether or not a given attribute is a number, and whether or not it is of a certain value. Available options:

- `only_integer`: value must be an integer (e.g. not a float), `message`: "is not a number"
- `even`, `message`: "must be even"
- `odd`, `message`: "must be odd"
- `greater_than`: >, message: "must be greater than %d"
- `greater_than_or_equal_to`: >=, message: "must be greater than or equal to %d"
- `equal_to`: ==, `message`: "must be equal to %d"
- `less_than`: <, `message`: "must be less than %d"
- `less_than_or_equal_to`: <=, `message`: "must be less than or equal to %d"

```php
class Order extends ActiveRecord\Model
{
  static $validates_numericality_of = array(
    array('price', 'greater_than' => 0.01),
    array('quantity', 'only_integer' => true),
    array('shipping', 'greater_than_or_equal_to' => 0),
    array('discount', 'less_than_or_equal_to' => 5, 'greater_than_or_equal_to' => 0)
  );
}

$order = new Order;
$order->price = 0;
$order->quantity = 1.25;
$order->shipping = 5;
$order->discount = 2;
$order->save();

echo $order->errors->on('price'); # => must be greater than 0.01
echo $order->errors->on('quantity'); # => is not a number
echo $order->errors->on('shipping'); # => null
echo $order->errors->on('discount'); # => null
```

### validates_uniqueness_of

Tests whether or not a given attribute already exists in the table or not.

`message`: custom error message

```php
class User extends ActiveRecord\Model
{
  static $validates_uniqueness_of = array(
    array('name'),
    array(array('blah', 'bleh'), 'message' => 'blah and bleh!')
  );
}

User::create(array('name' => 'Tito'));
$user = User::create(array('name' => 'Tito'));
$user->is_valid(); # => false
```

### validate (custom)

Generic method allows for custom business logic or advanced validation. You can add your own errors to errors object. This does not take any parameters. You place this logic in a public method named validate. (This feature is available since v1.1 or nightly builds.)

```php
class User extends ActiveRecord\Model
{
 public function validate()
 {
   if ($this->first_name == $this->last_name)
   {
     $this->errors->add('first_name', "can't be the same as Last Name");
     $this->errors->add('last_name', "can't be the same as First Name");
   }
 }
}

$user = User::create(array('first_name' => 'Tito', 'last_name' => 'Tito'));
$user->is_valid(); # => false
echo $user->errors->on('first_name'); # => can't be the same as Last Name
```
