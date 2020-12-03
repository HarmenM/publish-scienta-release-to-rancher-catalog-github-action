<?php

namespace HelmManipulator\Processor;

use Symfony\Component\Filesystem\Filesystem;

class ChartProcessor
{
	private $destinationPath;
	private $chartVersion;
	private $chartSuffix;
	private $scientaVersion;
	private $commitSha;

	private $filesystem;

	public function __construct(
		string $destinationPath,
		string $chartSuffix,
		string $chartVersion,
		string $scientaVersion,
		string $commitSha
	)
	{
		$this->destinationPath = $destinationPath;
		$this->chartSuffix = $chartSuffix;
		$this->chartVersion = $chartVersion;
		$this->scientaVersion = $scientaVersion;
		$this->commitSha = $commitSha;

		$this->filesystem = new Filesystem();
	}

	/**
	 * @param string $chartName
	 * @param string $sourcePath
	 * @param ManipulationDefinition[] $manipulators
	 */
	public function processChart(string $chartName, string $sourcePath, array $manipulators = [])
	{
		$destinationPath = $this->getChartDestination($chartName);

		if(realpath($destinationPath) !== false) {
			$this->filesystem->remove($destinationPath);
		}

		$this->filesystem->mirror($sourcePath, $destinationPath);

		$environment = new ChartEnvironment();
		$environment->chartSuffix = $this->chartSuffix;
		$environment->chartVersion = $this->chartVersion;
		$environment->chartName = $chartName;
		$environment->scientaVersion = $this->scientaVersion;
		$environment->commitSha = $this->commitSha;
		$environment->chartRoot = $destinationPath;

		foreach($manipulators as $relPath => $manipulator) {
			$absPath = $destinationPath . DIRECTORY_SEPARATOR . $relPath;
			if($this->filesystem->exists($absPath) === true) {
				$manipulator->manipulate($absPath, $environment);
			}
		}

	}

	private function getChartDestination(string $chartName): string
	{
		$variables = [
			"{{chartName}}" => $chartName,
			"{{chartSuffix}}" => $this->chartSuffix,
			"{{chartVersion}}" => $this->chartVersion
		];

		return str_replace(array_keys($variables), array_values($variables), $this->destinationPath);
	}
}