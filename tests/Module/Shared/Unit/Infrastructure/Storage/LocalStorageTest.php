<?php

declare(strict_types=1);

namespace Tests\Module\Shared\Unit\Infrastructure\Storage;

use Inquisition\Core\Infrastructure\Storage\LocalStorage;
use Inquisition\Foundation\Storage\Exception\StorageException;
use Inquisition\Foundation\Storage\StorageRegistry;
use Tests\Shared\UnitTestCase;

class LocalStorageTest extends UnitTestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        $this->cleanUpStorage();

        parent::tearDown();
    }

    public function test_it_should_create_a_file_in_local_storage(): void
    {
        $fileName = $this->faker->word();
        $fileContent = $this->faker->text();

        /**
         * @var LocalStorage $storage
         */
        $storage = StorageRegistry::getInstance()->storage('local');
        $storageDir = $storage->getRootPath();
        $fullPath = $storageDir . DIRECTORY_SEPARATOR . $fileName;

        $storage->writeByPath($fileName, $fileContent);
        $this->assertFileExists($fullPath);
        $this->assertFileIsReadable($fullPath);
        $this->assertEquals($fileContent, file_get_contents($fullPath));
    }

    public function test_it_should_throw_exception_when_file_does_not_exist(): void
    {
        $this->expectException(StorageException::class);
        $storage = StorageRegistry::getInstance()->storage('local');
        $storage->readByPath('non-existent-file');
    }

    public function test_it_should_read_file(): void
    {
        $fileName = $this->faker->word();
        $fileContent = $this->faker->text();

        $storage = StorageRegistry::getInstance()->storage('local');
        $storage->writeByPath($fileName, $fileContent);

        $this->assertEquals($fileContent, $storage->readByPath($fileName));
    }

    public function test_it_should_delete_file(): void
    {
        $fileName = $this->faker->word();
        $fileContent = $this->faker->text();

        /**
         * @var LocalStorage $storage
         */
        $storage = StorageRegistry::getInstance()->storage('local');
        $storage->writeByPath($fileName, $fileContent);
        $storage->deleteByPath($fileName);
        $this->assertFileDoesNotExist($storage->getRootPath() . DIRECTORY_SEPARATOR . $fileName);
    }

    public function test_it_should_create_file_in_sub_directory(): void
    {
        $dir = $this->faker->word();
        $fileName = $dir . DIRECTORY_SEPARATOR . $this->faker->word();
        $fileContent = $this->faker->text();

        /**
         * @var LocalStorage $storage
         */
        $storage = StorageRegistry::getInstance()->storage('local');
        $storage->writeByPath($fileName, $fileContent);

        $storageDir = $storage->getRootPath();
        $dirPath = $storageDir . DIRECTORY_SEPARATOR . $dir;
        $fullPath = $storageDir . DIRECTORY_SEPARATOR . $fileName;
        $this->assertDirectoryExists($dirPath);

        $this->assertFileExists($fullPath);
        $this->assertFileIsReadable($fullPath);
        $this->assertEquals($fileContent, file_get_contents($fullPath));
    }

    public function test_it_should_list_files_in_directory(): void
    {
        $dir = $this->faker->word();
        $files = [
            $this->faker->word(),
            $this->faker->word(),
        ];
        $storage = StorageRegistry::getInstance()->storage('local');
        foreach ($files as $file) {
            $storage->writeByPath($dir . DIRECTORY_SEPARATOR . $file, $this->faker->text());
        }

        $list = $storage->listFiles($dir, true);
        $this->assertCount(count($files), $list);

        foreach ($list as $file) {
            $this->assertTrue(
                condition: in_array($file->getFilename(), $files, true),
                message: "{$file->getFilename()} does not exist in " . implode(', ', $files),
            );
        }
    }
}
