<?php

namespace App\Logging;

use Monolog\LogRecord;
use Throwable;

/**
 * Retire `system_id` des lignes de log.
 *
 * Une exception de requête écrit le SQL avec ses valeurs : un `insert into
 * "alters" (...) values (7, ...)` suffit à relier un alter à son système. Le
 * fichier de log deviendrait la table de corrélation que tout le produit
 * s'interdit de publier.
 *
 * La colonne `sessions.user_id` porte le même identifiant : elle est traitée
 * de la même façon.
 */
class RedactSystemId
{
    public const PLACEHOLDER = '[system_id masqué]';

    /** Colonnes dont la valeur ne doit jamais atteindre un log. */
    protected const COLUMNS = ['system_id', 'user_id', 'target_ref_id'];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->scrub($record->message),
            context: $this->scrubDeep($record->context),
            extra: $this->scrubDeep($record->extra),
        );
    }

    public function scrub(string $value): string
    {
        $columns = implode('|', self::COLUMNS);

        // `"system_id" => 7`, `system_id = 7`, `'user_id': 7`
        $value = (string) preg_replace(
            '/(["\'`\[]?\b(?:'.$columns.')\b["\'`\]]?\s*(?:=>|=|:)\s*)(["\']?)(\d+)\2/i',
            '$1'.self::PLACEHOLDER,
            $value
        );

        // `insert into "alters" ("system_id", ...) values (7, ...)` : on ne sait
        // pas quelle valeur correspond à quelle colonne, la liste part entière.
        return (string) preg_replace(
            '/((?:insert into|replace into)\s.*?\b(?:'.$columns.')\b.*?\bvalues\s*)\((.*?)\)/is',
            '$1('.self::PLACEHOLDER.')',
            $value
        );
    }

    /** Représentation textuelle d'une exception, valeurs sensibles retirées. */
    protected function scrubThrowable(Throwable $exception): string
    {
        $text = sprintf(
            '[%s] %s at %s:%d%s%s',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            PHP_EOL,
            $exception->getTraceAsString(),
        );

        if ($previous = $exception->getPrevious()) {
            $text .= PHP_EOL.'Caused by: '.$this->scrubThrowable($previous);
        }

        return $this->scrub($text);
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    protected function scrubDeep(array $values): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = match (true) {
                is_string($value) => $this->scrub($value),
                is_array($value) => $this->scrubDeep($value),
                // Une exception est formatée après les processeurs : son message
                // échapperait au masquage. On la remplace par un texte déjà nettoyé.
                $value instanceof Throwable => $this->scrubThrowable($value),
                // Une valeur isolée sous une clé sensible n'a pas de contexte
                // textuel : elle est masquée telle quelle.
                is_int($value) && in_array((string) $key, self::COLUMNS, true) => self::PLACEHOLDER,
                default => $value,
            };
        }

        return $values;
    }
}
