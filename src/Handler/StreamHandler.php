<?php
namespace GuzzleHttp\Handler;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP handler that uses PHP's HTTP stream wrapper.
 */
class StreamHandler
{
    private $lastHeaders = [];

    /**
     * Sends an HTTP request.
     *
     * @param RequestInterface $request Request to send.
     * @param array            $options Request transfer options.
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        // Sleep if there is a delay specified.
        if (isset($options['delay'])) {
            usleep($options['delay'] * 1000);
        }

        $startTime = isset($options['on_stats']) ? microtime(true) : null;

        try {
            // Does not support the expect header.
            $request = $request->withoutHeader('Expect');

            // Append a content-length header if body size is zero to match
            // cURL's behavior.
            if (0 === $request->getBody()->getSize()) {
                $request = $request->withHeader('Content-Length', '0');
            }

            return $this->createResponse(
                $request,
                $options,
                $this->createStream($request, $options),
                $startTime
            );
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Determine if the error was a networking error.
            $message = $e->getMessage();
            // This list can probably get more comprehensive.
            if (strpos($message, 'getaddrinfo') // DNS lookup failed
                || strpos($message, 'Connection refused')
                || strpos($message, "couldn't connect to host") // error on HHVM
                || strpos($message, "connection attempt failed")
            ) {
                $e = new ConnectException($e->getMessage(), $request, $e);
            }
            $e = RequestException::wrapException($request, $e);
            $this->invokeStats($options, $request, $startTime, null, $e);

            return \GuzzleHttp\Promise\rejection_for($e);
        }
    }

    

    

    

    

    /**
     * Drains the source stream into the "sink" client option.
     *
     * @param StreamInterface $source
     * @param StreamInterface $sink
     * @param string          $contentLength Header specifying the amount of
     *                                       data to read.
     *
     * @return StreamInterface
     * @throws \RuntimeException when the sink option is invalid.
     */
    

    /**
     * Create a resource and check to ensure it was created successfully
     *
     * @param callable $callback Callable that returns stream resource
     *
     * @return resource
     * @throws \RuntimeException on error
     */
    

    

    

    

    

    

    

    

    

    

    

    
}
