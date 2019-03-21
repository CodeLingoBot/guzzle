<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;

/**
 * Creates curl resources from a request
 */
class CurlFactory implements CurlFactoryInterface
{
    /** @var array */
    private $handles = [];

    /** @var int Total number of idle handles to keep in cache */
    private $maxHandles;

    /**
     * @param int $maxHandles Maximum number of idle handles.
     */
    public function __construct($maxHandles)
    {
        $this->maxHandles = $maxHandles;
    }

    public function create(RequestInterface $request, array $options)
    {
        if (isset($options['curl']['body_as_string'])) {
            $options['_body_as_string'] = $options['curl']['body_as_string'];
            unset($options['curl']['body_as_string']);
        }

        $easy = new EasyHandle;
        $easy->request = $request;
        $easy->options = $options;
        $conf = $this->getDefaultConf($easy);
        $this->applyMethod($easy, $conf);
        $this->applyHandlerOptions($easy, $conf);
        $this->applyHeaders($easy, $conf);
        unset($conf['_headers']);

        // Add handler options from the request configuration options
        if (isset($options['curl'])) {
            $conf = array_replace($conf, $options['curl']);
        }

        $conf[CURLOPT_HEADERFUNCTION] = $this->createHeaderFn($easy);
        $easy->handle = $this->handles
            ? array_pop($this->handles)
            : curl_init();
        curl_setopt_array($easy->handle, $conf);

        return $easy;
    }

    public function release(EasyHandle $easy)
    {
        $resource = $easy->handle;
        unset($easy->handle);

        if (count($this->handles) >= $this->maxHandles) {
            curl_close($resource);
        } else {
            // Remove all callback functions as they can hold onto references
            // and are not cleaned up by curl_reset. Using curl_setopt_array
            // does not work for some reason, so removing each one
            // individually.
            curl_setopt($resource, CURLOPT_HEADERFUNCTION, null);
            curl_setopt($resource, CURLOPT_READFUNCTION, null);
            curl_setopt($resource, CURLOPT_WRITEFUNCTION, null);
            curl_setopt($resource, CURLOPT_PROGRESSFUNCTION, null);
            curl_reset($resource);
            $this->handles[] = $resource;
        }
    }

    /**
     * Completes a cURL transaction, either returning a response promise or a
     * rejected promise.
     *
     * @param callable             $handler
     * @param EasyHandle           $easy
     * @param CurlFactoryInterface $factory Dictates how the handle is released
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public static function finish(
        callable $handler,
        EasyHandle $easy,
        CurlFactoryInterface $factory
    ) {
        if (isset($easy->options['on_stats'])) {
            self::invokeStats($easy);
        }

        if (!$easy->response || $easy->errno) {
            return self::finishError($handler, $easy, $factory);
        }

        // Return the response if it is present and there is no error.
        $factory->release($easy);

        // Rewind the body of the response if possible.
        $body = $easy->response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return new FulfilledPromise($easy->response);
    }

    private static function invokeStats(EasyHandle $easy)
    {
        $curlStats = curl_getinfo($easy->handle);
        $stats = new TransferStats(
            $easy->request,
            $easy->response,
            $curlStats['total_time'],
            $easy->errno,
            $curlStats
        );
        call_user_func($easy->options['on_stats'], $stats);
    }

    private static function finishError(
        callable $handler,
        EasyHandle $easy,
        CurlFactoryInterface $factory
    ) {
        // Get error information and release the handle to the factory.
        $ctx = [
            'errno' => $easy->errno,
            'error' => curl_error($easy->handle),
        ] + curl_getinfo($easy->handle);
        $factory->release($easy);

        // Retry when nothing is present or when curl failed to rewind.
        if (empty($easy->options['_err_message'])
            && (!$easy->errno || $easy->errno == 65)
        ) {
            return self::retryFailedRewind($handler, $easy, $ctx);
        }

        return self::createRejection($easy, $ctx);
    }

    private static function createRejection(EasyHandle $easy, array $ctx)
    {
        static $connectionErrors = [
            CURLE_OPERATION_TIMEOUTED  => true,
            CURLE_COULDNT_RESOLVE_HOST => true,
            CURLE_COULDNT_CONNECT      => true,
            CURLE_SSL_CONNECT_ERROR    => true,
            CURLE_GOT_NOTHING          => true,
        ];

        // If an exception was encountered during the onHeaders event, then
        // return a rejected promise that wraps that exception.
        if ($easy->onHeadersException) {
            return \GuzzleHttp\Promise\rejection_for(
                new RequestException(
                    'An error was encountered during the on_headers event',
                    $easy->request,
                    $easy->response,
                    $easy->onHeadersException,
                    $ctx
                )
            );
        }

        $message = sprintf(
            'cURL error %s: %s (%s)',
            $ctx['errno'],
            $ctx['error'],
            'see https://curl.haxx.se/libcurl/c/libcurl-errors.html'
        );

        // Create a connection exception if it was a specific error code.
        $error = isset($connectionErrors[$easy->errno])
            ? new ConnectException($message, $easy->request, null, $ctx)
            : new RequestException($message, $easy->request, $easy->response, null, $ctx);

        return \GuzzleHttp\Promise\rejection_for($error);
    }

    

    

    

    

    /**
     * Remove a header from the options array.
     *
     * @param string $name    Case-insensitive header to remove
     * @param array  $options Array of options to modify
     */
    

    

    /**
     * This function ensures that a response was set on a transaction. If one
     * was not set, then the request is retried if possible. This error
     * typically means you are sending a payload, curl encountered a
     * "Connection died, retrying a fresh connect" error, tried to rewind the
     * stream, and then encountered a "necessary data rewind wasn't possible"
     * error, causing the request to be sent through curl_multi_info_read()
     * without an error status.
     */
    private static function retryFailedRewind(
        callable $handler,
        EasyHandle $easy,
        array $ctx
    ) {
        try {
            // Only rewind if the body has been read from.
            $body = $easy->request->getBody();
            if ($body->tell() > 0) {
                $body->rewind();
            }
        } catch (\RuntimeException $e) {
            $ctx['error'] = 'The connection unexpectedly failed without '
                . 'providing an error. The request would have been retried, '
                . 'but attempting to rewind the request body failed. '
                . 'Exception: ' . $e;
            return self::createRejection($easy, $ctx);
        }

        // Retry no more than 3 times before giving up.
        if (!isset($easy->options['_curl_retries'])) {
            $easy->options['_curl_retries'] = 1;
        } elseif ($easy->options['_curl_retries'] == 2) {
            $ctx['error'] = 'The cURL request was retried 3 times '
                . 'and did not succeed. The most likely reason for the failure '
                . 'is that cURL was unable to rewind the body of the request '
                . 'and subsequent retries resulted in the same error. Turn on '
                . 'the debug option to see what went wrong. See '
                . 'https://bugs.php.net/bug.php?id=47204 for more information.';
            return self::createRejection($easy, $ctx);
        } else {
            $easy->options['_curl_retries']++;
        }

        return $handler($easy->request, $easy->options);
    }

    
}
