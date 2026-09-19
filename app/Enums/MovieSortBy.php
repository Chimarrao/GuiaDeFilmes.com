<?php

namespace App\Enums;

enum MovieSortBy: string
{
    case POPULARITY = 'popularity';
    case RATING = 'rating';
    case RELEASE_DATE = 'release_date';
    case TITLE = 'title';
    case VOTE_COUNT = 'vote_count';

    /**
     * Retorna o nome legível da ordenação
     * 
     * @return string Nome da ordenação em português
     */
    public function label(): string
    {
        return match($this) {
            self::POPULARITY => 'Popularidade',
            self::RATING => 'Avaliação',
            self::RELEASE_DATE => 'Data de Lançamento',
            self::TITLE => 'Título (A-Z)',
            self::VOTE_COUNT => 'Quantidade de Votos',
        };
    }

    /**
     * Retorna a coluna do banco de dados correspondente
     * 
     * @return string Nome da coluna no banco
     */
    public function column(): string
    {
        return match($this) {
            self::POPULARITY => 'popularity',
            self::RATING => 'tmdb_rating',
            self::RELEASE_DATE => 'release_date',
            self::TITLE => 'title',
            self::VOTE_COUNT => 'tmdb_vote_count',
        };
    }

    /**
     * Retorna a direção da ordenação (ASC ou DESC)
     * 
     * @return string 'asc' ou 'desc'
     */
    public function direction(): string
    {
        return match($this) {
            self::TITLE => 'asc',
            default => 'desc',
        };
    }

    /**
     * Tenta converter uma string para o enum correspondente
     * 
     * @param string $value Valor da ordenação
     * @return self|null
     */
    public static function tryFromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Retorna todas as opções como array associativo [value => label]
     * 
     * @return array<string, string>
     */
    public static function all(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
