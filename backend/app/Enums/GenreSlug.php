<?php

namespace App\Enums;

enum GenreSlug: string
{
    case ACAO = 'acao';
    case AVENTURA = 'aventura';
    case COMEDIA = 'comedia';
    case DRAMA = 'drama';
    case FICCAO_CIENTIFICA = 'ficcao-cientifica';
    case TERROR = 'terror';
    case ROMANCE = 'romance';
    case SUSPENSE = 'suspense';
    case ANIMACAO = 'animacao';
    case CRIME = 'crime';
    case DOCUMENTARIO = 'documentario';
    case FAMILIA = 'familia';
    case FANTASIA = 'fantasia';
    case GUERRA = 'guerra';
    case HISTORIA = 'historia';
    case MISTERIO = 'misterio';
    case MUSICAL = 'musical';
    case WESTERN = 'western';

    /**
     * Retorna o nome completo do gênero em português
     * 
     * @return string Nome do gênero como aparece no TMDB
     */
    public function label(): string
    {
        return match($this) {
            self::ACAO => 'Ação',
            self::AVENTURA => 'Aventura',
            self::COMEDIA => 'Comédia',
            self::DRAMA => 'Drama',
            self::FICCAO_CIENTIFICA => 'Ficção científica',
            self::TERROR => 'Terror',
            self::ROMANCE => 'Romance',
            self::SUSPENSE => 'Thriller',
            self::ANIMACAO => 'Animação',
            self::CRIME => 'Crime',
            self::DOCUMENTARIO => 'Documentário',
            self::FAMILIA => 'Família',
            self::FANTASIA => 'Fantasia',
            self::GUERRA => 'Guerra',
            self::HISTORIA => 'História',
            self::MISTERIO => 'Mistério',
            self::MUSICAL => 'Música',
            self::WESTERN => 'Faroeste',
        };
    }

    /**
     * Tenta converter um slug em string para o enum correspondente
     * 
     * @param string $slug Slug do gênero (ex: "acao", "ficcao-cientifica")
     * @return self|null Retorna o enum ou null se não encontrado
     */
    public static function tryFromSlug(string $slug): ?self
    {
        return self::tryFrom($slug);
    }

    /**
     * Converte slug para nome do gênero, com fallback para o próprio slug
     * 
     * @param string $slug Slug do gênero
     * @return string Nome do gênero ou o próprio slug se não encontrado
     */
    public static function toGenreName(string $slug): string
    {
        $enum = self::tryFromSlug($slug);
        return $enum?->label() ?? $slug;
    }

    /**
     * Retorna todos os gêneros como array associativo [slug => nome]
     * 
     * @return array<string, string>
     */
    public static function all(): array
    {
        $genres = [];
        foreach (self::cases() as $case) {
            $genres[$case->value] = $case->label();
        }
        return $genres;
    }
}
