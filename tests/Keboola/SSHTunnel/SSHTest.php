<?php

namespace Keboola\SSHTunnel;

/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 17/02/16
 * Time: 16:06
 */
class SSHTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateKeyPair()
    {
        $ssh = new SSH();
        $keys = $ssh->generateKeyPair();

        $this->assertArrayHasKey('private', $keys);
        $this->assertArrayHasKey('public', $keys);
        $this->assertNotEmpty($keys['private']);
        $this->assertNotEmpty($keys['public']);
    }

    public function testOpenTunnel()
    {
        $ssh = new SSH();

        $process = $ssh->openTunnel([
            'user' => 'root',
            'sshHost' => 'sshproxy',
            'sshPort' => '22',
            'localPort' => '33306',
            'remoteHost' => 'mysql',
            'remotePort' => '3306',
            'privateKey' => $this->getPrivateKey()
        ]);

        var_dump($process->getOutput());
        var_dump($process->getErrorOutput());
    }

    private function getPrivateKey()
    {
        $privateKey = getenv('SSH_KEY_PRIVATE');
        if ($privateKey === false) {
            throw new \Exception("SSH_KEY_PRIVATE environment variable must be set");
        }
        return $privateKey;
    }
}
