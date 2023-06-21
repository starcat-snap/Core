<?php declare(strict_types=1);

namespace Shopware\Core\DevOps\StaticAnalyze\Coverage\Command;

use Composer\Autoload\ClassLoader;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'coverage:classes-per-area',
    description: 'Output all classes of the Shopware-namespace aggregated by area.

  In order for this command to work properly, you need to dump the composer autoloader before running it:
  $ composer dump-autoload -o
'
)]
#[Package('core')]
class GetClassesPerAreaCommand extends Command
{
    private const OPTION_JSON = 'json';
    private const OPTION_PRETTY = 'pretty-print';

    private const OPTION_GENERATE_PHPUNIT_TEST = 'generate-phpunit-test';

    private ClassLoader $classLoader;

    /**
     * @internal
     */
    public function __construct(
        private readonly string $projectDir,
    ) {
        $this->classLoader = require $this->projectDir . '/vendor/autoload.php';

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            self::OPTION_JSON,
            'j',
            InputOption::VALUE_NONE,
            'Output as JSON'
        );

        $this->addOption(
            self::OPTION_PRETTY,
            'H',
            InputOption::VALUE_NONE,
            'Format output to be human-readable'
        );

        $this->addOption(
            self::OPTION_GENERATE_PHPUNIT_TEST,
            'g',
            InputOption::VALUE_NONE,
            'Generate phpunit..xml'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classesPerArea = $this->getClassesPerArea();
        if ($input->getOption(self::OPTION_JSON)) {
            $output->write(
                json_encode(
                    $classesPerArea,
                    $input->getOption(self::OPTION_PRETTY) ? \JSON_PRETTY_PRINT : 0
                ) ?: ''
            );
        } else {
            $output->write(
                var_export(
                    $classesPerArea,
                    true
                )
            );
        }

        if ($input->getOption(self::OPTION_GENERATE_PHPUNIT_TEST)) {
            $unitFiles = [];
            foreach ($classesPerArea as $area => $classToFile) {
                $unitFile = new \DOMDocument();
                // Load phpunit template
                $unitFile->load('phpunit.xml.dist');
                $unitDocument = $unitFile->documentElement;
                if ($unitDocument === null) {
                    return 1;
                }
                $coverage = $unitDocument->getElementsByTagName('coverage')->item(0);
                if ($coverage === null) {
                    return 1;
                }
                $includeChildElement = $coverage->getElementsByTagName('include')->item(0);
                if ($includeChildElement === null) {
                    return 1;
                }
                // Remove include from coverage to create our own includes
                $coverage->removeChild($includeChildElement);
                $includeElement = $unitFile->createElement('include');

                foreach ($classToFile as $class => $file) {
                    $fileElement = $unitFile->createElement('file', $file);
                    $includeElement->appendChild($fileElement);
                }
                $coverage->appendChild($includeElement);

                // Create phpunit file per area
                file_put_contents("phpunit.$area.xml", $unitFile->saveXML());
            }
        }

        return 0;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getClassesPerArea(): array
    {
        $areas = [];

        foreach ($this->getShopwareClasses() as $class => $path) {
            try {
                $area = Package::getPackageName($class);
            } catch (\Throwable $e) {
                $areas['unknown'][$class] = $path;

                continue;
            }

            if (!\is_string($area)) {
                continue;
            }

            $areaTrim = strstr($area, \PHP_EOL, true) ?: $area;

            $areas[trim($areaTrim)][$class] = $path;
        }

        return $areas;
    }

    /**
     * @return array<string, string>
     */
    private function getShopwareClasses(): array
    {
        return array_filter($this->classLoader->getClassMap(), static function (string $class): bool {
            return str_starts_with($class, 'Shopware\\');
        }, \ARRAY_FILTER_USE_KEY);
    }
}
