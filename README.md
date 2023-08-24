# PHP wrapper for the [age command line encryption tool](https://github.com/FiloSottile/age)
Encrypt and decrypt files with age from PHP. This library is only a wrapper for the the command line tool, it does not implement the C2CP age specification in PHP.

```php
// Encrypt a file with a generated key
$age = new FileEncryption("hello.txt");
$keypair = $age->keygen("hello.key")->encrypt("hello.txt.age");

// Encrypt a file with a specific public key
$age->public_key("age1mrf8uana2kan6jsrnf04ywxycvl4nnkzzk3et8rdz6fe6vg7upssclnak7")->encrypt("hello.txt.age");
```
```php
// Decrypt a file with a key file
$age = new FileEncryption("hello.txt.age");
$age->private_key("hello.key")->decrypt("decrypted-hello.txt");
```

# Installation
This library requires PHP 8.1+ and the [age command line tool](https://github.com/FiloSottile/age).

1. [Install the age command line tool](https://github.com/FiloSottile/age#installation)
2. Install this library with composer
   ```
   composer require victorwesterlund/php-age
   ```

# How to use
Import and use the library:
```php
require_once "vendor/autoload.php";

use \Age\FileEncryption;
```

## Encrypt a file
Encrypt a file on disk by passing it to the `FileEncryption` constructor
```php
// Relative or absolute path to a file that should be encrypted
$age = new FileEncryption("hello.txt");
```

> **Note**
> The library will not archive a folder for you. You'll have to `tar` your folder before passing it to `FileEncryption`

### Generated key pair
You can encrypt a file with a generated key pair (`age-keygen`) by chaining `keygen()`
```php
// encrypt() will return the generated keypair as an assoc array
$keypair = $age->keygen()->encrypt("hello.txt.age"); // ["public" => "...", "private" => "..."]
```

You can also save the generated key file to disk by passing an absolute or relative path to `keygen()`
```php
$keypair = $age->keygen("hello.key)->encrypt("hello.txt.age"); // ["public" => "...", "private" => "..."]
```

### Existing public key
Encrypt a file with an existing public key by chaining the `public_key()` method
```php
$keypair = $age->public_key("age1mrf8uana2kan6jsrnf04ywxycvl4nnkzzk3et8rdz6fe6vg7upssclnak7")->encrypt("hello.txt.age"); // ["public" => "age1mrf8uana2kan6jsrnf04ywxycvl4nnkzzk3et8rdz6fe6vg7upssclnak7", "private" => null]
```

## Decrypt a file
Decrypt a file on disk by passing it to the `FileEncryption` constructor
```php
// Relative or absolute path to a file that should be decrypted
$age = new FileEncryption("hello.txt.age");
```
Chain `private_key()` with an absolute or relative path to the corresponding key file
```
$age->private_key("hello.key")->decrypt("decrypted-hello.txt"); // true
```

## Optional features

Enable PEM encoding by chaining the optional `armor()` method
```
$keypair = $age->armor()->keygen()->encrypt("hello.txt.age");
```
