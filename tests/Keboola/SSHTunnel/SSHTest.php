<?php

declare(strict_types=1);

namespace Keboola\SSHTunnel\Tests;

use Keboola\SSHTunnel\SSH;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SSHTest extends TestCase
{
    protected function tearDown(): void
    {
        $process = Process::fromShellCommandline('pgrep ssh | xargs -r kill');
        $process->run();
    }

    public function testGenerateKeyPair(): void
    {
        $ssh = new SSH();
        $keys = $ssh->generateKeyPair();

        $this->assertArrayHasKey('private', $keys);
        $this->assertArrayHasKey('public', $keys);
        $this->assertNotEmpty($keys['private']);
        $this->assertNotEmpty($keys['public']);
    }

    public function testOpenTunnel(): void
    {
        $config = $this->getConfig();
        $ssh = new SSH();
        $sshProcess = $ssh->openTunnel($config);
        $this->assertEquals(0, $sshProcess->getExitCode());

        $this->assertDbConnection($config);
    }

    public function testOpenTunnelWithCompression(): void
    {
        $config = $this->getConfig([
            'compression' => true,
        ]);

        $ssh = new SSH();
        $process = $ssh->openTunnel($config);

        $this->assertStringContainsString('-C', $process->getCommandLine());
        $this->assertEquals(0, $process->getExitCode());

        $this->assertDbConnection($config);
    }

    public function testOpenTunnelWithDebug(): void
    {
        $config = $this->getConfig([
            'debug' => true,
        ]);

        $ssh = new SSH();
        $process = $ssh->openTunnel($config);

        $this->assertStringContainsString('-vvv', $process->getCommandLine());
        $this->assertEquals(0, $process->getExitCode());

        $this->assertDbConnection($config);
    }
    
    public function testOpenTunnelWrongHost(): void
    {
        $this->expectException('PDOException');

        $config = $this->getConfig([
            'remoteHost' => 'shangri la'
        ]);

        $ssh = new SSH();
        $sshProcess = $ssh->openTunnel($config);
        $this->assertEquals(0, $sshProcess->getExitCode());

        $this->assertDbConnection($config);
    }

    private function getPrivateKey(): string
    {
        $privateKey = getenv('SSH_KEY_PRIVATE');
        if ($privateKey === false) {
            throw new \Exception("SSH_KEY_PRIVATE environment variable must be set");
        }
        return $privateKey;
    }

    private function getConfig(array $withParams = []): array
    {
        return array_merge([
            'user' => 'root',
            'sshHost' => 'sshproxy',
            'sshPort' => '22',
            'localPort' => '33306',
            'remoteHost' => 'mysql',
            'remotePort' => '3306',
            'privateKey' => $this->getPrivateKey(),
        ], $withParams);
    }

    private function assertDbConnection(array $config): void
    {
        $password = (string) getenv('MYSQL_ROOT_PASSWORD');
        $dbName = (string) getenv('MYSQL_DATABASE');
        $pdo = $this->getDbConnection(
            $config['remoteHost'],
            $config['remotePort'],
            $config['user'],
            $password,
            $dbName
        );
        $stmt = $pdo->query(sprintf("SHOW DATABASES LIKE '%s'", $dbName));

        $this->assertNotFalse($stmt);
        if ($stmt !== false) {
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->assertEquals($dbName, array_shift($result[0]));
        }
    }

    private function getDbConnection(string $host, string $port, string $user, string $password, string $db): \PDO
    {
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8", $host, $port, $db);
        return new \PDO($dsn, $user, $password);
    }
}
