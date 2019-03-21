<?php
namespace GuzzleHttp;

/**
 * Expands URI templates. Userland implementation of PECL uri_template.
 *
 * @link http://tools.ietf.org/html/rfc6570
 */
class UriTemplate
{
    /** @var string URI template */
    private $template;

    /** @var array Variables to use in the template expansion */
    private $variables;

    /** @var array Hash for quick operator lookups */
    private static $operatorHash = [
        ''  => ['prefix' => '',  'joiner' => ',', 'query' => false],
        '+' => ['prefix' => '',  'joiner' => ',', 'query' => false],
        '#' => ['prefix' => '#', 'joiner' => ',', 'query' => false],
        '.' => ['prefix' => '.', 'joiner' => '.', 'query' => false],
        '/' => ['prefix' => '/', 'joiner' => '/', 'query' => false],
        ';' => ['prefix' => ';', 'joiner' => ';', 'query' => true],
        '?' => ['prefix' => '?', 'joiner' => '&', 'query' => true],
        '&' => ['prefix' => '&', 'joiner' => '&', 'query' => true]
    ];

    /** @var array Delimiters */
    private static $delims = [':', '/', '?', '#', '[', ']', '@', '!', '$',
        '&', '\'', '(', ')', '*', '+', ',', ';', '='];

    /** @var array Percent encoded delimiters */
    private static $delimsPct = ['%3A', '%2F', '%3F', '%23', '%5B', '%5D',
        '%40', '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C',
        '%3B', '%3D'];

    public function expand($template, array $variables)
    {
        if (false === strpos($template, '{')) {
            return $template;
        }

        $this->template = $template;
        $this->variables = $variables;

        return preg_replace_callback(
            '/\{([^\}]+)\}/',
            [$this, 'expandMatch'],
            $this->template
        );
    }

    /**
     * Parse an expression into parts
     *
     * @param string $expression Expression to parse
     *
     * @return array Returns an associative array of parts
     */
    

    /**
     * Process an expansion
     *
     * @param array $matches Matches met in the preg_replace_callback
     *
     * @return string Returns the replacement string
     */
    

    /**
     * Determines if an array is associative.
     *
     * This makes the assumption that input arrays are sequences or hashes.
     * This assumption is a tradeoff for accuracy in favor of speed, but it
     * should work in almost every case where input is supplied for a URI
     * template.
     *
     * @param array $array Array to check
     *
     * @return bool
     */
    

    /**
     * Removes percent encoding on reserved characters (used with + and #
     * modifiers).
     *
     * @param string $string String to fix
     *
     * @return string
     */
    
}
