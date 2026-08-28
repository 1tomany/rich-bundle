<?php

namespace OneToMany\RichBundle\Tests\Maker;

use OneToMany\RichBundle\Maker\MakeRichModule;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

#[Group('UnitTests')]
#[Group('MakerTests')]
final class MakeRichModuleTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir().'/rich-bundle-maker-'.bin2hex(random_bytes(8));

        mkdir($this->rootDir.'/src', 0755, true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->rootDir);
    }

    public function testGeneratingCreatesTheActionDirectoryStructure(): void
    {
        $this->generate(['module' => 'account']);

        $moduleDir = $this->rootDir.'/src/Module/Account';

        $this->assertDirectoryExists($moduleDir.'/Action/Command');
        $this->assertDirectoryExists($moduleDir.'/Action/Event');
        $this->assertDirectoryExists($moduleDir.'/Action/Handler/Exception');
        $this->assertDirectoryExists($moduleDir.'/Action/Input');
    }

    public function testGeneratingCreatesTheControllerDirectoriesWhenWithControllersIsTrue(): void
    {
        $this->generate(['module' => 'account', '--with-controllers' => true]);

        $this->assertDirectoryExists($this->rootDir.'/src/Controller/Account/API');
        $this->assertDirectoryExists($this->rootDir.'/src/Controller/Account/Web');
    }

    public function testGeneratingSkipsTheControllerDirectoriesWhenWithControllersIsFalse(): void
    {
        $this->generate(['module' => 'account', '--no-with-controllers' => true]);

        $this->assertDirectoryDoesNotExist($this->rootDir.'/src/Controller');
    }

    public function testGeneratingCreatesTheCommandDirectoryWhenWithCommandsIsTrue(): void
    {
        $this->generate(['module' => 'account', '--with-commands' => true]);

        $this->assertDirectoryExists($this->rootDir.'/src/Command/Account');
    }

    public function testGeneratingSkipsTheCommandDirectoryWhenWithCommandsIsFalse(): void
    {
        $this->generate(['module' => 'account', '--no-with-commands' => true]);

        $this->assertDirectoryDoesNotExist($this->rootDir.'/src/Command');
    }

    public function testGeneratingConvertsTheModuleNameToPascalCase(): void
    {
        $this->generate(['module' => 'search index']);

        $this->assertDirectoryExists($this->rootDir.'/src/Module/SearchIndex');
        $this->assertDirectoryExists($this->rootDir.'/src/Controller/SearchIndex/API');
        $this->assertDirectoryExists($this->rootDir.'/src/Command/SearchIndex');
    }

    public function testGeneratingRemovesCharactersNotInTheAsciiAlphabet(): void
    {
        $this->generate(['module' => 'account_2 v2!']);

        $this->assertDirectoryExists($this->rootDir.'/src/Module/AccountV');
    }

    public function testGeneratingFailsWhenTheModuleNameDoesNotContainAnyLetters(): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('The module name "123" is not valid because it does not contain any letters.');

        $this->generate(['module' => '123']);
    }

    public function testGeneratingFailsWhenTheModuleNameIsBlank(): void
    {
        $this->expectException(RuntimeCommandException::class);

        $this->generate(['module' => '   ']);
    }

    public function testGeneratingFailsWhenTheSourceDirectoryDoesNotExist(): void
    {
        $this->expectException(RuntimeCommandException::class);

        $this->generate(['module' => 'account', '--src-dir' => $this->rootDir.'/does-not-exist']);
    }

    public function testGeneratingCreatesTheContractExceptionInterface(): void
    {
        $this->generate(['module' => 'account']);

        $file = $this->rootDir.'/src/Module/Account/Contract/Exception/ExceptionInterface.php';

        $this->assertFileExists($file);
        $this->assertSame(
            "<?php\n\nnamespace App\\Module\\Account\\Contract\\Exception;\n\ninterface ExceptionInterface extends \\Throwable\n{\n}\n",
            file_get_contents($file),
        );
    }

    public function testGeneratingCreatesTheModuleExceptions(): void
    {
        $this->generate(['module' => 'account']);

        $invalidArgumentExceptionFile = $this->rootDir.'/src/Module/Account/Exception/InvalidArgumentException.php';
        $runtimeExceptionFile = $this->rootDir.'/src/Module/Account/Exception/RuntimeException.php';

        $this->assertFileExists($invalidArgumentExceptionFile);
        $this->assertSame(
            "<?php\n\nnamespace App\\Module\\Account\\Exception;\n\nuse App\\Module\\Account\\Contract\\Exception\\ExceptionInterface;\n\nclass InvalidArgumentException extends \\InvalidArgumentException implements ExceptionInterface\n{\n}\n",
            file_get_contents($invalidArgumentExceptionFile),
        );

        $this->assertFileExists($runtimeExceptionFile);
        $this->assertSame(
            "<?php\n\nnamespace App\\Module\\Account\\Exception;\n\nuse App\\Module\\Account\\Contract\\Exception\\ExceptionInterface;\n\nclass RuntimeException extends \\RuntimeException implements ExceptionInterface\n{\n}\n",
            file_get_contents($runtimeExceptionFile),
        );
    }

    public function testGeneratingCreatesTheRepositoryInterfaceWhenTheRepositoryDirectoryIsWritable(): void
    {
        mkdir($this->rootDir.'/src/Repository', 0755, true);

        $this->generate(['module' => 'account', '--with-repository' => true]);

        $file = $this->rootDir.'/src/Module/Account/Contract/Repository/AccountRepositoryInterface.php';

        $this->assertFileExists($file);
        $this->assertSame(
            "<?php\n\nnamespace App\\Module\\Account\\Contract\\Repository;\n\ninterface AccountRepositoryInterface\n{\n}\n",
            file_get_contents($file),
        );
    }

    public function testGeneratingSkipsTheRepositoryInterfaceWhenTheRepositoryDirectoryDoesNotExist(): void
    {
        $this->generate(['module' => 'account', '--with-repository' => true]);

        $this->assertFileDoesNotExist($this->rootDir.'/src/Module/Account/Contract/Repository/AccountRepositoryInterface.php');
    }

    public function testGeneratingSkipsTheRepositoryInterfaceWhenWithRepositoryIsFalse(): void
    {
        mkdir($this->rootDir.'/src/Repository', 0755, true);

        $this->generate(['module' => 'account', '--no-with-repository' => true]);

        $this->assertFileDoesNotExist($this->rootDir.'/src/Module/Account/Contract/Repository/AccountRepositoryInterface.php');
    }

    public function testGeneratingDoesNotOverwriteFilesThatAlreadyExist(): void
    {
        $this->generate(['module' => 'account']);

        $file = $this->rootDir.'/src/Module/Account/Exception/RuntimeException.php';
        file_put_contents($file, 'CUSTOM CONTENT');

        $this->generate(['module' => 'account']);

        $this->assertSame('CUSTOM CONTENT', file_get_contents($file));
    }

    public function testGeneratingResolvesARelativeSourceDirectoryAgainstTheWorkingDirectory(): void
    {
        $previousWorkingDirectory = getcwd();
        $this->assertIsString($previousWorkingDirectory);

        chdir($this->rootDir);

        try {
            $this->generate(['module' => 'account'], injectSourceDirectory: false);
        } finally {
            chdir($previousWorkingDirectory);
        }

        $this->assertDirectoryExists($this->rootDir.'/src/Module/Account');
    }

    /**
     * @param array<string, bool|string> $arguments
     */
    private function generate(array $arguments, bool $injectSourceDirectory = true): string
    {
        if ($injectSourceDirectory) {
            $arguments += ['--src-dir' => $this->rootDir.'/src'];
        }

        $maker = new MakeRichModule();

        $command = new Command('make:rich-module');
        $maker->configureCommand($command, new InputConfiguration());

        $input = new ArrayInput($arguments, $command->getDefinition());
        $output = new BufferedOutput();

        $io = new ConsoleStyle($input, $output);
        $generator = $this->createGenerator();

        $maker->generate($input, $io, $generator);

        return $output->fetch();
    }

    private function createGenerator(): Generator
    {
        $fileManager = new FileManager(
            new Filesystem(),
            new AutoloaderUtil(new ComposerAutoloaderFinder('App')),
            new MakerFileLinkFormatter(),
            $this->rootDir,
        );

        return new Generator($fileManager, 'App');
    }
}
