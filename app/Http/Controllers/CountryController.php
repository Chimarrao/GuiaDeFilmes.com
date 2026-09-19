<?php

namespace App\Http\Controllers;

use App\Enums\CountryCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CountryController extends Controller
{
    /**
     * Retorna mapeamento de países extintos/históricos
     * Sincronizado com extinctCountryFlags do frontend
     * 
     * @return array
     */
    private static function getExtinctCountriesMap(): array
    {
        return [
            'Czechoslovakia' => [
                'code' => 'CZE',
                'name' => 'Tchecoslováquia',
                'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/Flag_of_the_Czech_Republic.svg/1920px-Flag_of_the_Czech_Republic.svg.png'
            ],
            'East Germany' => [
                'code' => 'GDR',
                'name' => 'Alemanha Oriental',
                'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/97/Flag_of_the_German_Democratic_Republic.svg/2560px-Flag_of_the_German_Democratic_Republic.svg.png'
            ],
            'Soviet Union' => [
                'code' => 'SU',
                'name' => 'União Soviética',
                'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Flag_of_the_Soviet_Union.svg/2560px-Flag_of_the_Soviet_Union.svg.png'
            ],
            'Yugoslavia' => [
                'code' => 'YU',
                'name' => 'Iugoslávia',
                'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/61/Flag_of_Yugoslavia_%281946-1992%29.svg/2560px-Flag_of_Yugoslavia_%281946-1992%29.svg.png'
            ],
            'Serbia and Montenegro' => [
                'code' => 'SAM',
                'name' => 'Sérvia e Montenegro',
                'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg/2560px-Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg.png'
            ],
            'Netherlands Antilles' => [
                'code' => 'AN',
                'name' => 'Antilhas Holandesas',
                'flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ae/Flag_of_the_Netherlands_Antilles_%281986%E2%80%932010%29.svg/1920px-Flag_of_the_Netherlands_Antilles_%281986%E2%80%932010%29.svg.png'
            ],
        ];
    }

    /**
     * Retorna a lista de países com contagem de filmes
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cacheKey = 'countries_with_counts_v2';
        $countries = Cache::get($cacheKey);
        
        if (!$countries) {
            return response()->json([
                'error' => "Cache '{$cacheKey}' não carregado. Execute o comando cache:generate."
            ], 500);
        }

        return response()->json($countries);
    }
}
