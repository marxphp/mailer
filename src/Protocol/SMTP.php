<?php

namespace Max\Mailer\Protocol;

use Exception;

class SMTP
{
    protected array $headers = [
        //        'From'         => '',
        //        'To'           => '',
        //        'Cc'           => '',
        //        'Date'         => '',
        'Subject'                   => '',
        'MIME-Version'              => '1.0',
        'Content-type'              => 'text/html; charset=utf-8',
        'Content-Transfer-Encoding' => 'base64',
        'Content-ID'                => '1',
        'Content-Description'       => 'd',
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

    protected const CODE = [
        421 => 'Service not available, closing transmission channel (This may be a reply to any command if the service knows it must shut down)',
        450 => 'Requested mail action not taken: mailbox unavailable (E.g., mailbox busy)',
        451 => 'Requested action aborted: local error in processing',
        452 => 'Requested action not taken: insufficient system storage',
        500 => 'Syntax error, command unrecognized (This may include errors such as command line too long)',
        501 => 'Syntax error in parameters or arguments',
        502 => 'Command not implemented',
        503 => 'Bad sequence of commands',
        504 => 'Command parameter not implemented',
        550 => 'Requested action not taken: mailbox unavailable (E.g., mailbox not found, no access)',
        551 => 'User not local; please try',
        552 => 'Requested mail action aborted: exceeded storage allocation',
        553 => 'Requested action not taken: mailbox name not allowed (E.g., mailbox syntax incorrect)',
        554 => 'Transaction failedThe other codes that provide you with helpful information about what’s happening with your messages are:',
        211 => 'System status, or system help reply',
        214 => 'Help message (Information on how to use the receiver or the meaning of a particular non-standard command; this reply is useful only to the human user)',
        220 => 'Service ready',
        221 => 'Service closing transmission channel',
        250 => 'Requested mail action okay, completed',
        251 => 'User not local; will forward to',
        354 => 'Start mail input; end with . (a dot)',
    ];

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
