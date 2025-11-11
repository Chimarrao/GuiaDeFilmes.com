<?php

namespace App\Enums;

enum CountryCode: string
{
    // América do Sul
    case BRAZIL = 'BR';
    case ARGENTINA = 'AR';
    case CHILE = 'CL';
    case COLOMBIA = 'CO';
    case PERU = 'PE';
    case URUGUAY = 'UY';
    case VENEZUELA = 'VE';

    // América do Norte
    case USA = 'US';
    case CANADA = 'CA';
    case MEXICO = 'MX';

    // Europa Ocidental
    case UNITED_KINGDOM = 'GB';
    case FRANCE = 'FR';
    case GERMANY = 'DE';
    case ITALY = 'IT';
    case SPAIN = 'ES';
    case PORTUGAL = 'PT';
    case NETHERLANDS = 'NL';
    case BELGIUM = 'BE';
    case SWITZERLAND = 'CH';
    case AUSTRIA = 'AT';
    case IRELAND = 'IE';

    // Europa do Norte
    case SWEDEN = 'SE';
    case NORWAY = 'NO';
    case DENMARK = 'DK';
    case FINLAND = 'FI';
    case ICELAND = 'IS';

    // Europa Oriental
    case RUSSIA = 'RU';
    case POLAND = 'PL';
    case CZECH_REPUBLIC = 'CZ';
    case HUNGARY = 'HU';
    case ROMANIA = 'RO';
    case UKRAINE = 'UA';

    // Ásia
    case JAPAN = 'JP';
    case SOUTH_KOREA = 'KR';
    case CHINA = 'CN';
    case INDIA = 'IN';
    case THAILAND = 'TH';
    case HONG_KONG = 'HK';
    case TAIWAN = 'TW';
    case SINGAPORE = 'SG';
    case INDONESIA = 'ID';
    case PHILIPPINES = 'PH';
    case MALAYSIA = 'MY';
    case VIETNAM = 'VN';

    // Oceania
    case AUSTRALIA = 'AU';
    case NEW_ZEALAND = 'NZ';

    // Oriente Médio
    case TURKEY = 'TR';
    case ISRAEL = 'IL';
    case IRAN = 'IR';
    case SAUDI_ARABIA = 'SA';
    case UAE = 'AE';

    // África
    case SOUTH_AFRICA = 'ZA';
    case EGYPT = 'EG';
    case MOROCCO = 'MA';
    case NIGERIA = 'NG';
    case KENYA = 'KE';

    /**
     * Retorna o nome completo do país em inglês (como no TMDB)
     * 
     * @return string Nome oficial do país
     */
    public function fullName(): string
    {
        return match($this) {
            // América do Sul
            self::BRAZIL => 'Brazil',
            self::ARGENTINA => 'Argentina',
            self::CHILE => 'Chile',
            self::COLOMBIA => 'Colombia',
            self::PERU => 'Peru',
            self::URUGUAY => 'Uruguay',
            self::VENEZUELA => 'Venezuela',

            // América do Norte
            self::USA => 'United States of America',
            self::CANADA => 'Canada',
            self::MEXICO => 'Mexico',

            // Europa Ocidental
            self::UNITED_KINGDOM => 'United Kingdom',
            self::FRANCE => 'France',
            self::GERMANY => 'Germany',
            self::ITALY => 'Italy',
            self::SPAIN => 'Spain',
            self::PORTUGAL => 'Portugal',
            self::NETHERLANDS => 'Netherlands',
            self::BELGIUM => 'Belgium',
            self::SWITZERLAND => 'Switzerland',
            self::AUSTRIA => 'Austria',
            self::IRELAND => 'Ireland',

            // Europa do Norte
            self::SWEDEN => 'Sweden',
            self::NORWAY => 'Norway',
            self::DENMARK => 'Denmark',
            self::FINLAND => 'Finland',
            self::ICELAND => 'Iceland',

            // Europa Oriental
            self::RUSSIA => 'Russia',
            self::POLAND => 'Poland',
            self::CZECH_REPUBLIC => 'Czech Republic',
            self::HUNGARY => 'Hungary',
            self::ROMANIA => 'Romania',
            self::UKRAINE => 'Ukraine',

            // Ásia
            self::JAPAN => 'Japan',
            self::SOUTH_KOREA => 'South Korea',
            self::CHINA => 'China',
            self::INDIA => 'India',
            self::THAILAND => 'Thailand',
            self::HONG_KONG => 'Hong Kong',
            self::TAIWAN => 'Taiwan',
            self::SINGAPORE => 'Singapore',
            self::INDONESIA => 'Indonesia',
            self::PHILIPPINES => 'Philippines',
            self::MALAYSIA => 'Malaysia',
            self::VIETNAM => 'Vietnam',

            // Oceania
            self::AUSTRALIA => 'Australia',
            self::NEW_ZEALAND => 'New Zealand',

            // Oriente Médio
            self::TURKEY => 'Turkey',
            self::ISRAEL => 'Israel',
            self::IRAN => 'Iran',
            self::SAUDI_ARABIA => 'Saudi Arabia',
            self::UAE => 'United Arab Emirates',

            // África
            self::SOUTH_AFRICA => 'South Africa',
            self::EGYPT => 'Egypt',
            self::MOROCCO => 'Morocco',
            self::NIGERIA => 'Nigeria',
            self::KENYA => 'Kenya',
        };
    }

    /**
     * Retorna o nome do país em português para exibição
     * 
     * @return string Nome do país em PT-BR
     */
    public function label(): string
    {
        return match($this) {
            // América do Sul
            self::BRAZIL => 'Brasil',
            self::ARGENTINA => 'Argentina',
            self::CHILE => 'Chile',
            self::COLOMBIA => 'Colômbia',
            self::PERU => 'Peru',
            self::URUGUAY => 'Uruguai',
            self::VENEZUELA => 'Venezuela',

            // América do Norte
            self::USA => 'Estados Unidos',
            self::CANADA => 'Canadá',
            self::MEXICO => 'México',

            // Europa Ocidental
            self::UNITED_KINGDOM => 'Reino Unido',
            self::FRANCE => 'França',
            self::GERMANY => 'Alemanha',
            self::ITALY => 'Itália',
            self::SPAIN => 'Espanha',
            self::PORTUGAL => 'Portugal',
            self::NETHERLANDS => 'Holanda',
            self::BELGIUM => 'Bélgica',
            self::SWITZERLAND => 'Suíça',
            self::AUSTRIA => 'Áustria',
            self::IRELAND => 'Irlanda',

            // Europa do Norte
            self::SWEDEN => 'Suécia',
            self::NORWAY => 'Noruega',
            self::DENMARK => 'Dinamarca',
            self::FINLAND => 'Finlândia',
            self::ICELAND => 'Islândia',

            // Europa Oriental
            self::RUSSIA => 'Rússia',
            self::POLAND => 'Polônia',
            self::CZECH_REPUBLIC => 'República Tcheca',
            self::HUNGARY => 'Hungria',
            self::ROMANIA => 'Romênia',
            self::UKRAINE => 'Ucrânia',

            // Ásia
            self::JAPAN => 'Japão',
            self::SOUTH_KOREA => 'Coreia do Sul',
            self::CHINA => 'China',
            self::INDIA => 'Índia',
            self::THAILAND => 'Tailândia',
            self::HONG_KONG => 'Hong Kong',
            self::TAIWAN => 'Taiwan',
            self::SINGAPORE => 'Singapura',
            self::INDONESIA => 'Indonésia',
            self::PHILIPPINES => 'Filipinas',
            self::MALAYSIA => 'Malásia',
            self::VIETNAM => 'Vietnã',

            // Oceania
            self::AUSTRALIA => 'Austrália',
            self::NEW_ZEALAND => 'Nova Zelândia',

            // Oriente Médio
            self::TURKEY => 'Turquia',
            self::ISRAEL => 'Israel',
            self::IRAN => 'Irã',
            self::SAUDI_ARABIA => 'Arábia Saudita',
            self::UAE => 'Emirados Árabes',

            // África
            self::SOUTH_AFRICA => 'África do Sul',
            self::EGYPT => 'Egito',
            self::MOROCCO => 'Marrocos',
            self::NIGERIA => 'Nigéria',
            self::KENYA => 'Quênia',
        };
    }

    /**
     * Tenta converter um código de país para o enum correspondente
     * 
     * @param string $code Código ISO do país (ex: "BR", "US")
     * @return self|null Retorna o enum ou null se não encontrado
     */
    public static function tryFromCode(string $code): ?self
    {
        return self::tryFrom(strtoupper($code));
    }

    /**
     * Retorna todos os países como array associativo [código => nome_pt]
     * 
     * @return array<string, string>
     */
    public static function all(): array
    {
        $countries = [];
        foreach (self::cases() as $case) {
            $countries[$case->value] = $case->label();
        }
        return $countries;
    }

    /**
     * Retorna todos os países como array associativo [código => nome_ingles]
     * Útil para busca no banco de dados
     * 
     * @return array<string, string>
     */
    public static function allFullNames(): array
    {
        $countries = [];
        foreach (self::cases() as $case) {
            $countries[$case->value] = $case->fullName();
        }
        return $countries;
    }
}
