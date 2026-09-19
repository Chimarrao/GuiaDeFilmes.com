<?php

namespace App\Enums;

enum DecadeRange: string
{
    case DECADES_2020 = '2020s';
    case DECADES_2010 = '2010s';
    case DECADES_2000 = '2000s';
    case DECADES_1990 = '1990s';
    case DECADES_1980 = '1980s';
    case DECADES_1970 = '1970s';
    case DECADES_1960 = '1960s';
    case DECADES_1950 = '1950s';
    case DECADES_1940 = '1940s';
    case DECADES_1930 = '1930s';
    case DECADES_1920 = '1920s';
    case PRE_1920 = 'pre-1920';

    /**
     * Retorna o intervalo de anos [início, fim] para a década
     * 
     * @return array{0: int, 1: int} Array com [ano_início, ano_fim]
     */
    public function range(): array
    {
        return match($this) {
            self::DECADES_2020 => [2020, 2029],
            self::DECADES_2010 => [2010, 2019],
            self::DECADES_2000 => [2000, 2009],
            self::DECADES_1990 => [1990, 1999],
            self::DECADES_1980 => [1980, 1989],
            self::DECADES_1970 => [1970, 1979],
            self::DECADES_1960 => [1960, 1969],
            self::DECADES_1950 => [1950, 1959],
            self::DECADES_1940 => [1940, 1949],
            self::DECADES_1930 => [1930, 1939],
            self::DECADES_1920 => [1920, 1929],
            self::PRE_1920 => [1850, 1919],
        };
    }

    /**
     * Retorna o nome legível da década
     * 
     * @return string Nome da década em português
     */
    public function label(): string
    {
        return match($this) {
            self::DECADES_2020 => 'Anos 2020',
            self::DECADES_2010 => 'Anos 2010',
            self::DECADES_2000 => 'Anos 2000',
            self::DECADES_1990 => 'Anos 1990',
            self::DECADES_1980 => 'Anos 1980',
            self::DECADES_1970 => 'Anos 1970',
            self::DECADES_1960 => 'Anos 1960',
            self::DECADES_1950 => 'Anos 1950',
            self::DECADES_1940 => 'Anos 1940',
            self::DECADES_1930 => 'Anos 1930',
            self::DECADES_1920 => 'Anos 1920',
            self::PRE_1920 => 'Antes de 1920',
        };
    }

    /**
     * Tenta converter um slug/número para enum
     * 
     * @param string|int $value Slug da década ou ano numérico
     * @return self|null
     */
    public static function tryFromValue(string|int $value): ?self
    {
        // Se for string, tenta buscar direto
        if (is_string($value)) {
            return self::tryFrom($value);
        }

        // Se for número, tenta determinar a década
        return match (true) {
            $value >= 2020 => self::DECADES_2020,
            $value >= 2010 => self::DECADES_2010,
            $value >= 2000 => self::DECADES_2000,
            $value >= 1990 => self::DECADES_1990,
            $value >= 1980 => self::DECADES_1980,
            $value >= 1970 => self::DECADES_1970,
            $value >= 1960 => self::DECADES_1960,
            $value >= 1950 => self::DECADES_1950,
            $value >= 1940 => self::DECADES_1940,
            $value >= 1930 => self::DECADES_1930,
            $value >= 1920 => self::DECADES_1920,
            $value < 1920  => self::PRE_1920,
        };

        return null;
    }

    /**
     * Retorna todas as décadas como array associativo [slug => label]
     * 
     * @return array<string, string>
     */
    public static function all(): array
    {
        $decades = [];
        foreach (self::cases() as $case) {
            $decades[$case->value] = $case->label();
        }
        return $decades;
    }
}
