# RICH Bundle for Symfony
This bundle makes it easy to incorporate the RICH architecture into your Symfony application.
RICH stands for Request, Input, Command, Handler, and its goal is to make backend web application
development as easy, straightforward, and futureproof as possible. RICH applications apply
the single responsibility principle to each action a user can take with your application which
guarantees that it can mutate over time without unintended consequences.

## RICH philosophy
The central philosophy behind a RICH application is to apply to backend engineering what
Tailwind CSS did to frontend engineering minus the unecessary complexity of hexagonal
architecture, domain driven design, and CQRS.

Tailwind broke CSS down into very loosely coupled atomic components that you can apply as
classes to HTML elements. This essentially undoes the Cascading in Cascading Style Sheets,
but for good reason: you can safely modify the style of one element without much fear that
it will radically alter the layout of your application. This is incredibly powerful for
teams of developers: the tenured team member knows that the CSS class `.btn-blue` makes a
full width block level red button across the application, but the developer that started last
week doesn't, and he accidentally destroyed the styling of the application with his first PR.

RICH applies these sames principles to backend engineering: each input, command, and handler are
separate PHP classes that only have a single responsibility. The naming of these classes should
describe an action that can be performed in your application. For example, if your application
allows users to be created and updated, you would have `CreateUserInput` and `UpdateUserInput`
as your input classes, `CreateUserCommand` and `UpdateUserCommand` as your command classes,
and you guessed it, `CreateUserHandler` and `UpdateUserHandler` as your handler classes. Like
Tailwind, this may seem redundant and a source of code duplication, but the benefits an
architecture like this provide far outweigh the negatives.

## RICH structure
**Input** A request, whether it be from an API request, a form submission, the command line, or
elsewhere, is mapped onto an input object. The input object is responsible for manipulating and
validating the request data. By default, this bundle uses the Serializer and Validator components
bundled with Symfony, but you're welcome to manually map data and validate it however you see fit.

Input objects can contain some basic logic, but should generally rely on no additional dependencies
outside of the standard PHP library.

**Command** If the request data is valid and successfully mapped to an input object, the input
object will create a command object. A command object is as simple of a class as you can get in
PHP. Ideally, it should be `final`, `readonly`, and use constructor promotion to ensure immutability.
A command object is a POPO - Plain Old PHP Object - and should do its best to use scalar primitives
(`null`, `bool`, `int`, `float`, and `string`), basic arrays, standard PHP classes, or other easily
serialized and deserialized objects as its properties.

In other words, a command object would use an `int` (or a simple value object) to refer to the
primary key of a Doctrine entity rather than the entity itself. Command objects should be so simple
they can easily be serialized and deserialized so they can used in an asynchronous message queue.

**Handler** Once created, the command object is passed to the handler. For the vast majority of
applications, this can (and should) be done manually - using an asynchronous message queue is not
necessary. However, each handler should assume it is being called entirely statelessly, and hydrate
the environment it needs without assuming it already exists. For example, the handler should not
be aware of an HTTP request, session data, cookie data, or that an entity it relies on is already
being managed by Doctrine.

The handler that runs synchronously today may need to be placed in a message queue tomorrow for
a variety of reason and having the foresight to make it stateless today will save you endless
headaches tomorrow. This is also why you want your handlers to rehydrate your entity map: an
entity that existed when the command was pushed onto an asynchronous queue may not exist when the
handler is a

Each handler should contain the business logic necessary to handle the command passed to it.
Ideally, handlers should be `final` and `readonly` as well to ensure they don't accidentally
rely on any previous state to handle a command.

## Getting started
Because this is a new bundle, you'll have to manually create the structure for each module in
your application. My goal is to leverage the Symfony Maker Bundle to allow you to create the
RICH structure for each action similar to how you would create a Doctrine entity.

### Install the bundle
Install the bundle using Composer:

```shell
composer require 1tomany/rich-bundle
```

### Create the module structure
Next, you'll need to create the directory structure for your first module. There is no strict
definition on what a module is, other than a set of features that are loosely related to the
same domain. To get started, it's easiest to think of a module as being related to each of
your "primary" entities.

I recommend the following directory structure for each module:

```
src/
  <Module>/
    Action/
      Command/
      Handler/
      Exception/
      Input/
      Result/
    Contract/
      Exception/
    Framework/
      Controller/
        Api/
        Web/
```

We'll get into the purpose of each of these soon. You can use the following command to quickly create
this structure. Replace `<Module>` with the actual name of your module (e.g. `Account` or `Invoice`).

```shell
mkdir -p src/<Module>/{Action/{Command,Handler/Exception,Input,Result},Contract/Exception,Framework/Controller/{Api,Web}}
```

Moving forward, lets assume we're working on a module named `Account` for a Doctrine entity also
named `Account` which uses a repository (shockingly) named `AccountRepository`.

### Create the module's contracts
As the name implies, the `Contract` directory stores contracts to interact with this module. You should
have, at minimum, two contracts to start with: a `RepositoryInterface` and `ExceptionInterface`. In the
`Contract` directory, create a file named `<Entity>RepositoryInterface.php` where `<Entity>` is the
Doctrine entity that will use this repository. Create a file named `AccountRepositoryInterface.php` in
`src/Account/Contract` and populate it with the following code:

```php
<?php

namespace App\Account\Contract;

use App\Entity\Account;

interface AccountRepositoryInterface
{
    public function findOneById(?int $accountId): ?Account;
}
```

Again, a RICH application is flexible by nature so you're not required to create a method named
`findOneById()`, but I find it more descriptive and extensible than just `find()`.

A RICH application encourages you to keep your Doctrine entities and repositories in their original
locations. There's no reason to struggle with the Doctrine configuration to force each entity to
reside in a directory or namespace different than what it expects. While modules remain loosely
coupled in a RICH application, entities will always remain tightly coupled, so it's best to leave
them in `src/Entity`.

Assuming the `Account` entity and `AccountRepository` repository already exist, update the
`AccountRepository` class to implement your new `AccountRepositoryInterface`. If you only have a
single class implementing an interface, you can inject that interface into a service and Symfony
will know to inject an instance of the class implementing it - it's fantastic!

```php
<?php

namespace App\Repository;

use App\Entity\Account;
use App\Account\Contract\AccountRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
* @extends ServiceEntityRepository<Account>
*/
class AccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
    * @see App\Account\Contract\AccountRepositoryInterface
    */
    public function findOneById(?int $accountId): ?Account
    {
        if (!$accountId) {
            return null;
        }

        return $this->find($accountId);
    }
}
```

We also need an exception interface that all exceptions from this module originate from. This
makes it easy for any other module using services from this module to catch all thrown exceptions.

In the `src/Account/Contract/Exception` directory, create a file named `ExceptionInterface.php`
and populate it with the following code:

```php
<?php

namespace App\Account\Contract\Exception;

interface ExceptionInterface extends \Throwable
{
}
```

### Create the command class
Though the input class is used first, the command class is shared amongst the input and handler
classes, so lets start by creating it first. In the `src/Account/Action/Command` directory,
create a file named `CreateAccountCommand.php` and populate it with the following code:

```php
<?php

namespace App\Account\Action\Command;

use OneToMany\RichBundle\Contract\CommandInterface;

final readonly class CreateAccountCommand implements CommandInterface
{
    public function __construct(
        public string ?$author,
        public string $name,
        public string $company,
        public string $email,
        public ?string $notes,
        public ?\DateTimeImmutable $foundedDate = null,
        public ?string $ipAddress = null,
    ) {
    }
}
```

### Create input class
Now that we have a command class, the input class that creates the command class needs
to be created. In the `src/Account/Action/Input` directory, create a file named
`CreateAccountInput.php` and populate it with the following code:

```php
<?php

namespace App\Account\Action\Input;

use App\Account\Action\Command\CreateAccountCommand;
use OneToMany\RichBundle\Attribute\SourceIpAddress;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Attribute\SourceSecurity;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements InputInterface<CreateAccountCommand>
 */
final class CreateAccountInput implements InputInterface
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 128)]
        #[SourceSecurity]
        private(set) public ?string $author,

        #[Assert\Length(min: 4, max: 128)]
        #[SourceRequest]
        private(set) public string $name,

        #[Assert\Length(min: 4, max: 48)]
        #[SourceRequest]
        private(set) public string $company,

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 128)]
        #[SourceRequest]
        private(set) public string $email,

        #[Assert\Length(max: 1024)]
        #[SourceRequest]
        private(set) public ?string $notes,

        #[SourceRequest]
        private(set) public ?\DateTimeImmutable $foundedDate = null,

        #[Assert\Ip(version: 'all')]
        #[SourceIpAddress]
        private(set) public ?string $ipAddress = null,
    ) {
    }

    public function toCommand(): CommandInterface
    {
        return new CreateAccountCommand(...[
            'author' => $this->author,
            'name' => $this->name,
            'company' => $this->company,
            'email' => $this->email,
            'notes' => $this->notes,
            'foundedDate' => $this->foundedDate,
            'ipAddress' => $this->ipAddress,
        ]);
    }
}
```

While the input class is also fairly simple in nature, it accomplishes a lot. First, I recommend
you take advantage of asymmetric visibility in PHP 8.4. Making the class `readonly` limits
what can be done with property hooks, so it's best to make the setters private and the getters public.

You'll also notice some new attributes: `SourceSecurity`, `SourceRequest`, and `SourceIpAddress`.
These allow you to indicate where in the request the data should come from. The `MapRequestPayload`
attribute that was announced in Symfony 6.3 is powerful, but limiting in that it assumes everything
comes from the request body. There are eight attributes provided by this bundle that allow you to
specify both the source and name of the data from the request.

- `#[SourceContainer(name: 'app.custom_property')]` Fetches a parameter named `app.custom_property` from the container.
- `#[SourceFile(name: 'file')]` Fetches a parameter named `file` from the `Request::$files` bag and converts it to
   a [`OneToMany\DataUri\DataUri`](https://github.com/1tomany/data-uri) object.
- `#[SourceIpAddress]` Fetches the value returned by the `Request::getClientIp()` method.
- `#[SourceQuery(name: 'query')]` Fetches a parameter named `query` from the `Request::$query` bag.
- `#[SourceRequest(name: 'user')]` Fetches a parameter named `user` from the result of the `Request::getPayload()` method.
- `#[SourceRoute(name: 'productId')]` Fetches a parameter named `productId` from the parsed route.
- `#[SourceSecurity]` Fetches the value returned by the `Symfony\Component\Security\Core\Authentication\TokenInterface::getUserIdentifier()` method
   if the request is made by an authenticated user. This is helpful if you want to bind this input object to an authorized request.
- `#[PropertyIgnored]` Forces the parser to ignore this property.

If a property is not explicitly ignored or sourced, the value resolver will assume it uses
the `#[SourceRequest]` attribute.

Additionaly, the `name` argument for each attribute is optional. The value resolver will use
the name of the property if a `name` is not given. The `#[SourceIpAddress]` and `#[SourceSecurity]`
attributes do not have a `name` argument because their values are the results of a method call.

Sources are also chainable. This allows you to support multiple versions of an API without
having to change the underlying input object. For example, the first version if your API might
have used a property named `email` but the second version of your API changed that to `username`.
All attributes are chainable, but `#[SourceIpAddress]` and `#[SourceSecurity]` are not repeatable.

In the example below, the `$username` property could be mapped from either of the following URLs:

- `/api/v1/accounts?email=vic@1tomany.com`
- `/api/v2/accounts?username=vic@1tomany.com`

```php
<?php

namespace App\Account\Action\Input;

use App\Account\Action\Command\ReadAccountCommand;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements InputInterface<ReadAccountCommand>
 */
final class ReadAccountInput implements InputInterface
{
    public function __construct(
        #[Assert\Email]
        #[Assert\NotBlank]
        #[Assert\Length(max: 128)]
        #[SourceQuery('email')]
        #[SourceQuery('username')]
        private(set) public string $username,
    ) {
    }

    public function toCommand(): CommandInterface
    {
        return new ReadAccountCommand($this->username);
    }
}
```

The value resolver will attempt to extract a value from a source until it finds one. Note that this
means a `NULL` or falsy value is valid! In the URL `/api/v1/accounts?email=&username=vic@1tomany.com`,
the value of the `$username` property would be an empty string because the key `email` is present
and comes before the `username` key.

You can also mix chained sources. For example, you can have both a `#[SourceRequest]` and `#[SourceContainer]`
attribute on a property: if the value wasn't found in the request body, then it would be retrieved from
the container parameters.

### Create the result class

### Create handler class

### Wire to a controller
