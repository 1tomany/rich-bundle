<?php

class MyClass
{
    public function sayHi(string $name): void
    {
        echo("Hi, {$name}\n");
    }
}

$class = new MyClass();

call_user_func_array([$class, 'sayHi'], ['Vic']);

var_dump(is_callable([$class, 'sayHi']));
