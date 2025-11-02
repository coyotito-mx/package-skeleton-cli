# Package Skeleton CLI

![example workflow](https://github.com/coyotito-mx/package-skeleton-cli/actions/workflows/tests.yml/badge.svg)
![Release version](https://img.shields.io/github/v/release/coyotito-mx/package-skeleton-cli?label=Release%20Version&color=2cbe4e&labelColor=444d56)

The **Package Skeleton CLI** is a command-line interface that allows you to initialize a PHP package skeleton by replacing all the placeholders in the files with the values you provide.


## Installation

> ⚠️
> The CLI currently **only** works on **macOS** 🤷‍♂️

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

Then you can move the binary to the desired location and use it.

## Usage

> ⚠️
> To use the CLI, you must have a package skeleton with the placeholders you want to replace, or you can call the command with the option `--bootstrap=` and one of the valid templates to bootstrap (`vanilla`, `laravel`).

The [placeholders](#placeholders-and-modifiers) must be written in the following format: `{{placeholder}}`. You can use modifiers with the placeholders to format the values before replacing them. The modifiers must be written in the following format:

```bash
{{placeholder|modifier[,modifier]}}
```

### Running the CLI

This command will bootstrap a `Laravel Project` using the `laravel` [template](https://github.com/coyotito-mx/laravel-package-skeleton) from one of our template skeletons.

```bash
skeleton init \
  asciito \
  acme \
  "This is a sample package" \
  --author="John Doe" \
  --email="john@doe.com" \
  --license=MIT \
  --namespace="Asciito\\Acme" \
  --package-version=v1.0.0 \
  --minimum-stability=stable \
  --type=library \
  --path="$HOME" \
  --bootstrap=laravel
```

### Placeholders and modifiers

The available placeholders (replacers):

- `vendor`
- `package`
- `namespace`
- `description`
- `author`
- `email` (Author's email)
- `package-version`
- `minimum-stability`
- `license` (package license: MIT)
- `type` (package type: library, project, metapackage, composer-plugin, etc.)
- **More placeholders will be added in the future.**

**Global modifiers**:

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

> ⚠️
> Beware: the order is important, calling a modifier like `<placeholder>|upper,slug` might not be what you want.
> 
> For example, the value `John Doe` could be:
> ```text
> {{author|upper,slug}} → john-doe
> ```
> Instead, you must use them in the following order:
> ```text
> {{author|slug,upper}} → JOHN-DOE
> ```


Modifiers by `Replacer`:

- `Namespace`
  - `escape` (escapes the `namespace` separator `\` to `\\`)
  - `reverse` (reverses the `namespace` separator)

### CLI available arguments and options

```shell
skeleton init [options] [--] <vendor> <package> <description>

Options:
      --namespace=NAMESPACE                  The namespace of the package
      --author[=AUTHOR]                      The author of the package
      --email=EMAIL                          The email of the author
      --minimum-stability=MINIMUM-STABILITY  The minimum stability allowed for the package [default: "dev"]
  -b, --bootstrap=BOOTSTRAP                  Bootstrap a package using a template (vanilla, laravel)
      --type[=TYPE]                          The package type [default: "library"]
      --package-version=PACKAGE-VERSION      The package version [default: "0.0.1"]
      --replace-license                      Force replace the `LICENSE.md` file
      --skip-license-generation              Skip license generation
      --dir[=DIR]                            The excluded directories (multiple values allowed)
      --file[=FILE]                          The excluded files (multiple values allowed)
      --path[=PATH]                          The path where the package will be initialized
      --confirm                              Skip the confirmation prompt
  -d, --do-not-install-dependencies          Do not install the dependencies after initialization
  -s, --no-self-delete                       Do not delete the CLI after initialization finished
```

## Contributing

If you would like to contribute to the Skeleton CLI, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Make your changes.
4. Submit a pull request.

## License

The Skeleton CLI is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
