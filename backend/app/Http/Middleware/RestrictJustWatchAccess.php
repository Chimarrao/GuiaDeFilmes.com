<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictJustWatchAccess
{
    /**
     * MIDDLEWARE DE SEGURANÇA PARA ENDPOINT /justwatch/search
     * 
     * OBJETIVO:
     * Restringir acesso ao endpoint JustWatch apenas para requisições
     * provenientes de origens autorizadas (mesmo servidor ou IP específico).
     * 
     * ORIGENS PERMITIDAS:
     * 1. Domínio: guiadefilmes.com (HTTP/HTTPS, qualquer porta)
     * 2. IP: 163.176.145.249 (qualquer porta)
     * 3. Requisições locais/internas (sem cabeçalho Origin/Referer)
     * 
     */
    public function handle(Request $request, Closure $next): Response
    {
        // IP permitido
        $allowedIp = '163.176.145.249';
        
        // Domínio permitido
        $allowedDomain = 'guiadefilmes.com';
        
        // VERIFICAÇÃO 1: Checar IP do cliente
        $clientIp = $request->ip();
        
        if ($clientIp === $allowedIp) {
            return $next($request);
        }
        
        // VERIFICAÇÃO 2: Checar Origin header (requisições CORS/AJAX)
        $origin = $request->header('Origin');
        
        if ($origin) {
            $parsedOrigin = parse_url($origin);
            $originHost = $parsedOrigin['host'] ?? '';
            
            // Permite guiadefilmes.com e subdomínios (ex: www.guiadefilmes.com)
            if (str_contains($originHost, $allowedDomain)) {
                return $next($request);
            }
        }
        
        // VERIFICAÇÃO 3: Checar Referer header (requisições diretas)
        $referer = $request->header('Referer');
        
        if ($referer) {
            $parsedReferer = parse_url($referer);
            $refererHost = $parsedReferer['host'] ?? '';
            
            if (str_contains($refererHost, $allowedDomain)) {
                return $next($request);
            }
        }
        
        // VERIFICAÇÃO 4: Permitir requisições internas (sem Origin/Referer)
        // Útil para chamadas via cURL, scripts, ou requisições server-side
        if (!$origin && !$referer) {
            return $next($request);
        }
        
        // BLOQUEAR: Origem não autorizada
        return response()->json([
            'error' => 'Acesso negado. Endpoint restrito.',
            'message' => 'Este endpoint só aceita requisições de origens autorizadas.'
        ], 403);
    }
}
