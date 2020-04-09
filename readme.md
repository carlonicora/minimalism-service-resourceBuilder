# minimalism-service-resource-builder

**minimalism-service-resource-builder** is a service for [minimalism](https://github.com/carlonicora/minimalism) to 
manage the transformation of data arrays from one structure to another and from array to 
[{jsonapi}](https://jsonapi.org). This service is usually employed to transform objects coming from 
[minimalism-service-mysql](https://github.com/carlonicora/minimalism-service-mysql) to objects which can be publicly
sent over the web. 

## Getting Started

To use this library, you need to have an application using minimalism. This library does not work outside this scope.

### Prerequisite

You should have read the [minimalism documentation](https://github.com/carlonicora/minimalism/readme.md) and understand
the concepts of services in the framework.

### Installing

Require this package, with [Composer](https://getcomposer.org/), in the root directory of your project.

```
$ composer require carlonicora/minimalism-service-resource-builder
```

or simply add the requirement in `composer.json`

```json
{
    "require": {
        "carlonicora/minimalism-service-resource-builder": "~1.0"
    }
}
```

## Deployment

This service does not requires any configuration.

## Build With

* [minimalism](https://github.com/carlonicora/minimalism) - minimal modular PHP MVC framework
* [minimalism-service-jsonapi](https://github.com/carlonicora/minimalism-service-jsonapi)
* [minimalism-service-encrypter](https://github.com/carlonicora/minimalism-service-encrypter)

## Versioning

This project use [Semantiv Versioning](https://semver.org/) for its tags.

## Authors

* **Sergey Kuzminich** - Initial version - [GitHub](https://github.com/aldoka) |
* **Carlo Nicora** - maintenance and expansion - [GitHub](https://github.com/carlonicora) |
[phlow](https://phlow.com/@carlo)

# License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT) - see the
[LICENSE.md](LICENSE.md) file for details 

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)