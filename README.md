PHP-APCCache
===
The PHP-APCCache library includes just one statically accessed class which acts
as a wrapper for PHP&#039;s native caching engine,
[APC](http://php.net/manual/en/book.apc.php). While not too popular when
compared with it&#039;s neighbour Memcached (due most likely to not being
distributed), APC is still a **very** solid, **very** fast local caching engine
which can be useful in a one-server environment.

Customary functionality with the data-store include flush, read and write
capabilities. Added functionality includes analytic methods to view the misses
(aka unsuccessful reads), reads and writes with the data-store.

One quirk of this class is it&#039;s dependency on the
[JSON](http://php.net/manual/en/book.json.php) extension/module. In order to
allow for the storage of the false boolean value, the JSON extension is used to
encode certain data values. This is a requirement due to APC&#039;s structure,
and it&#039;s lacking of flags to determine if a read was successfully
evaluated, regardless of the return value.

### Write/Read Example

    // dependency
    require_once APP . '/vendors/PHP-APCCache/APCCache.class.php';
    
    // write to cache; read; exit script
    APCCache::write('oliver', 'nassar');
    echo APCCache::read('oliver');
    exit(0);

The above example simply writes to the APC data-store, reads from it, and exits
the script. Nothing fancy, and super-straightforward.
