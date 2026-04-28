<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Database;

final class OroConnectionFactory
{
    private ?\PDO $connection = null;

    public function __construct(private readonly string $dsn)
    {
    }

    public function getConnection(): \PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        if ($this->dsn === '') {
            throw new \RuntimeException('Database not configured. Set oro_mate_extension.database_url in your mate/config.php.');
        }

        $parsed = parse_url($this->dsn);

        if ($parsed === false) {
            throw new \RuntimeException('oro_mate_extension.database_url is not a valid DSN.');
        }

        try {
            $this->connection = new \PDO(
                $this->buildPdoDsn($parsed),
                isset($parsed['user']) ? rawurldecode($parsed['user']) : null,
                isset($parsed['pass']) ? rawurldecode($parsed['pass']) : null,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ],
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException(\sprintf('Cannot connect to OroCommerce database: %s', $e->getMessage()), previous: $e);
        }

        return $this->connection;
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function buildPdoDsn(array $parsed): string
    {
        $host   = isset($parsed['host']) ? (string) $parsed['host'] : 'localhost';
        $port   = isset($parsed['port']) ? (int) $parsed['port'] : 5432;
        $dbname = isset($parsed['path']) ? ltrim((string) $parsed['path'], '/') : '';

        return \sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $dbname);
    }
}
