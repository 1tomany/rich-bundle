# RICH Bundle for Symfony
This bundle makes it easy to incorporate the RICH architecture into your Symfony application.
RICH stands for Request, Input, Command, Handler, and its goal is to make backend web application
development as straightforward as possible. A RICH application applies the single responsibility
principle to each action a user can take with your software.

## RICH philosophy
The central philosophy behind a RICH application is to apply to backend engineering what
Tailwind CSS did to frontend engineering. Tailwind broke CSS down into very loosely coupled
atomic components that you can apply as classes to HTML elements. This essentially undoes
the Cascading in Cascading Style Sheets, but for good reason: you can safely modify the style
of one element without much fear that it will radically alter the layout of your application.
This is incredibly powerful for teams of developers: the tenured team member knows that the
CSS class `.btn-blue` makes a full width block level red button across the application, but
the developer that just started last week doesn't, and he accidentally destroyed the styling
of the application with his first PR.

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
validating the request data. By default, this bundle uses the serializer and validator components
bundled with Symfony, but you're welcome to manually map data and validate it however you see fit.

Input objects can contain some basic logic, but should generally rely on no additional dependencies
outside of the standard PHP library.

**Command** If the request data is valid and successfully mapped to an input object, the input
object will create a command object. A command object is as simple of a class as you can get in
PHP. Ideally, it should be `final`, `readonly`, and use constructor promotion to ensure immutability.
A command object is a POPO - Plain 'Ol PHP Object and should do its best to use scalar primitives
(`null`, `bool`, `int`, `float`, and `string`), basic arrays, or other POPO's as its properties.
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
headaches tomorrow.

## Getting started

### Install the bundle
Install the bundle using Composer:

```shell
composer require 1tomany/rich-bundle
```

### Create module structure

### Create contracts

### Create input class

### Create command class

### Create handler class

### Wire to a controller

1. **Install the bundle**

2. **Create module structure** Next, you'll need to create the directory structure for your first module.
   There is no strict definition on what a module is, other than a set of features that are loosely related
   to the same domain. To get started, it's easiest to think of a module as being related to each of your
   "primary" entities.

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

   To keep things simple, lets assume we're working on a module named `Account` for a Doctrine entity named `Account`.

3. **Create contracts** As the name implies, the `Contract` directory stores contracts to interact with
   this module. You should have, at minimum, two contracts to start with: a `RepositoryInterface`
   and `ExceptionInterface`. In the `Contract` directory, create a file named `<Entity>RepositoryInterface.php`
   where `<Entity>` is the Doctrine entity that will use this repository. Continuing with our example
   from above, we'll create a file named `AccountRepositoryInterface.php` in `src/Account/Contract` and populate
   it with the following code:

   ```php
   <?php

   namespace App\Account\Contract;

   use App\Entity\Account;

   interface AccountRepositoryInterface
   {

       public function findOneById(?int $accountId): ?Account;

   }
   ```

   Again, a RICH application is flexible by nature so you're not required to create a method named `findOneById()`,
   but I find it more descriptive and extensible than just `find()`.

   A RICH application encourages you to keep your Doctrine entities and repositories in their original
   locations. There's no reason to struggle with the Doctrine configuration to force each entity to reside in
   a directory or namespace different than what it expects. While modules remain loosely coupled in a RICH
   application, entities will always remain tightly coupled, so it's best to leave them in `src/Entity`.

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

   We also need an exception interface that all exceptions from this module originate from. This makes it easy
   for any other module using services from this module to catch all thrown exceptions.

   In the `src/Account/Contract/Exception` directory, create a file named `ExceptionInterface.php` and
   populate it with the following code:

   ```php
   <?php

   namespace App\Account\Contract\Exception;

   interface ExceptionInterface extends \Throwable
   {
   }
   ```

3. **Create input class**

4. **Create command class**

5. **Create handler class**

6. **Wire to controller**
