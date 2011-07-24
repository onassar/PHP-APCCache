<?php

    /**
     * Abstract APCCache class.
     * 
     * @note handles false-value caching through json_encode-ing
     * @abstract
     */
    abstract class APCCache
    {
        /**
         * _misses. Number of failed APC key hits/APC key read failures.
         * 
         * (default value: 0)
         * 
         * @var int
         * @access protected
         * @static
         */
        protected static $_misses = 0;

        /**
         * _reads. Number of successful APC key reads/successful hits
         * 
         * (default value: 0)
         * 
         * @var int
         * @access protected
         * @static
         */
        protected static $_reads = 0;

        /**
         * _writes. Number of APC key/value sets/writes.
         * 
         * (default value: 0)
         * 
         * @var int
         * @access protected
         * @static
         */
        protected static $_writes = 0;

        /**
         * flush function. Empties the server's APC 'user' and 'system' caches
         *     ('user' being data/value set; 'system' being cached
         *     files/bytecode/opcode).
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
         * getMisses function. Returns the number of times a request was made to
         *     APC with a key, which couldn't be found/was missed.
         * 
         * @access public
         * @static
         * @return int number of read/fetch misses for APC requests
         */
        public static function getMisses()
        {
            return self::$_misses;
        }

        /**
         * getReads function. Returns the number of times a value was
         *     successfully read from APC.
         * 
         * @access public
         * @static
         * @return int number of read/fetch requests for APC
         */
        public static function getReads()
        {
            return self::$_reads;
        }

        /**
         * getStats function. Returns an associative array of statistics for the
         *     current request's APC usage.
         * 
         * @access public
         * @static
         * @return array associative array of key APC statistics
         */
        public static function getStats()
        {
            return array(
                'reads' => self::$_reads,
                'misses' => self::$_misses,
                'writes' => self::$_writes
            );
        }

        /**
         * getWrites function. Returns the number of successful write's to APC
         * 
         * @access public
         * @static
         * @return int number of times a mixed value was written to APC
         */
        public static function getWrites()
        {
            return self::$_writes;
        }

        /**
         * read function. Tries to read a value from the APC cache based on the
         *     passed in key. Handles false/null return value logic changes.
         * 
         * @access public
         * @static
         * @param string $key key for the cache position
         * @return mixed cache record value, or else null if it's not present
         */
        public static function read($key)
        {
            // safely attempt to read from APC store
            try {
                // check apc
                $response = apc_fetch($key);

                // not found
                if ($response === false) {
                    ++self::$_misses;
                    return null;
                }

                // increment apc-reads
                ++self::$_reads;

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
         * write function. Write's a value to APC cache based on the passed in
         *     key. Handles false/null value storage logic changes.
         * 
         * @access public
         * @static
         * @param string $key key for the cache value in the hash
         * @param mixed $value value for the cache key, which cannot be an
         *     object or object reference
         * @param int $ttl. (default: 0) time to live (ttl) for the cache value,
         *     after which it won't be accessible in the mapping (in seconds)
         * @return bool whether or not the apc_store call was successful
         */
        public static function write($key, $value, $ttl = 0)
        {
            // null value storage-attempt check
            if ($value === null) {
                throw new Exception(
                    'Cannot perform APCCache write: attempted to store null' .
                    'value in key *' . ($key) . '*.');
            }
            /**
             * 'false' string storage-attempt check, which conflicts with method
             *     of storing boolean false-value
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
                // attempt to store
                if (apc_store($key, $value, $ttl) === false) {
                    return false;
                }
                ++self::$_writes;
                return true;
            } catch(Exception $exception) {
                throw new Exception(
                    'APCCache Error: Exception while attempting to write to' .
                    'store.'
                );
            }
        }
    }

?>
