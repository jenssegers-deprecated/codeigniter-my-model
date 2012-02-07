<?php
/**
 * @name		CodeIgniter Base Model
 * @author		Jens Segers
 * @modified	Jamie Rumbelow <http://jamierumbelow.net>
 * @modified	Phil Sturgeon <http://philsturgeon.co.uk>
 * @modified	Dan Horrigan <http://dhorrigan.com>
 * @modified	Adam Jackett <http://darkhousemedia.com>
 * @link		http://www.jenssegers.be
 * @license		MIT License Copyright (c) 2011 Jens Segers
 * 
 * This model is based on Jamie Rumbelow's model with some personal modifications
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

if (!defined("BASEPATH"))
    exit("No direct script access allowed");

class MY_Model extends CI_Model {
    
    /*
     * Your database table, if not set the name will be guessed
     */
    protected $table = NULL;
    
    /*
     * The primary key name, by default set to 'id'
     */
    protected $primary_key = 'id';
    
    /*
     * The database table fields, used for filtering data arrays before inserting and updating
     */
    protected $fields = array();
    
    /*
     * Callbacks, should contain an array of methods
     */
    protected $before_create = array();
    protected $after_create = array();
    protected $before_update = array();
    protected $after_update = array();
    protected $before_get = array();
    protected $after_get = array();
    protected $before_delete = array();
    protected $after_delete = array();
    
    /*
     * Validation, should contain validation arrays like the form validation
     */
    protected $validate = array();
    
    /*
     * Skip the validation
     */
    protected $skip_validation = FALSE;
    
    /**
     * Constructer, tries to guess the table name.
     */
    public function __construct() {
        parent::__construct();
        
        if (get_class($this) != "MY_Model") {
            if ($this->table == NULL) {
                $this->load->helper('inflector');
                $class = preg_replace('#((_m|_model)$|$(m_))?#', '', strtolower(get_class($this)));
                $this->table = plural(strtolower($class));
            }
        }
        
        if ($this->table && count($this->fields) == 0) {
            $this->fields = $this->db->list_fields($this->table);
        }
    }
    
    /**
     * Magic function that passes unrecognized method calls to the database class for chaining
     * 
     * @param string $method
     * @param array $params
     * @return void
     */
    public function __call($method, $params) {
        if (method_exists($this->db, $method)) {
            call_user_func_array(array($this->db, $method), $params);
            return $this;
        }
    }
    
    /**
     * Get a single record with matching WHERE parameters
     *
     * @param string $key
     * @param string $val
     * @return object
     */
    public function get() {
        $where = & func_get_args();
        $this->_set_where($where);
        
        $this->_callbacks('before_get', array($where));
        $row = $this->db->get($this->table)->row_array();
        $row = $this->_callbacks('after_get', array($row));
        
        return $row;
    }
    
    /**
     * Get all records from the database
     * 
     * @return array
     */
    public function get_all() {
        $where = & func_get_args();
        $this->_set_where($where);
        
        $this->_callbacks('before_get', array($where));
        $result = $this->db->get($this->table)->result_array();
        
        foreach ($result as &$row) {
            $row = $this->_callbacks('after_get', array($row));
        }
        
        return $result;
    }
    
    /**
     * Get multiple records from the database with matching WHERE parameters
     *
     * @param string $key
     * @param string $val
     */
    public function get_many() {
        $where = & func_get_args();
        $this->_set_where($where);
        
        $this->_callbacks('before_get', array($where));
        $result = $this->db->get($this->table)->result_array();
        
        foreach ($result as &$row) {
            $row = $this->_callbacks('after_get', array($row));
        }
        
        return $result;
    }
    
    /**
     * Insert a new record into the database
     * Returns the insert ID
     *
     * @param array $data
     * @param bool $skip_validation
     * @return integer
     */
    public function insert($data, $skip_validation = FALSE) {
        $valid = TRUE;
        
        if ($skip_validation === FALSE) {
            $valid = $this->_run_validation($data);
        }
        
        if ($valid) {
            $data = $this->_callbacks('before_create', array($data));
            $data = array_intersect_key($data, array_flip($this->fields));
            $this->db->insert($this->table, $data);
            $this->_callbacks('after_create', array($data, $this->db->insert_id()));
            
            return $this->db->insert_id();
        } else {
            return FALSE;
        }
    }
    
    /**
     * Update a record, specified by an ID.
     *
     * @param integer $id
     * @param array $data
     * @return int
     */
    public function update($primary_value, $data, $skip_validation = FALSE) {
        $valid = TRUE;
        
        $data = $this->_callbacks('before_update', array($data, $primary_value));
        
        if ($skip_validation === FALSE) {
            $valid = $this->_run_validation($data);
        }
        
        if ($valid) {
            $data = array_intersect_key($data, array_flip($this->fields));
            
            $result = $this->db->where($this->primary_key, $primary_value)->set($data)->update($this->table);
            
            $this->_callbacks('after_update', array($data, $primary_value, $result));
            
            return $this->db->affected_rows();
        } else {
            return FALSE;
        }
    }
    
    /**
     * Delete a row from the database based on a WHERE parameters
     *
     * @param string $key
     * @param string $val
     * @return bool
     */
    public function delete() {
        $where = & func_get_args();
        $this->_set_where($where);
        
        $this->_callbacks('before_delete', array($where));
        
        $result = $this->db->delete($this->table);
        
        $this->_callbacks('after_delete', array($where, $result));
        
        return $this->db->affected_rows();
    }
    
    /**
     * Count the number of rows based on a WHERE parameters
     *
     * @param string $key
     * @param string $val
     * @return integer
     */
    public function count_all_results() {
        $where = & func_get_args();
        $this->_set_where($where);
        
        return $this->db->count_all_results($this->table);
    }
    
    /**
     * Return a count of every row in the table
     *
     * @return integer
     */
    public function count_all() {
        return $this->db->count_all($this->table);
    }
    
    /**
     * An easier limit function
     * 
     * @param int $limit
     * @param int $offset
     */
    public function limit($limit = NULL, $offset = NULL) {
        if (is_numeric($limit) && is_numeric($offset)) {
            $this->db->limit($limit, $offset);
        } elseif (is_numeric($limit)) {
            $this->db->limit($limit);
        }
        return $this;
    }
    
    /**
     * List all table fields
     * 
     * @return array $fields
     */
    public function list_fields() {
        return $this->db->list_fields($this->table);
    }
    
    /**
     * Retrieve and generate a dropdown-friendly array of the data
     * in the table based on a key and a value.
     *
     * @param string $key
     * @param string $value
     * @return arrat $options
     */
    public function dropdown() {
        $args = & func_get_args();
        
        if (count($args) == 2) {
            list($key, $value) = $args;
        } else {
            $key = $this->primary_key;
            $value = $args[0];
        }
        
        $this->_callbacks('before_get', array($key, $value));
        
        $result = $this->db->select(array($key, $value))->get($this->table)->result_array();
        
        $this->_callbacks('after_get', array($key, $value, $result));
        
        $options = array();
        foreach ($result as $row) {
            $options[$row->{$key}] = $row->{$value};
        }
        
        return $options;
    }
    
    /**
     * Skip the insert validation
     */
    public function skip_validation($bool = TRUE) {
        $this->skip_validation = $bool;
        return $this;
    }
    
    /**
     * Run the specific callbacks, each callback taking a $data
     * variable and returning it
     * 
     * @TODO: use references?
     */
    private function _callbacks($name, $params = array()) {
        $data = (isset($params[0])) ? $params[0] : FALSE;
        
        if (!empty($this->$name)) {
            foreach ($this->$name as $method) {
                $data = call_user_func_array(array($this, $method), $params);
            }
        }
        
        return $data;
    }
    
    /**
     * Runs validation on the passed data.
     *
     * @return bool
     */
    private function _run_validation($data) {
        if ($this->skip_validation) {
            return TRUE;
        }
        
        if (!empty($this->validate)) {
            foreach ($data as $key => $val) {
                $_POST[$key] = $val;
            }
            
            $this->load->library('form_validation');
            
            if (is_array($this->validate)) {
                $this->form_validation->set_rules($this->validate);
                
                return $this->form_validation->run();
            } else {
                return $this->form_validation->run($this->validate);
            }
        } else {
            return TRUE;
        }
    }
    
    /**
     * Sets WHERE depending on the number of parameters
     */
    private function _set_where($params) {
        if (count($params) == 1) {
            if (!is_array($params[0] && !strstr($params[0], "'"))) {
                $this->db->where($this->primary_key, $params[0]);
            } else {
                $this->db->where($params[0]);
            }
        } elseif (count($params) == 2) {
            $this->db->where($params[0], $params[1]);
        }
    }
}
