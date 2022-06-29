# PrestaShop OpenSource API

## Installation

1. Run `composer install`
2. Get a Github token (with write rights on the repository defined in `config/parameters.yaml`: [https://github.com/settings/tokens/new?description=PrestaShopOpenSourceAPI&scopes=repo](https://github.com/settings/tokens/new?description=PrestHubot&scopes=repo)
3. Create a bucket on GCP and download a [Service Account key file](https://developers.google.com/identity/protocols/OAuth2ServiceAccount#creatinganaccount)

## Usage

### Requirements
You should have 3 environment variables defined:
- `TOKEN` - The Github token required to use the Github API
- `GOOGLE_APPLICATION_CREDENTIALS` - Path to the json file previously downloaded containing the authentication information to use GCP
- `BUCKET_NAME` - The name of the bucket where json files should be uploaded

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

#### Update modules' config file
```shell
$ ./bin/console updateModuleConfigFiles
```
This will add new versions of module with their PrestaShop versions compatibility to the repository defined for the key `module_list_repository` in `config/parameters.yaml`

#### Generate json files
```shell
$ ./bin/console generateJson
```
This will generate the different json files to be publicly exposed in the `public/json/` folder

#### Upload generated files to a GCP bucket
```shell
$ ./bin/console uploadAssets
```
This will upload the generated json files to the GCP bucket

#### Everything together
```shell
$ ./bin/console run
```
This will execute the 5 previous commands:
- `downloadNativeModuleMainClasses`
- `downloadPrestaShopInstallVersions`
- `updateModuleConfigFiles`
- `generateJson`
- `uploadAssets`

### Utility commands

#### Check that there is no error on the module's repositories:
```shell
$ ./bin/console checkRepos
```

#### Clean the folder(s) passed as an argument:
```shell
$ ./bin/console clean all|json|modules|prestashop
```

### Docker

To use this tool with Docker, you have to:
- Build the image: `$ docker build -t distribution-api .`
- Run it with the command you want: `$ docker run --rm -v /path/to/credentials.json:/app/credentials.json -e TOKEN=your_github_token -e BUCKET_NAME=distribution-api -e GOOGLE_APPLICATION_CREDENTIALS=credentials.json distribution-api run`

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


