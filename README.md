# Package Skeleton CLI

The **Package Skeleton CLI** is a command-line interface that allows you to initialize a PHP package skeleton by replacing all the placeholders in the files with the values you provide.

## Installation

The installation only works on Unix-like systems, such as Linux and macOS. To use it on Windows, you can use the Windows Subsystem for Linux (WSL) to be able to use the CLI.

### Cloning the repository:

```bash
git clone https://github.com/coyotito-mx/package-skeleton-cli.git

cd package-skeleton-cli

php skeleton app:build skeleton

chmod +x build/skeleton
```

Once the command is built, you can move the binary to the desired location and use it as a global command.

```bash
mv build/skeleton /usr/local/bin/skeleton
```

### Downloading the CLI

Using **cURL**:
```bash
curl -L "https://github.com/coyotito-mx/package-skeleton-cli/releases/download/v0.0.3/skeleton" -o skeleton

chmod +x skeleton
```

Using **wget**:
```bash
wget "https://github.com/coyotito-mx/package-skeleton-cli/releases/download/v0.0.3/skeleton" -O skeleton

chmod +x skeleton
```

Then you can move the binary to the desired location and use it as a global command.

```bash
mv skeleton /usr/local/bin/skeleton
```

## Usage

To use the CLI, you must have a package skeleton with the placeholders you want to replace. The placeholders must be written in the following format: `{{placeholder}}`. You can use modifiers with the placeholders to format the values before replacing them. The modifiers must be written in the following format:

```bash
{{placeholder|modifier[,modifier]}}
```

The available placeholders:

- `vendor`
- `package`
- `namespace`
- `description`
- `author`
- `package-version`
- `minimum-stability`
- `license` (package license: MIT, GPL, etc.)
- `type` (package type: library, project, metapackage, composer-plugin, etc.)
- **More placeholders will be added in the future.**

The available modifiers (global):

- `upper` (converts the value to uppercase)
- `lower` (converts the value to lowercase)
- `ucfirst` (converts the first character to uppercase)
- `title` (converts the value to title case)
- `studly` (converts the value to studly case)
- `camel` (converts the value to camel case)
- `slug` (converts the value to slug case)
- `snake` (converts the value to snake case)
- `kebab` (converts the value to kebab case)
- `plural` (converts the value to plural)
- `reverse` (reverses the value)
- **More modifiers will be added in the future.**

Modifiers by `Replacer`:

- `Namespace`
    - `escape` (escapes the `namespace` separator)
    - `reverse` (reverses the `namespace` separator)

### Initializing the package skeleton

To initialize the package skeleton, you must run the following command:

```bash
skeleton package:init [options] [--] <vendor> <package> <description>
```

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

> **Note**: If you want to know the available options, you can use the `--help` option.

You can also run the command without any arguments. This will prompt you to enter the arguments needed to initialize the package skeleton.

```bash
skeleton package:init
```

### Example

```bash
skeleton package:init asciito acme "This is a sample package" --author="John Doe" --license=MIT --namespace="Asciito\\Acme" --package-version=v1.0.0 --minimum-stability=stable --type=library --path=./packages
```

This command will initialize a new package with the provided details and options.

## Contributing

If you would like to contribute to the Skeleton CLI, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Make your changes.
4. Submit a pull request.

## License

The Skeleton CLI is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
