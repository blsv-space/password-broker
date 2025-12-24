<?php

namespace Shared\Unit\Infrastructure\Storage;

use Inquisition\Foundation\Storage\Exception\StorageException;
use Inquisition\Foundation\Storage\StorageRegistry;
use Tests\Shared\UnitTestCase;

class LocalStorageTest extends UnitTestCase
{

    protected function tearDown(): void
    {
        $this->cleanUpStorage();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testItShouldCreateAFileInLocalStorage(): void
    {
        $fileName = $this->faker->word();
        $fileContent = $this->faker->text();

        $storage = StorageRegistry::getInstance()->storage('local');
        $storageDir = $storage->getRootPath();
        $fullPath = $storageDir . DIRECTORY_SEPARATOR . $fileName;

        $storage->writeByPath($fileName, $fileContent);
        $this->assertFileExists($fullPath);
        $this->assertFileIsReadable($fullPath);
        $this->assertEquals($fileContent, file_get_contents($fullPath));
    }

    /**
     * @return void
     */
    public function testItShouldThrowExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(StorageException::class);
        $storage = StorageRegistry::getInstance()->storage('local');
        $storage->readByPath('non-existent-file');
    }

    /**
     * @return void
     */
    public function testItShouldReadFile(): void
    {
        $fileName = $this->faker->word();
        $fileContent = $this->faker->text();

        $storage = StorageRegistry::getInstance()->storage('local');
        $storage->writeByPath($fileName, $fileContent);

        $this->assertEquals($fileContent, $storage->readByPath($fileName));
    }

    /**
     * @return void
     */
    public function testItShouldDeleteFile(): void
    {
        $fileName = $this->faker->word();
        $fileContent = $this->faker->text();

        $storage = StorageRegistry::getInstance()->storage('local');
        $storage->writeByPath($fileName, $fileContent);
        $storage->deleteByPath($fileName);
        $this->assertFileDoesNotExist($storage->getRootPath() . DIRECTORY_SEPARATOR . $fileName);
    }

    /**
     * @return void
     */
    public function testItShouldCreateFileInSubDirectory(): void
    {
        $dir = $this->faker->word();
        $fileName = $dir . DIRECTORY_SEPARATOR . $this->faker->word();
        $fileContent = $this->faker->text();

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

    /**
     * @return void
     */
    public function testItShouldListFilesInDirectory(): void
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
        $this->assertIsArray($list);
        $this->assertCount(count($files), $list);

        foreach ($list as $file) {
            $this->assertTrue(
                condition: in_array($file->getFilename(), $files, true),
                message: "{$file->getFilename()} does not exist in " . implode(', ', $files));
        }
    }
}