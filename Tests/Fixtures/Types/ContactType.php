<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Types;

use function strtoupper;
use TheCodingMachine\GraphQL\Controllers\Annotations\ExtendType;
use TheCodingMachine\GraphQL\Controllers\Annotations\Field;
use TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Entities\Contact;


/**
 * @ExtendType(class=Contact::class)
 */
class ContactType
{
    /**
     * @Field()
     */
    public function uppercaseName(Contact $contact): string
    {
        return strtoupper($contact->getName());
    }
}