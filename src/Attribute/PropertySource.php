<?php

namespace OneToMany\RichBundle\Attribute;

enum PropertySource
{

    case Container;
    case Query;
    case Payload;
    case Route;
    case Token;

}
