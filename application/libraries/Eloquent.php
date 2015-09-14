<?php defined('BASEPATH') or die();

/**
 * Eloquent - Using Eloquent ORM inside CodeIgniter
 *
 * Adapted from
 * http://jamieonsoftware.com/post/90299647695/using-eloquent-orm-inside-codeigniter-with-added
 *
 * The Eloquent ORM is a simple ActiveRecord implementation for
 * working with your database.
 *
 * @package     CodeIgniter
 * @subpackage  Twig
 * @category    Libraries
 * @author      Jamie Rumbelow
 * @author      Indra Ginn <indra.ginn@gmail.com>
 * @license     http://www.opensource.org/licenses/MIT
 * @version     0.0.1
 * @copyright   Copyright (c) 2015 Indra Ginn <indra.ginn@gmail.com>
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher as Events;

class Eloquent
{
    /**
     * Reference to code CodeIgniter instance.
     * @var codeIgniter object
     */
    private $_CI;

    /**
     * The constructor
     */
    public function __construct()
    {
        // Get reference to CodeIgniter Instance
        $this->_CI =& get_instance();

        $capsule = new Capsule;

        $capsule->addConnection(array(
            'driver'    => in_array($this->_CI->db->dbdriver, array('mysql', 'mysqli')) ? 'mysql' : $this->_CI->db->dbdriver,
            'host'      => $this->_CI->db->hostname,
            'database'  => $this->_CI->db->database,
            'username'  => $this->_CI->db->username,
            'password'  => $this->_CI->db->password,
            'charset'   => $this->_CI->db->char_set,
            'collation' => $this->_CI->db->dbcollat,
            'prefix'    => $this->_CI->db->dbprefix,
        ));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $events = new Events;
        $events->listen('illuminate.query', function($query, $bindings, $time, $name)
        {
            // Format binding data for sql insertion
            foreach ($bindings as $i => $binding)
            {
                if ($binding instanceof \DateTime) {
                    $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                } else if (is_string($binding)) {
                    $bindings[$i] = "'$binding'";
                }
            }

            // Insert bindings into query
            $query = str_replace(array('%', '?'), array('%%', '%s'), $query);
            $query = vsprintf($query, $bindings);

            // Add it into CodeIgniter
            $db =& get_instance()->db;

            $db->query_times[] = $time;
            $db->queries[] = $query;
        });
        $capsule->setEventDispatcher($events);
    }
}
