<?php

namespace OneToMany\RichBundle\Attribute;

enum SourceType
{

    case Container;
    case Query;
    case Request;
    case Route;
    case Security;

}
