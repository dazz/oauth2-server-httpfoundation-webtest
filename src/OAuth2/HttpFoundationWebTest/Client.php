<?php
namespace OAuth2\HttpFoundationWebTest;

use OAuth2\HttpFoundationBridge\Request;

use Symfony\Component\HttpKernel\Client as BaseClient;
use Symfony\Component\BrowserKit\Request as DomRequest;
use Symfony\Component\BrowserKit\Cookie as DomCookie;
use Symfony\Component\HttpKernel\TerminableInterface;

class Client extends BaseClient
{
    /**
     * Makes a request.
     *
     * @param Request $request A Request instance
     *
     * @return Response A Response instance
     */
    protected function doRequest($request)
    {
        $response = $this->kernel->handle($request);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }

        return $response;
    }

    /**
     * Calls a URI.
     *
     * @param string  $method        The request method
     * @param string  $uri           The URI to fetch
     * @param array   $parameters    The Request parameters
     * @param array   $files         The files
     * @param array   $server        The server parameters (HTTP headers are referenced with a HTTP_ prefix as PHP does)
     * @param string  $content       The raw body data
     * @param Boolean $changeHistory Whether to update the history or not (only used internally for back(), forward(), and reload())
     *
     * @return Crawler
     *
     * @api
     */
    public function request($method, $uri, array $parameters = array(), array $files = array(), array $server = array(), $content = null, $changeHistory = true)
    {
        $uri = $this->getAbsoluteUri($uri);

        $server = array_merge($this->server, $server);
        if (!$this->history->isEmpty()) {
            $server['HTTP_REFERER'] = $this->history->current()->getUri();
        }
        $server['HTTP_HOST'] = parse_url($uri, PHP_URL_HOST);
        $server['HTTPS'] = 'https' == parse_url($uri, PHP_URL_SCHEME);

        $request = new DomRequest($uri, $method, $parameters, $files, $this->cookieJar->allValues($uri), $server, $content);

        $this->request = $this->filterRequest($request);

        if (true === $changeHistory) {
            $this->history->add($request);
        }

        if ($this->insulated) {
            $this->response = $this->doRequestInProcess($this->request);
        } else {
            $this->response = $this->doRequest($this->request);
        }

        $response = $this->filterResponse($this->response);

        $this->cookieJar->updateFromResponse($response);

        $this->redirect = $response->getHeader('Location');

        if ($this->followRedirects && $this->redirect) {
            return $this->crawler = $this->followRedirect();
        }

        return $this->crawler = $this->createCrawlerFromContent($request->getUri(), $response->getContent(), $response->getHeader('Content-Type'));
    }

    /**
     * Converts the BrowserKit request to a HttpKernel request.
     *
     * @param DomRequest $request A Request instance
     *
     * @return Request A Request instance
     */
    protected function filterRequest(DomRequest $request)
    {
        $httpRequest = Request::create($request->getUri(), $request->getMethod(), $request->getParameters(), $request->getCookies(), $request->getFiles(), $request->getServer(), $request->getContent());

        /** this happens so we can send oauth2 tokens in the header */
        if(isset($this->server['HTTP_AUTHORIZATION'])){
            $httpRequest->headers->add(array('HTTP_AUTHORIZATION'=>$this->server['HTTP_AUTHORIZATION']));
        }

        $httpRequest->files->replace($this->filterFiles($httpRequest->files->all()));

        return $httpRequest;
    }
}
