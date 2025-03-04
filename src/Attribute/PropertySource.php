<?php

namespace OneToMany\RichBundle\Attribute;

enum PropertySource
{

    case QueryString;
    case RequestContent;
    case RouteParameter;
    case TokenStorage;
    // case Container;

}
