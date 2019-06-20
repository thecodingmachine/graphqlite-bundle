<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities;


use DateTimeInterface;
use Psr\Http\Message\UploadedFileInterface;
use stdClass;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Controller\TestGraphqlController;

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

    /**
     * @Field()
     * @return string
     */
    public function injectService(TestGraphqlController $testService = null, stdClass $someService = null): string
    {
        if (!$testService instanceof TestGraphqlController || $someService === null) {
            return 'KO';
        }
        return 'OK';
    }
}
