# ssh-tunnel

[![Build on push](https://github.com/keboola/ssh-tunnel/actions/workflows/push.yml/badge.svg?branch=master)](https://github.com/keboola/ssh-tunnel/actions/workflows/push.yml)

Simple PHP class for opening SSH tunnels

## Usage

Require with composer:

```shell
composer require keboola/ssh-tunnel
```

## Development

Developed with TTD. Requires Git, Composer, Docker and Docker Compose.

Clone repository, install dependencies, source private key ([stored in 1password](https://start.1password.com/open/i?a=Z6RK6YPRYZESDHSAB2SWYZSSUM&v=y2u4vyq4mfdxrnlnn6zxqgr6e4&i=oizwwpxlbrmhtzed4jyp552hri&h=keboola.1password.com)) and run tests:

```shell
git clone git@github.com:keboola/ssh-tunnel.git
cd ssh-tunnel
composer install
docker-compose run
```

## License

MIT licensed, see [LICENSE](./LICENSE) file.
