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

#### Download the module's main files
```
$ ./bin/console downloadNativeModuleMainClasses
```
This will download only the main file of the module so the app can extract the module's version and the PrestaShop versions compliance

#### Download PrestaShop's `install/install_version.php` files
```
$ ./bin/console downloadPrestaShopInstallVersions
```
This will download the file `install/install_version.php` so the app can extract the PHP version compatibilities

#### Generate json files
```
$ ./bin/console generateJson
```

## Endpoints

`http://<domain.to.public.folder>/modules/<prestashop_version>`<br>
Returns last version of every native module compatible with the specified PrestaShop version

`http://<domain.to.public.folder>/prestashop`<br>
Returns every PrestaShop versions

`http://<domain.to.public.folder>/prestashop/<channel>`<br>
Returns the latest version of the specified channel<br>
`<channel>` can be: `stable`, `rc` or `beta`