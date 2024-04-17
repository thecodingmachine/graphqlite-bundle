<?php

namespace TheCodingMachine\GraphQLite\Bundle\EventListener;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;
use TheCodingMachine\GraphQLite\Http\HttpCodeDecider;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;

class ExceptionListener
{
    private readonly HttpCodeDeciderInterface $httpCodeDecider;

    public function __construct(
        ?HttpCodeDeciderInterface $httpCodeDecider = null,
    ) {
        $this->httpCodeDecider = $httpCodeDecider ?? new HttpCodeDecider();
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof GraphQLExceptionInterface) {
            $result = new ExecutionResult(
                errors: [new Error(
                    message: $exception->getMessage(),
                    previous: $exception,
                    extensions: $exception->getExtensions()
                )]
            );

            $response = new JsonResponse($result->toArray(), $this->httpCodeDecider->decideHttpStatusCode($result));

            $event->setResponse($response);
        }
    }
}
