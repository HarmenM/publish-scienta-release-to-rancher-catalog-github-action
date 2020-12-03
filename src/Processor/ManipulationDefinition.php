<?php

namespace HelmManipulator\Processor;

class ManipulationDefinition
{
	private $manipulator;
	private $inputHandler;
	private $outputHandler;

	public function __construct(callable $manipulator, callable $inputHandler, callable $outputHandler)
	{
		$this->manipulator = $manipulator;
		$this->inputHandler = $inputHandler;
		$this->outputHandler = $outputHandler;
	}

	public function manipulate(string $filePath, ChartEnvironment $environment)
	{
		$data = ($this->inputHandler)($filePath);
		$data = ($this->manipulator)($data, $environment);
		($this->outputHandler)($filePath, $data);
	}
}
