<?php


namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Types;

use function strtoupper;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Entities\Contact;


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
