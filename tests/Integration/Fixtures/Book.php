<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Integration\Fixtures;

use Phunkie\Phetch\Attributes\Generated;

final readonly class Book
{
    public function __construct(
        #[Generated]
        public int $id,
        public string $title,
        public string $author,
        public bool $inStock,
    ) {
    }
}
