<?php
namespace GuzzleHttp;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @method ResponseInterface get(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface head(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface put(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface post(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface patch(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface delete(string|UriInterface $uri, array $options = [])
 * @method Promise\PromiseInterface getAsync(string|UriInterface $uri, array $options = [])
 * @method Promise\PromiseInterface headAsync(string|UriInterface $uri, array $options = [])
 * @method Promise\PromiseInterface putAsync(string|UriInterface $uri, array $options = [])
 * @method Promise\PromiseInterface postAsync(string|UriInterface $uri, array $options = [])
 * @method Promise\PromiseInterface patchAsync(string|UriInterface $uri, array $options = [])
 * @method Promise\PromiseInterface deleteAsync(string|UriInterface $uri, array $options = [])
 */
class Client implements ClientInterface
{
    /** @var array Default request options */
    private $config;

    /**
     * Clients accept an array of constructor parameters.
     *
     * Here's an example of creating a client using a base_uri and an array of
     * default request options to apply to each request:
     *
     *     $client = new Client([
     *         'base_uri'        => 'http://www.foo.com/1.0/',
     *         'timeout'         => 0,
     *         'allow_redirects' => false,
     *         'proxy'           => '192.168.16.1:10'
     *     ]);
     *
     * Client configuration settings include the following options:
     *
     * - handler: (callable) Function that transfers HTTP requests over the
     *   wire. The function is called with a Psr7\Http\Message\RequestInterface
     *   and array of transfer options, and must return a
     *   GuzzleHttp\Promise\PromiseInterface that is fulfilled with a
     *   Psr7\Http\Message\ResponseInterface on success. "handler" is a
     *   constructor only option that cannot be overridden in per/request
     *   options. If no handler is provided, a default handler will be created
     *   that enables all of the request options below by attaching all of the
     *   default middleware to the handler.
     * - base_uri: (string|UriInterface) Base URI of the client that is merged
     *   into relative URIs. Can be a string or instance of UriInterface.
     * - **: any request option
     *
     * @param array $config Client configuration settings.
     *
     * @see \GuzzleHttp\RequestOptions for a list of available request options.
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['handler'])) {
            $config['handler'] = HandlerStack::create();
        } elseif (!is_callable($config['handler'])) {
            throw new \InvalidArgumentException('handler must be a callable');
        }

        // Convert the base_uri to a UriInterface
        if (isset($config['base_uri'])) {
            $config['base_uri'] = Psr7\uri_for($config['base_uri']);
        }

        $this->configureDefaults($config);
    }

    public function __call($method, $args)
    {
        if (count($args) < 1) {
            throw new \InvalidArgumentException('Magic request methods require a URI and optional options array');
        }

        $uri = $args[0];
        $opts = isset($args[1]) ? $args[1] : [];

        return substr($method, -5) === 'Async'
            ? $this->requestAsync(substr($method, 0, -5), $uri, $opts)
            : $this->request($method, $uri, $opts);
    }

    public function sendAsync(RequestInterface $request, array $options = [])
    {
        // Merge the base URI into the request URI if needed.
        $options = $this->prepareDefaults($options);

        return $this->transfer(
            $request->withUri($this->buildUri($request->getUri(), $options), $request->hasHeader('Host')),
            $options
        );
    }

    public function send(RequestInterface $request, array $options = [])
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        return $this->sendAsync($request, $options)->wait();
    }

    public function requestAsync($method, $uri = '', array $options = [])
    {
        $options = $this->prepareDefaults($options);
        // Remove request modifying parameter because it can be done up-front.
        $headers = isset($options['headers']) ? $options['headers'] : [];
        $body = isset($options['body']) ? $options['body'] : null;
        $version = isset($options['version']) ? $options['version'] : '1.1';
        // Merge the URI into the base URI.
        $uri = $this->buildUri($uri, $options);
        if (is_array($body)) {
            $this->invalidBody();
        }
        $request = new Psr7\Request($method, $uri, $headers, $body, $version);
        // Remove the option so that they are not doubly-applied.
        unset($options['headers'], $options['body'], $options['version']);

        return $this->transfer($request, $options);
    }

    public function request($method, $uri = '', array $options = [])
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        return $this->requestAsync($method, $uri, $options)->wait();
    }

    public function getConfig($option = null)
    {
        return $option === null
            ? $this->config
            : (isset($this->config[$option]) ? $this->config[$option] : null);
    }

    

    /**
     * Configures the default options for a client.
     *
     * @param array $config
     */
    

    /**
     * Merges default options into the array.
     *
     * @param array $options Options to modify by reference
     *
     * @return array
     */
    

    /**
     * Transfers the given request and applies request options.
     *
     * The URI of the request is not modified and the request options are used
     * as-is without merging in default options.
     *
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return Promise\PromiseInterface
     */
    

    /**
     * Applies the array of request options to a request.
     *
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return RequestInterface
     */
    

    
}
