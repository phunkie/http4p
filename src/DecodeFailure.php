<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p;

use RuntimeException;
use Throwable;

/**
 * Raised by a body decoder when the request body cannot be read as expected.
 * The router answers it with a 400 response carrying the message and the per-field errors.
 */
final class DecodeFailure extends RuntimeException
{
    /**
     * @param array<string, string> $errors field name => what is wrong with it
     */
    public function __construct(string $message, private array $errors = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
