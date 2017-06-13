# Associations

What are associations? By declaring associations on your models, you allow them to communicate with each other. These associations should match the way data in your tables relate to each other.

## Common options

These are available amongst each type of association.

`conditions`: string/array of finder conditions
`readonly`: whether associated objects can be saved/destroyed
`select`: specify fields in the select clause
`class_name`: the class name of the associated model
`foreign_key`: name of foreign_key

Let's take a look at these options with a few different association types

### conditions

Below, we specify that associated payments of an order object should not be void.

```php
class Order extends ActiveRecord\Model {
  static $has_many = array(
    array('payments', 'conditions' => array('void = ?' => array(0)))
  );
}
```

### readonly

If you add a readonly option to your association, then the associatied object cannot be saved, although, the base object can still be saved.

```php
class Payment extends ActiveRecord\Model {
  static $belongs_to = array(
    array('user', 'readonly' => true)
  );
}

$payment = Payment::first();
$payment->paid = 1;
$payment->save(); # this will save just fine

$payment->user->first_name = 'John';
$payment->user->save(); # this will throw a ReadOnlyException
```

### select

Sometimes you may not need all of the fields back from one of your associations (e.g. it may be a ridiculously large table) and so you can specify the particular fields you want.

```php
class Payment extends ActiveRecord\Model {
  static $belongs_to = array(
    array('person', 'select' => 'id, first_name, last_name')
  );
}
```

### class_name

In this example payment has a one-to-one relationship with a user, but we want to access the association thru "person." Thus, we have to provide the class name of the associated model; otherwise, ActiveRecord would try to look for a "Person" class.

```php
class Payment extends ActiveRecord\Model {
  static $belongs_to = array(
    array('person', 'class_name' => 'User')
  );
}
```

## `has_many`

A one-to-many relationship. You should use a pluralized form of the associated model when declaring a has_many association, unless you want to use the class_name option.

```php
# one-to-many association with the model "Payment" 
class User extends ActiveRecord\Model {
  static $has_many = array(
    array('payments')
  );
}

$user = User::first();
print_r($user->payments); # => will print an array of Payment objects

$payment = $user->create_payment(array('amount' => 1)); # build|create for associations.
```

![](http://phpactiverecord.org/images/guides/has_many.png)

### Options (not part of common options)

`limit`/`offset`: limit the number of records
`primary_key`: name of the primary_key of the association (assumed to be "id")
`group`: GROUP BY clause
`order`: ORDER BY clause
`through`: the association used to go "through"

```php
class Order extends ActiveRecord\Model {
  static $has_many = array(
    array('payments', 'limit' => 5),
    array('items', 'order' => 'name asc', 'group' => 'type')
  );
}
```

## has_many through (many to many)

This is a convenient way to configure a many-to-many association. In this example an order is associated with users by going the its payments association.

```php
class Order extends ActiveRecord\Model {
  static $has_many = array(
    array('payments'),
    array('users', 'through' => 'payments')
  );
}

class Payment extends ActiveRecord\Model {
  static $belongs_to = array(
    array('user'),
    array('order')
  );
}

class User extends ActiveRecord\Model {
  static $has_many = array(
    array('payments')
  );
}

$order = Order::first();
# direct access to users
print_r($order->users); # will print an array of User object
```

![](http://phpactiverecord.org/images/guides/has_many_through.png)

## belongs_to

This indicates a one-to-one relationship. Its difference from has_one is that the foreign key will be on the table which declares a belongs_to association. You should use a singular form of the associated model when declaring this association, unless you want to use the class_name option.

```php
class Payment extends ActiveRecord\Model {
  static $belongs_to = array(
    array('user')
  );
}

$payment = Payment::first();
echo $payment->user->first_name; # first_name of associated User object
```

![](http://phpactiverecord.org/images/guides/belongs_to.png)

### Options (not part of common options)

`primary_key`: name of the primary_key of the association (assumed to be "id")

## has_one

This indicates a one-to-one relationship. A has_one will have the foreign key on the associated table unlike belongs_to. You should use a singular form of the associated model when declaring this association, unless you want to use the class_name option.

```php
class Payment extends ActiveRecord\Model {
  static $has_one = array(
    array('receipt')
  );
}
```

![](http://phpactiverecord.org/images/guides/has_one.png)

### Options (not part of common options)

`primary_key`: name of the primary_key of the association (assumed to be "id")
`through`: the association used to go "through"

## has_one through

A one-to-one association. In this example, an owner has a quarter_back by going through its team association.

```php
class Owner extends ActiveRecord\Model {
  static $has_one = array(
    array('team'),
    array('quarter_back', 'through' => 'team')
  );
}

class Team extends ActiveRecord\Model {
  static $belongs_to = array(
    array('owner')
  );

  static $has_one = array(
    array('quarter_back')
  );
}

class QuarterBack extends ActiveRecord\Model {
  static $belongs_to = array(
    array('team')
  );
}
```

![](http://phpactiverecord.org/images/guides/has_one_through.png)

## Self-referential

Model's can declare associations to themselves. This can be helpful for table auditing, or in the example below, where a post would need to know about its parent.

```php
class Post extends ActiveRecord\Model {
  static $belongs_to = array(array('parent_post', 'class_name' => 'Post'));
}
```

![](http://phpactiverecord.org/images/guides/belongs_to_self_referential.png)
