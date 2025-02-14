# Skeleton CLI

The Skeleton CLI is a command-line tool to initialize/bootstrap the creation of a PHP package without the need to manually configure the package.

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

## Example

```sh
./skeleton package:init asciito acme "This is a sample package" --author="John Doe" --license=MIT --namespace="Asciito\\Acme" --package-version=v1.0.0 --minimum-stability=stable --type=library --path=./packages
```

This command will initialize a new package with the provided details and options.
