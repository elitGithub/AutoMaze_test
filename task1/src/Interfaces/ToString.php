<?php

namespace Interfaces;

/**
 * Pollyfill for the \Stringable interface introduced in PHP8.0
 */
interface ToString
{
    public function __toString();

}
