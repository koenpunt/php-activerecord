# Callbacks

Callbacks allow the programmer to hook into the life cycle of an ActiveRecord\Model object. You can control the state of your object by declaring certain methods to be called before or after methods are invoked on your object inside of ActiveRecord.

## Before callbacks

If a `before_*` callback returns false, execution of any other callbacks after the offending callback will not be fired and the model will not be saved/deleted.

`before_save`: called before a model is saved
`before_create`: called before a NEW model is to be inserted into the database
`before_update`: called before an existing model has been saved
`before_validation`: called before running validators
`before_validation_on_create`: called before validation on a NEW model being inserted
`before_validation_on_update`: same as above except for an existing model being saved
`before_destroy`: called before a model has been deleted

```php
class Order extends ActiveRecord\Model {
  static $before_create = array('apply_tax'); # new records only
  static $before_save = array('upcase_state'); # new OR updated records

  public function apply_tax() {
    if ($this->state == 'VA')
      $tax = 0.045;
    elseif ($this->state == 'CA')
      $tax = 0.10;
    else
      $tax = 0.02;

    $this->tax = $this->price * $tax;
  }

  public function upcase_state() {
    $this->state = strtoupper($this->state);
  }
}

$attributes = array('item_name' => 'Honda Civic',
  'price' => 7000.00, 'state' => 'va');

$order = Order::create($attributes);
echo $order->tax; # => 315.00
echo $order->state; # => VA

# somehow our order changed states!
$order->state = 'nc';
$order->save();
echo $order->state; # => NC
```

## After callbacks

`after_save`: called after a model is saved
`after_create`: called after a NEW model has been inserted into the database
`after_update`: called after an existing model has been saved
`after_validation`: called after running validators
`after_validation_on_create`: called after validation on a NEW model being inserted
`after_validation_on_update`: same as above except for an existing model being saved
`after_destroy`: called after a model has been deleted

```php
class User extends ActiveRecord\Model {
  static $after_create = array('send_new_user_email'); # new records only
  static $after_destroy = array('delete_all_related_data');

  public function delete_all_related_data() {
    # delete all associated objects
  }

  public function send_new_user_email() {
    mail($this->email, "Thanks for signing up, {$this->name}!", "The subject");
  }
}

$attributes = array('name' => 'Jax', 'email' => 'nonya@nonya.com');
$user = User::create($attributes);
# an e-mail was just sent...

$user->delete();
# everything associated with this user was just deleted
```
