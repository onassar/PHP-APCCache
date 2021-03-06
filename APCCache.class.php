<?php

    // apc dependecy checks
    if (!in_array('apc', get_loaded_extensions())) {
        throw new Exception('APC extension needs to be installed.');
    }

    // json dependecy checks
    if (!in_array('json', get_loaded_extensions())) {
        throw new Exception('JSON extension needs to be installed.');
    }

    /**
     * APCCache
     * 
     * Provides accessors for reading, writing and flushing an apc-level
     * cache/data-store.
     * 
     * @author   Oliver Nassar <onassar@gmail.com>
     * @abstract
     * @notes    handles false-value caching through <json> encoding
     * @todo     implement prefixing
     * @example
     * <code>
     *     // dependency
     *     require_once APP . '/vendors/PHP-APCCache/APCCache.class.php';
     *     APCCache::init('namespace');
     * 
     *     // write to cache; read; exit script
     *     APCCache::write('oliver', 'nassar');
     *     echo APCCache::read('oliver');
     *     exit(0);
     * </code>
     */
    abstract class APCCache
    {
        /**
         * _analytics
         * 
         * APC cache request/writing statistics array.
         * 
         * @var    array
         * @access protected
         */
        protected static $_analytics = array(
            'deletes' => 0,
            'misses' => 0,
            'reads' => 0,
            'writes' => 0
        );

        /**
         * _bypass
         * 
         * @var    boolean
         * @access protected
         */
        protected static $_bypass = false;

        /**
         * _namespace
         * 
         * @var    string
         * @access protected
         */
        protected static $_namespace;

        /**
         * _clean
         * 
         * @access protected
         * @static
         * @param  string $str
         * @return string
         */
        protected static function _clean($str)
        {
            $str = (self::$_namespace) . ($str);
            return md5($str);
        }

        /**
         * checkForFlushing
         * 
         * @note   If you are using apc for session storage, this will clear
         *         them!
         * @access public
         * @static
         * @param  string $key
         * @return void
         */
        public static function checkForFlushing($key)
        {
            if (isset($_GET[$key])) {
                self::flush();
            }
        }

        /**
         * delete
         * 
         * @access public
         * @static
         * @param  string $key
         * @param  boolean $throwException (false)
         * @return void
         */
        public static function delete($key, $throwException = false)
        {
            // ensure namespace set
            if (is_null(self::$_namespace)) {
                throw new Exception('Namespace not set');
            }

            // safely attempt to delete from APC store
            try {

                // delete from store
                $key = self::_clean($key);
                $response = apc_delete($key);
                if ($response === false) {
                    throw new Exception('Error deleting');
                }

                // increment statistic (after store call to allow for exception)
                ++self::$_analytics['deletes'];
            } catch(Exception $exception) {
                if ($throwException === true) {
                    throw new Exception(
                        'APCCache Error: Exception while attempting to delete ' .
                        'from store.'
                    );
                }
            }
        }

        /**
         * init
         * 
         * @access public
         * @static
         * @param  string $namespace
         * @return void
         */
        public static function init($namespace)
        {
            self::$_namespace = $namespace;
        }

        /**
         * flush
         * 
         * Empties apc-level cache records and bytecode/opcode stores.
         * 
         * @access public
         * @static
         * @return void
         */
        public static function flush()
        {
            // safely try to flush resource
            try {
                apc_clear_cache();
                apc_clear_cache('user');
            } catch(Exception $exception) {
                throw new Exception(
                    'APCCache Error: Exception while attempting to flush store.'
                );
            }
        }

        /**
         * getDeletes
         * 
         * @access public
         * @static
         * @return integer
         */
        public static function getDeletes()
        {
            return self::$_analytics['deletes'];
        }

        /**
         * getMisses
         * 
         * Returns the number of apc-level missed cache reads.
         * 
         * @access public
         * @static
         * @return integer number of read/fetch misses for APC requests
         */
        public static function getMisses()
        {
            return self::$_analytics['misses'];
        }

        /**
         * getReads
         * 
         * Returns the number of apc-level successful cache reads.
         * 
         * @access public
         * @static
         * @return integer number of read/fetch requests for APC
         */
        public static function getReads()
        {
            return self::$_analytics['reads'];
        }

        /**
         * getStats
         * 
         * Returns an associative array of apc-level cache performance
         * statistics.
         * 
         * @access public
         * @static
         * @return array associative array of key APC statistics
         */
        public static function getStats()
        {
            return self::$_analytics;
        }

        /**
         * getWrites
         * 
         * Returns the number of successful apc-level cache writes.
         * 
         * @access public
         * @static
         * @return integer number of times a mixed value was written to APC
         */
        public static function getWrites()
        {
            return self::$_analytics['writes'];
        }

        /**
         * read
         * 
         * Attempts to read an apc-level cache record, returning null if it
         * couldn't be accessed. Handles false/null return value logic.
         * 
         * @access public
         * @static
         * @param  string $key key for the cache position
         * @return mixed cache record value, or else null if it's not present
         */
        public static function read($key)
        {
            // ensure namespace set
            if (is_null(self::$_namespace)) {
                throw new Exception('Namespace not set');
            }

            // safely attempt to read from APC store
            try {

                // Bypassing checking
                if (self::$_bypass === true) {
                    ++self::$_analytics['misses'];
                    return null;
                }

                // check apc
                $key = self::_clean($key);
                $response = apc_fetch($key);

                // not found
                if ($response === false) {
                    ++self::$_analytics['misses'];
                    return null;
                }

                // increment apc-reads
                ++self::$_analytics['reads'];

                // falsy value, not `not-found` value
                if ($response === json_encode(false)) {
                    return false;
                }

                // previously set response
                return $response;
            } catch(Exception $exception) {
                throw new Exception(
                    'APCache Error: Exception while attempting to read from ' .
                    'store.'
                );
            }
        }

        /**
         * setupBypassing
         * 
         * @access public
         * @static
         * @param  string $key The key, which if found in _GET, will turn
         *         caching off
         * @return void
         */
        public static function setupBypassing($key)
        {
            if (isset($_GET[$key])) {
                self::$_bypass = true;
            }
        }

        /**
         * write
         * 
         * Writes a value to the apc-level cache, based on the passed in key.
         * Handles false/null value storage logic.
         * 
         * @access public
         * @static
         * @param  string $key key for the cache value in the hash
         * @param  mixed $value value for the cache key, which cannot be an
         *         object or object reference
         * @param  integer $ttl. (default: 0) time to live (ttl) for the cache
         *         value, after which it won't be accessible in the store (in
         *         seconds)
         * @return void
         */
        public static function write($key, $value, $ttl = 0)
        {
            // ensure namespace set
            if (is_null(self::$_namespace)) {
                throw new Exception('Namespace not set');
            }

            // null value storage-attempt check
            if ($value === null) {
                throw new Exception(
                    'Cannot perform APCCache write: attempted to store null' .
                    'value in key *' . ($key) . '*.');
            }
            /**
             * <false> string storage-attempt check, which conflicts with method
             * of storing boolean false-value
             */
            elseif ($value === 'false') {
                throw new Exception(
                    'Cannot perform APCCache write: attempted to store string' .
                    'value of *false* in key *' . ($key) . '*.');
            }
            // false value check, and encoding
            elseif ($value === false) {
                $value = json_encode($value);
            }

            // safely attempt to write to APC store
            try {
                // write to store
                $key = self::_clean($key);
                $response = apc_store($key, $value, $ttl);
                if ($response === false) {
                    throw new Exception('Error writing');
                }

                // increment statistic (after store call to allow for exception)
                ++self::$_analytics['writes'];
            } catch(Exception $exception) {
                throw new Exception(
                    'APCCache Error: Exception while attempting to write to' .
                    'store.'
                );
            }
        }
    }
