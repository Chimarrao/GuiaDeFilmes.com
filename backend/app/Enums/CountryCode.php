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
    case NORTH_KOREA = 'KP';
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
    case ALGERIA = 'DZ';
    case TUNISIA = 'TN';

    // Europa do Sul
    case GREECE = 'GR';
    case CROATIA = 'HR';
    case SERBIA = 'RS';
    case SLOVENIA = 'SI';
    case BULGARIA = 'BG';
    case ALBANIA = 'AL';
    case MACEDONIA = 'MK';
    case BOSNIA = 'BA';
    case MONTENEGRO = 'ME';
    case KOSOVO = 'XK';
    case CYPRUS = 'CY';
    case MALTA = 'MT';

    // Europa Central
    case SLOVAKIA = 'SK';
    case ESTONIA = 'EE';
    case LATVIA = 'LV';
    case LITHUANIA = 'LT';
    case LUXEMBOURG = 'LU';

    // América Central e Caribe
    case CUBA = 'CU';
    case PANAMA = 'PA';
    case COSTA_RICA = 'CR';
    case GUATEMALA = 'GT';
    case EL_SALVADOR = 'SV';
    case HONDURAS = 'HN';
    case NICARAGUA = 'NI';
    case BOLIVIA = 'BO';
    case PARAGUAY = 'PY';
    case ECUADOR = 'EC';
    case DOMINICAN_REPUBLIC = 'DO';
    case PUERTO_RICO = 'PR';
    case JAMAICA = 'JM';
    case TRINIDAD_TOBAGO = 'TT';
    case BAHAMAS = 'BS';
    case BARBADOS = 'BB';

    // Ásia Central e Sul
    case PAKISTAN = 'PK';
    case BANGLADESH = 'BD';
    case NEPAL = 'NP';
    case SRI_LANKA = 'LK';
    case MONGOLIA = 'MN';
    case KAZAKHSTAN = 'KZ';
    case UZBEKISTAN = 'UZ';
    case ARMENIA = 'AM';
    case GEORGIA = 'GE';
    case AZERBAIJAN = 'AZ';
    case LAOS = 'LA';

    // Oriente Médio (continuação)
    case IRAQ = 'IQ';
    case LEBANON = 'LB';
    case JORDAN = 'JO';
    case PALESTINE = 'PS';
    case AFGHANISTAN = 'AF';
    case QATAR = 'QA';
    case OMAN = 'OM';
    case KUWAIT = 'KW';
    case BAHRAIN = 'BH';
    case YEMEN = 'YE';
    case LIBYA = 'LY';
    case VATICAN = 'VA';

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
            self::LAOS => 'Laos',
            self::NORTH_KOREA => 'North Korea',
            self::LIBYA => 'Libya',
            self::QATAR => 'Qatar',
            self::OMAN => 'Oman',
            self::KUWAIT => 'Kuwait',
            self::BAHRAIN => 'Bahrain',
            self::YEMEN => 'Yemen',
            self::VATICAN => 'Vatican City',

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
            self::ALGERIA => 'Algeria',
            self::TUNISIA => 'Tunisia',

            // Europa do Sul
            self::GREECE => 'Greece',
            self::CROATIA => 'Croatia',
            self::SERBIA => 'Serbia',
            self::SLOVENIA => 'Slovenia',
            self::BULGARIA => 'Bulgaria',
            self::ALBANIA => 'Albania',
            self::MACEDONIA => 'North Macedonia',
            self::BOSNIA => 'Bosnia and Herzegovina',
            self::MONTENEGRO => 'Montenegro',
            self::KOSOVO => 'Kosovo',
            self::CYPRUS => 'Cyprus',
            self::MALTA => 'Malta',

            // Europa Central
            self::SLOVAKIA => 'Slovakia',
            self::ESTONIA => 'Estonia',
            self::LATVIA => 'Latvia',
            self::LITHUANIA => 'Lithuania',
            self::LUXEMBOURG => 'Luxembourg',

            // América Central e Caribe
            self::CUBA => 'Cuba',
            self::PANAMA => 'Panama',
            self::COSTA_RICA => 'Costa Rica',
            self::GUATEMALA => 'Guatemala',
            self::EL_SALVADOR => 'El Salvador',
            self::HONDURAS => 'Honduras',
            self::NICARAGUA => 'Nicaragua',
            self::BOLIVIA => 'Bolivia',
            self::PARAGUAY => 'Paraguay',
            self::ECUADOR => 'Ecuador',
            self::DOMINICAN_REPUBLIC => 'Dominican Republic',
            self::PUERTO_RICO => 'Puerto Rico',
            self::JAMAICA => 'Jamaica',
            self::TRINIDAD_TOBAGO => 'Trinidad and Tobago',
            self::BAHAMAS => 'Bahamas',
            self::BARBADOS => 'Barbados',

            // Ásia Central e Sul
            self::PAKISTAN => 'Pakistan',
            self::BANGLADESH => 'Bangladesh',
            self::NEPAL => 'Nepal',
            self::SRI_LANKA => 'Sri Lanka',
            self::MONGOLIA => 'Mongolia',
            self::KAZAKHSTAN => 'Kazakhstan',
            self::UZBEKISTAN => 'Uzbekistan',
            self::ARMENIA => 'Armenia',
            self::GEORGIA => 'Georgia',
            self::AZERBAIJAN => 'Azerbaijan',

            // Oriente Médio (continuação)
            self::IRAQ => 'Iraq',
            self::LEBANON => 'Lebanon',
            self::JORDAN => 'Jordan',
            self::PALESTINE => 'Palestine',
            self::AFGHANISTAN => 'Afghanistan',
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
            self::LAOS => 'Laos',
            self::NORTH_KOREA => 'Coreia do Norte',
            self::LIBYA => 'Líbia',
            self::QATAR => 'Catar',
            self::OMAN => 'Omã',
            self::KUWAIT => 'Kuwait',
            self::BAHRAIN => 'Bahrein',
            self::YEMEN => 'Iêmen',
            self::VATICAN => 'Vaticano',

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
            self::ALGERIA => 'Argélia',
            self::TUNISIA => 'Tunísia',

            // Europa do Sul
            self::GREECE => 'Grécia',
            self::CROATIA => 'Croácia',
            self::SERBIA => 'Sérvia',
            self::SLOVENIA => 'Eslovênia',
            self::BULGARIA => 'Bulgária',
            self::ALBANIA => 'Albânia',
            self::MACEDONIA => 'Macedônia do Norte',
            self::BOSNIA => 'Bósnia e Herzegovina',
            self::MONTENEGRO => 'Montenegro',
            self::KOSOVO => 'Kosovo',
            self::CYPRUS => 'Chipre',
            self::MALTA => 'Malta',

            // Europa Central
            self::SLOVAKIA => 'Eslováquia',
            self::ESTONIA => 'Estônia',
            self::LATVIA => 'Letônia',
            self::LITHUANIA => 'Lituânia',
            self::LUXEMBOURG => 'Luxemburgo',

            // América Central e Caribe
            self::CUBA => 'Cuba',
            self::PANAMA => 'Panamá',
            self::COSTA_RICA => 'Costa Rica',
            self::GUATEMALA => 'Guatemala',
            self::EL_SALVADOR => 'El Salvador',
            self::HONDURAS => 'Honduras',
            self::NICARAGUA => 'Nicarágua',
            self::BOLIVIA => 'Bolívia',
            self::PARAGUAY => 'Paraguai',
            self::ECUADOR => 'Equador',
            self::DOMINICAN_REPUBLIC => 'República Dominicana',
            self::PUERTO_RICO => 'Porto Rico',
            self::JAMAICA => 'Jamaica',
            self::TRINIDAD_TOBAGO => 'Trinidad e Tobago',
            self::BAHAMAS => 'Bahamas',
            self::BARBADOS => 'Barbados',

            // Ásia Central e Sul
            self::PAKISTAN => 'Paquistão',
            self::BANGLADESH => 'Bangladesh',
            self::NEPAL => 'Nepal',
            self::SRI_LANKA => 'Sri Lanka',
            self::MONGOLIA => 'Mongólia',
            self::KAZAKHSTAN => 'Cazaquistão',
            self::UZBEKISTAN => 'Uzbequistão',
            self::ARMENIA => 'Armênia',
            self::GEORGIA => 'Geórgia',
            self::AZERBAIJAN => 'Azerbaijão',

            // Oriente Médio (continuação)
            self::IRAQ => 'Iraque',
            self::LEBANON => 'Líbano',
            self::JORDAN => 'Jordânia',
            self::PALESTINE => 'Palestina',
            self::AFGHANISTAN => 'Afeganistão',
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

    /**
     * Tenta encontrar um país pelo nome em inglês (do banco de dados)
     * Suporta países modernos, extintos e aliases
     * 
     * @param string $englishName Nome do país em inglês
     * @return self|null
     */
    public static function findByEnglishName(string $englishName): ?self
    {
        $normalized = strtolower(trim($englishName));
        
        // Mapeamento de variações, sinônimos e países extintos
        $nameMap = [
            // Aliases modernos
            'united states' => self::USA,
            'united states of america' => self::USA,
            'usa' => self::USA,
            'united kingdom' => self::UNITED_KINGDOM,
            'uk' => self::UNITED_KINGDOM,
            'england' => self::UNITED_KINGDOM,
            'scotland' => self::UNITED_KINGDOM,
            'wales' => self::UNITED_KINGDOM,
            'northern ireland' => self::UNITED_KINGDOM,
            'south korea' => self::SOUTH_KOREA,
            'korea' => self::SOUTH_KOREA,
            'republic of korea' => self::SOUTH_KOREA,
            'netherlands' => self::NETHERLANDS,
            'holland' => self::NETHERLANDS,
            'czechia' => self::CZECH_REPUBLIC,
            'north macedonia' => self::MACEDONIA,
            'bosnia and herzegovina' => self::BOSNIA,
            'trinidad and tobago' => self::TRINIDAD_TOBAGO,
            
            // Nomes antigos / variações que foram atualizados
            'libyan arab jamahiriya' => self::LIBYA,
            'macedonia' => self::MACEDONIA,
            'macao' => self::CHINA, // Macau é região administrativa da China
            "lao people's democratic republic" => self::LAOS,
            'laos' => self::LAOS,
            'lao pdr' => self::LAOS,
            'holy see' => self::VATICAN,
            'vatican' => self::VATICAN,
            // North Korea aliases
            'north korea' => self::NORTH_KOREA,
            'democratic people\'s republic of korea' => self::NORTH_KOREA,
            'dprk' => self::NORTH_KOREA,
        ];
        
        if (isset($nameMap[$normalized])) {
            return $nameMap[$normalized];
        }
        
        // Procura pelo nome oficial em inglês
        foreach (self::cases() as $country) {
            if (strtolower($country->fullName()) === $normalized) {
                return $country;
            }
        }
        
        return null;
    }

    /**
     * Retorna URL da bandeira do país
     * 
     * @return string
     */
    public function getFlagUrl(): string
    {
        return 'https://flagcdn.com/w40/' . strtolower($this->value) . '.png';
    }

    /**
     * Retorna array com todos os dados do país
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->value,
            'name' => $this->label(),
            'flag' => $this->getFlagUrl(),
        ];
    }
}
