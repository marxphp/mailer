<?php

namespace Max\Mailer\Protocol;

use Exception;

class SMTP
{
    protected array $headers = [
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=utf-8',
        //        'To'           => '',
        //        'Cc'           => '',
        //        'From'         => '',
        'Subject'      => '',
        //        'Date'         => '',
    ];

    protected array $options = [
        'host'    => 'smtp.qq.com',
        'domain'  => 'localhost',
        'port'    => 25,
        'timeout' => 30,
        'user'    => null,
        'pass'    => null,
    ];

    public const HELO      = "HELO %s\r\n";
    public const AUTH      = "AUTH LOGIN\r\n";
    public const MAIL_FROM = "MAIL FROM: <%s>\r\n";
    public const MAIL_TO   = "RCPT TO: <%s>\r\n";
    public const DATA      = "DATA\r\n%s\r\n.\r\n";
    public const QUIT      = "QUIT\r\n";

    protected $socket;

    /**
     * @throws Exception
     */
    public function __construct(array $options)
    {
        $this->options = array_replace($this->options, $options);
        $this->socket  = fsockopen(
            $this->options['host'],
            $this->options['port'],
            $errno,
            $error,
            $this->options['timeout']
        );
        if ($errno) {
            throw new Exception($error);
        }
        $this->helo();
        if (!is_null($this->options['user'])) {
            $this->auth();
        }
    }

    public function put($command, array $arg = [])
    {
        fwrite($this->socket, vsprintf($command, $arg));
        echo fgets($this->socket, 512);
    }

    protected function auth()
    {
        $this->put(self::AUTH);
        $this->put(base64_encode($this->options['user']) . "\r\n");
        $this->put(base64_encode($this->options['pass']) . "\r\n");
    }

    public function send(string $from, string $to, string $subject, string $body)
    {
        $this->helo();
        $this->mailFrom($from);
        $this->mailTo($to);
        $headers            = $this->headers;
        $headers['Subject'] = $subject;
        $headerString       = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }
        $this->put(self::DATA, [sprintf("%s\r\n\r\n%s", $headerString, $body)]);
        $this->put('QUIT');
    }

    public function mailTo($to)
    {
        $this->put(self::MAIL_TO, [$to]);
    }

    public function mailFrom(string $from)
    {
        $this->put(self::MAIL_FROM, [$from]);
    }

    /**
     * @return void
     */
    protected function helo()
    {
        $this->put(self::HELO, [$this->options['domain']]);
    }
}
