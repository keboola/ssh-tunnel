<?php

declare(strict_types=1);

namespace Keboola\SSHTunnel;

use Symfony\Component\Process\Process;

class SSH
{
    private const SSH_CONNECT_TIMEOUT_SECONDS = 60;
    private const SSH_SERVER_ALIVE_INTERVAL = 15;

    public function generateKeyPair(): array
    {
        $process = Process::fromShellCommandline("ssh-keygen -b 2048 -t rsa -f ./ssh.key -N '' -q");
        $process->run();

        $res = [
            'private' => file_get_contents('./ssh.key'),
            'public' => file_get_contents('./ssh.key.pub'),
        ];

        @unlink('./ssh.key');
        @unlink('./ssh.key.pub');

        return $res;
    }

    /**
     * Open SSH tunnel defined by config
     *
     * @param array $config
     *
     * Configuration fields:
     *
     * - user: (string) SSH proxy username. Required.
     * - sshHost: (string) SSH proxy hostname. Required.
     * - sshPort: (string) SSH protocol port. Optional, default 22.
     * - localPort: (string) local port. Optional, default 33006.
     * - remoteHost: (string) destination machine hostname. Required.
     * - remotePort: (string) destination machine port. Required.
     * - privateKey: (string) SSH private key. Required.
     * - compression: (bool) whether to use compression. Optional, default false.
     *
     *
     * @return Process
     * @throws SSHException
     */
    public function openTunnel(array $config): Process
    {
        $this->validateConfig($config);

        $privateKeyPath = $this->writeKeyToFile($config['privateKey']);
        sleep(1);

        $process = new Process($this->createSshCommand($config, $privateKeyPath));
        $process->setTimeout(60);
        $process->start();

        while ($process->isRunning()) {
            sleep(1);
        }

        if ($process->getExitCode() !== 0) {
            throw new SSHException(sprintf(
                "Unable to create ssh tunnel. Output: %s ErrorOutput: %s",
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }

        return $process;
    }

    private function createSshCommand(array $config, string $privateKeyPath): array
    {
        $cmd = [
            'ssh',
            '-p',
            $config['sshPort'],
            sprintf('%s@%s', $config['user'], $config['sshHost']),
            '-L',
            sprintf('%s:%s:%s', $config['localPort'], $config['remoteHost'], $config['remotePort']),
            '-i',
            $privateKeyPath,
            '-fN',
            '-o',
            sprintf('ConnectTimeout=%d', self::SSH_CONNECT_TIMEOUT_SECONDS),
            '-o',
            sprintf('ServerAliveInterval=%d', self::SSH_SERVER_ALIVE_INTERVAL),
            '-o',
            'ExitOnForwardFailure=yes',
            '-o',
            'StrictHostKeyChecking=no',
        ];

        if (isset($config['compression']) && $config['compression'] === true) {
            $cmd[] = '-C';
        }

        if (isset($config['debug']) && $config['debug'] === true) {
            $cmd[] = '-vvv';
        }

        return $cmd;
    }

    private function validateConfig(array $config): array
    {
        $defaultValues = [
            'sshPort' => 22,
            'localPort' => 33006,
            'compression' => false,
        ];

        $configWithDefaults = array_merge($defaultValues, $config);

        $missingParams = array_diff(
            ['user', 'sshHost', 'sshPort', 'localPort', 'remoteHost', 'remotePort', 'privateKey'],
            array_keys($configWithDefaults)
        );

        if (!empty($missingParams)) {
            throw new SSHException(sprintf("Missing parameters '%s'", implode(',', $missingParams)));
        }

        return $configWithDefaults;
    }

    private function writeKeyToFile(string $key): string
    {
        if (empty($key)) {
            throw new SSHException("Key must not be empty");
        }

        // Set restrictive umask before creating the temp file to ensure it's created with 0600 permissions
        // This prevents a race condition where the file could be read with insecure permissions
        // before chmod() is called
        $oldUmask = umask(0077);
        try {
            $fileName = (string) tempnam('/tmp/', 'ssh-key-');
        } finally {
            umask($oldUmask);
        }

        file_put_contents($fileName, $key);
        if (!chmod($fileName, 0600)) {
            throw new SSHException("Cannot set permissions to SSH private key file.");
        }

        return (string) realpath($fileName);
    }
}
