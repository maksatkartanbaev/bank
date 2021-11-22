<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\Messages\Message;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;
use Phalcon\Validation\Validator\InclusionIn;

class Accounts extends Model
{
    public function validation()
    {
        $validator = new Validation();

        $validator->add(
            "type",
            new InclusionIn(
                [
                    'message' => 'Type must be "credit", "debit", or "instant"',
                    'domain' => [
                        'credit',
                        'debit',
                        'instant',
                    ],
                ]
            )
        );

        $validator->add(
            'name',
            new Uniqueness(
                [
                    'field'   => 'name',
                    'message' => 'The name must be unique',
                ]
            )
        );

        if ($this->balance < 0) {
            $this->appendMessage(
                new Message('The balance cannot be less than zero')
            );
        }

        // Validate the validator
        return $this->validate($validator);
    }
}
