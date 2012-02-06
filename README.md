CodeIgniter My Model
====================

This model extension provides easy database access function for your models. It contains CRUD methods as well as before/after callbacks.

Installation
------------

Place MY_Model.php into the application/core folder. Make sure your models extend MY_Model and you are ready to go!

Methods
-------

 - **get($id)**: get the record matching this id (or other primary key)
 - **get($key, $value)**: get the record matching these where parameters
 - **get_all()**: get all parameters
 - **get_many($key, $value)**: get the records matching these where parameters
 - **count_all_results($key, $value)**: count all records matching these where parameters
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

This models provides a wrapper for CodeIgniter's form validation, it will check all declared rules before inserting or updating. To add rules, add them to the $this->validate array, like this:

	$this->validate[] = array(
							 'field'   => 'username',
							 'label'   => 'Username',
							 'rules'   => 'required'
						  );
						  
You can find more information about these rules here: http://codeigniter.com/user_guide/libraries/form_validation.html

You can bypass the validation by calling skip_validation() before an insert or update.

Contributors
------------

This model is based on Jamie Rumbelow's base model that was based on Phil Sturgeon's model.