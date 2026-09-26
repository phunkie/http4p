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

/**
 * Raised by a body decoder when the request body cannot be read as expected.
 * The router answers it with a 400 response carrying the message.
 */
final class DecodeFailure extends RuntimeException
{
}
