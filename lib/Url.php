<?php


class Url
{
    const PATH_SEGMENT_SEPARATOR = '/';

    protected $original_url;

    protected $scheme;
    
    protected $user;
    
    protected $pass;
    
    protected $host;
    
    protected $port;
    
    protected $path;
    
    protected $query;
    
    protected $query_array = array();
    
    protected $fragment;

    
    public function __construct($url)
    {

        $url = trim($url);

        $this->original_url = $url;


        // parse_url erkennt keine Urls die mit // anfangen
        if ($this->isProtocolRelative()) {

            $url = 'http:' . $url;

        }


        $components = parse_url($url);

        if (isset($components['scheme'])) {
            $this->setScheme( $components['scheme'] );
        }

        if (isset($components['user'])) {
            $this->setUser( $components['user'] );
        }

        if (isset($components['pass'])) {
            $this->setPass( $components['pass'] );
        }

        if (isset($components['host'])) {
            $this->setHost( $components['host'] );
        }

        if (isset($components['port'])) {
            $this->setPort( $components['port'] );
        }

        if (isset($components['path'])) {
            $this->setPath( $components['path'] );
        } else {
            $this->setPath( '/' );
        }

        if (isset($components['query'])) {
            $this->setQuery( $components['query'] );
        }

        if (isset($components['fragment'])) {
            $this->setFragment( $components['fragment'] );
        }


        //UrlControl::debug($components);

    }


    /**
     * @return bool
     */
    public function isProtocolRelative()
    {
        return (substr($this->original_url, 0, 2) == '//');
    }

    /**
     * @param string $fragment
     * @return Url $this
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
    }

    /**
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param string $host
     * @return Url $this
     */
    public function setHost($host)
    {
        $this->host = strtolower($host);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param $pass
     * @return Url $this
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $path
     * @return Url $this
     */
    public function setPath($path)
    {
        $this->path = static::normalizePath($path);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = intval($port);
    }

    /**
     * @return int
     */
    public function getPort()
    {        
        return $this->port;
    }


    /**
     * Set the query from an already url encoded query string
     *
     * @param string $query The query string, must be already url encoded!!
     * @return Url $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        parse_str(html_entity_decode($query), $this->query_array);
    }

    /**
     * @param array $query_array
     * @return Url $this
     */
    public function setQueryFromArray(array $query_array)
    {
        $this->query_array = $query_array;
        $this->query = http_build_query($this->query_array);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Url $this
     */
    public function setQueryParameter($name, $value)
    {
        $this->query_array[$name] = $value;
        $this->query = http_build_query($this->query_array);
    }

    /**
     * @return string The url encoded query string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the query parameters as array
     *
     * @return array
     */
    public function getQueryArray()
    {
        return $this->query_array;
    }

    /**
     * Get the query parameters as array
     *
     * @return array
     */
    public function getQueryAsArray()
    {
        return $this->getQueryArray();
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getQueryParameter($name)
    {
        if (isset($this->query_array[$name])) {
            
            return $this->query_array[$name];

        } else {

            return null;

        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasQueryParameter($name)
    {
        return isset($this->query_array[$name]);
    }


    /**
     * @param string $scheme
     * @return Url $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = strtolower($scheme);
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $user
     * @return Url $this
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Returns the HTTP host being requested.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port   = $this->getPort();

        if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
            return $this->getHost();
        }

        return $this->getHost() . ':' . $port;
    }


    /**
     * Gets the scheme and HTTP host.
     *
     * @return string
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }


    /**
     * Get the filename from the path (the last path segment as returned by basename())
     *
     * @return string
     */
    public function getFilename()
    {
        return static::filename($this->path);
    }

    /**
     * Get the directory name from the path
     *
     * @return string
     */
    public function getDirname()
    {
        return static::dirname($this->path);
    }


    public function appendPathSegment($segment)
    {
        if (substr($this->path, -1) != static::PATH_SEGMENT_SEPARATOR) {

            $this->path .= static::PATH_SEGMENT_SEPARATOR;

        }

        if (substr($segment, 0, 1) == static::PATH_SEGMENT_SEPARATOR) {

            $segment = substr($segment, 1);

        }

        $this->path .= $segment;
    }

    /**
     * @param Url|string $compare_url
     * @return bool
     */
    public function compare($compare_url)
    {
        if (! ($compare_url instanceof Url)) {

            $compare_url = new static($compare_url);

        }

        return $this->getScheme()   == $compare_url->getScheme()
            && $this->getUser()     == $compare_url->getUser()
            && $this->getPass()     == $compare_url->getPass()
            && $this->compareHost(     $compare_url->getHost())
            && $this->getPort()     == $compare_url->getPort()
            && $this->comparePath(     $compare_url->getPath())
            && $this->compareQuery(    $compare_url->getQuery())
            && $this->getFragment() == $compare_url->getFragment()
            ;
    }

    /**
     * @param string $compare_path
     * @return bool
     */
    public function comparePath($compare_path)
    {
        return $this->getPath() == static::normalizePath($compare_path);
    }

    /**
     * Check whether the path is within another path
     *
     * @param string $another_path
     * @return bool True if $this->path is a subpath of $another_path
     */
    public function isInPath($another_path)
    {
        $p = static::normalizePath($another_path);
        if ($p == $this->path) {

            return true;

        }

        if (substr($p, -1) != self::PATH_SEGMENT_SEPARATOR) {

            $p .= self::PATH_SEGMENT_SEPARATOR;

        }

        return (strlen($this->path) > $p && substr($this->path, 0, strlen($p)) == $p);
    }

    /**
     * Get the base URL for the request.
     *
     * @param  string  $scheme
     * @param  string  $root
     * @return string
     */
    protected function getRootUrl($scheme, $root = null)
    {
        $root = $root ?: self::current();

        if (strpos($root, 'http://') === 0) {
            $start = 'http://';
        } else {
            $start = 'https://';            
        }

        return preg_replace('~'.$start.'~', $scheme, $root, 1);
    }


    /**
     * Determine if the given path is a valid URL.
     *
     * @param  string  $path
     * @return bool
     */
    public function isValidUrl($path)
    {
        $needles = array('#', '//', 'mailto:', 'tel:');

        foreach ((array) $needles as $needle) {
            
            if ($needle != '' && strpos($path, $needle) === 0) {

                return true;

            }

        }

        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Format the given URL segments into a single URL.
     *
     * @param  string  $root
     * @param  string  $path
     * @param  string  $tail
     * @return string
     */
    protected function trimUrl($root, $path, $tail = '')
    {
        return trim($root.'/'.trim($path.'/'.$tail, '/'), '/');
    }

    /**
     * @param string $compare_hostname
     * @return bool
     */
    public function compareHost($compare_hostname)
    {
        // TODO: normalize IDN
        return $this->getHost() == strtolower($compare_hostname);
    }


    /**
     * @param string|array|Url $compare_query
     * @return bool
     */
    public function compareQuery($compare_query)
    {
        $compare_query_array = array();
        
        if (is_array($compare_query)) {

            $compare_query_array = $compare_query;

        } elseif ($compare_query instanceof Url) {

            $compare_query_array = $compare_query->getQueryArray();

        } else {

            parse_str(html_entity_decode((string) $compare_query), $compare_query_array);

        }

        return !count(array_diff_assoc($this->getQueryArray(), $compare_query_array));
    }


    public function getDomain() 
    {
        
        return $this->getHost();
        
    }


    public function getFullUrl() 
    {

        $string = '';

        $string .= $this->getSchemeAndHttpHost();
        $string .= $this->getUrl();

        return $string;
        
    }


    public function getUrl() 
    {

        $string = '';

        if ($this->getPath()) {
            $string .= $this->getPath();
        }

        if ($this->getQuery()) {
            $string .= '?' . $this->getQuery();
        }

        if ($this->getFragment()) {
            $string .= '#' . $this->getFragment();
        }

        return $string;
        
    }


    /**
     * Generate a absolute URL to the given path.
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool  $secure
     * @return string
     */
    static public function to($path, $extra = array(), $secure = null)
    {
        $url = static::parse($path);

        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
        if ($url->isValidUrl($path)) return $path;


        $scheme = $url->getScheme($secure);

        $tail = implode('/', array_map(
            'rawurlencode', (array) $extra)
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        $root = $url->getRootUrl($scheme);

        return $url->trimUrl($root, $path, $tail);
    }

    static public function current()
    {

        $secure = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;
        $scheme = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/')) . (($secure) ? 's' : '');


        $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        
        return $url;
    }


    static public function previous()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        $url = static::parse($referer);

        if ($url->getScheme() == 'http') {

            $url->setPort(80);

        } elseif ($url->getScheme() == 'https') {
            
            $url->setPort(443);

        }

        return $url->getUrl();
    }

    /**
     * @param string $path
     * @return string
     */
    static public function normalizePath($path)
    {
        $path = preg_replace('|/\./|', '/', $path);   // entferne /./
        $path = preg_replace('|^\./|', '', $path);    // entferne ./ am Anfang
        $i = 0;
        while (preg_match('|[^/]+/\.{2}/|', $path) && $i < 10) {
            $path = preg_replace('|([^/]+)(/\.{2}/)|e', "'\\1'=='..'?'\\0':''", $path);
            $i++;
        }
        return $path;
    }


    /**
     * @param $path
     * @return string
     */
    static public function filename($path)
    {
        if (substr($path, -1) == self::PATH_SEGMENT_SEPARATOR) {
            
            return '';

        } else {

            return basename($path);

        }
    }

    static public function dirname($path)
    {
        if (substr($path, -1) == self::PATH_SEGMENT_SEPARATOR) {

            return substr($path, 0, -1);

        } else {
            
            $dirname = dirname($path);
            
            if ($dirname == DIRECTORY_SEPARATOR) {

                $dirname = self::PATH_SEGMENT_SEPARATOR;

            }

            return $dirname;
        }
    }

    /**
     * @param string $url
     * @return Url
     */
    static public function parse($url)
    {
        return new static($url);
    }
}
