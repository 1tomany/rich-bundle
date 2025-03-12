# RICH Bundle for Symfony
This bundle makes it easy to incorporate the RICH architecture into your Symfony application.
RICH stands for Request, Input, Command, Handler, and its goal is to make backend web application
development as straightforward as possible. A RICH application applies the single responsibility
principle to each action a user can take with your software. Once applying RICH practices,
you'll no longer have to fear any side effects from modifying a specific component of your software.

## Getting Started
1. **Install the bundle** Install the bundle using Composer:
   ```shell
   composer require 1tomany/rich-bundle
   ```

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
