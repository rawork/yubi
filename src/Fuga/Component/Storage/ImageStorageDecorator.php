<?php

namespace Fuga\Component\Storage;


use Fuga\Component\PHPThumb\GD;

class ImageStorageDecorator implements StorageInterface
{
	
	private $storageEngine;
	private $options;
	
	public function __construct($storageEngine, $options = [])
	{
		$this->storageEngine = $storageEngine;
		$this->setOptions($options);
	}
	
	public function setOptions($options)
	{
		foreach ($options as $name => $value) {
			$this->options[$name] = $value;
		}
	}
	
	public function hasOption($name)
	{
		return isset($this->options[$name]);
	}
	
	public function getOption($name)
	{
		return $this->options[$name];
	}
	
	public function save($filename, $sorcePath)
	{
		$createdFileName = $this->storageEngine->save($filename, $sorcePath);
		$this->afterSave($createdFileName);

		return $createdFileName;
	}
	
	public function copy($filename, $sorcePath)
	{
		return $this->storageEngine->copy($filename, $sorcePath);
	}
	
	public function remove($filename)
	{
		if ($this->hasOption('sizes') && $filename) {
			$pathInfo = pathinfo($filename);
			$sizes = $this->getOption('sizes');
			foreach ($sizes as $name => $size) {
				$this->storageEngine->remove($pathInfo['dirname']
						.DIRECTORY_SEPARATOR.$pathInfo['filename']
						.'_'.$name
						.(isset($pathInfo['extension']) ? '.'.$pathInfo['extension'] : '')
				);
			}

		}

		return $this->storageEngine->remove($filename);
	}
	
	public function exists($filename)
	{
		return $this->storageEngine->exists($this->realPath($filename));
	}
	
	public function realPath($filename)
	{
		return $this->storageEngine->realPath($filename);
	}
	
	public function path($filename)
	{
		return $this->storageEngine->path($filename);
	}
	
	public function size($filename, $precision = 2)
	{
		return $this->storageEngine->size($this->realPath($filename), $precision);
	}
	
	public function additionalFiles($filename, $options = [])
	{
		$this->setOptions($options);
		$files = [];

		if ($this->hasOption('sizes') && $filename) {
			$pathParts = pathinfo($filename);
			$sizes = $this->getOption('sizes');
			if (!is_array($sizes)) {
				return $files;
			}

			foreach ($sizes as $name => $size) {
				$path = $pathParts['dirname'].'/'.$pathParts['filename'].'_'.$name;
				$path .= isset($pathParts['extension']) ? '.'.$pathParts['extension'] : '';
				$files[$name] = [
					'name' => $name,
					'path' => $this->path($path),
					'size' => $this->size($path)
				];
			}
		}

		return $files;
	}
	
	public function afterSave($filename, $options = [])
	{
		$this->setOptions($options);

		if ($this->exists($filename) && $this->hasOption('sizes')) {
			foreach ($this->getOption('sizes') as $name => $size) {
				try {
					$method = isset($size['adaptive']) ? 'adaptiveResize' : 'resize';
					$pathParts = pathinfo($filename);
					$thumb = new GD($this->realPath($filename));
					$thumb->$method($size['width'], $size['height']);
					$thumb->save($this->realPath($pathParts['dirname'].'/'.$pathParts['filename'].'_'.$name.'.'.$pathParts['extension']));
				} catch (\Exception $e) {
					// handle error here however you'd like
					throw $e;
				}
			}
		}
	}
	
}