<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 17/02/16
 * Time: 16:03
 */

namespace Keboola\SSHTunnel;

use Symfony\Component\Process\Process;

class SSH
{
    const SSH_SERVER_ALIVE_INTERVAL = 15;

    public function generateKeyPair()
    {
        $process = Process::fromShellCommandline("ssh-keygen -b 2048 -t rsa -f ./ssh.key -N '' -q");
        $process->run();

        $res = [
            'private' => file_get_contents('./ssh.key'),
            'public' => file_get_contents('./ssh.key.pub')
        ];

        @unlink('./ssh.key');
        @unlink('./ssh.key.pub');

        return $res;
    }

    /**
     *
     * $user, $sshHost, $localPort, $remoteHost, $remotePort, $privateKey, $sshPort = '22'
     *
     * @param array $config
     *  - user
     *  - sshHost
     *  - sshPort
     *  - localPort
     *  - remoteHost
     *  - remotePort
     *  - privateKey
     *
     * @return Process
     * @throws SSHException
     */
    public function openTunnel(array $config)
    {
        $missingParams = array_diff(
            ['user', 'sshHost', 'sshPort', 'localPort', 'remoteHost', 'remotePort', 'privateKey'],
            array_keys($config)
        );

        if (!empty($missingParams)) {
            throw new SSHException(sprintf("Missing parameters '%s'", implode(',', $missingParams)));
        }

        $cmd = [
            'ssh',
            '-p',
            sprintf('%s', $config['sshPort']),
            sprintf('%s@%s', $config['user'], $config['sshHost']),
            '-L',
            sprintf('%s:%s:%s', $config['localPort'], $config['remoteHost'], $config['remotePort']),
            '-i',
            sprintf('%s', $this->writeKeyToFile($config['privateKey'])),
            sprintf('-fN%s', (isset($config['compression']) && $config['compression'] === true) ? 'C' : ''),
            '-o',
            sprintf('ServerAliveInterval=%d', self::SSH_SERVER_ALIVE_INTERVAL),
            '-o',
            'ExitOnForwardFailure=yes',
            '-o',
            'StrictHostKeyChecking=no',
        ];

        $process = new Process($cmd);
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

    /**
     * @param string $key
     * @return string
     * @throws SSHException
     */
    private function writeKeyToFile($key)
    {
        if (empty($key)) {
            throw new SSHException("Key must not be empty");
        }
        $fileName = tempnam('/tmp/',  'ssh-key-');
        file_put_contents($fileName, $key);
        chmod($fileName, 0600);
        return realpath($fileName);
    }
}
