<?php

namespace App\Tests\Service;

use App\Entity\KeyValueStore;
use App\Repository\KeyValueStoreRepository;
use App\Service\KeyValueStoreService;
use App\Transformer\KeyValueStoreTransformer;
use Mockery;
use PHPUnit\Framework\TestCase;

class KeyValueStoreServiceTest extends TestCase
{
    private $keyValueStoreRepository;
    private $keyValueStoreTransformer;
    private $keyValueStoreService;

    protected function setUp(): void
    {
        $this->keyValueStoreRepository = Mockery::mock(KeyValueStoreRepository::class);
        $this->keyValueStoreTransformer = Mockery::mock(KeyValueStoreTransformer::class);

        $this->keyValueStoreService = new KeyValueStoreService(
            $this->keyValueStoreRepository,
            $this->keyValueStoreTransformer
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSaveKeyValueStore()
    {
        $keyValueStore = new KeyValueStore();

        $this->keyValueStoreRepository
            ->shouldReceive('save')
            ->once()
            ->with($keyValueStore);

        $this->keyValueStoreService->save($keyValueStore);
    }

    public function testDeleteKeyValueStore()
    {
        $keyValueStore = new KeyValueStore();

        $this->keyValueStoreRepository
            ->shouldReceive('delete')
            ->once()
            ->with($keyValueStore);

        $this->keyValueStoreService->delete($keyValueStore);
    }

    public function testGetTransformedKeyValueStore()
    {
        $keyValueStore = new KeyValueStore();
        $transformedKeyValueStore = ['id' => 1, 'key' => 'example', 'value' => 'example_value'];

        $this->keyValueStoreRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($keyValueStore);

        $this->keyValueStoreTransformer
            ->shouldReceive('transform')
            ->once()
            ->with($keyValueStore)
            ->andReturn($transformedKeyValueStore);

        $result = $this->keyValueStoreService->getTransformedKeyValueStore(1);

        $this->assertEquals($transformedKeyValueStore, $result);
    }

    public function testGetTransformedKeyValueStoreNotFound()
    {
        $this->keyValueStoreRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(null);

        $result = $this->keyValueStoreService->getTransformedKeyValueStore(1);

        $this->assertNull($result);
    }

    public function testUpdateFromData()
    {
        $keyValueStore = new KeyValueStore();
        $data = ['key' => 'updated_key', 'value' => 'updated_value'];

        $this->keyValueStoreRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($keyValueStore);

        $this->keyValueStoreTransformer
            ->shouldReceive('reverseTransform')
            ->once()
            ->with($data, $keyValueStore)
            ->andReturn($keyValueStore);

        $this->keyValueStoreRepository
            ->shouldReceive('save')
            ->once()
            ->with($keyValueStore);

        $result = $this->keyValueStoreService->updateFromData(1, $data);

        $this->assertEquals($keyValueStore, $result);
    }

    public function testUpdateFromDataNotFound()
    {
        $data = ['key' => 'updated_key', 'value' => 'updated_value'];

        $this->keyValueStoreRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(null);

        $result = $this->keyValueStoreService->updateFromData(1, $data);

        $this->assertNull($result);
    }
}
