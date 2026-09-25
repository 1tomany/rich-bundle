<?php

namespace OneToMany\RichBundle\Tests\Maker;

use Composer\Autoload\ClassLoader;
use OneToMany\RichBundle\Maker\MakeRichModule;
use PHPUnit\Framework\Attributes\DataProvider;
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
use Symfony\Component\Finder\Finder;

use function bin2hex;
use function file_get_contents;
use function iterator_to_array;
use function random_bytes;
use function sort;
use function sys_get_temp_dir;

use const TOKEN_PARSE;

#[Group('UnitTests')]
#[Group('MakerTests')]
final class MakeRichModuleTest extends TestCase
{
    private Filesystem $filesystem;
    private ClassLoader $classLoader;
    private string $projectDirectory;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();

        $this->projectDirectory = sys_get_temp_dir().'/rich-bundle-'.bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->projectDirectory.'/src');

        // The generator resolves class paths from the Composer autoloader
        $this->classLoader = new ClassLoader();
        $this->classLoader->addPsr4('App\\', $this->projectDirectory.'/src');
        $this->classLoader->register(true);
    }

    protected function tearDown(): void
    {
        $this->classLoader->unregister();
        $this->filesystem->remove($this->projectDirectory);
    }

    public function testMakingModuleGeneratesModuleStructure(): void
    {
        $this->makeModule('Account');

        $expectedFiles = [
            'src/Domain/Account/Action/Command/CreateAccountCommand.php',
            'src/Domain/Account/Action/Command/ReadAccountCommand.php',
            'src/Domain/Account/Action/Event/AccountCreated.php',
            'src/Domain/Account/Action/Handler/CreateAccountHandler.php',
            'src/Domain/Account/Action/Handler/ReadAccountHandler.php',
            'src/Domain/Account/Action/Input/CreateAccountInput.php',
            'src/Domain/Account/Action/Input/ReadAccountInput.php',
            'src/Domain/Account/Contract/Exception/ExceptionInterface.php',
            'src/Domain/Account/Contract/Repository/AccountRepositoryInterface.php',
            'src/Domain/Account/Exception/DomainException.php',
            'src/Domain/Account/Exception/RuntimeException.php',
            'src/Domain/Account/Framework/Command/ReadAccountCommand.php',
        ];

        $this->assertSame($expectedFiles, $this->findFiles());

        $expectedEmptyDirectories = [
            'src/Domain/Account/Action/Handler/Exception',
            'src/Domain/Account/Contract/Enum',
            'src/Domain/Account/Framework/Controller/API',
            'src/Domain/Account/Framework/Controller/Web',
        ];

        foreach ($expectedEmptyDirectories as $directory) {
            $this->assertDirectoryExists($this->projectDirectory.'/'.$directory);
        }
    }

    #[DataProvider('providerModuleFileAndContents')]
    public function testMakingModuleGeneratesFile(string $file, string $contents): void
    {
        $this->makeModule('Account');

        $path = $this->projectDirectory.'/'.$file;
        $this->assertFileExists($path);

        $generatedContents = (string) file_get_contents($path);
        $this->assertSame($contents, $generatedContents);

        // Throws a ParseError if the generated code is not valid
        $this->assertNotEmpty(\PhpToken::tokenize($generatedContents, TOKEN_PARSE));
    }

    /**
     * @return list<list<string>>
     */
    public static function providerModuleFileAndContents(): array
    {
        $provider = [
            [
                'src/Domain/Account/Action/Command/CreateAccountCommand.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Action\Command;

                use OneToMany\RichBundle\Contract\Action\CommandInterface;

                final readonly class CreateAccountCommand implements CommandInterface
                {
                    public function __construct()
                    {
                    }
                }

                PHP,
            ],
            [
                'src/Domain/Account/Action/Command/ReadAccountCommand.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Action\Command;

                use OneToMany\RichBundle\Contract\Action\CommandInterface;

                final readonly class ReadAccountCommand implements CommandInterface
                {
                    public function __construct(
                        public int $accountId,
                    ) {
                    }
                }

                PHP,
            ],
            [
                'src/Domain/Account/Action/Event/AccountCreated.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Action\Event;

                final readonly class AccountCreated
                {
                    public function __construct(
                        public int $accountId,
                    ) {
                    }
                }

                PHP,
            ],
            [
                'src/Domain/Account/Action/Handler/CreateAccountHandler.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Action\Handler;

                use App\Domain\Account\Action\Command\CreateAccountCommand;
                use App\Domain\Account\Exception\RuntimeException;
                use App\Entity\Account;
                use OneToMany\RichBundle\Contract\Action\CommandInterface;
                use OneToMany\RichBundle\Contract\Action\HandlerInterface;
                use OneToMany\RichBundle\Contract\Action\ResultInterface;

                /**
                 * @implements HandlerInterface<CreateAccountCommand, ResultInterface<Account>>
                 */
                final readonly class CreateAccountHandler implements HandlerInterface
                {
                    public function __construct()
                    {
                    }

                    /**
                     * @see OneToMany\RichBundle\Contract\Action\HandlerInterface
                     */
                    #[\Override]
                    public function handle(CommandInterface $command): ResultInterface
                    {
                        throw new RuntimeException('Not implemented!');
                    }
                }

                PHP,
            ],
            [
                'src/Domain/Account/Action/Handler/ReadAccountHandler.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Action\Handler;

                use App\Domain\Account\Action\Command\ReadAccountCommand;
                use App\Domain\Account\Exception\RuntimeException;
                use App\Entity\Account;
                use OneToMany\RichBundle\Contract\Action\CommandInterface;
                use OneToMany\RichBundle\Contract\Action\HandlerInterface;
                use OneToMany\RichBundle\Contract\Action\ResultInterface;

                /**
                 * @implements HandlerInterface<ReadAccountCommand, ResultInterface<Account>>
                 */
                final readonly class ReadAccountHandler implements HandlerInterface
                {
                    public function __construct()
                    {
                    }

                    /**
                     * @see OneToMany\RichBundle\Contract\Action\HandlerInterface
                     */
                    #[\Override]
                    public function handle(CommandInterface $command): ResultInterface
                    {
                        throw new RuntimeException('Not implemented!');
                    }
                }

                PHP,
            ],
            [
                'src/Domain/Account/Action/Input/CreateAccountInput.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Action\Input;

                use App\Domain\Account\Action\Command\CreateAccountCommand;
                use OneToMany\RichBundle\Contract\Action\CommandInterface;
                use OneToMany\RichBundle\Contract\Action\InputInterface;

                /**
                 * @implements InputInterface<CreateAccountCommand>
                 */
                final readonly class CreateAccountInput implements InputInterface
                {
                    public function __construct()
                    {
                    }

                    /**
                     * @see OneToMany\RichBundle\Contract\Action\InputInterface
                     */
                    #[\Override]
                    public function toCommand(): CommandInterface
                    {
                        return new CreateAccountCommand();
                    }
                }

                PHP,
            ],
            [
                'src/Domain/Account/Action/Input/ReadAccountInput.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Action\Input;

                use App\Domain\Account\Action\Command\ReadAccountCommand;
                use OneToMany\RichBundle\Contract\Action\CommandInterface;
                use OneToMany\RichBundle\Contract\Action\InputInterface;

                /**
                 * @implements InputInterface<ReadAccountCommand>
                 */
                final readonly class ReadAccountInput implements InputInterface
                {
                    public function __construct(
                        public ?int $accountId,
                    ) {
                    }

                    /**
                     * @see OneToMany\RichBundle\Contract\Action\InputInterface
                     */
                    #[\Override]
                    public function toCommand(): CommandInterface
                    {
                        return new ReadAccountCommand((int) $this->accountId);
                    }
                }

                PHP,
            ],
            [
                'src/Domain/Account/Contract/Exception/ExceptionInterface.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Contract\Exception;

                interface ExceptionInterface extends \Throwable
                {
                }

                PHP,
            ],
            [
                'src/Domain/Account/Contract/Repository/AccountRepositoryInterface.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Contract\Repository;

                interface AccountRepositoryInterface
                {
                }

                PHP,
            ],
            [
                'src/Domain/Account/Exception/DomainException.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Exception;

                use App\Domain\Account\Contract\Exception\ExceptionInterface;

                class DomainException extends \DomainException implements ExceptionInterface
                {
                }

                PHP,
            ],
            [
                'src/Domain/Account/Exception/RuntimeException.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Exception;

                use App\Domain\Account\Contract\Exception\ExceptionInterface;

                class RuntimeException extends \RuntimeException implements ExceptionInterface
                {
                }

                PHP,
            ],
            [
                'src/Domain/Account/Framework/Command/ReadAccountCommand.php',
                <<<'PHP'
                <?php

                namespace App\Domain\Account\Framework\Command;

                use App\Domain\Account\Exception\RuntimeException;
                use Symfony\Component\Console\Attribute\Argument;
                use Symfony\Component\Console\Attribute\AsCommand;
                use Symfony\Component\Console\Command\Command;
                use Symfony\Component\Console\Style\SymfonyStyle;

                #[AsCommand(
                    name: 'app:read-account',
                )]
                final readonly class ReadAccountCommand
                {
                    public function __construct()
                    {
                    }

                    public function __invoke(
                        SymfonyStyle $io,
                    ): int {
                        throw new RuntimeException('Not implemented!');

                        return Command::SUCCESS;
                    }
                }

                PHP,
            ],
        ];

        return $provider;
    }

    public function testMakingModuleWithoutRepositoryDoesNotGenerateRepositoryInterface(): void
    {
        $this->makeModule('Account', [
            '--without-repository' => true,
        ]);

        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Contract/Repository/AccountRepositoryInterface.php');
        $this->assertDirectoryDoesNotExist($this->projectDirectory.'/src/Domain/Account/Contract/Repository');
    }

    public function testMakingModuleWithoutCreateStubDoesNotGenerateCreateClasses(): void
    {
        $this->makeModule('Account', [
            '--no-with-create-stub' => true,
        ]);

        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Action/Command/CreateAccountCommand.php');
        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Action/Event/AccountCreated.php');
        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Action/Handler/CreateAccountHandler.php');
        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Action/Input/CreateAccountInput.php');
        $this->assertFileExists($this->projectDirectory.'/src/Domain/Account/Action/Command/ReadAccountCommand.php');
    }

    public function testMakingModuleWithoutReadStubDoesNotGenerateReadClasses(): void
    {
        $this->makeModule('Account', [
            '--no-with-read-stub' => true,
        ]);

        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Action/Command/ReadAccountCommand.php');
        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Action/Handler/ReadAccountHandler.php');
        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Action/Input/ReadAccountInput.php');
        $this->assertFileDoesNotExist($this->projectDirectory.'/src/Domain/Account/Framework/Command/ReadAccountCommand.php');
        $this->assertFileExists($this->projectDirectory.'/src/Domain/Account/Action/Command/CreateAccountCommand.php');
    }

    public function testMakingModuleWithoutStubsOrRepositoryGeneratesExceptionsAndDirectories(): void
    {
        $this->makeModule('Account', [
            '--without-repository' => true,
            '--no-with-create-stub' => true,
            '--no-with-read-stub' => true,
        ]);

        $expectedFiles = [
            'src/Domain/Account/Contract/Exception/ExceptionInterface.php',
            'src/Domain/Account/Exception/DomainException.php',
            'src/Domain/Account/Exception/RuntimeException.php',
        ];

        $this->assertSame($expectedFiles, $this->findFiles());
        $this->assertDirectoryExists($this->projectDirectory.'/src/Domain/Account/Action/Command');
        $this->assertDirectoryExists($this->projectDirectory.'/src/Domain/Account/Framework/Controller/Web');
    }

    public function testMakingModuleNormalizesModuleName(): void
    {
        $this->makeModule('invoice-line');

        $command = (string) file_get_contents($this->projectDirectory.'/src/Domain/InvoiceLine/Action/Command/ReadInvoiceLineCommand.php');
        $this->assertStringContainsString('namespace App\Domain\InvoiceLine\Action\Command;', $command);
        $this->assertStringContainsString('public int $invoiceLineId,', $command);

        $consoleCommand = (string) file_get_contents($this->projectDirectory.'/src/Domain/InvoiceLine/Framework/Command/ReadInvoiceLineCommand.php');
        $this->assertStringContainsString("name: 'app:read-invoice-line',", $consoleCommand);
    }

    public function testMakingModuleDoesNotOverwriteExistingFiles(): void
    {
        $path = $this->projectDirectory.'/src/Domain/Account/Exception/RuntimeException.php';
        $this->filesystem->dumpFile($path, '<?php // Custom exception');

        $output = $this->makeModule('Account');

        $this->assertStringEqualsFile($path, '<?php // Custom exception');
        $this->assertStringContainsString('skipped: src/Domain/Account/Exception/RuntimeException.php (already exists)', $output);
        $this->assertFileExists($this->projectDirectory.'/src/Domain/Account/Exception/DomainException.php');
    }

    public function testMakingModuleAgainGeneratesMissingFilesOnly(): void
    {
        $this->makeModule('Account');

        $path = $this->projectDirectory.'/src/Domain/Account/Action/Input/ReadAccountInput.php';
        $this->filesystem->remove($path);

        $output = $this->makeModule('Account');

        $this->assertFileExists($path);
        $this->assertStringNotContainsString('skipped: src/Domain/Account/Action/Input/ReadAccountInput.php', $output);
        $this->assertStringContainsString('skipped: src/Domain/Account/Action/Input/CreateAccountInput.php (already exists)', $output);
    }

    #[DataProvider('providerInvalidModule')]
    public function testMakingModuleRequiresValidModuleName(string $module): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessageMatches('/The module name ".*" is not valid/');

        $this->makeModule($module);
    }

    /**
     * @return list<list<string>>
     */
    public static function providerInvalidModule(): array
    {
        $provider = [
            [''],
            [' '],
            ['1Account'],
            ['Account\\User'],
            ['Account/User'],
            ['Accöunt'],
        ];

        return $provider;
    }

    /**
     * @param array<string, bool> $options
     */
    private function makeModule(string $module, array $options = []): string
    {
        $fileManager = new FileManager($this->filesystem, new AutoloaderUtil(new ComposerAutoloaderFinder('App')), new MakerFileLinkFormatter(), $this->projectDirectory);

        $maker = new MakeRichModule($fileManager);
        $command = new Command(MakeRichModule::getCommandName());
        $maker->configureCommand($command, new InputConfiguration());

        $input = new ArrayInput(['module' => $module, ...$options], $command->getDefinition());
        $output = new BufferedOutput();

        $maker->generate($input, new ConsoleStyle($input, $output), new Generator($fileManager, 'App'));

        return $output->fetch();
    }

    /**
     * @return list<string>
     */
    private function findFiles(): array
    {
        $finder = new Finder()->files()->in($this->projectDirectory);

        $files = [];

        foreach (iterator_to_array($finder, false) as $file) {
            $files[] = $file->getRelativePathname();
        }

        sort($files);

        return $files;
    }
}
