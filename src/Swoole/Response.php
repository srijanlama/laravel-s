<?php


namespace Hhxsv5\LaravelS\Swoole;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

abstract class Response implements ResponseInterface
{
    protected $swooleResponse;

    protected $laravelResponse;

    public function __construct(\swoole_http_response $swooleResponse, SymfonyResponse $laravelResponse)
    {
        $this->swooleResponse = $swooleResponse;
        $this->laravelResponse = $laravelResponse;
    }

    public function sendStatusCode()
    {
        $this->swooleResponse->status($this->laravelResponse->getStatusCode());
    }

    public function sendHeaders()
    {
        $headers = method_exists($this->laravelResponse->headers, 'allPreserveCaseWithoutCookies') ?
            $this->laravelResponse->headers->allPreserveCaseWithoutCookies() : $this->laravelResponse->headers->allPreserveCase();
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $this->swooleResponse->header($name, $value);
            }
        }
    }

    public function sendCookies()
    {
        $hasIsRaw = null;
        foreach ($this->laravelResponse->headers->getCookies() as $cookie) {
            if ($hasIsRaw === null) {
                $hasIsRaw = method_exists($cookie, 'isRaw');
            }
            $setCookie = $hasIsRaw && $cookie->isRaw() ? 'rawcookie' : 'cookie';
            $this->swooleResponse->$setCookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
    }

    public function send($gzip = false)
    {
        $this->sendStatusCode();
        $this->sendHeaders();
        $this->sendCookies();
        if ($gzip) {
            $this->gzip();
        }
        $this->sendContent();
    }
}
