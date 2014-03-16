<?php

namespace FeM\sPof\form\element;

/**
 * Text input with user suggestion.
 *
 * @package FeM\sPi\core\form
 */
class UserInput extends \FeM\sPof\form\AbstractInputElement
{

    /**
     * @param $field
     * @param bool $required
     * @param int $maxlength
     */
    public function __construct($field, $required = true, $maxlength = 255)
    {
        parent::__construct('text', $field, $required);
        $this->addAttribute('maxlength', $maxlength);
        $this->appendAttribute('class', 'username-suggest');
    } // constructor
}// class