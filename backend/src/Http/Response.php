<?php

declare(strict_types=1);

namespace Kodex\Http;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly mixed $body = null,
        public array $headers = [],
    ) {
    }

    /** Respuesta exitosa: { "data": ..., "meta": ... } */
    public static function data(mixed $data, int $status = 200, ?array $meta = null): self
    {
        $body = ['data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return new self($status, $body);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    public static function error(HttpException $e): self
    {
        $error = ['code' => $e->errorCode, 'message' => $e->getMessage()];
        if ($e->details !== []) {
            $error['details'] = $e->details;
        }

        return new self($e->status, ['error' => $error]);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        if ($this->status === 204 || $this->body === null) {
            return;
        }
        if (is_string($this->body)) {
            echo $this->body;
        } else {
            echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
