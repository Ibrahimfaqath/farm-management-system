<?php

namespace App\Services;

use App\Models\FeedItem;
use RuntimeException;

/**
 * Thrown when a consumption would push usable stock below zero and no explicit
 * override was granted.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?FeedItem $feedItem = null,
        public readonly ?float $requested = null,
        public readonly ?float $available = null,
        public readonly ?float $shortfall = null,
    ) {
        parent::__construct($message);
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'feed_item' => $this->feedItem?->name,
            'requested' => $this->requested,
            'available' => $this->available,
            'shortfall' => $this->shortfall,
        ];
    }
}
