# Skeleton CLI

The Skeleton CLI is a command-line tool to initialize/bootstrap the creation of a PHP package without the need to manually configure the package.

## Installation

To install the Skeleton CLI, you can clone the repository and install the dependencies:

```sh
git clone https://github.com/your-repo/skeleton-cli.git
cd skeleton-cli
composer install
```

## Usage

To use the `package:init` command, run the following command in your terminal:

```sh
./skeleton package:init <vendor> <package> <description> [options]
```

### Arguments

- `vendor`: The vendor name.
- `package`: The package name.
- `description`: The package description.

### Options

- `--author=`: The package author. If not provided, it will be generated automatically.
- `--license=`: The package license. Available values: MIT, Apache-2.0, GPL-3.0, default: MIT.
- `--namespace=`: The package namespace. If not provided, it will be generated automatically.
- `--package-version=`: The package version. Default: v0.0.1.
- `--minimum-stability=`: The package minimum-stability. Available values: dev, alpha, beta, RC, stable.
- `--type=`: The package type. Available values: project, library, metapackage, composer-plugin.
- `--dir=*`: The excluded directories.
- `--path=`: The path where the package will be initialized.
- `--template=`: The path to a custom template for package initialization.

### Example

```sh
./skeleton package:init asciito acme "This is a sample package" --author="John Doe" --license=MIT --namespace="Asciito\\Acme" --package-version=v1.0.0 --minimum-stability=stable --type=library --path=./packages
```

This command will initialize a new package with the provided details and options.

## Commands

### `package:init`

Initializes a new PHP package with the specified vendor, package name, and description. You can also provide additional options to customize the package initialization.

#### Usage

```sh
./skeleton package:init <vendor> <package> <description> [options]
```

#### Arguments

- `vendor`: The vendor name.
- `package`: The package name.
- `description`: The package description.

#### Options

- `--author=`: The package author. If not provided, it will be generated automatically.
- `--license=`: The package license. Available values: MIT, Apache-2.0, GPL-3.0, default: MIT.
- `--namespace=`: The package namespace. If not provided, it will be generated automatically.
- `--package-version=`: The package version. Default: v0.0.1.
- `--minimum-stability=`: The package minimum-stability. Available values: dev, alpha, beta, RC, stable.
- `--type=`: The package type. Available values: project, library, metapackage, composer-plugin.
- `--dir=*`: The excluded directories.
- `--path=`: The path where the package will be initialized.
- `--template=`: The path to a custom template for package initialization.

### `package:install`

Installs the dependencies for the initialized package.

#### Usage

```sh
./skeleton package:install
```

## Contributing

If you would like to contribute to the Skeleton CLI, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Make your changes.
4. Submit a pull request.

## License

The Skeleton CLI is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
