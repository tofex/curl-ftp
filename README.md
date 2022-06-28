# curl-ftp

Tofex Curl FTP provides a client based on Curl.

## Installation

```bash
$ composer require tofex/curl-ftp
```

## License

Tofex Curl FTP is licensed under the MIT License - see the LICENSE file for details.

## Usage

### Creating a connection

```php
$ftp = new Client();
$ftp->open([
    'host' => 'ftp.example.com',
    'user' => 'username',
    'password' => 'password',
    'port' => 990,
    'ssl' => true,
    'passive' => true,
    'timeout' => 30
]);
```

Alternatively you can create a connection using the `connect()` method

```php
$ftp->connect($hostName, $port, $userName, $password, $useSsl, $usePassiveMode, $timeout);
```

### Listing files

```php
$files = $ftp->ls();

print_r($files);
```

produces

```text
Array
(
    [0] => Array
        (
            [text] => file_1.zip
            [id] => /file_1.zip
        )

    [1] => Array
        (
            [text] => file_2.zip
            [id] => /file_2.zip
        )
)
```

### Setting/changing current directory

```php
$ftp->cd('directory/subdirectory');
```

### Read file contents

```php
$contents = $ftp->read('path/to/file.zip');
```

### Write to file

```php
$contents = 'file contents';
$ftp->write('path/to/file.txt', $contents);
```

### Remove a file/directory
```php
$ftp->rm('path/to/file.txt');
```

### Catching errors

All exceptions are thrown as standard `Exception` classes with the following message format:

```text
Could not handle content in path: {path} ({cURL error number})
```
