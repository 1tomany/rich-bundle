<?php

namespace OneToMany\RichBundle\Attribute;

enum PropertySource
{

    case Container;
    case File;
    case Payload;
    case Query;
    case Route;
    case Security;
    case Session;

}
