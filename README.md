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
     ModuleName/
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

   ```shell
   mkdir -p src/<Module>/{Action/{Command,Handler/Exception,Input,Result},Contract/Exception,Framework/Controller/{Api,Web}}
   ```

3. **Create input class**

4. **Create command class**

5. **Create handler class**

6. **Wire to controller**
