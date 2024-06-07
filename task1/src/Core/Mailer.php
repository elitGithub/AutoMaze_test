<?php

declare(strict_types = 1);

namespace Core;

use Exception;
use Helpers\ArrayManipulator;
use JobPortal\Entities\Email;
use Libraries\PHPMailer\PHPMailer\PHPMailer;
use Libraries\PHPMailer\PHPMailer\SMTP;
use Log\LogConfig;
use Log\LogWriter;
use Log\StormLogger;
use RuntimeException;
use Throwable;

class Mailer
{
    protected static array $emailConfig     = [];
    protected static array $debugOutput     = [];
    protected PHPMailer    $mailService;
    protected LogWriter    $log;
    protected LogWriter    $exceptionLog;
    protected              $requestID;
    protected bool         $isFileContent   = false;
    protected int          $emptyRowsCount  = 0;
    protected string       $fromEmail       = '';
    protected string       $fromName        = '';
    protected string       $replyToEmail    = '';
    protected string       $attachmentMode  = 'all';
    protected array        $attachmentFiles = [];
    protected string       $mailOrigin      = 'Mail';

    public function __construct()
    {
        $this->requestID = Storm::getStorm()->security->uniqueIdsGenerator->generateTrueRandomString();
        $this->mailService = new PHPMailer(true);
        $this->log = StormLogger::getLogger(LogConfig::SENDMAIL_NAME);
        $this->exceptionLog = StormLogger::getLogger(LogConfig::EXCEPTION_NAME);

    }

    public function sendEmail()
    {

    }

    protected function setOutgoingMailProperties(string $subject, string $body, $logo = '')
    {
        $this->getEmailSystemConfig();
    }

    protected function addAllAttachments($emailId) {
        $attachmentsForEmail = Storm::getStorm()->getModuleInstance('Attachments')->getModel()->getAttachmentsForEmail($emailId);
        if (!count($attachmentsForEmail)) {
            return;
        }
        foreach ($attachmentsForEmail as $attachment) {
            $fileUrl = decode_html($attachment['name']);
            $fileName = basename($fileUrl);
            $localFileDirectory = SITE_IMAGES_UPLOAD_DIR;
            // If the attachment is in a subdirectory, for example, public/uploads/something/
            if (SITE_IMAGES_UPLOAD_DIR !== $attachment['path']) {
                $localFileDirectory = SITE_IMAGES_UPLOAD_DIR . $attachment['path'];
            }
            if (!is_dir($localFileDirectory)) {
                // Precaution if someone deleted public/uploads.
                mkdir($localFileDirectory, 0775, true);
            }
            $localFilePath = $localFileDirectory . $fileName;
            //if the file exists in test/upload directory, then we will add directly
            //else get the contents of the file and write it as a file and then attach (this will occur when we unlink the file)
            if (is_file($localFilePath)) {
                $this->mailService->AddAttachment($localFilePath, $fileName);
            } else {
                $params = [
                    'attempted_file_path' => $localFilePath,
                    'file_url'            => $fileUrl,
                ];
                $this->log->critical("Could not find file $fileUrl for attachment.", $params);
            }
        }
    }

    protected function sendSingleEmail()
    {
        // TODO: add handling for entities
        // $entityId = Storm::getStorm()->request['record'] ?? null;

    }

    /**
     * @throws \Libraries\PHPMailer\PHPMailer\Exception
     */
    public function setSendTo($email): void
    {
        if (is_string($email)) {
            $this->mailService->addAddress($email);
        }

        if (is_array($email)) {
            $this->processToEmailArray($email);
        }
    }

    /**
     * @param  array  $email
     *
     * @throws Exception|\Libraries\PHPMailer\PHPMailer\Exception
     */
    protected function processToEmailArray(array $email)
    {
        if (key_exists('name', $email) && key_exists('address', $email)) {
            $this->mailService->addAddress($email['address'], $email['name']);
            return;
        }
        $email = ArrayManipulator::flatten($email);
        $this->mailService->addAddress($email[0]);
    }

    protected function setMailServerProperties(): Mailer
    {
        $this->mailService->SMTPDebug = SMTP::DEBUG_SERVER;
        $this->mailService->Debugoutput = function ($str, $level) {
            if (substr_count($str, 'Content-Type: application')) {
                $this->isFileContent = true;
                $this->emptyRowsCount = 0;
            }

            if (!$this->isFileContent) {
                static::$debugOutput[] = $this->requestID . '_ ' . $str;
            }

            if ($str === '') {
                if ($this->emptyRowsCount) {
                    $this->isFileContent = false;
                } else {
                    $this->emptyRowsCount++;
                }
            }
        };
        $server = Storm::getStorm()->request['email']['server'] ?? static::$emailConfig['server'];

        if (empty($server)) {
            $this->log->critical('No Host set, stopping.', ['REQUEST' => $_REQUEST]);
            throw new RuntimeException('Missing host - cannot set up mail server');
        }
        $port = Storm::getStorm()->request['email']['port'] ?? static::$emailConfig['server_port'];
        $secure = Storm::getStorm()->request['email']['secure'] ?? static::$emailConfig['server_secure'];
        $username = Storm::getStorm()->request['email']['server_username'] ?? static::$emailConfig['server_username'];
        $password = Storm::getStorm()->request['email']['server_password'] ?? htmlspecialchars_decode(static::$emailConfig['server_password']);


        if (isset(Storm::getStorm()->request['email']['smtp_auth'])) {
            $smtp_auth = Storm::getStorm()->request['email']['smtp_auth'];
            if ($smtp_auth === 'on') {
                $smtp_auth = 'true';
            }
        } elseif (isset(Storm::getStorm()->request['email']['module']) &&
                  Storm::getStorm()->request['email']['module'] == 'Settings' &&
                  (!isset(Storm::getStorm()->request['email']['smtp_auth']))) {
            //added to avoid issue while editing the values in the outgoing mail server.
            $smtp_auth = 'false';
        } else {
            $smtp_auth = static::$emailConfig['smtp_auth'];
        }

        if ($smtp_auth === 'true') {
            $this->mailService->SMTPAuth = true; // turn on SMTP authentication
        }
        $this->mailService->Host = $server;
        if ($port) {
            $this->mailService->Port = $port;
        }
        if ($secure) {
            $this->mailService->SMTPSecure = $secure;
        }
        $this->mailService->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];
        $this->mailService->SMTPKeepAlive = true;

        $this->mailService->Username = $username;   // SMTP username
        $this->mailService->Password = $password;   // SMTP password
        return $this;
    }


    public function getEmailSystemConfig()
    {
        self::$emailConfig = System::readSMTPSettings();
    }

    /**
     * @param  string  $fromEmail
     *
     * @return $this
     */
    public function setFromEmail(string $fromEmail): Mailer
    {
        $this->fromEmail = $fromEmail;
        $this->mailService->From = $fromEmail;
        return $this;
    }

    public function setFromName(string $fromName): Mailer
    {
        $this->fromName = $fromName;
        $this->mailService->FromName = $this->fromName;
        return $this;
    }

    protected function addCCorBCC(string $address, string $mode)
    {
        if (!empty($address)) {
            // By default, add BCC.
            $method = 'AddBCC';
            if ($mode === 'cc') {
                $method = 'AddCC';
            }

            $cc_address = explode(',', trim($address, ','));
            for ($i = 0; $i < count($cc_address); $i++) {
                $addr = $cc_address[$i];
                $cc_name = preg_replace('/([^@]+)@(.*)/', '$1', $addr); // First Part Of Email
                if (stripos($addr, '<')) {
                    $name_addr_pair = explode('<', $cc_address[$i]);
                    $cc_name = $name_addr_pair[0];
                    $addr = trim($name_addr_pair[1], '>');
                }
                if ($cc_address[$i] != '') {
                    $this->mailService->$method($addr, $cc_name);
                }
            }
        }
    }

    /**
     * @param  string  $filePath
     *
     * @throws \Libraries\PHPMailer\PHPMailer\Exception
     */
    protected function addAttachment (string $filePath = '')
    {
        if (is_file($filePath)) {
            $this->mailService->AddAttachment($filePath);
        }
    }

}
