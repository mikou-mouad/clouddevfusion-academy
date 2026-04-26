<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Lit l'en-tête X-Session-Id et l'injecte comme cookie PHPSESSID pour que la session soit chargée (proxy).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 1024)]
class SessionIdHeaderListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $sessionId = $request->headers->get('X-Session-Id');
        if ($sessionId !== null && $sessionId !== '' && \strlen($sessionId) <= 128) {
            $request->cookies->set('PHPSESSID', $sessionId);
            $_COOKIE['PHPSESSID'] = $sessionId;
        }
    }
}
