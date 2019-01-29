<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities;


use DateTimeInterface;
use Psr\Http\Message\UploadedFileInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
class Contact
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @Field(name="name")
     */
    public function getName(): string
    {
        return $this->name;
    }
}
