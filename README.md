# PHP wrapper for the [age command line encryption tool](https://github.com/FiloSottile/age)
Encrypt and decrypt files with age from PHP. This library is only a wrapper for the the command line tool, it does not implement the C2CP age specification in PHP.

```php
$age = new FileEncryption("hello.txt");
$age->encrypt("hello.txt.age", "hello.txt.age.key");
```
```php
$age = new FileEncryption("hello.txt.age");
$age->decrypt("hello.txt.age.key", "hello-decrypted.txt");
```

## Installation
1. [Install the age command line tool](https://github.com/FiloSottile/age#installation)
2. Install this library with composer
   ```
   composer require victorwesterlund/php-age
   ```
3. Import and use in your project

## How to use
Import and use the library:
```php
require_once "vendor/autoload.php";

use \Age\FileEncryption;
```

### Encrypt a file
Encrypt a file on disk by passing it to the `FileEncryption` constructor
```php
// Relative or absolute path to a file that should be encrypted
$age = new FileEncryption("hello.txt");
// Encrypted file destination and private key destination
// This method also returns the private key as a string
$age->encrypt("hello.txt.age", "hello.txt.age.key");
```

You can enable optional PEM encoding by chaining `armor()` before `encrypt()`
```php
// Encrypt with PEM encoding
$age->armor()->encrypt("hello.txt.age", "hello.txt.age.key");
```

### Decrypt a file
Decrypt a file on disk by passing it to the `FileEncryption` constructor
```php
// Relative or absolute path to a file that should be decrypted
$age = new FileEncryption("hello.txt.age");
// Private key file and destination of decrypted file
$age->decrypt("hello.txt.age.key", "hello.txt");
```
