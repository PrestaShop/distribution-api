# PrestaShop OpenSource API

## Installation

1. Run `composer install`
2. Get a Github token: [https://github.com/settings/tokens/new?description=PrestaShopOpenSourceAPI&scopes=repo](https://github.com/settings/tokens/new?description=PrestHubot&scopes=repo)
3. Copy `config/parameters.yaml.dist` to `config/parameters.yaml` with the generated Github token

## Usage

### Main commands

#### Download the module's main files
```shell
$ ./bin/console downloadNativeModuleMainClasses
```
This will download only the main file of the module so the app can extract the module's version and the PrestaShop versions compliance

#### Download PrestaShop's `install/install_version.php` files
```shell
$ ./bin/console downloadPrestaShopInstallVersions
```
This will download the file `install/install_version.php` so the app can extract the PHP version compatibilities

#### Generate json files
```shell
$ ./bin/console generateJson
```

#### Everything together
```shell
$ ./bin/console run
```
This will execute the 3 previous commands:
- `downloadNativeModuleMainClasses`
- `downloadPrestaShopInstallVersions`
- `generateJson`

### Utility commands

#### Check that there is no error on the module's repositories:
```shell
$ ./bin/console checkRepos
```

```shell
$ ./bin/console clean all|json|modules|prestashop
```
This will clean the folder(s) passed as an argument

## Endpoints

`http://<domain.to.public.folder>/modules/<prestashop_version>`<br>
Returns last version of every native module compatible with the specified PrestaShop version

`http://<domain.to.public.folder>/prestashop`<br>
Returns every PrestaShop versions

`http://<domain.to.public.folder>/prestashop/<channel>`<br>
Returns the latest version of the specified channel<br>
`<channel>` can be: `stable`, `rc` or `beta`

### Environments

There are 3 targeted environment at the moment:

* **Integration**: integration-api.prestashop-project.org
* **Préproduction**: preprod-api.prestashop-project.org
* **Production**: api.prestashop-project.org

Those edge URLs are hosted at the Cloudflare level, proxyfying the origin GCP Storage.

## Architecture

![alt text](pics/architecture.png "Build & Refresh Workflow")

## Workflow

Being on github we'll use the github workflow as follow:

![alt text](pics/workflow.png "Github Workflow")

At the moment, we only have the integration workflow setup:

* **[Integration CD](.github/workflows/integration-cd.yml)**: Mostly manages the development part, is triggered when a PR has the `integration-deployment` label setup
* **[Integration Cron](.github/workflows/integration-cron.yml)**: Runs every half past hour, from to 9 to 19 UTC time
