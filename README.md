# Oasys (Open Authentication SYStem) v0.4.12


Multi-provider, extensible authentication framework

## Build Status

[![Latest Stable Version](https://poser.pugx.org/dreamfactory/oasys/v/stable.png)](https://packagist.org/packages/dreamfactory/oasys) [![Total Downloads](https://poser.pugx.org/dreamfactory/oasys/downloads.png)](https://packagist.org/packages/dreamfactory/oasys) [![Latest Unstable Version](https://poser.pugx.org/dreamfactory/oasys/v/unstable.png)](https://packagist.org/packages/dreamfactory/oasys) [![License](https://poser.pugx.org/dreamfactory/oasys/license.png)](https://packagist.org/packages/dreamfactory/oasys)<a href="http://tc.dreamfactory.com:8111/viewType.html?buildTypeId=oasys_release&guest=1"><img src="http://tc.dreamfactory.com:8111/app/rest/builds/buildType:(id:oasys_release)/statusIcon"/></a>

## Installation

Package installation is handled by Composer.

* If you haven't already, please [install Composer](http://getcomposer.org/doc/00-intro.md#installation-nix)
* Create `composer.json` in the root of your project:

``` json
{
    "require": {
        "dreamfactory/oasys": "~0.4.*"
    }
}
```

* Run `composer install`
* Require Composer's `vendor/autoload` script in your bootstrap/init script

## Feedback and Contributions

* Feedback is welcome in the form of pull requests and/or issues.
* Contributions should generally follow the strategy outlined in ["Contributing
  to a project"](https://help.github.com/articles/fork-a-repo#contributing-to-a-project)
* Please submit pull requests against the `develop` branch

## Credits

* This code is an amalgamation of four different libraries that I've been carting around for years.  It wouldn't have been possible if there weren't so many poorly crafted
generic authentication systems available. ;)
