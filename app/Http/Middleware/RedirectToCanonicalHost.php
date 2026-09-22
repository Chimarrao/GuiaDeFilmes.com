<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * www.guiadefilmes.com respondia 200 igual a guiadefilmes.com (sem
 * redirecionar), então o Google via duas cópias completas e independentes
 * do site — duplicação de conteúdo em nível de domínio inteiro. Não mexe em
 * /api porque o front chama a API via path relativo e um redirect no meio
 * de uma chamada AJAX só adiciona latência/risco de CORS à toa.
 */
class RedirectToCanonicalHost
{
    private const CANONICAL_HOST = 'guiadefilmes.com';

    public function handle(Request $request, Closure $next)
    {
        if ($request->getHost() === 'www.' . self::CANONICAL_HOST && !$request->is('api/*')) {
            return redirect()->to('https://' . self::CANONICAL_HOST . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
