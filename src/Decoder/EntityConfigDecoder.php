<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Decoder;

final class EntityConfigDecoder
{
    /**
     * @return array<string, mixed>
     */
    public function decode(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $serialized = base64_decode($raw, strict: true);

        if ($serialized === false) {
            throw new \RuntimeException('data column is not valid base64.');
        }

        $result = @unserialize($serialized, ['allowed_classes' => false]);

        if ($result === false) {
            throw new \RuntimeException('data column is not valid serialized PHP.');
        }

        if (!\is_array($result)) {
            throw new \RuntimeException(\sprintf('data column decoded to unexpected type "%s".', \get_debug_type($result)));
        }

        return $result;
    }
}
