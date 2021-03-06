<?php

namespace Tests\AppBundle\Mailjet\Message;

use AppBundle\Mailjet\Message\CommitteeCitizenInitiativeNotificationMessage;
use AppBundle\Mailjet\Message\MailjetMessageRecipient;

class CommitteeCitizenInitiativeNotificationMessageTest extends AbstractEventMessageTest
{
    const SHOW_CITIZEN_INITIATIVE_URL = 'https://localhost/initiative_citoyenne/46ba94dc-b1ff-4807-b8a3-db7366ec805f/2017-08-18-apprenez-a-sauver-des-vies';
    const ATTEND_CITIZEN_INITIATIVE_URL = 'https://localhost/initiative_citoyenne/46ba94dc-b1ff-4807-b8a3-db7366ec805f/2017-08-18-apprenez-a-sauver-des-vies/inscription';

    public function testCreateCommitteeCitizenInitiativeNotificationMessage()
    {
        $recipients[] = $this->createAdherentMock('em@example.com', 'Émmanuel', 'Macron');
        $recipients[] = $this->createAdherentMock('jb@example.com', 'Jean', 'Berenger');
        $recipients[] = $this->createAdherentMock('ml@example.com', 'Marie', 'Lambert');
        $recipients[] = $this->createAdherentMock('ez@example.com', 'Éric', 'Zitrone');

        $message = CommitteeCitizenInitiativeNotificationMessage::create(
            $recipients,
            $this->createCommitteeFeedItemMock(
                $this->createAdherentMock('kl@exemple.com', 'Kévin', 'Lafont'),
                'Cette initiative est superbe !',
                $this->createCitizenInitiativeMock(
                    'En Marche Lyon',
                    '2017-02-01 15:30:00',
                    '15 allées Paul Bocuse',
                    '69006-69386',
                    'en-marche-lyon',
                    $this->createAdherentMock('jb@exemple.com', 'Jean', 'Baptiste')
                )
            ),
            self::SHOW_CITIZEN_INITIATIVE_URL,
            self::ATTEND_CITIZEN_INITIATIVE_URL
        );

        $this->assertInstanceOf(CommitteeCitizenInitiativeNotificationMessage::class, $message);
        $this->assertSame('196519', $message->getTemplate());
        $this->assertCount(4, $message->getRecipients());
        $this->assertSame('Des nouvelles de votre comité', $message->getSubject());
        $this->assertCount(12, $message->getVars());
        $this->assertSame(
            [
                'animator_firstname' => 'Kévin',
                'IC_organizer_firstname' => 'Jean',
                'IC_organizer_lastname' => 'Baptiste',
                'IC_name' => 'En Marche Lyon',
                'IC_date' => 'mercredi 1 février 2017',
                'IC_hour' => '15h30',
                'IC_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'IC_slug' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'IC_link' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'IC_attend_link' => self::ATTEND_CITIZEN_INITIATIVE_URL,
                'target_message' => 'Cette initiative est superbe !',
                'target_firstname' => '',
            ],
            $message->getVars()
        );

        $recipient = $message->getRecipient(0);
        $this->assertInstanceOf(MailjetMessageRecipient::class, $recipient);
        $this->assertSame('em@example.com', $recipient->getEmailAddress());
        $this->assertSame('Émmanuel Macron', $recipient->getFullName());
        $this->assertSame(
            [
                'animator_firstname' => 'Kévin',
                'IC_organizer_firstname' => 'Jean',
                'IC_organizer_lastname' => 'Baptiste',
                'IC_name' => 'En Marche Lyon',
                'IC_date' => 'mercredi 1 février 2017',
                'IC_hour' => '15h30',
                'IC_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'IC_slug' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'IC_link' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'IC_attend_link' => self::ATTEND_CITIZEN_INITIATIVE_URL,
                'target_message' => 'Cette initiative est superbe !',
                'target_firstname' => 'Émmanuel',
            ],
            $recipient->getVars()
        );

        $recipient = $message->getRecipient(3);
        $this->assertInstanceOf(MailjetMessageRecipient::class, $recipient);
        $this->assertSame('ez@example.com', $recipient->getEmailAddress());
        $this->assertSame('Éric Zitrone', $recipient->getFullName());
        $this->assertSame(
            [
                'animator_firstname' => 'Kévin',
                'IC_organizer_firstname' => 'Jean',
                'IC_organizer_lastname' => 'Baptiste',
                'IC_name' => 'En Marche Lyon',
                'IC_date' => 'mercredi 1 février 2017',
                'IC_hour' => '15h30',
                'IC_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'IC_slug' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'IC_link' => self::SHOW_CITIZEN_INITIATIVE_URL,
                'IC_attend_link' => self::ATTEND_CITIZEN_INITIATIVE_URL,
                'target_message' => 'Cette initiative est superbe !',
                'target_firstname' => 'Éric',
            ],
            $recipient->getVars()
        );
    }
}
