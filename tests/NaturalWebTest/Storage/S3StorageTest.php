<?php
namespace NaturalWebTest;

use Mockery as m;
use Aws\S3\Exception\S3Exception;
use PHPUnit_Framework_TestCase;
use NaturalWeb\FileStorage\Storage\S3Storage;

class S3StorageTest extends PHPUnit_Framework_TestCase
{
    private function makeS3Client($methods = array())
    {
        return $this->getMockBuilder('Aws\S3\S3Client')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testConstruct()
    {
        $clientS3 = $this->makeS3Client();
        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertInstanceOf('NaturalWeb\FileStorage\Storage\StorageInterface', $storage);
        $this->assertAttributeEquals($clientS3, 'clientS3', $storage);
        $this->assertAttributeEquals('bucket-foo', 'bucket', $storage);
        $this->assertEquals('bucket-foo', $storage->getBucket());
        $this->assertEquals($clientS3, $storage->getClient());
    }

    public function testMethodUploadOverride()
    {
        $source = __DIR__ . '/_file/file_upload.txt';
        $expires_seconds = 30 * 365 * 24 * 60 * 60;
        $clientS3 = $this->makeS3Client(['putObject']);
        $clientS3->expects($this->once())
            ->method('putObject')
            ->with(array(
                'Bucket'      => 'bucket-foo',
                'Key'         => 'foo/bar.txt',
                'SourceFile'  => $source,
                'ACL'         => 'public-read',
                'ContentType' => 'text/plain',
                'CacheControl' => "max-age={$expires_seconds}",
                'Expires' => gmdate("D, d M Y H:i:s T", time()+$expires_seconds),
            ))
            ->will($this->returnValue(true));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->upload($source, '/foo/bar.txt'));
    }

    public function testMethodDelete()
    {
        $clientS3 = $this->makeS3Client(['deleteObject', 'doesObjectExist']);
        
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'foo.txt')
            ->will($this->returnValue(true));

        $clientS3->expects($this->once())
            ->method('deleteObject')
            ->with(array('Bucket' => 'bucket-foo', 'Key' => 'foo.txt'))
            ->will($this->returnValue(true));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->delete('/foo.txt'));
    }

    public function testMethodDeleteNotExists()
    {
        $this->setExpectedException('NaturalWeb\FileStorage\Storage\ExceptionStorage', 'File bucket-foo/foo.txt Not found or Not Is File');

        $clientS3 = $this->makeS3Client(['deleteObject', 'doesObjectExist']);
        
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'foo.txt')
            ->will($this->returnValue(false));
            
        $clientS3->expects($this->never())
            ->method('deleteObject');

        $storage = new S3Storage('bucket-foo', $clientS3);

        $storage->delete('/foo.txt');
    }

    public function testMethodCreateFolder()
    {
        $clientS3 = $this->makeS3Client(['putObject', 'doesObjectExist']);
        
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'foobar/')
            ->will($this->returnValue(false));

        $clientS3->expects($this->once())
            ->method('putObject')
            ->with(array('Bucket' => 'bucket-foo', 'Key' => 'foobar/', 'ACL' => 'public-read', 'Body' => ""))
            ->will($this->returnValue(true));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->createFolder('foobar'));
    }

    public function testMethodCreateFolderExists()
    {
        $clientS3 = $this->makeS3Client(['putObject', 'doesObjectExist']);
        
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'foobar/')
            ->will($this->returnValue(true));

        $clientS3->expects($this->never())
            ->method('putObject');

        $storage = new S3Storage('bucket-foo', $clientS3);
        $this->assertTrue($storage->createFolder('foobar'));
    }

    public function testMethodCreateFolderRoot()
    {
        $clientS3 = $this->makeS3Client(['putObject', 'doesObjectExist']);
        
        $clientS3->expects($this->never())
            ->method('doesObjectExist');

        $clientS3->expects($this->never())
            ->method('putObject');

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->createFolder('/'));
    }

    public function testMethodDeleteFolder()
    {
        $clientS3 = $this->makeS3Client(['deleteMatchingObjects', 'doesObjectExist']);
        
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'foo/')
            ->will($this->returnValue(true));

        $clientS3->expects($this->once())
            ->method('deleteMatchingObjects')
            ->with('bucket-foo', 'foo/')
            ->will($this->returnValue(true));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->deleteFolder('/foo'));
    }

    public function testMethodDeleteFolderNotExists()
    {
        $this->setExpectedException('NaturalWeb\FileStorage\Storage\ExceptionStorage', 'Directory bucket-foo/foo/ Not found or Not Is Diretory');

        $clientS3 = $this->makeS3Client(['deleteMatchingObjects', 'doesObjectExist']);
        
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'foo/')
            ->will($this->returnValue(false));

        $clientS3->expects($this->never())
            ->method('deleteMatchingObjects');

        $storage = new S3Storage('bucket-foo', $clientS3);

        $storage->deleteFolder('/foo');
    }

    public function testMethodGetUrl()
    {
        $url = 'https://aws.amazon.com/s/hash123';

        $clientS3 = $this->makeS3Client(['getObjectUrl']);
        $clientS3->expects($this->once())
            ->method('getObjectUrl')
            ->with('bucket-foo', '/foo.txt')
            ->will($this->returnValue($url));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertEquals($url, $storage->getUrl('/foo.txt'));
    }
    
    public function testMethodExists()
    {
        $clientS3 = $this->makeS3Client(['doesObjectExist']);
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', '/path/foo.txt')
            ->will($this->returnValue(true));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->exists('/path/foo.txt'));
    }

    public function testMethodIsFileTrue()
    {
        $clientS3 = $this->makeS3Client(['doesObjectExist']);
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'path/foo.txt')
            ->will($this->returnValue(true));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->isFile('/path/foo.txt'));
    }

    public function testMethodIsDir()
    {
        $clientS3 = $this->makeS3Client(['doesObjectExist']);
        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with('bucket-foo', 'path/foo/')
            ->will($this->returnValue(true));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $this->assertTrue($storage->isDir('/path/foo'));
    }

    public function testMethodFiles()
    {
        $iterator = new \ArrayIterator(array(
            array('Key' => 'foobar/logo.jpg'),
            array('Key' => 'foobar/newfolder/other.png'),
            array('Key' => 'foobar/image.jpg'),
            array('Key' => 'foobar/emptyfolder/'),
        ));

        $clientS3 = $this->makeS3Client(['getIterator']);
        
        $params = array(
            'Bucket' => 'bucket-foo',
            'Prefix' => 'foobar/',
        );
        $clientS3->expects($this->once())
            ->method('getIterator')
            ->with('ListObjects', $params)
            ->will($this->returnValue($iterator));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $expecteds = array(
            'foobar/logo.jpg',
            'foobar/newfolder/other.png',
            'foobar/image.jpg'
        );
        $this->assertEquals($expecteds, $storage->files('/foobar'));
    }

    public function testMethodItems()
    {
        $iterator = new \ArrayIterator(array(
            array('Key' => 'foobar/logo.jpg'),
            array('Prefix' => 'foobar/newfolder/'),
            array('Prefix' => 'foobar/emptyfolder/'),
        ));

        $clientS3 = $this->makeS3Client(['getIterator']);
        
        $params = array(
            'Bucket' => 'bucket-foo',
            'Prefix' => 'foobar/',
            'Delimiter' => '/',
        );
        $clientS3->expects($this->once())
            ->method('getIterator')
            ->with('ListObjects', $params)
            ->will($this->returnValue($iterator));

        $storage = new S3Storage('bucket-foo', $clientS3);

        $expecteds = array(
            'foobar/logo.jpg',
            'foobar/newfolder/',
            'foobar/emptyfolder/'
        );
        $this->assertEquals($expecteds, $storage->items('/foobar'));
    }

    public function testMethodRenameWithFile()
    {
        $clientS3 = $this->makeS3Client(['copyObject', 'doesObjectExist', 'getIterator', 'deleteObject']);
        
        $bucket = 'bucket-foo';

        $clientS3->expects($this->at(0))
            ->method('doesObjectExist')
            ->with($bucket, 'old-logo.jpg')
            ->will($this->returnValue(true));

        $clientS3->expects($this->at(1))
            ->method('doesObjectExist')
            ->with($bucket, 'new-logo.jpg')
            ->will($this->returnValue(false));

        $paramCopy = array(
            'Bucket'     => $bucket,
            'Key'        => 'new-logo.jpg',
            'CopySource' => $bucket . '/old-logo.jpg',
            'ACL'        => 'public-read',
            'MetadataDirective' => 'COPY',
        );
        $clientS3->expects($this->at(2))->method('copyObject')->with($paramCopy);

        $paramsList = array(
            'Bucket' => $bucket,
            'Prefix' => 'old-logo.jpg/',
        );
        $clientS3->expects($this->at(3))
            ->method('getIterator')
            ->with('ListObjects', $paramsList)
            ->will($this->returnValue(array()));

        $clientS3->expects($this->at(4))
            ->method('doesObjectExist')
            ->with($bucket, 'old-logo.jpg/')
            ->will($this->returnValue(false));

        $clientS3->expects($this->at(5))
            ->method('doesObjectExist')
            ->with($bucket, 'old-logo.jpg')
            ->will($this->returnValue(true));
            
        $clientS3->expects($this->at(6))
            ->method('deleteObject')
            ->with(array('Bucket' => $bucket, 'Key' => 'old-logo.jpg'))
            ->will($this->returnValue(true));

        $storage = new S3Storage($bucket, $clientS3);

        $this->assertTrue($storage->rename('old-logo.jpg', 'new-logo.jpg'));
    }

    public function testMethodRenameWithFolder()
    {
        $clientS3 = $this->makeS3Client(['copyObject', 'doesObjectExist', 'getIterator', 'deleteMatchingObjects']);
        
        $bucket = 'bucket-foo';

        $clientS3->expects($this->at(0))
            ->method('doesObjectExist')
            ->with($bucket, 'old-dir/')
            ->will($this->returnValue(true));

        $clientS3->expects($this->at(1))
            ->method('doesObjectExist')
            ->with($bucket, 'new-dir/')
            ->will($this->returnValue(false));

        $paramCopy = array(
            'Bucket'     => $bucket,
            'Key'        => 'new-dir/',
            'CopySource' => $bucket . '/' . rawurlencode('old-dir/'),
            'ACL'        => 'public-read',
            'MetadataDirective' => 'COPY',
        );
        $clientS3->expects($this->at(2))->method('copyObject')->with($paramCopy);

        $iterator = new \ArrayIterator(array(
            array('Key' => 'old-dir/'),
            array('Key' => 'old-dir/image.png'),
        ));
        $paramsList = array(
            'Bucket' => $bucket,
            'Prefix' => 'old-dir/',
        );
        $clientS3->expects($this->at(3))
            ->method('getIterator')
            ->with('ListObjects', $paramsList)
            ->will($this->returnValue($iterator));

        $paramCopy2 = array(
            'Bucket'     => $bucket,
            'Key'        => 'new-dir/image.png',
            'CopySource' => $bucket . '/old-dir/image.png',
            'ACL'        => 'public-read',
            'MetadataDirective' => 'COPY',
        );
        $clientS3->expects($this->at(4))->method('copyObject')->with($paramCopy2);

        $clientS3->expects($this->at(5))
            ->method('doesObjectExist')
            ->with($bucket, 'old-dir/')
            ->will($this->returnValue(true));

        $clientS3->expects($this->at(6))
            ->method('doesObjectExist')
            ->with($bucket, 'old-dir/')
            ->will($this->returnValue(true));
            
        $clientS3->expects($this->at(7))
            ->method('deleteMatchingObjects')
            ->with($bucket,'old-dir/')
            ->will($this->returnValue(true));

        $storage = new S3Storage($bucket, $clientS3);

        $this->assertTrue($storage->rename('old-dir/', 'new-dir/'));
    }

    public function testMethodRenameNotOldPath()
    {
        $this->setExpectedException('NaturalWeb\FileStorage\Storage\ExceptionStorage', 'Object bucket-foo/old-dir/ Not found');

        $clientS3 = $this->makeS3Client(['doesObjectExist']);
        
        $bucket = 'bucket-foo';

        $clientS3->expects($this->once())
            ->method('doesObjectExist')
            ->with($bucket, 'old-dir/')
            ->will($this->returnValue(false));

        $storage = new S3Storage($bucket, $clientS3);

        $storage->rename('old-dir/', 'new-dir/');
    }

    public function testMethodRenameNewPathExists()
    {
        $this->setExpectedException('NaturalWeb\FileStorage\Storage\ExceptionStorage', 'Object bucket-foo/new-dir/ exists');

        $clientS3 = $this->makeS3Client(['doesObjectExist']);
        
        $bucket = 'bucket-foo';

        $clientS3->expects($this->at(0))
            ->method('doesObjectExist')
            ->with($bucket, 'old-dir/')
            ->will($this->returnValue(true));

        $clientS3->expects($this->at(1))
            ->method('doesObjectExist')
            ->with($bucket, 'new-dir/')
            ->will($this->returnValue(true));

        $storage = new S3Storage($bucket, $clientS3);

        $storage->rename('old-dir/', 'new-dir/');
    }
}