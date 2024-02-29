<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Middleware;

use GraphQL\Error\DebugFlag;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Server\StandardServer;
use GraphQL\Upload\UploadMiddleware;
use Hyperf\Codec\Json;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GraphQLMiddleware implements MiddlewareInterface
{
    protected ?int $debug;

    public function __construct(
        protected StandardServer $standardServer,
        protected ConfigInterface $config,
        protected HttpCodeDeciderInterface $httpCodeDecider
    ) {
        $this->debug = $this->config->get('graphql.debug', DebugFlag::RETHROW_UNSAFE_EXCEPTIONS);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isGraphQL($request)) {
            return $handler->handle($request);
        }

        $request = $this->parseUpload($this->parseRequest($request));

        return $this->handleRequest($request);
    }

    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->standardServer->executePsrRequest($request);

        if ($result instanceof ExecutionResult) {
            return ResponseContext::get()
                ->setStatus($this->httpCodeDecider->decideHttpStatusCode($result))
                ->setHeader('Content-Type', 'application/json')
                ->setBody(new SwooleStream(Json::encode($result->toArray($this->debug))));
        }

        if (is_array($result)) {
            $finalResult = array_map(fn (ExecutionResult $result) => $result->toArray($this->debug), $result);
            $statuses = array_map([$this->httpCodeDecider, 'decideHttpStatusCode'], $result);
            $status = max($statuses);

            return ResponseContext::get()
                ->setStatus($status)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(new SwooleStream(Json::encode($finalResult)));
        }

        if ($result instanceof Promise) {
            throw new RuntimeException('Only SyncPromiseAdapter is supported');
        }

        throw new RuntimeException('Unexpected response from StandardServer::executePsrRequest');
    }

    protected function parseRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        if ('POST' === strtoupper($request->getMethod()) && empty($request->getParsedBody())) {
            $content = $request->getBody()->getContents();
            $parsedBody = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON received in POST body: '.json_last_error_msg());
            }

            $request = $request->withParsedBody($parsedBody);
        }

        return $request;
    }

    protected function parseUpload(ServerRequestInterface $request): ServerRequestInterface
    {
        if (class_exists('\GraphQL\Upload\UploadMiddleware')) {
            // Let's parse the request and adapt it for file uploads.
            $uploadMiddleware = new UploadMiddleware();
            $request = $uploadMiddleware->processRequest($request);
        }

        return $request;
    }

    protected function isGraphQL(ServerRequestInterface $request): bool
    {
        return 'POST' === strtoupper($request->getMethod())
            && $request->getUri()->getPath() === $this->config->get('graphql.uri');
    }
}
