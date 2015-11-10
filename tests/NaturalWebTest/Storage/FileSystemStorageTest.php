<?php
namespace NaturalWebTest;

use Mockery as m;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use Exception;
use PHPUnit_Framework_TestCase;
use NaturalWeb\FileStorage\Storage\FileSystemStorage;

class FileSystemStorageTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        parent::setUp();

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('uploads'));
    }

    public function testConstruct()
    {
        $storage = new FileSystemStorage('/var/www/', 'http://localhost/');

        $this->assertInstanceOf('NaturalWeb\FileStorage\Storage\StorageInterface', $storage);
        $this->assertAttributeEquals('http://localhost/', 'host', $storage);
        $this->assertAttributeEquals('/var/www/', 'pathRoot', $storage);
        $this->assertEquals('/var/www/', $storage->getPathRoot());
    }

    public function testMethodUploadWithoutOverride()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $source = __DIR__ . '/_file/file_upload.txt';
        $this->assertTrue($storage->upload($source, '/file_upload.txt'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('file_upload.txt'));
    }

    public function testMethodDelete()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newFile('foo.txt')->at(vfsStreamWrapper::getRoot());
        $path = vfsStream::url("uploads/foo.txt");

        $this->assertTrue($storage->delete('/foo.txt'));
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('foo.txt'));
        $this->assertFalse(file_exists($path));
    }

    public function testMethodCreateFolder()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $path = vfsStream::url("uploads/baz");

        $this->assertTrue($storage->createFolder('/baz'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('baz'));
        $this->assertTrue(is_dir($path));
    }

    public function testMethodDeleteFolderWithItens()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $folder = vfsStream::newDirectory('/foo')->at(vfsStreamWrapper::getRoot());
        $folder->addChild(vfsStream::newDirectory('files'));
        $folder->addChild(vfsStream::newFile('text.txt'));

        $path = vfsStream::url("uploads/foo");

        $this->assertTrue($storage->deleteFolder('/foo'));
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('foo'));
        $this->assertFalse(file_exists($path));
    }

    public function testMethodDeleteFolderNotExists()
    {
        $this->setExpectedException('NaturalWeb\FileStorage\Storage\ExceptionStorage', 'Directory foo Not found or Not Is Diretory');

        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $path = vfsStream::url("uploads/foo");

        $this->assertTrue($storage->deleteFolder('/foo'));
    }

    public function testMethodGetUrlFileExists()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newFile('foo.txt')->at(vfsStreamWrapper::getRoot());
        $path = vfsStream::url("uploads/foo.txt");

        $this->assertEquals('http://localhost/foo.txt', $storage->getUrl('/foo.txt'));
    }

    public function testMethodGetUrlFileNotExists()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $path = vfsStream::url("uploads/foo.txt");

        $this->assertEquals('', $storage->getUrl('/foo.txt'));
    }

    public function testMethodExistsTrue()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newFile('foo.txt')->at(vfsStreamWrapper::getRoot());

        $this->assertTrue($storage->exists('/foo.txt'));
    }

    public function testMethodExistsFalse()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $this->assertFalse($storage->exists('/foobar'));
    }

    public function testMethodIsFileTrue()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newFile('foobar')->at(vfsStreamWrapper::getRoot());

        $this->assertTrue($storage->isFile('/foobar'));
    }

    public function testMethodIsFileFalse()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newDirectory('foobar')->at(vfsStreamWrapper::getRoot());

        $this->assertFalse($storage->isFile('/foobar'));
    }

    public function testMethodIsDirTrue()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newDirectory('foobar')->at(vfsStreamWrapper::getRoot());

        $this->assertTrue($storage->isDir('/foobar'));
    }

    public function testMethodIsDirFalse()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newFile('foobar')->at(vfsStreamWrapper::getRoot());

        $this->assertFalse($storage->isDir('/foobar'));
    }

    public function testMethodFiles()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $foobar = vfsStream::newDirectory('foobar')->at(vfsStreamWrapper::getRoot());
        vfsStream::newFile('logo.jpg')->at($foobar);
        vfsStream::newFile('image.jpg')->at($foobar);

        $newsfolder = vfsStream::newDirectory('newfolder')->at($foobar);
        vfsStream::newFile('other.png')->at($newsfolder);

        $newsfolder = vfsStream::newDirectory('emptyfolder')->at($foobar);

        $expecteds = array(
            $pathRoot.'/foobar/logo.jpg',
            $pathRoot.'/foobar/image.jpg',
            $pathRoot.'/foobar/newfolder/other.png',
        );

        $this->assertEquals($expecteds, $storage->files('/foobar'));
    }

    public function testMethodItemsDirectory()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $foobar = vfsStream::newDirectory('foobar')->at(vfsStreamWrapper::getRoot());
        vfsStream::newFile('logo.jpg')->at($foobar);
        vfsStream::newFile('image.jpg')->at($foobar);

        $newsfolder = vfsStream::newDirectory('newfolder')->at($foobar);
        vfsStream::newFile('other.png')->at($newsfolder);

        $newsfolder = vfsStream::newDirectory('emptyfolder')->at($foobar);

        $expecteds = array(
            $pathRoot.'/foobar/logo.jpg',
            $pathRoot.'/foobar/image.jpg',
            $pathRoot.'/foobar/newfolder',
            $pathRoot.'/foobar/emptyfolder',
        );

        $this->assertEquals($expecteds, $storage->items('/foobar'));
    }

    public function testMethodRename()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $foobar = vfsStream::newDirectory('foobar')->at(vfsStreamWrapper::getRoot());
        vfsStream::newFile('logo.jpg')->at($foobar);

        $pathOld = vfsStream::url("uploads/foobar");
        $pathNew = vfsStream::url("uploads/newfolder");
        $this->assertTrue(file_exists($pathOld));
        
        $this->assertTrue($storage->rename("foobar", "newfolder"));
        
        $this->assertFalse(file_exists($pathOld));
        $this->assertTrue(file_exists($pathNew));
    }

    public function testMethodCopyWithFile()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        vfsStream::newFile('logo.jpg')->at(vfsStreamWrapper::getRoot());

        $pathOld = vfsStream::url("uploads/logo.jpg");
        $pathNew = vfsStream::url("uploads/copyed.jpg");
        $this->assertTrue(file_exists($pathOld));
        
        $this->assertTrue($storage->copy("logo.jpg", "copyed.jpg"));
        
        $this->assertTrue(file_exists($pathOld));
        $this->assertTrue(file_exists($pathNew));
    }

    public function testMethodCopyWithDirectory()
    {
        $pathRoot = vfsStream::url('uploads');
        $storage = new FileSystemStorage($pathRoot, 'http://localhost/');

        $foobar = vfsStream::newDirectory('foobar')->at(vfsStreamWrapper::getRoot());
        vfsStream::newFile('logo.jpg')->at($foobar);

        $empty = vfsStream::newDirectory('empty-folder')->at($foobar);
        vfsStream::newFile('image.jpg')->at($empty);

        vfsStream::newDirectory('other-folder')->at($foobar);

        $pathOld = vfsStream::url("uploads/foobar");
        $pathNew = vfsStream::url("uploads/newfolder");
        $this->assertTrue(file_exists($pathOld));
        
        $this->assertTrue($storage->copy("foobar", "newfolder"));
        
        $this->assertTrue(file_exists($pathOld));
        $this->assertTrue(file_exists($pathNew));
    }
}