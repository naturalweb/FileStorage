<?php
namespace NaturalWebTest;

use Mockery as m;
use Exception;
use PHPUnit_Framework_TestCase;
use NaturalWeb\FileStorage\Storage\DropboxStorage;

class DropboxStorageTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testConstruct()
    {
        $dropbox = m::mock('Dropbox\Client');
        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertInstanceOf('NaturalWeb\FileStorage\Storage\StorageInterface', $storage);
        $this->assertAttributeEquals($dropbox, 'dropbox', $storage);
        $this->assertAttributeEquals('/var/www', 'pathRoot', $storage);
        $this->assertEquals('/var/www', $storage->getPathRoot());
        $this->assertEquals($dropbox, $storage->getDropbox());
    }

    public function testMethodUploadWithoutOverride()
    {
        $metadata = array('path'=>'/foo-bar.txt', 'is_dir' => false);

        $source = __DIR__ . '/_file/file_upload.txt';

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('uploadFile')
            ->once()
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertTrue($storage->upload($source, '/foo-bar.txt'));
    }

    public function testMethodDelete()
    {
        $metadata = array('path'=>'/foo-bar.txt', 'is_dir' => false);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foo-bar.txt')
            ->andReturn($metadata)

            ->getMock()
            ->shouldReceive('delete')
            ->once()
            ->with('/var/www/foo-bar.txt')
            ->andReturn(array('is_dir' => false));

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertTrue($storage->delete('/foo-bar.txt'));
    }

    public function testMethodDeleteNotExists()
    {
        $this->setExpectedException('NaturalWeb\FileStorage\Storage\ExceptionStorage', 'File /var/www/foo-bar.txt Not found or Not Is File');

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foo-bar.txt')
            ->andReturn(null)

            ->getMock()
            ->shouldReceive('delete')
            ->never();

        $storage = new DropboxStorage('/var/www', $dropbox);

        $storage->delete('/foo-bar.txt');
    }

    public function testMethodCreateFolder()
    {
        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foo-bar')
            ->andReturn(null)

            ->getMock()
            ->shouldReceive('createFolder')
            ->once()
            ->with('/var/www/foo-bar')
            ->andReturn(array('is_dir' => true));

        $storage = new DropboxStorage('/var/www', $dropbox);
        
        $this->assertTrue($storage->createFolder('/foo-bar'));
    }

    public function testMethodCreateFolderExists()
    {
        $metadata = array('path'=>'/foo-bar', 'is_dir' => true);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foo-bar')
            ->andReturn($metadata)

            ->getMock()
            ->shouldReceive('createFolder')
            ->never();

        $storage = new DropboxStorage('/var/www', $dropbox);
        
        $this->assertTrue($storage->createFolder('/foo-bar'));
    }

    public function testMethodDeleteFolder()
    {
        $metadata = array('path'=>'/foo-bar', 'is_dir' => true);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foo-bar')
            ->andReturn($metadata)

            ->getMock()
            ->shouldReceive('delete')
            ->once()
            ->with('/var/www/foo-bar')
            ->andReturn(array('is_dir' => true));

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertTrue($storage->deleteFolder('/foo-bar'));
    }

    public function testMethodDeleteFolderNotExists()
    {
        $this->setExpectedException('NaturalWeb\FileStorage\Storage\ExceptionStorage', 'Directory /var/www/foo-bar Not found or Not Is Diretory');

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foo-bar')
            ->andReturn(null)

            ->getMock()
            ->shouldReceive('delete')
            ->never();

        $storage = new DropboxStorage('/var/www', $dropbox);

        $storage->deleteFolder('/foo-bar');
    }

    public function testMethodGetUrl()
    {
        $url = 'https://www.dropbox.com/s/hash123';

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('createShareableLink')
            ->once()
            ->with('/var/www/foobar.txt')
            ->andReturn($url);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertEquals('https://dl.dropboxusercontent.com/s/hash123', $storage->getUrl('/foobar.txt'));
    }

    public function testMethodExistsTrue()
    {
        $metadata = array('path'=>'/foobar', 'is_dir' => false);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foobar')
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertTrue($storage->exists('/foobar'));
    }

    public function testMethodExistsFalse()
    {
        $metadata = null;

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foobar')
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertFalse($storage->exists('/foobar'));
    }

    public function testMethodIsFileTrue()
    {
        $metadata = array('path'=>'/foobar.txt', 'is_dir' => false);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foobar.txt')
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertTrue($storage->isFile('/foobar.txt'));
    }

    public function testMethodIsFileFalse()
    {
        $metadata = array('path'=>'/foobar', 'is_dir' => true);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foobar')
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertFalse($storage->isFile('/foobar'));
    }

    public function testMethodIsDirTrue()
    {
        $metadata = array('path'=>'/foobar', 'is_dir' => true);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foobar')
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertTrue($storage->isDir('/foobar'));
    }

    public function testMethodIsDirFalse()
    {
        $metadata = array('path'=>'/foobar', 'is_dir' => false);

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadata')
            ->once()
            ->with('/var/www/foobar')
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $this->assertFalse($storage->isDir('/foobar'));
    }

    public function testMethodFiles()
    {
        $metadata = array(
            'path' => '/foobar',
            'is_dir' => true,
            'contents' => array(
                array(
                    'path' => '/foobar/logo.jpg',
                    'is_dir' => false,
                ),
                array(
                    'path' => '/foobar/newfolder/other.png',
                    'is_dir' => false,
                ),
                array(
                    'path' => '/foobar/image.jpg',
                    'is_dir' => false,
                ),
                array(
                    'path' => '/foobar/emptyfolder/',
                    'is_dir' => true,
                ),
            )
        );

        $dropbox = m::mock('Dropbox\Client');
        $dropbox->shouldReceive('getMetadataWithChildren')
            ->once()
            ->with('/var/www/foobar')
            ->andReturn($metadata);

        $storage = new DropboxStorage('/var/www', $dropbox);

        $expecteds = array(
            '/foobar/logo.jpg',
            '/foobar/newfolder/other.png',
            '/foobar/image.jpg'
        );
        
        $this->assertEquals($expecteds, $storage->files('/foobar'));
    }
}