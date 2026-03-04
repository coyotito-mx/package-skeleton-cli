# Package Skeleton CLI

![Example Workflow](https://github.com/coyotito-mx/package-skeleton-cli/actions/workflows/tests.yml/badge.svg)
![Release Version](https://img.shields.io/github/v/release/coyotito-mx/package-skeleton-cli?label=Release%20Version&color=2cbe4e&labelColor=444d56)
![macOS ARM only](https://shields.io/badge/MacOS--9cf?logo=Apple&style=social)

The **Package Skeleton CLI** is a command-line interface that allows you to initialize a PHP package skeleton by replacing placeholders in files with the values you provide.

## Installation

> ⚠️
> The CLI currently works **only** on **macOS** with ARM architecture.

### Downloading the CLI

Using **cURL**:

```bash
curl -L "https://github.com/coyotito-mx/package-skeleton-cli/releases/latest/download/skeleton.tar.gz" -o skeleton.tar.gz

tar -xzf skeleton.tar.gz && chmod +x skeleton
```

Using **wget**:

```bash
wget "https://github.com/coyotito-mx/package-skeleton-cli/releases/latest/download/skeleton.tar.gz" -O skeleton.tar.gz

tar -xzf skeleton.tar.gz && chmod +x skeleton
```

Then you can move the binary to your `PATH`:

```bash
mv skeleton /usr/local/bin/
```

## Usage

> ✅
> The CLI initializes packages in the **current working directory** or a specified `--path`. You can initialize from existing files or bootstrap from a template using `--bootstrap`.

The [placeholders](#placeholders-and-modifiers) must follow the format: `{{placeholder}}`. You can apply modifiers to format values before replacement:

```bash
{{placeholder|modifier[,modifier]}}
```

### Running the CLI

Initialize a package with vendor, package name, and author details:

```bash
skeleton init \
  acme \
  blog \
  "Acme\\Blog" \
  "John Doe" \
  "john@doe.com" \
  "A blogging package for Laravel" \
  --path="$HOME/projects/my-package"
```

Or use prompts:

```bash
skeleton init
# Prompts: vendor, package, namespace (optional), author, email (fetched from git config, if available), description
```

> Note: The testing framework prompt appears only when dependencies are installed (i.e., without `--no-install`).

To skip confirmation:

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "Description" \
  --proceed
```

To skip Composer dependency installation:

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "Description" \
  --no-install
```

To skip creating a LICENSE file when it does not exist:

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "Description" \
  --skip-license
```

To exclude specific files/directories from processing:

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "Description" \
  --exclude="composer.json" \
  --exclude="package.json"
```

To bootstrap from a skeleton template (`vanilla` or `laravel`):

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "Description" \
  --bootstrap=vanilla
```

To force bootstrapping into a non-empty directory:

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "Description" \
  --bootstrap=laravel \
  --force
```

### Placeholders and Modifiers

#### Available Placeholders

- `vendor` - Package vendor (e.g., `acme`)
- `package` - Package name (e.g., `blog`)
- `namespace` - Package namespace (auto-generated from vendor\package, or custom)
- `description` - Package description
- `author` - Package author name
- `email` - Author's email address
- `license` - License name (defaults to `MIT`)
- `version` - Package version (defaults to `0.0.1`)
- `year` - Current year
- `class` - Class name (defaults to package name in PascalCase). Used primarily in filenames

> **Namespace Format Requirements**
>
> The `namespace` argument must follow this pattern: `Vendor\Package`
>
> - Must have exactly two parts separated by a backslash
> - Each part must start with an **uppercase letter**
> - Each part can contain alphanumeric characters (A-Z, a-z, 0-9)
> - Invalid example: `acme\blog`, `Acme_Blog`, `Acme/Blog` ❌
> - Valid example: `Acme\Blog`, `MyVendor\MyPackage` ✅
>
> If not provided, the namespace will be auto-generated as `Vendor\Package` based on the vendor and package arguments.

#### Global Modifiers

- `upper` - Converts to UPPERCASE
- `lower` - Converts to lowercase
- `title` - Converts to Title Case
- `snake` - Converts to snake_case
- `kebab` - Converts to kebab-case
- `camel` - Converts to camelCase
- `pascal` - Converts to PascalCase (StudlyCase)
- `slug` - Converts to slug-format
- `ucfirst` - Converts first character to uppercase
- `acronym` - Generates acronym (e.g., "John Doe" → "JD")

> ⚠️
> **Modifier Order Matters!** The order of chained modifiers affects the output.
>
> ```text
> John Doe → JOHN-DOE
>
> {{author|upper,slug}} → john-doe     (incorrect)
> {{author|slug,upper}} → JOHN-DOE     (correct)
> ```

#### Replacer-Specific Modifiers

##### `namespace` Replacer

- `escape` - Escapes `\` to `\\` (e.g., `Acme\Blog` → `Acme\\Blog`)
- `reverse` - Reverses `\` to `/` (e.g., `Acme\Blog` → `Acme/Blog`)

> **Note**: Modifiers are applied to each part of the namespace separately (vendor and package).

##### `version` Replacer

- `major` - Extracts major version (e.g., `2` from `2.5.3`)
- `minor` - Extracts minor version (e.g., `5` from `2.5.3`)
- `patch` - Extracts patch version (e.g., `3` from `2.5.3`)
- `pre` - Extracts pre-release (e.g., `alpha` from `1.0.0-alpha`)
- `meta` - Extracts build metadata (e.g., `abc123` from `1.0.0+abc123`)
- `prefix` - Adds `v` prefix if not present (e.g., `1.0.0` → `v1.0.0`)

##### `email` Replacer

- `upper` - Converts email to uppercase while preserving `@` and `.`

##### `class` Replacer

- Used only in **filename** contexts
- Converts to kebab-case in filenames (e.g., `{{class}}` → `my-class.php`)
- Default value is transformed from package name (e.g., `blog` → `Blog` → `blog` in filename)
- Supports all global modifiers when used in content

### CLI Arguments and Options

```bash
SYNOPSIS
  skeleton init [options] [--] <vendor> <package> <namespace> <author> <email> <description>

Arguments
  vendor                       The name of the package vendor (prompted if not provided)
  package                      The name of the package (prompted if not provided)
  namespace                    The package namespace (auto-generated as Vendor\Package if not provided)
  author                       The package author (fetched from `git config user.name` or prompted)
  email                        The package author email (fetched from `git config user.email` or prompted)
  description                  The package description (prompted if not provided)

Options
      --bootstrap[=BOOTSTRAP]  Initialize a new package from skeleton template (options: laravel, vanilla)
      --class[=CLASS]          The class name to use in replacements (defaults to package name)
      --force                  Force bootstrapping even if target directory is not empty (use with --bootstrap)
      --proceed                Accept the configuration and proceed without confirmation
      --no-install             Skip installing composer dependencies
      --skip-license           Skip creating a LICENSE file if one does not exist
      --path[=PATH]            The path to initialize the package in (defaults to current working directory)
      --exclude[=EXCLUDE]      Paths to exclude when processing files (multiple values allowed)
  -h, --help                   Display help for the command
      --silent                 Do not output any messages
  -q, --quiet                  Only errors are displayed. All other output is suppressed
  -V, --version                Display this application version
      --ansi|--no-ansi         Force (or disable --no-ansi) ANSI output
  -n, --no-interaction         Do not ask any interactive question
      --env[=ENV]              The environment the command should run under
  -v|vv|vvv, --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output, and 3 for debug output
```

### Excluded Paths (Default)

By default, the following paths are **excluded** from placeholder replacement:

- `.git`
- `.DS_Store`
- `vendor`
- `node_modules`

Add custom exclusions with `--exclude`:

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "Description" \
  --exclude="dist" \
  --exclude="build" \
  --exclude=".env"
```

### Author Information

The CLI automatically fetches author information from git configuration:

```bash
git config user.name "John Doe"
git config user.email "john@doe.com"
```

If git config is not available, you'll be prompted interactively.

### Testing Framework Selection

When dependencies are enabled (without `--no-install`), you'll be prompted to choose your testing framework:

- **Pest** (default) - Modern, elegant testing for PHP
- **PHPUnit** - Industry-standard PHP testing framework

The selected framework's dev dependencies will be automatically installed.

If `--no-install` is used, this prompt is skipped and no testing framework dependencies are installed.

### CLI Removal Prompt

At the end of a successful `init` run, the CLI always asks:

```text
Do you want to remove this CLI now?
```

If you answer `yes`, the CLI removes the invoked executable file. If it cannot resolve or delete that executable, it shows a warning and continues.

## Examples

### Example 1: Initialize with all prompted values

```bash
$ skeleton init --path="$HOME/projects"
Enter the package vendor name: acme
Enter the package name: blog
Enter the package namespace (Optional, press Enter to auto-generate): Acme\Blog
Enter the package description: A blogging package
(Author and email fetched from git config, if available)

...

Do you want to proceed with this configuration?: Yes

...

Which testing framework do you want to use? [pest/phpunit]: pest

...

Package [Acme\Blog] initialized successfully!

Do you want to remove this CLI now?: No
```

### Example 2: Initialize with all arguments provided

```bash
skeleton init acme blog "Acme\\Blog" "Jane Doe" "jane@example.com" "A blogging package" \
  --proceed \
  --path="$HOME/projects"
```

### Example 3: Initialize without dependencies

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "A blogging package" \
  --no-install \
  --proceed
```

### Example 4: Initialize with a custom class name

```bash
skeleton init acme blog "Acme\\Blog" "John Doe" "john@doe.com" "A blogging package" \
  --class="BlogManager" \
  --proceed
```

## Contributing

If you would like to contribute to the Skeleton CLI, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Make your changes and add tests.
4. Ensure all tests pass: `composer test`
5. Run code standards: `composer lint:with-style`
6. Submit a pull request.

## Testing

Run tests with:

```bash
composer test         # Run all tests
composer test:ci      # Run tests in CI mode
```

Check code standards with:

```bash
composer lint         # Run PHPStan (level 5)
composer style        # Check code formatting with Pint
```

Fix code formatting:

```bash
composer fix           # Auto-fix code formatting
```

## License

The Skeleton CLI is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
