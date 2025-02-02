<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class ExceptionListener
{
    private $logger;
    private $twig;

    public function __construct(LoggerInterface $logger, Environment $twig)
    {
        $this->logger = $logger;
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $this->logger->error($exception->getMessage());

        if ($exception instanceof NotFoundHttpException) {
            error_log('NotFoundHttpException triggered'); // Debugging line
            $response = new Response(
                $this->twig->render('errors/error404.html.twig'),
                Response::HTTP_NOT_FOUND
            );
        } else {
            error_log('General exception triggered'); // Debugging line
            $response = new Response(
                $this->twig->render('errors/error.html.twig', ['message' => $exception->getMessage()]),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $event->setResponse($response);
    }
}
