<?php

declare(strict_types=1);

namespace BEAR\QueryRepository;

use BEAR\QueryRepository\Exception\LogicException;
use BEAR\Resource\ResourceObject;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Throwable;

use function assert;
use function get_class;
use function sprintf;
use function trigger_error;

use const E_USER_WARNING;

class CacheInterceptor implements MethodInterceptor
{
    /** @var QueryRepositoryInterface */
    private $repository;

    public function __construct(
        QueryRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(MethodInvocation $invocation)
    {
        $ro = $invocation->getThis();
        assert($ro instanceof ResourceObject);
        try {
            $state = $this->repository->get($ro->uri);
        } catch (Throwable $e) {
            $this->triggerWarning($e);

            return $invocation->proceed(); // @codeCoverageIgnore
        }

        if ($state) {
            $ro->uri = $state->uri;
            $ro->code = $state->code;
            $ro->headers = $state->headers;
            $ro->body = $state->body;
            $ro->view = $state->view;

            return $ro;
        }

        /** @psalm-suppress MixedAssignment */
        $ro = $invocation->proceed();
        assert($ro instanceof ResourceObject);
        try {
            $ro->code === 200 ? $this->repository->put($ro) : $this->repository->purge($ro->uri);
        } catch (LogicException $e) {
            throw $e;
        } catch (Throwable $e) {  // @codeCoverageIgnore
            $this->triggerWarning($e); // @codeCoverageIgnore
        }

        return $ro;
    }

    /**
     * Trigger warning
     *
     * When the cache server is down, it will issue a warning rather than an exception to continue service.
     */
    private function triggerWarning(Throwable $e): void
    {
        $message = sprintf('%s: %s in %s:%s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
        trigger_error($message, E_USER_WARNING);
        // @codeCoverageIgnoreStart
    }
}
