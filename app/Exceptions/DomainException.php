<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** A business rule said no. Safe to show to the customer. */
class DomainException extends RuntimeException {}
