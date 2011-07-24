<?php

    /**
     * Abstract APCCache class. Provides accessors for reading, writing and
     *     flushing an apc-level cache/data-store.
     * 
     * @todo implement prefixing
     * @note handles false-value caching through json_encode-ing
     * @abstract
     */
    abstract class APCCache
    {
        /**
         * _misses. Number of failed apc-level cache reads/hits.
         * 
         * (default value: 0)
         * 
         * @var int
         * @access protected
         * @static
         */
        protected static $_misses = 0;

        /**
         * _reads. Number of successful apc-level cache reads/hits.
         * 
         * (default value: 0)
         * 
         * @var int
         * @access protected
         * @static
         */
        protected static $_reads = 0;

        /**
         * _writes. Number of apc-level cache writes/sets.
         * 
         * (default value: 0)
         * 
         * @var int
         * @access protected
         * @static
         */
        protected static $_writes = 0;

        /**
         * flush function. Empties apc-level cache records and bytecode/opcode
         *     stores.
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
         * getMisses function. Returns the number of apc-level missed cache
         *     reads.
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
         * getReads function. Returns the number of apc-level successful cache
         *     reads.
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
         * getStats function. Returns an associative array of apc-level cache
         *     performance statistics.
         * 
         * @access public
         * @static
         * @return array associative array of key APC statistics
         */
        public static function getStats()
        {
            return array(
                'misses' => self::$_misses,
                'reads' => self::$_reads,
                'writes' => self::$_writes
            );
        }

        /**
         * getWrites function. Returns the number of successful apc-level cache
         *     writes.
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
         * read function. Attempts to read an apc-level cache record, returning
         *     null if it couldn't be accessed. Handles false/null return value
         *     logic.
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
         * write function. Writes a value to the apc-level cache, based on the
         *     passed in key. Handles false/null value storage logic.
         * 
         * @access public
         * @static
         * @param string $key key for the cache value in the hash
         * @param mixed $value value for the cache key, which cannot be an
         *     object or object reference
         * @param int $ttl. (default: 0) time to live (ttl) for the cache value,
         *     after which it won't be accessible in the store (in seconds)
         * @return void
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
                // write to store
                apc_store($key, $value, $ttl);

                // increment statistic (after store call to allow for exception)
                ++self::$_writes;
            } catch(Exception $exception) {
                throw new Exception(
                    'APCCache Error: Exception while attempting to write to' .
                    'store.'
                );
            }
        }
    }

?>
