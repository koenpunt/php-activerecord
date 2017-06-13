# Finders

ActiveRecord supports a number of methods by which you can find records such as: via primary key, dynamic field name finders. It has the ability to fetch all the records in a table with a simple call, or you can make use of options like order, limit, select, and group.

There are essentially two groups of finders you will be working with: a single record result and multiple records result. Sometimes there will be little transparency for the method calls, meaning you may use the same method to get either one, but you will pass an option to that method to signify which type of result you will fetch.

All methods used to fetch records from your database will go through `Model::find()`, with one exception, custom sql can be passed to `Model::find_by_sql()`. In all cases, the finder methods in ActiveRecord are statically invoked. This means you will always use the following type of syntax.

```php
class Book extends ActiveRecord\Model {}

Book::find('all');
Book::find('last');
Book::first();
Book::last();
Book::all();
```

## Single record result

Whenever you invoke a method which produces a single result, that method will return an instance of your model class. There are 3 different ways to fetch a single record result. We'll start with one of the most basic forms.

### Find by primary key

You can grab a record by passing a primary key to the find method. You may pass an options array as the second argument for creating specific queries. If no record is found, a RecordNotFound exception will be thrown.

```php
# Grab the book with the primary key of 2
Book::find(2);
# sql => SELECT * FROM `books` WHERE id = 2
```

### Find first

You can get the first record back from your database two ways. If you do not pass conditions as the second argument, then this method will fetch all the results from your database, but will only return the very first result back to you. Null will be returned if no records are found.

```php
# Grab all books, but only return the first result back as your model object.
$book = Book::first();
echo "the first id is: {$book->id}" # => the first id is: 1
# sql => SELECT * FROM `books`

# this produces the same sql/result as above
Book::find('first');
```

### Find last

If you haven't yet fallen asleep reading this guide, you should've guessed this is the same as "find first", except that it will return the last result. Null will be returned if no records are found.

```php
# Grab all books, but only return the last result back as your model object.
$book = Book::last();
echo "the last id is: {$book->id}" # => the last id is: 32
# sql => SELECT * FROM `books`

# this produces the same sql/result as above
Book::find('last');
```

## Multiple records result

This type of result will always return an array of model objects. If your table holds no records, or your query yields no results, then an empty array will be given.

### Find by primary key

Just like the single record result for find by primary key, you can pass an array to the find method for multiple primary keys. Again, you may pass an options array as the last argument for creating specific queries. Every key which you use as an argument must produce a corresponding record, otherwise, a RecordNotFound exception will be thrown.

```php
# Grab books with the primary key of 2 or 3
Book::find(2, 3);
# sql => SELECT * FROM `books` WHERE id IN (2,3)

# same as above
Book::find(array(2, 3));
```

### Find all

There are 2 more ways which you can use to get multiple records back from your database. They use different methods; however, they are basically the same thing. If you do not pass an options array, then it will fetch all records.

```php
# Grab all books from the database
Book::all();
# sql => SELECT * FROM `books`

# same as above
Book::find('all');
```

Here we pass some options to the same method so that we don't fetch every record.

```php
$options = array('limit' => 2);
Book::all($options);
# sql => SELECT * FROM `books` LIMIT 0,2

# same as above
Book::find('all', $options);
```

## Finder options

There are a number of options available to pass to one of the finder methods for granularity. Let's start with one of the most important options: conditions.

### Conditions

This is the "WHERE" of a SQL statement. By creating conditions, ActiveRecord will parse them into a corresponding "WHERE" SQL statement to filter out your results. Conditions can be extremely simple by only supplying a string. They can also be as complex as you'd like by creating a conditions string that uses ? marks as placeholders for values. Let's start with a simple example of a conditions string.

```php
# fetch all the cheap books!
Book::all(array('conditions' => 'price < 15.00'));
# sql => SELECT * FROM `books` WHERE price < 15.00

# fetch all books that have "war" somewhere in the title
Book::find('all', array('conditions' => "title LIKE '%war%'"));
# sql => SELECT * FROM `books` WHERE title LIKE '%war%'
```

As stated, you can use *?* marks as placeholders for values which ActiveRecord will replace with your supplied values. The benefit of using this process is that ActiveRecord will escape your string in the backend with your database's native function to prevent SQL injection.

```php
# fetch all the cheap books!
Book::all(array('conditions' => array('price < ?', 15.00)));
# sql => SELECT * FROM `books` WHERE price < 15.00

# fetch all lousy romance novels
Book::find('all', array('conditions' => array('genre = ?', 'Romance')));
# sql => SELECT * FROM `books` WHERE genre = 'Romance'

# fetch all books with these authors
Book::find('all', array('conditions' => array('author_id in (?)', array(1, 2, 3))));
# sql => SELECT * FROM `books` WHERE author_id in (1,2,3)

# fetch all lousy romance novels which are cheap
Book::all(array('conditions' => array('genre = ? AND price < ?', 'Romance', 15.00)));
# sql => SELECT * FROM `books` WHERE genre = 'Romance' AND price < 15.00
```

Here is a more complicated example. Again, the first index of the conditions array are the condition strings. The values in the array after that are the values which replace their corresponding ? marks.

```php
# fetch all cheap books by these authors
$cond =array('conditions'=>array('author_id in(?) AND price < ?', array(1, 2, 3), 15.00));
Book::all($cond);
# sql => SELECT * FROM `books` WHERE author_id in(1,2,3) AND price < 15.00
```

### Limit & Offset

This one should be fairly obvious. A limit option will produce a SQL limit clause for supported databases. It can be used in conjunction with the offset option.

```php
# fetch all but limit to 10 total books
Book::find('all', array('limit' => 10));
# sql => SELECT * FROM `books` LIMIT 0,10

# fetch all but limit to 10 total books starting at the 6th book
Book::find('all', array('limit' => 10, 'offset' => 5));
# sql => SELECT * FROM `books` LIMIT 5,10
```

### Order

Produces an ORDERY BY SQL clause.

```php
# order all books by title desc
Book::find('all', array('order' => 'title desc'));
# sql => SELECT * FROM `books` ORDER BY title desc

# order by most expensive and title
Book::find('all', array('order' => 'price desc, title asc'));
# sql => SELECT * FROM `books` ORDER BY price desc, title asc
```

### Select

Passing a select key in your options array will allow you to specify which columns you want back from the database. This is helpful when you have a table with too many columns, but you might only want 3 columns back for 50 records. It is also helpful when used with a group statement.

```php
# fetch all books, but only the id and title columns
Book::find('all', array('select' => 'id, title'));
# sql => SELECT id, title FROM `books`

# custom sql to feed some report
Book::find('all', array('select' => 'avg(price) as avg_price, avg(tax) as avg_tax'));
# sql => SELECT avg(price) as avg_price, avg(tax) as avg_tax FROM `books` LIMIT 5,10
```

### From

This designates the table you are selecting from. This can come in handy if you do a join or require finer control.

```php
# fetch the first book by aliasing the table name
Book::first(array('select'=> 'b.*', 'from' => 'books as b'));
# sql => SELECT b.* FROM books as b LIMIT 0,1
```

### Group

Generate a GROUP BY clause.

```php
# group all books by prices
Book::all(array('group' => 'price'));
# sql => SELECT * FROM `books` GROUP BY price
```

### Having

Generate a HAVING clause to add conditions to your GROUP BY.

```php
# group all books by prices greater than $45
Book::all(array('group' => 'price', 'having' => 'price > 45.00'));
# sql => SELECT * FROM `books` GROUP BY price HAVING price > 45.00
```

### Read only

Readonly models are just that: readonly. If you try to save a readonly model, then a ReadOnlyException will be thrown.

```php
# specify the object is readonly and cannot be saved
$book = Book::first(array('readonly' => true));

try {
  $book->save();
} catch (ActiveRecord\ReadOnlyException $e) {
  echo $e->getMessage();
  # => Book::save() cannot be invoked because this model is set to read only
}
```

### Dynamic finders

These offer a quick and easy way to construct conditions without having to pass in a bloated array option. This option makes use of PHP 5.3's late static binding combined with __callStatic() allowing you to invoke undefined static methods on your model. You can either use YourModel::find_by which returns a single record result and YourModel::find_all_by returns multiple records result. All you have to do is add an underscore and another field name after either of those two methods. Let's take a look.

```php
# find a single book by the title of War and Peace
$book = Book::find_by_title('War and Peace');
#sql => SELECT * FROM `books` WHERE title = 'War and Peace'

# find all discounted books
$book = Book::find_all_by_discounted(1);
#sql => SELECT * FROM `books` WHERE discounted = 1

# find all discounted books by given author
$book = Book::find_all_by_discounted_and_author_id(1, 5);
#sql => SELECT * FROM `books` WHERE discounted = 1 AND author_id = 5

# find all discounted books or those which cost 5 bux
$book = Book::find_by_discounted_or_price(1, 5.00);
#sql => SELECT * FROM `books` WHERE discounted = 1 OR price = 5.00
```

### Joins

A join option may be passed to specify SQL JOINS. There are two ways to produce a JOIN. You may pass custom SQL to perform a join as a simple string. By default, the joins option will not select the attributes from the joined table; instead, it will only select the attributes from your model's table. You can pass a select option to specify the fields.

```php
# fetch all books joining their corresponding authors
$join = 'LEFT JOIN authors a ON(books.author_id = a.author_id)';
$book = Book::all(array('joins' => $join));
# sql => SELECT `books`.* FROM `books`
#      LEFT JOIN authors a ON(books.author_id = a.author_id)
```

Or, you may specify a join via an associated model.

```php
class Book extends ActiveRecord\Model
{
  static $belongs_to = array(array('author'), array('publisher'));
}

# fetch all books joining their corresponding author
$book = Book::all(array('joins' => array('author')));
# sql => SELECT `books`.* FROM `books`
#      INNER JOIN `authors` ON(`books`.author_id = `authors`.id)

# here's a compound join
$book = Book::all(array('joins' => array('author', 'publisher')));
# sql => SELECT `books`.* FROM `books`
#      INNER JOIN `authors` ON(`books`.author_id = `authors`.id)
#         INNER JOIN `publishers` ON(`books`.publisher_id = `publishers`.id)
```

Joins can be combined with strings and associated models.

```php
class Book extends ActiveRecord\Model
{
  static $belongs_to = array(array('publisher'));
}

$join = 'LEFT JOIN authors a ON(books.author_id = a.author_id)';
# here we use our $join string and the association publisher
$book = Book::all(array('joins' => $join, 'publisher'));
# sql => SELECT `books`.* FROM `books`
#      LEFT JOIN authors a ON(books.author_id = a.author_id)
#         INNER JOIN `publishers` ON(`books`.publisher_id = `publishers`.id)
```

## Find by custom SQL

If, for some reason, you need to create a complicated SQL query beyond the capacity of finder options, then you can pass a custom SQL query through Model::find_by_sql(). This will render your model as readonly so that you cannot use any write methods on your returned model(s).

Caution: find_by_sql() will NOT prevent SQL injection like all other finder methods. The burden to secure your custom find_by_sql() query is on you. You can use the Model::connection()->escape() method to escape SQL strings.

```php
# this will return a single result of a book model with only the title as an attirubte
$book = Book::find_by_sql('select title from `books`');

# you can even select from another table
$cached_book = Book::find_by_sql('select * from books_cache');
# this will give you the attributes from the books_cache table
```

## Eager loading associations

Eager loading retrieves the base model and its associations using only a few queries. This avoids the N + 1 problem.

Imagine you have this code which finds 10 posts and then displays each post's author's first name.

```php
$posts = Post::find('all', array('limit' => 10));
foreach ($posts as $post)
  echo $post->author->first_name;
```

What happens here is the we get 11 queries, 1 to find 10 posts, + 10 (one per each post to get the first name from the authors table).

We solve this problem by using the include option which would only issue two queries instead of 11. Here's how this would be done:

```php
$posts = Post::find('all', array('limit' => 10, 'include' => array('author')));
foreach ($posts as $post)
  echo $post->author->first_name;

SELECT * FROM `posts` LIMIT 10
SELECT * FROM `authors` WHERE `post_id` IN (1,2,3,4,5,6,7,8,9,10)
```

Since `include` uses an array, you can specify more than one association:

```php
$posts = Post::find('all', array('limit' => 10, 'include' => array('author', 'comments')));
```

You can also nest the include option to eager load associations of associations. The following would find the first post, eager load the first post's category, its associated comments and the associated comments' author:

```php
$posts = Post::find('first', array('include' => array('category', 'comments' => array('author'))));
```
