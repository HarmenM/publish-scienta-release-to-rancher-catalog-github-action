<?php

namespace HelmManipulator\Console;

use HelmManipulator\Processor\ChartEnvironment;
use HelmManipulator\Processor\ChartProcessor;
use HelmManipulator\Processor\ManipulationDefinition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class HelmChartsManipulationCommand extends Command
{
	private $filesystem;

	private $destinationRelPath = "{{chartName}}{{chartSuffix}}/v{{chartVersion}}";

	public function __construct($name = null)
	{
		$this->filesystem = new Filesystem();

		parent::__construct($name);
	}

	protected function configure()
	{
		// php bin/convert-versions.php convert -s$CHART_SUFFIX -- /data/deploy-source/helm /output/charts $CHART_VERSION $SCIENTA_VERSION
		$this->setName("convert")
			->addArgument("chartsPath")
			->addArgument("destinationPath")
			->addArgument("chartVersion")
			->addArgument("scientaVersion")
			->addOption("suffix", "s", InputOption::VALUE_OPTIONAL, "Branch suffix", "")
			->addOption("commitSha", "c", InputOption::VALUE_OPTIONAL, "Commit SHA of build", "")
			->addOption("destinationRelPath", "p", InputOption::VALUE_OPTIONAL, "Destination relative path",
				$this->destinationRelPath);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (($chartsPath = realpath($input->getArgument("chartsPath"))) === false) {
			throw new \RuntimeException("Cannot find helm charts path");
		}

		if (($destinationPath = realpath($input->getArgument("destinationPath"))) === false) {
			throw new \RuntimeException("Cannot find destination path");
		}

		$manipulators = $this->getChartManipulationsMap();

		$processor = new ChartProcessor(
			$destinationPath . DIRECTORY_SEPARATOR . $this->destinationRelPath,
			(string)$input->getOption("suffix"),
			(string)$input->getArgument("chartVersion"),
			(string)$input->getArgument("scientaVersion"),
			(string)$input->getOption("commitSha")
		);

		$chartsFinder = new Finder();
		$chartsFinder->depth(0)->directories()->in($chartsPath);

		foreach ($chartsFinder as $chartDir) {
			$chartName = $chartDir->getFilename();

			$processor->processChart(
				$chartName,
				$chartDir->getRealPath(),
				$manipulators[$chartName] ?? []
			);

			$output->writeln(sprintf("Converted %s chart", $chartDir->getFilename()));
		}
	}

	private function getChartManipulationsMap(): array
	{
		$yamlInput = function (string $path) {
			return Yaml::parseFile($path);
		};

		$yamlOutput = function ($path, $data) {
			file_put_contents($path, Yaml::dump($data, 4));
		};

		return [
			"scienta" => [
				"values.yaml" => new ManipulationDefinition(function (
					array $contents,
					ChartEnvironment $environment
				): array {

					if (!isset($contents['global']['versions']['scienta'])) {
						throw new \RuntimeException('Version for `scienta` is required to be replaced');
					}
					$contents['global']['versions']['scienta'] = $environment->scientaVersion;

					$valuesFinder = new Finder();
					$valuesFinder->depth(0)->files()->in(sprintf("%s/values", $environment->chartRoot));

					foreach ($valuesFinder as $valueFile) {
						$name = str_replace(".values.yaml", "", $valueFile->getFilename());
						$contents[$name] = Yaml::parseFile((string)$valueFile);
					}

					if (isset($contents['commitSha']) === true) {
						$contents['commitSha'] = $environment->commitSha;
					}

					return $contents;
				}, $yamlInput, $yamlOutput),
				"Chart.yaml" => new ManipulationDefinition(function (
					array $contents,
					ChartEnvironment $environment
				): array {
					$contents['version'] = $environment->chartVersion;
					$contents['name'] = trim(str_replace(".", "-", $environment->chartName . $environment->chartSuffix), "- ");
					$contents['namespace'] = trim(str_replace(".", "-", $environment->chartName . $environment->chartSuffix), "- ");

					return $contents;
				}, $yamlInput, $yamlOutput),
				"charts/test/Chart.yaml" => new ManipulationDefinition(function (
					array $contents,
					ChartEnvironment $environment
				): array {
					$contents['version'] = $environment->chartVersion;
					$contents['namespace'] = trim(str_replace(".", "-", $environment->chartName .  "-test" . $environment->chartSuffix), "- ");

					return $contents;
				}, $yamlInput, $yamlOutput)
			]
		];
	}
}
