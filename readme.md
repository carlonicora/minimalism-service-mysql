# minimalism-service-mysql

**minimalism-service-mysql** is a service for [minimalism](https://github.com/carlonicora/minimalism) to read and 
write data on MySQL. minimalism-service-mysql is not a full-fledged ORM and relies on arrays to transport data.

## Getting Started

To use this library, you need to have an application using minimalism. This library does not work outside this scope.

### Prerequisite

You should have read the [minimalism documentation](https://github.com/carlonicora/minimalism/readme.md) and understand
the concepts of services in the framework.

Encrypter requires either the [MySQLi](https://www.php.net/manual/en/book.mysqli.php) extension in order to work.

### Installing

Require this package, with [Composer](https://getcomposer.org/), in the root directory of your project.

```
$ composer require carlonicora/minimalism-service-mysql
```

or simply add the requirement in `composer.json`

```json
{
    "require": {
        "carlonicora/minimalism-service-mysql": "~1.0"
    }
}
```

## Deployment

This service requires you to set up a special set of parameters in your `.env` file in order to connect with MySQL.
The first parameter identifies a comma separated list of parameters which, in turns, contain a comma separated list
of connection parameters for every single database.

### Required parameters

```dotenv
#comma separated list of connections
MINIMALISM_SERVICE_ENCRYPTER_KEY=connection1,connection2

#comma separated list of connection parameters
connection1=host,username,password,dbName,port
```

## Build With

* [minimalism](https://github.com/carlonicora/minimalism) - minimal modular PHP MVC framework

## Versioning

This project use [Semantiv Versioning](https://semver.org/) for its tags.

## Authors

* **Carlo Nicora** - Initial version - [GitHub](https://github.com/carlonicora) |
[phlow](https://phlow.com/@carlo)
* **Sergey Kuzminich** - maintenance and expansion - [GitHub](https://github.com/aldoka) |

# License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT) - see the
[LICENSE.md](LICENSE.md) file for details 

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)