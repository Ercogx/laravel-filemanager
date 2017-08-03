<?php

namespace Tests;

use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use UniSharp\LaravelFilemanager\Lfm;
use UniSharp\LaravelFilemanager\LfmItem;
use UniSharp\LaravelFilemanager\LfmPath;

class LfmPathTest extends TestCase
{
    public function tearDown()
    {
        m::close();

        parent::tearDown();
    }

    public function test__Get()
    {
        $storage = m::mock(LfmStorage::class);

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getStorage')->with('laravel-filemanager/files/bar')->andReturn($storage);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/bar');

        $path = new LfmPath($helper);

        $this->assertEquals($storage, $path->storage);
    }

    public function test__Call()
    {
        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('foo')->andReturn('bar');

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getStorage')->with('laravel-filemanager/files/bar')->andReturn($storage);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/bar');

        $path = new LfmPath($helper);

        $this->assertEquals('bar', $path->foo());
    }

    public function testDirAndNormalizeWorkingDir()
    {
        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('input')->with('working_dir')->once()->andReturn('foo');

        $path = new LfmPath($helper);

        $this->assertEquals('foo', $path->normalizeWorkingDir());
        $this->assertEquals('bar', $path->dir('bar')->normalizeWorkingDir());
    }

    public function testSetNameAndGetName()
    {
        $path = new LfmPath(m::mock(Lfm::class));

        $path->setName('bar');

        $this->assertEquals('bar', $path->getName());
    }

    public function testPath()
    {
        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('allowFolderType')->with('user')->andReturn(true);
        $helper->shouldReceive('getRootFolder')->with('user')->andReturn('/foo');
        $helper->shouldReceive('basePath')->andReturn(realpath(__DIR__ . '/../'));
        $helper->shouldReceive('input')->with('working_dir')->andReturnNull();
        $helper->shouldReceive('getCategoryName')->andReturn('files');

        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('rootPath')->andReturn(realpath(__DIR__ . '/../') . '/storage/app');

        $helper->shouldReceive('getStorage')->andReturn($storage);

        $path = new LfmPath($helper);

        $this->assertEquals('laravel-filemanager/files/foo', $path->path());
        $this->assertEquals('laravel-filemanager/files/foo/bar', $path->setName('bar')->path('storage'));
    }

    public function testUrl()
    {
        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getUrlPrefix')->once()->andReturn('laravel-filemanager');
        $helper->shouldReceive('allowFolderType')->with('user')->once()->andReturn(true);
        $helper->shouldReceive('getRootFolder')->with('user')->once()->andReturn('/foo');
        $helper->shouldReceive('url')->with(m::type('string'))->once()->andReturnUsing(function ($path) {
            return "http://localhost/{$path}";
        });
        $helper->shouldReceive('input')->with('working_dir')->once()->andReturnNull();
        $helper->shouldReceive('getCategoryName')->andReturn('files');

        $path = new LfmPath($helper);

        $this->assertEquals('http://localhost/laravel-filemanager/files/foo/foo', $path->setName('foo')->url());
    }

    public function testAppendStorageFullPath()
    {
        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->andReturn('/shares');

        $path = new LfmPath($helper);

        $prefix = 'laravel-filemanager';

        $this->assertEquals($prefix . '/files/shares', $path->appendStorageFullPath($prefix));
    }

    public function testThumbAndAppendPathToFile()
    {
        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getThumbFolderName')->andReturn('thumbs');

        $path = new LfmPath($helper);
        $path->setName('baz');

        $prefix = 'bar';

        $this->assertEquals($prefix . '/baz', $path->appendPathToFile($prefix));

        $path->thumb();

        $this->assertEquals($prefix . '/thumbs/baz', $path->appendPathToFile($prefix));
    }

    public function testFolders()
    {
        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('directories')->andReturn(['foo/bar']);

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/shares');
        $helper->shouldReceive('getStorage')->andReturn($storage);
        $helper->shouldReceive('getNameFromPath')->andReturn('bar');
        $helper->shouldReceive('getThumbFolderName')->andReturn('thumbs');

        $path = new LfmPath($helper);

        $this->assertInstanceOf(LfmItem::class, $path->folders()[0]);
    }

    public function testFiles()
    {
        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('files')->andReturn(['foo/bar']);

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/shares');
        $helper->shouldReceive('getStorage')->andReturn($storage);
        $helper->shouldReceive('getNameFromPath')->andReturn('bar');

        $path = new LfmPath($helper);

        $this->assertInstanceOf(LfmItem::class, $path->files()[0]);
    }

    public function testGet()
    {
        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getNameFromPath')->andReturn('bar');

        $path = new LfmPath($helper);

        $this->assertInstanceOf(LfmItem::class, $path->get('foo'));
    }

    public function testDelete()
    {
        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('isDirectory')->andReturn(true);
        $storage->shouldReceive('deleteDirectory')->andReturn('folder_deleted');

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getStorage')->with('laravel-filemanager/files/bar')->andReturn($storage);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/bar');

        $path1 = new LfmPath($helper);

        $this->assertEquals('folder_deleted', $path1->delete());

        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('isDirectory')->andReturn(false);
        $storage->shouldReceive('delete')->andReturn('file_deleted');

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getStorage')->with('laravel-filemanager/files/bar')->andReturn($storage);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/bar');

        $path2 = new LfmPath($helper);

        $this->assertEquals('file_deleted', $path2->delete());
    }

    public function testCreateFolder()
    {
        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('rootPath')->andReturn(realpath(__DIR__ . '/../') . '/storage/app');
        $storage->shouldReceive('exists')->andReturn(false);
        $storage->shouldReceive('makeDirectory')->andReturn(true);

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getStorage')->with('laravel-filemanager/files/bar')->andReturn($storage);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/bar');

        $path = new LfmPath($helper);

        $this->assertTrue($path->createFolder('bar'));
    }

    public function testCreateFolderButFolderAlreadyExists()
    {
        $storage = m::mock(LfmStorage::class);
        $storage->shouldReceive('exists')->andReturn(true);
        $storage->shouldReceive('makeDirectory')->andReturn(true);

        $helper = m::mock(Lfm::class);
        $helper->shouldReceive('getStorage')->with('laravel-filemanager/files/bar')->andReturn($storage);
        $helper->shouldReceive('getCategoryName')->andReturn('files');
        $helper->shouldReceive('input')->with('working_dir')->andReturn('/bar');

        $path = new LfmPath($helper);

        $this->assertFalse($path->createFolder('foo'));
    }
}