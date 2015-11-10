<?php
namespace NaturalWebTest;

use Mockery as m;
use Exception;
use \PHPUnit_Framework_TestCase as TestCase;
use NaturalWeb\FileStorage\FileStorage;

class FileStorageTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testConstruct()
    {
        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $fileStorage = new FileStorage($storage);

        $this->assertAttributeEquals($storage, 'storage', $fileStorage);
        $this->assertAttributeEquals(null, 'error', $fileStorage);
        $this->assertEquals($storage, $fileStorage->getStorage());
    }

    public function testSaveThrowException()
    {
        $e = new Exception;

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('upload')
            ->once()
            ->andThrow($e)

            ->getMock()
            ->shouldReceive('exists')
            ->times(2)
            ->andReturn(false)

            ->getMock()
            ->shouldReceive('createFolder')
            ->once()
            ->with('/remote/folder')
            ->andReturn(true);

        $fileStorage = new FileStorage($storage);

        $this->assertFalse($fileStorage->save('name', '/source', '/remote/folder'));
        $this->assertAttributeEquals($e, 'error', $fileStorage);
        $this->assertEquals($e, $fileStorage->getError());
    }

    public function testSaveSucessCreatingFolder()
    {
        $url = 'http://share/file';

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('upload')
            ->once()
            ->andReturn(true)

            ->getMock()
            ->shouldReceive('exists')
            ->times(1)
            ->with('/remote/folder')
            ->andReturn(false)

            ->getMock()
            ->shouldReceive('createFolder')
            ->once()
            ->with('/remote/folder')
            ->andReturn(true)

            ->getMock()
            ->shouldReceive('exists')
            ->once()
            ->with('/remote/folder/nome_original.txt')
            ->andReturn(true)

            ->getMock()
            ->shouldReceive('exists')
            ->once()
            ->with('/remote/folder/nome_original-1.txt')
            ->andReturn(false)

            ->getMock()
            ->shouldReceive('getUrl')
            ->once()
            ->andReturn($url);

        $fileStorage = new FileStorage($storage);
        $return = $fileStorage->save('nome_original.txt', '/source', '/remote/folder');
        
        $this->assertEquals(3, count($return));
        
        $this->assertEquals('nome_original-1.txt', $return['name']);
        $this->assertEquals('/remote/folder/nome_original-1.txt', $return['path']);
        $this->assertNull($fileStorage->getError());
    }

    public function testGetUrlSucess()
    {
        $url = 'http://share/file';

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('getUrl')
            ->once()
            ->with('foobar.txt')
            ->andReturn($url);

        $fileStorage = new FileStorage($storage);
        $this->assertEquals($url, $fileStorage->getUrl('foobar.txt'));
    }

    public function testGetUrlThrowException()
    {
        $e = new Exception;

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('getUrl')
            ->once()
            ->with('foobar.txt')
            ->andThrow($e);

        $fileStorage = new FileStorage($storage);

        $this->assertFalse($fileStorage->getUrl('foobar.txt'));
    }

    public function testDeleteThrowException()
    {
        $e = new Exception;

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('delete')
            ->once()
            ->with('foobar.txt')
            ->andThrow($e);

        $fileStorage = new FileStorage($storage);

        $this->assertFalse($fileStorage->delete('foobar.txt'));
        $this->assertEquals($e, $fileStorage->getError());
    }

    public function testDeleteSuccess()
    {
        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('delete')
            ->once()
            ->with('foobar.txt')
            ->andReturn(true);

        $fileStorage = new FileStorage($storage);

        $this->assertTrue($fileStorage->delete('foobar.txt'));
    }

    public function testCreateFolderThrowException()
    {
        $e = new Exception;

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('createFolder')
            ->once()
            ->with('/foobar')
            ->andThrow($e);

        $fileStorage = new FileStorage($storage);

        $this->assertFalse($fileStorage->createFolder('/foobar'));
        $this->assertEquals($e, $fileStorage->getError());
    }

    public function testCreateFolderSuccess()
    {
        $e = new Exception;

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('createFolder')
            ->once()
            ->with('/foobar')
            ->andReturn(true);

        $fileStorage = new FileStorage($storage);

        $this->assertTrue($fileStorage->createFolder('/foobar'));
    }
    
    public function testDeleteFolderThrowException()
    {
        $e = new Exception;

        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('deleteFolder')
            ->once()
            ->with('/foobar')
            ->andThrow($e);

        $fileStorage = new FileStorage($storage);

        $this->assertFalse($fileStorage->deleteFolder('/foobar'));
        $this->assertEquals($e, $fileStorage->getError());
    }

    public function testDeleteFolderSuccess()
    {
        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('deleteFolder')
            ->once()
            ->with('/foobar')
            ->andReturn(true);

        $fileStorage = new FileStorage($storage);

        $this->assertTrue($fileStorage->deleteFolder('/foobar'));
    }

    public function testExistsTrue()
    {
        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('exists')
            ->once()
            ->with('/foobar')
            ->andReturn(true);

        $fileStorage = new FileStorage($storage);

        $this->assertTrue($fileStorage->exists('/foobar'));
    }

    public function testIsFileTrue()
    {
        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('isFile')
            ->once()
            ->with('/foobar.jpg')
            ->andReturn(true);

        $fileStorage = new FileStorage($storage);

        $this->assertTrue($fileStorage->isFile('/foobar.jpg'));
    }

    public function testIsDirTrue()
    {
        $storage = m::mock('NaturalWeb\FileStorage\Storage\StorageInterface');
        $storage->shouldReceive('isDir')
            ->once()
            ->with('/foobar')
            ->andReturn(true);

        $fileStorage = new FileStorage($storage);

        $this->assertTrue($fileStorage->isDir('/foobar'));
    }
}