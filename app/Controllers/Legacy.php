<?php

declare(strict_types=1);

namespace App\Controllers;

require_once APPPATH . 'Legacy/Bootstrap.php';

use CodeIgniter\HTTP\ResponseInterface;
use LegacyHttpException;
use LegacyRedirectException;
use ReflectionMethod;
use Throwable;

final class Legacy extends BaseController
{
    public function dispatch(string ...$routeSegments): ResponseInterface|string
    {
        $path           = implode('/', $routeSegments);
        $segments       = $path === '' ? [] : explode('/', trim($path, '/'));
        $controllerName = $segments[0] ?? 'login';
        $method         = $segments[1] ?? 'index';
        $arguments      = array_slice($segments, 2);

        $controllerFile = $this->findControllerFile($controllerName);
        if ($controllerFile === null) {
            return $this->response->setStatusCode(404)->setBody('Controller not found.');
        }

        require_once LEGACY_APPPATH . 'core/MY_Controller.php';
        require_once $controllerFile;

        $class = pathinfo($controllerFile, PATHINFO_FILENAME);
        if (! class_exists($class)) {
            return $this->response->setStatusCode(500)->setBody('Controller class not found: ' . esc($class));
        }

        ob_start();
        try {
            $controller = new $class();
            if (! method_exists($controller, $method) || str_starts_with($method, '_')) {
                ob_end_clean();

                return $this->response->setStatusCode(404)->setBody('Action not found.');
            }

            $reflection = new ReflectionMethod($controller, $method);
            if (! $reflection->isPublic()) {
                ob_end_clean();

                return $this->response->setStatusCode(404)->setBody('Action not found.');
            }

            $result = $reflection->invokeArgs($controller, $arguments);
            $output = (string) ob_get_clean();

            if ($result instanceof ResponseInterface) {
                return $result;
            }

            return $this->response->setBody($output . (is_string($result) ? $result : ''));
        } catch (LegacyRedirectException $exception) {
            ob_end_clean();

            return redirect()->to($exception->target, $exception->status);
        } catch (LegacyHttpException $exception) {
            ob_end_clean();

            return $this->response
                ->setStatusCode($exception->status)
                ->setBody('<h1>' . esc($exception->heading) . '</h1><p>' . $exception->getMessage() . '</p>');
        } catch (Throwable $exception) {
            ob_end_clean();
            log_message('error', '[Legacy] {exception}', ['exception' => $exception]);

            if (ENVIRONMENT === 'development') {
                throw $exception;
            }

            return $this->response->setStatusCode(500)->setBody('The application encountered an error.');
        }
    }

    private function findControllerFile(string $controller): ?string
    {
        $wanted = strtolower(str_replace('-', '_', $controller));
        foreach (glob(LEGACY_APPPATH . 'controllers/*.php') ?: [] as $file) {
            if (strtolower(pathinfo($file, PATHINFO_FILENAME)) === $wanted) {
                return $file;
            }
        }

        return null;
    }
}
