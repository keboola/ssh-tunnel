<?php

namespace Keboola\SSHTunnel;

use Symfony\Component\Process\Process;

class SSHTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        $process = Process::fromShellCommandline('pgrep ssh | xargs -r kill');
        $process->run();
    }

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

        $this->assertEquals(0, $process->getExitCode());
    }

    public function testOpenTunnelWithCompression()
    {
        $ssh = new SSH();

        $process = $ssh->openTunnel([
            'user' => 'root',
            'sshHost' => 'sshproxy',
            'sshPort' => '22',
            'localPort' => '33306',
            'remoteHost' => 'mysql',
            'remotePort' => '3306',
            'privateKey' => $this->getPrivateKey(),
            'compression' => true
        ]);

        $this->assertContains('-fNC', $process->getCommandLine());
        $this->assertEquals(0, $process->getExitCode());
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
