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
    public function __construct()
    {

    }

    public function generateKeyPair()
    {
        $process = new Process("ssh-keygen -b 2048 -t rsa -f ./ssh.key -N '' -q");
        $process->run();

        // return public key
        return [
            'private' => file_get_contents('ssh.key'),
            'public' => file_get_contents('ssh.key.pub')
        ];
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

        $cmd = sprintf(
            'ssh -p %s %s@%s -L %s:%s:%s -i %s -fN -o ExitOnForwardFailure=yes -o StrictHostKeyChecking=no -o "UserKnownHostsFile /dev/null"',
            $config['sshPort'],
            $config['user'],
            $config['sshHost'],
            $config['localPort'],
            $config['remoteHost'],
            $config['remotePort'],
            $this->writeKeyToFile($config['privateKey'])
        );

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
     * @param string $path
     * @return string
     * @throws SSHException
     */
    private function writeKeyToFile($key, $path = '.')
    {
        if (empty($key)) {
            throw new SSHException("Key must not be empty");
        }
        $fileName = 'ssh.' . microtime(true) . '.key';
        file_put_contents($path . '/' . $fileName, $key);
        chmod($fileName, 0600);
        return realpath($fileName);
    }
}
