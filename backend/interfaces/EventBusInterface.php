<?php
declare(strict_types=1);

namespace App\Interfaces;

interface EventBusInterface {
    public function dispatch(string $event, array $payload): void;
    public function subscribe(string $event, callable $handler): void;
}