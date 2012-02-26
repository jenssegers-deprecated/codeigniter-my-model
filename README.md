CodeIgniter My Model
====================

This model extension provides easy database access function for your models. It contains CRUD methods as well as before/after callbacks.

Installation
------------

Place MY_Model.php into the application/core folder (or use spark). Make sure your models extend MY_Model and you are ready to go!

Usage
-----

Create a new model class with an appropriate name, that extends MY_Model. You are advised to fill in the following attributes:

    class Book_model extends MY_Model {
    
        protected $table = "books";
        protected $primary_key = "id";
        protected $fields = array("id", "author", "title", "published", "created_at");
    
    }
    
 - **$table**: the name of the database table, if not set it will try to guess the table from the model's name: *Book*_model -> *books*
 - **$primary_key**: the name of the primary key of your database, set to 'id' by default
 - **$fields**: you table's fields, if not set 1 extra query will be performed to get these automatically. These are used to filter arrays before inserting and updating
 
Methods
-------

 - **get($id)**: get the record matching this id (or other primary key)
 - **get($key, $value)**: get the record matching these where parameters
 - **get_all()**: get all records
 - **get_many($key, $value)**: get the records matching these where parameters
 - **count\_all\_results($key, $value)**: count all records matching these where parameters
 - **insert($data)**: insert a new record
 - **update($id, $data)**: update a record matching this id (or other primary key)
 - **delete($id)**: delete the record matching this id (or other primary key)
 - **dropdown($key, $value)**: creates a dropdown-friendly array based on the key/value
 - **skip_validation(TRUE)**: skip validation

Some active record methods are also available, these can be used without the database table parameter:

 - **count_all()**: count all records
 - **list_fields()**: returns an array containing all database table fields
 
All other active records are also available, these are immediately passed to the database class. This allows you to use chaining:

    $books = $this->book_model->order_by("author", "desc")->limit(50, 0)->get_many();

Callbacks
---------

Callbacks are functions that are activated on specific occasions that allows you to hook custom logic into the CRUD process. This is a list of the available callback points:

 - $before_create
 - $after_create
 - $before_update
 - $after_update
 - $before_get
 - $after_get
 - $before_delete
 - $after_delete

Example usage, add a timestamp whenever a book is created:
 
    class Book_model extends MY_Model {
        public $before_create = array('timestamps');

        function timestamps($book) {
            $book['created_at'] = date('Y-m-d H:i:s');
            return $book;
        }
    }
    
Validation
----------

This models provides a wrapper for CodeIgniter's form validation, it will check all declared rules before inserting or updating. To add rules, add them to the `$this->validate` array, like this:

    $this->validate[] = array(
                             'field'   => 'username',
                             'label'   => 'Username',
                             'rules'   => 'required'
                          );
                          
You can find more information about these rules here: http://codeigniter.com/user_guide/libraries/form_validation.html

You can bypass the validation by calling `skip_validation()` before an insert or update.

Contributors
------------

This model is based on Jamie Rumbelow's base model: https://github.com/jamierumbelow/codeigniter-base-model