# Utilities

ActiveRecord offers numerous ways to make your life easier by adding some interesting features to your models.

## Delegators

This is similar to attribute aliasing, except that it works via your associations. You can alias an attribute on your model to use a particular attribute on an association. Let's take a look.

```php
class Person extends ActiveRecord\Model {
  static $belongs_to = array(array('venue'), array('host'));
  static $delegate = array(
    array('name', 'state', 'to' => 'venue'),
    array('name', 'to' => 'host', 'prefix' => 'host'));
}

$person = Person::first();
$person->state     # same as calling $person->venue->state
$person->name      # same as calling $person->venue->name
$person->host_name # same as calling $person->host->name
```

## Attribute setters

Setters allow you to define custom methods for assigning a value to one of your attributes. This means you can intercept the assign process and filter/modify the data to your needs. This is helpful in a situation such as encrypting user passwords. Normally, you define a setter which does not carry the same name as your attribute, but you can set your attribute inside of the method. In the example below, $user->password is a virtual attribute: if you try to read/access the attribute instead of assign, an UndefinedPropertyException will be thrown.

```php
class User extends ActiveRecord\Model {

  # A setter method must have set_ prepended to its name to qualify.
  # $this->encrypted_password is the actual attribute for this model.
  public function set_password($plaintext) {
    $this->encrypted_password = md5($plaintext);
  }
}

$user = new User;
$user->password = 'plaintext';  # will call $user->set_password('plaintext')
# if you did an echo $user->password you would get an UndefinedPropertyException
```

If you define a custom setter with the same name as an attribute then you will need to use assign_attribute() to assign the value to the attribute. This is necessary due to the way Model::__set() works. For example, assume 'name' is a field on the table and we're defining a custom setter called 'name':

```php
class User extends ActiveRecord\Model {

 # INCORRECT:
 # function set_name($name) {
 #   $this->name = strtoupper($name);
 # }

 public function set_name($name) {
   $this->assign_attribute('name', strtoupper($name));
 }
}

$user = new User;
$user->name = 'bob';
echo $user->name; # => BOB
```

## Attribute getters

Getters allow you to intercept attribute/property value retrieval on your models. They are defined in a similar manner to setters. See `Model::__get` for details.

## Aliased attributes

This option is fairly straight-forward. An aliased attribute allows you to set/get the attribute via a different name. This comes in handy when you have terrible field names like field_one, field_two, or for legacy tables. In this example, the alias first_name is created to reference the existing field person_first_name.

```php
class Person extends ActiveRecord\Model {
  static $alias_attribute = array(
    'first_name' => 'person_first_name',
    'last_name' => 'person_last_name');
}

$person = Person::first();
echo $person->person_first_name; # => Jax

$person->first_name = 'Tito';
echo $person->first_name; # => Tito
echo $person->person_first_name; # => Tito
```

## Protected attributes

Blacklist of attributes that cannot be mass-assigned. Protecting these attributes allows you to avoid security problems where a malicious user may try to create additional post values. This is the opposite of accessible attributes.

```php
class User extends ActiveRecord\Model {
  static $attr_protected = array('admin');
}

$attributes = array('first_name' => 'Tito', 'admin' => 1);
$user = new User($attributes);

echo $user->first_name; # => Tito
echo $user->admin; # => null
# now no one can fake post values and make themselves an admin against your will!
```

## Accessible attributes

Whitelist of attributes that are checked from mass-assignment calls such as constructing a model or using Model::update_attributes(). This is the opposite of protected attributes. Accessible attributes can also be used as a security measure against fake post values, except that it is often more pragmatic because it is a whitelist approach.

```php
class User extends ActiveRecord\Model {
  static $attr_accessible = array('first_name');
}

$attributes = array('first_name' => 'Tito', 'last_name' => 'J.', 'admin' => 1);
$user = new User($attributes);

echo $person->last_name; # => null
echo $person->admin; # => null
echo $person->first_name; # => Tito
# first_name is the only attribute that can be mass-assigned, so the other 2 are null
```

## Serialization

This is not the normal kind of PHP serialization you are used to. This will not serialize your entire object; however, it will serialize the attributes of your model to either an xml or a json representation. An options array can take the following parameters:

`only`: a string or array of attributes to be included.
`except`: a string or array of attributes to be excluded.
`methods`: a string or array of methods to invoke. The method's name will be used as a key for the final attributes array along with the method's returned value
`include`: a string or array of associated models to include in the final serialized product.
`skip_instruct`: set to true to skip the declaration.

Below only includes `Model::to_json()` examples; however, you can use all of the examples with `Model::to_xml()`

```php
class User extends ActiveRecord\Model {
  static $has_many = array(array('orders'));

  public function name() {
    return $this->first_name .' '. $this->last_name;
  }
}

# assume these fields are on our `users` table:
# id, first_name, last_name, email, social_security, phone_number

$user = User::first();

# json should only contain id and email
$json = $user->to_json(array(
  'only' => array('id', 'email')
));

echo $json; # => {"id":1,"email":"none@email.com"}

# limit via exclusion (here we use a string, but an array can be passed)
$json = $user->to_json(array(
  'except' => 'social_security'
));

echo $json; # => {"id":1,"first_name":"George","last_name":"Bush",
            #     "email":"none@email.com","phone_number":"555-5555"}

# call $user->name() and the returned value will be in our json
$json = $user->to_json(array(
  'only' => array('email', 'name'),
  'methods' => 'name'
));

echo $json; # => {"name":"George Bush","email":"none@email.com"}

# include the orders association
$json = $user->to_json(array(
  'include' => array('orders')
));

# you can nest includes .. here orders also has a payments association
$json = $user->to_json(array(
  'include' => array('orders' => array('except' => 'id', 'include' => 'payments')
));
```

DateTime fields are serialized to ISO8601 format by default. This format can be changed by setting ActiveRecord\Serialization::$DATETIME_FORMAT. You can use a raw formatter or any of the pre-defined formats defined in DateTime::$FORMAT

```php
ActiveRecord\Serialization::$DATETIME_FORMAT = 'Y-m-d';
ActiveRecord\Serialization::$DATETIME_FORMAT = 'atom';
ActiveRecord\Serialization::$DATETIME_FORMAT = 'long';
ActiveRecord\Serialization::$DATETIME_FORMAT = \DateTime::RSS;
```

## Automatic Timestamps

Models with fields named created_at and updated_at will have those fields automatically updated upon model creation and model updates.
