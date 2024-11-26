<?php

declare(strict_types=1);

namespace Tofex\CurlFtp;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Client
{
    /** @var Variables */
    protected $variableHelper;

    /** @var Arrays */
    protected $arrayHelper;

    /** @var resource */
    private $curlHandle;

    /** @var string */
    private $hostName;

    /** @var string */
    private $path = '/';

    /** @var bool */
    private $useSsl = false;

    /**
     * @param Variables|null $variableHelper
     * @param Arrays|null    $arrayHelper
     */
    public function __construct(Variables $variableHelper = null, Arrays $arrayHelper = null)
    {
        if ($variableHelper === null) {
            $variableHelper = new Variables();
        }

        if ($arrayHelper === null) {
            $arrayHelper = new Arrays($variableHelper);
        }

        $this->variableHelper = $variableHelper;
        $this->arrayHelper = $arrayHelper;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        if ($this->curlHandle !== null) {
            curl_close($this->curlHandle);
        }
    }

    /**
     * @throws Exception
     */
    public function open(array $args = [])
    {
        $hostName = $this->arrayHelper->getValue($args, 'host');

        if ($this->variableHelper->isEmpty($hostName)) {
            throw new Exception('The specified host is empty. Set the host and try again.');
        }

        $port = $this->arrayHelper->getValue($args, 'port', 21);
        $userName = $this->arrayHelper->getValue($args, 'user', 'anonymous');
        $password =
            $this->arrayHelper->getValue($args, 'password', $userName === 'anonymous' ? 'anonymous@noserver.com' : '');
        $useSsl = $this->arrayHelper->getValue($args, 'ssl', false);
        $usePassiveMode = $this->arrayHelper->getValue($args, 'passive', false);
        $timeout = $this->arrayHelper->getValue($args, 'timeout', 30);

        $this->connect($hostName, $port, $userName, $password, $useSsl, $usePassiveMode, $timeout);
    }

    /**
     * @throws Exception
     */
    public function connect(
        string $hostName,
        int $port,
        string $userName,
        string $password,
        bool $useSsl = false,
        bool $usePassiveMode = true,
        int $timeout = 30)
    {
        $this->curlHandle = @curl_init();

        if ($this->curlHandle === false) {
            throw new Exception('Could not initialize curl');
        }

        $this->hostName = $hostName;
        $this->useSsl = $useSsl;

        $options = [
            CURLOPT_PORT           => $port,
            CURLOPT_USERPWD        => sprintf('%s:%s', $userName, $password),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HEADER         => false,
            CURLOPT_UPLOAD         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true
        ];

        if ($useSsl) {
            $options[ CURLOPT_SSL_VERIFYPEER ] = false;
            $options[ CURLOPT_SSL_VERIFYHOST ] = false;
            $options[ CURLOPT_USE_SSL ] = CURLFTPSSL_ALL;
            $options[ CURLOPT_FTPSSLAUTH ] = CURLFTPAUTH_DEFAULT;
        }

        if ( ! $usePassiveMode) {
            $options[ CURLOPT_FTPPORT ] = '-';
        }

        foreach ($options as $key => $value) {
            $this->setCurlOption($key, $value);
        }

        $this->executeCurl();
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->curlHandle = null;
        $this->hostName = null;
        $this->path = '/';
        $this->useSsl = false;
    }

    protected function getUrl(?string $fileName = null): string
    {
        return sprintf('%s://%s/%s', $this->useSsl ? 'ftps' : 'ftp', $this->hostName, $this->getPath($fileName));
    }

    protected function getPath(?string $fileName = null): string
    {
        return sprintf('%s/%s', trim($this->path, '/'), $this->variableHelper->isEmpty($fileName) ? '' : $fileName);
    }

    public function cd(string $path)
    {
        $this->path = $path;
    }

    /**
     * @throws Exception
     */
    public function ls(): array
    {
        $result = $this->executeCurl();

        if ($this->variableHelper->isEmpty($result)) {
            return [];
        }

        $files = explode("\n", trim($result));

        $list = [];

        foreach ($files as $file) {
            $list[] = ['text' => $file, 'id' => sprintf('%s/%s', rtrim($this->path, '/'), $file)];
        }

        return $list;
    }

    /**
     * @return string|bool
     * @throws Exception
     */
    public function read(string $fileName)
    {
        return $this->executeCurl($fileName);
    }

    /**
     * @return string|bool
     * @throws Exception
     */
    public function rm(string $fileName)
    {
        $this->setCurlOption(CURLOPT_QUOTE, [sprintf('DELE /%s', ltrim($this->getPath($fileName), '/'))]);

        $result = $this->executeCurl();

        $this->setCurlOption(CURLOPT_QUOTE, []);

        return $result;
    }

    /**
     * @throws Exception
     */
    public function write(string $fileName, string $src): void
    {
        $tempDirectory = sys_get_temp_dir();

        $tempFileName = tempnam($tempDirectory, 'foo');

        file_put_contents($tempFileName, $src);

        $tempFileHandle = fopen($tempFileName, 'r');

        $this->setCurlOption(CURLOPT_UPLOAD, true);
        $this->setCurlOption(CURLOPT_INFILE, $tempFileHandle);
        $this->setCurlOption(CURLOPT_INFILESIZE, filesize($tempFileName));

        $this->executeCurl($fileName);

        $this->setCurlOption(CURLOPT_UPLOAD, false);
        $this->setCurlOption(CURLOPT_INFILE, null);
        $this->setCurlOption(CURLOPT_INFILESIZE, null);

        fclose($tempFileHandle);
    }

    /**
     * @throws Exception
     */
    protected function setCurlOption(int $key, $value): void
    {
        $optionResult = @curl_setopt($this->curlHandle, $key, $value);

        if ( ! $optionResult) {
            throw new Exception(sprintf('Could not set curl option with key: %s (%d)', $key,
                curl_errno($this->curlHandle)));
        }
    }

    /**
     * @return string|bool
     * @throws Exception
     */
    protected function executeCurl(?string $fileName = null)
    {
        $this->setCurlOption(CURLOPT_FTPLISTONLY, $this->variableHelper->isEmpty($fileName));
        $this->setCurlOption(CURLOPT_URL, $this->getUrl($fileName));

        $result = @curl_exec($this->curlHandle);

        if ($result === false) {
            throw new Exception(sprintf('Could not handle content in path: %s (%d)', $this->getPath($fileName),
                curl_errno($this->curlHandle)));
        }

        return $result;
    }
}
