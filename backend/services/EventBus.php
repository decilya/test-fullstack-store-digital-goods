<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\EventBusInterface;

/**
 * Простой event bus для domain events.
 */
class EventBus implements EventBusInterface {
    private array $subscribers = [];
    
    public function dispatch(string $event, array $payload): void {
        error_log("Event dispatched: {$event}");
        
        if (!isset($this->subscribers[$event])) {
            return;
        }
        
        foreach ($this->subscribers[$event] as $handler) {
            try {
                $handler($payload);
            } catch (\Throwable $e) {
                error_log("Event handler error: " . $e->getMessage());
            }
        }
    }
    
    public function subscribe(string $event, callable $handler): void {
        $this->subscribers[$event][] = $handler;
    }
}