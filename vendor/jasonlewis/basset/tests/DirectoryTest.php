<?php

use Mockery as m;

class DirectoryTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testFiltersAreAppliedToDirectory()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('get')->with('basset::filters')->andReturn(array());
		$app['config']->shouldReceive('has')->with('basset::filters.FooFilter')->andReturn(false);
		$file = m::mock('stdClass');
		$file->shouldReceive('getPathname')->once()->andReturn('path/to/some/style.css');
		$directory = m::mock('Basset\Directory[iterateDirectory]');
		$directory->__construct('path/to/foo', $app);
		$directory->shouldReceive('iterateDirectory')->once()->andReturn(array($file));
		$directory->requireDirectory();
		$directory->apply('FooFilter', array('option', 'option'));
		$pending = $directory->getPending();
		$this->assertArrayHasKey('FooFilter', $pending[0]->getFilters());
		$this->assertContains(array('option', 'option'), $pending[0]->getFilters());
	}


	public function testAssetsAreExcluded()
	{
		$app = $this->getApplication();
		$files = array(
			m::mock('stdClass'),
			m::mock('stdClass')
		);
		$files[0]->shouldReceive('getPathname')->andReturn($app['path.public'].'/style.css');
		$files[1]->shouldReceive('getPathname')->andReturn($app['path.public'].'/nested/style.css');
		$directory = m::mock('Basset\Directory[iterateDirectory]');
		$directory->__construct('path/to/foo', $app);
		$directory->shouldReceive('iterateDirectory')->andReturn($files);
		$directory->requireDirectory()->except(array('style.css', 'nested/style.css'));
		$this->assertEmpty($directory->getPending());
		$directory->requireDirectory()->except(array('style.css'));
		$pending = $directory->getPending();
		$asset = array_pop($pending);
		$this->assertEquals('nested/style.css', $asset->getRelativePath());
		$directory->requireDirectory()->except(array('nested/style.css'));
		$pending = $directory->getPending();
		$asset = array_pop($pending);
		$this->assertEquals('style.css', $asset->getRelativePath());
	}


	public function testAssetsAreIncluded()
	{
		$app = $this->getApplication();
		$files = array(
			m::mock('stdClass'),
			m::mock('stdClass')
		);
		$files[0]->shouldReceive('getPathname')->andReturn($app['path.public'].'/style.css');
		$files[1]->shouldReceive('getPathname')->andReturn($app['path.public'].'/nested/style.css');
		$directory = m::mock('Basset\Directory[iterateDirectory]');
		$directory->__construct('path/to/foo', $app);
		$directory->shouldReceive('iterateDirectory')->andReturn($files);
		$directory->requireDirectory()->only(array());
		$this->assertEmpty($directory->getPending());
		$directory->requireDirectory()->only(array('style.css'));
		$pending = $directory->getPending();
		$asset = array_pop($pending);
		$this->assertEquals('style.css', $asset->getRelativePath());
		$directory->requireDirectory()->only(array('nested/style.css'));
		$pending = $directory->getPending();
		$asset = array_pop($pending);
		$this->assertEquals('nested/style.css', $asset->getRelativePath());
	}


	protected function getApplication()
	{
		$app = new Illuminate\Container\Container;
		$app['files'] = m::mock('Illuminate\Filesystem\Filesystem');
		$app['files']->shouldReceive('extension')->andReturn('css');
		$app['files']->shouldReceive('lastModified')->andReturn(time());
		$app['files']->shouldReceive('getRemote')->andReturn('html { background-color: #fff; }');
		$app['config'] = m::mock('stdClass');
		$app['path.public'] = 'path/to/public';

		return $app;
	}


}