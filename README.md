# PrestaShop OpenSource API

## Installation

1. Run `composer install`
2. Get a Github token: [https://github.com/settings/tokens/new?description=PrestaShopOpenSourceAPI&scopes=repo](https://github.com/settings/tokens/new?description=PrestHubot&scopes=repo)
3. Copy `config/parameters.yaml.dist` to `config/parameters.yaml` with the generated Github token

## Usage

#### Check that there is no error on the module's repositories:
```
$ ./bin/console checkRepos
```

#### Download the modules
```
$ ./bin/console downloadNativeModules
```

#### Generate json files
```
$ ./bin/console generateJson
```

## Endpoints

`http://<domain.to.public.folder>/modules`
Returns every native modules with their versions

`http://<domain.to.public.folder>/modules/<prestashop_version>`
Returns last version of every native module compatible with the specified PrestaShop version

`http://<domain.to.public.folder>/modules/<module_name>/version[/<prestashop_version>]`
Returns last version of specified module compatible with the specified PrestaShop version or the latest PrestaShop version if not specified

`http://<domain.to.public.folder>/modules/<module_name>/download/<module_version>`
Download specified version of the specified module 
