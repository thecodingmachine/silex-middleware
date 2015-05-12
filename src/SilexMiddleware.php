<?php

namespace Mouf\StackPhp;

use Silex\Application;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This StackPHP middleware creates a middleware from a Silex application.
 * Basically, the middleware will use the Silex application to catch any request.
 * If no request is found, instead of returning a 404 page, control is passed
 * to the next middleware.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class SilexMiddleware implements HttpKernelInterface
{
	private $request;
	private $type;
	private $catch;
	private $silex;
	
	/**
	 * 
	 * @param HttpKernelInterface $app The next application the request will be forwarded to if not handled by Silex
	 * @param Application $silex The Silex application that will try catching requests
	 */
	public function __construct(HttpKernelInterface $app, Application $silex) {
		$this->silex = $silex;
		$this->silex->error(function(\Exception $e, $code) use ($app) {
			if ($code == 404) {
				$response = $app->handle($this->request, $this->type, $this->catch);
				// Let's force the return code of the response into HttpKernel:
				$response->headers->set('X-Status-Code', $response->getStatusCode());
				return $response;				
			} else {
				if (!$this->catch) {
					// If we are not to catch the exception, let's rethrow it.
					throw new $e;
				}
				return;
			}
		});
	}
	
	/* (non-PHPdoc)
	 * @see \Symfony\Component\HttpKernel\HttpKernelInterface::handle()
	 */
	public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {
		$this->request = $request;
		$this->type = $type;
		$this->catch = $catch;
		
		return $this->silex->handle($request, $type, true);
	}
}
