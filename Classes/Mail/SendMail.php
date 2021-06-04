<?php
declare(strict_types=1);
namespace In2code\Luxletter\Mail;

use In2code\Luxletter\Domain\Model\Configuration;
use In2code\Luxletter\Signal\SignalTrait;
use In2code\Luxletter\Utility\ConfigurationUtility;
use In2code\Luxletter\Utility\ObjectUtility;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Class SendMail
 */
class SendMail
{
    use SignalTrait;

    /**
     * @var string
     */
    protected $subject = '';

    /**
     * @var string
     */
    protected $bodytext = '';

    /**
     * @var Configuration|null
     */
    protected $configuration = null;

    /**
     * SendMail constructor.
     * @param string $subject
     * @param string $bodytext
     * @param Configuration $configuration
     */
    public function __construct(string $subject, string $bodytext, Configuration $configuration)
    {
        $this->subject = $subject;
        $this->bodytext = $bodytext;
        $this->configuration = $configuration;
    }

    /**
     * @param array $receiver ['a@mail.org' => 'Receivername']
     * @return bool the number of recipients who were accepted for delivery
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function sendNewsletter(array $receiver): bool
    {
        if (ConfigurationUtility::isVersionToCompareSameOrLowerThenCurrentTypo3Version('10.0.0')) {
            // TYPO3 10
            return $this->send($receiver);
        } else {
            // TYPO3 9
            return $this->sendLegacy($receiver);
        }
    }

    /**
     * Send with new MailMessage from TYPO3 10
     *
     * @param array $receiver
     * @return bool
     * @throws Exception
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @throws TransportExceptionInterface
     */
    protected function send(array $receiver): bool
    {
        $send = true;
        $this->signalDispatch(__CLASS__, __FUNCTION__ . 'beforeSend', [&$send, $receiver, $this]);
        $mailMessage = ObjectUtility::getObjectManager()->get(MailMessage::class);
        $mailMessage
            ->setTo($receiver)
            ->setFrom([$this->configuration->getFromEmail() => $this->configuration->getFromName()])
            ->setReplyTo([$this->configuration->getReplyEmail() => $this->configuration->getReplyName()])
            ->setSubject($this->subject)
            ->html($this->bodytext);
        $this->signalDispatch(__CLASS__, __FUNCTION__ . 'mailMessage', [$mailMessage, &$send, $receiver, $this]);
        if ($send === true) {
            // Todo: Can be renamed to send() when TYPO3 9 support is dropped
            return $mailMessage->sendMail();
        }
        return false;
    }

    /**
     * Send with old MailMessage from TYPO3 9
     *
     * @param array $receiver
     * @return bool
     * @throws Exception
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @throws TransportExceptionInterface
     * Todo: Can be removed when TYPO3 9 support is dropped
     */
    protected function sendLegacy(array $receiver): bool
    {
        $send = true;
        $this->signalDispatch(__CLASS__, __FUNCTION__ . 'beforeSendLegacy', [&$send, $receiver, $this]);
        $mailMessage = ObjectUtility::getObjectManager()->get(MailMessageLegacy::class);
        $mailMessage
            ->setTo($receiver)
            ->setFrom([$this->configuration->getFromEmail() => $this->configuration->getFromName()])
            ->setReplyTo([$this->configuration->getReplyEmail() => $this->configuration->getReplyName()])
            ->setSubject($this->subject)
            ->setBody($this->bodytext, 'text/html');
        $this->signalDispatch(__CLASS__, __FUNCTION__ . 'mailMessageLegacy', [$mailMessage, &$send, $receiver, $this]);
        if ($send === true) {
            return $mailMessage->send() > 0;
        }
        return false;
    }
}
