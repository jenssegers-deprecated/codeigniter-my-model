<?php
/**
 * @name		CodeIgniter Base Model
 * @author		Jens Segers
 * @contributor	Jamie Rumbelow <http://jamierumbelow.net>
 * @link		http://www.jenssegers.be
 * @license		MIT License Copyright (c) 2012 Jens Segers
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

if (!class_exists('CI_Model'))
    require_once (BASEPATH . 'core/Model.php');

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
	 * If not set, an additional query will be made to fetch these fields
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
     * Database result mode, choose between object and array
     * Depending on this value result() or result_array() will be used internally
     */
    protected $result_mode = 'array';
    
    /*
     * Validation, should contain validation arrays like the form validation
     */
    protected $validate = array();
    
    /*
     * Skip the validation
     */
    protected $skip_validation = FALSE;
    
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
        
        if ($this->result_mode == 'object') {
            $row = $this->db->get($this->_table())->row();
        } else {
            $row = $this->db->get($this->_table())->row_array();
        }
        
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
        
        if ($this->result_mode == 'object') {
            $result = $this->db->get($this->_table())->result();
        } else {
            $result = $this->db->get($this->_table())->result_array();
        }
        
        foreach ($result as &$row) {
            $row = $this->_callbacks('after_get', array($row));
        }
        
        return $result;
    }
    
    /**
     * Get multiple records from the database with matching WHERE parameters
     * Alias for get_all, created for when get_all does not sound good enough
     *
     * @param string $key
     * @param string $val
     * @return array
     */
    public function get_many() {
        return $this->get_all();
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
            
            $data = array_intersect_key($data, array_flip($this->_fields()));
            $this->db->insert($this->_table(), $data);
            
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
     * @return integer
     */
    public function update($primary_value, $data, $skip_validation = FALSE) {
        $valid = TRUE;
        
        $data = $this->_callbacks('before_update', array($data, $primary_value));
        
        if ($skip_validation === FALSE) {
            $valid = $this->_run_validation($data);
        }
        
        if ($valid) {
            $data = array_intersect_key($data, array_flip($this->_fields()));
            
            $result = $this->db->where($this->primary_key, $primary_value)->set($data)->update($this->_table());
            
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
        
        $result = $this->db->delete($this->_table());
        
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
        
        return $this->db->count_all_results($this->_table());
    }
    
    /**
     * Return a count of every row in the table
     *
     * @return integer
     */
    public function count_all() {
        return $this->db->count_all($this->_table());
    }
    
    /**
     * An easier limit function
     * 
     * @param integer $limit
     * @param integer $offset
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
        return $this->db->list_fields($this->_table());
    }
    
    /**
     * Retrieve and generate a dropdown-friendly array of the data
     * in the table based on a key and a value.
     *
     * @param string $key
     * @param string $value
     * @return array $options
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
        
        if ($this->result_mode == 'object') {
            $result = $this->db->select(array($key, $value))->get($this->_table())->result();
            
            $options = array();
            foreach ($result as $row) {
                $row = $this->_callbacks('after_get', array($row));
                $options[$row->{$key}] = $row->{$value};
            }
        } else {
            $result = $this->db->select(array($key, $value))->get($this->_table())->result_array();
            
            $options = array();
            foreach ($result as $row) {
                $row = $this->_callbacks('after_get', array($row));
                $options[$row[$key]] = $row[$value];
            }
        }
        
        return $options;
    }
    
    /**
     * Skip the insert validation for future calls
     */
    public function skip_validation($bool = TRUE) {
        $this->skip_validation = $bool;
        return $this;
    }
    
    /**
     * Run the specific callbacks, each callback taking a $data
     * variable and returning it
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
     * Sets WHERE depending on the number of parameters, has 4 modes:
     * 1. ($id) primary key value mode
     * 2. (array("name"=>$name)) associative array mode
     * 3. ("name", $name) custom key/value mode
     * 4. ("id", array(1, 2, 3)) where in mode
     */
    private function _set_where($params) {
        if (count($params) == 1) {
            if (!is_array($params[0]) && !strstr($params[0], "'")) {
                $this->db->where($this->primary_key, $params[0]); // 1.
            } else {
                $this->db->where($params[0]); // 2.
            }
        } elseif (count($params) == 2) {
            if (is_array($params[1])) {
                $this->db->where_in($params[0], $params[1]); // 4.
            } else {
                $this->db->where($params[0], $params[1]); // 3.
            }
        }
    }
    
    /**
     * Return or fetch the database fields
     */
    private function _fields() {
        if ($this->_table() && empty($this->fields)) {
            $this->fields = $this->db->list_fields($this->_table());
        }
        return $this->fields;
    }
    
    /**
     * Return or guess the database table
     */
    private function _table() {
        if ($this->table == NULL) {
            $this->load->helper('inflector');
            $class = preg_replace('#((_m|_model)$|$(m_))?#', '', strtolower(get_class($this)));
            $this->table = plural(strtolower($class));
        }
        return $this->table;
    }
}