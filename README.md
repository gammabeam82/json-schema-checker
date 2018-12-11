####Description
This library provides an easy way to validate api responses.

####Usage

```php
use Gammabeam82\SchemaChecker\SchemaChecker;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected static $client;
    
    /**
     * @var SchemaChecker
     */
    protected static $schemaChecker;
    
    /**
     * @inheritdoc
     */
    public function setUpBeforeClass()
    {
        static::$client = static::createClient();
        static::$schemaChecker = new SchemaChecker();
    }

    public function testListAction(): void
    {
        static::$client->request('GET', '/api/v1/categories/');
        $response = static::$client->getResponse();
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $this->assertTrue(
            static::$schemaChecker->assertSchema($response->getContent(), [
                'id' => 'integer',
                'name' => 'string',
                'image' => 'string|nullable',
                'is_active' => 'boolean',
                'products' => [
                    'nullable' => true,
                    'id' => 'integer',
                    'name' => 'string'.
                    'description' => 'string',
                    'images' => [
                        'nullable' => true,
                        'id' => 'integer',
                        'filename' => 'string'.
                    ]
                ]
            ]),
            static::$schemaChecker->getViolations()
        );
    }
}
```
