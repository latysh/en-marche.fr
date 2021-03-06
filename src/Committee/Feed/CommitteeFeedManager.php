<?php

namespace AppBundle\Committee\Feed;

use AppBundle\Committee\CommitteeManager;
use AppBundle\Entity\Committee;
use AppBundle\Entity\CommitteeFeedItem;
use AppBundle\Mailjet\MailjetService;
use AppBundle\Mailjet\Message\CommitteeCitizenInitiativeNotificationMessage;
use AppBundle\Mailjet\Message\CommitteeCitizenInitiativeOrganizerNotificationMessage;
use AppBundle\Mailjet\Message\CommitteeMessageNotificationMessage;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommitteeFeedManager
{
    private $manager;
    private $committeeManager;
    private $mailjet;
    private $urlGenerator;

    public function __construct(ObjectManager $manager, CommitteeManager $committeeManager, MailjetService $mailjet, UrlGeneratorInterface $urlGenerator)
    {
        $this->manager = $manager;
        $this->committeeManager = $committeeManager;
        $this->mailjet = $mailjet;
        $this->urlGenerator = $urlGenerator;
    }

    public function createEvent(CommitteeEvent $event): CommitteeFeedItem
    {
        $item = CommitteeFeedItem::createEvent(
            $event->getEvent(),
            $event->getAuthor(),
            true,
            $event->getCreatedAt()->format(DATE_RFC2822)
        );

        $this->manager->persist($item);
        $this->manager->flush();

        return $item;
    }

    public function createMessage(CommitteeMessage $message): CommitteeFeedItem
    {
        $item = CommitteeFeedItem::createMessage(
            $message->getCommittee(),
            $message->getAuthor(),
            $message->getContent(),
            $message->isPublished(),
            $message->getCreatedAt()->format(DATE_RFC2822)
        );

        $this->manager->persist($item);
        $this->manager->flush();

        $this->sendMessageToFollowers($item, $message->getCommittee());

        return $item;
    }

    public function createCitizenInitiative(CommitteeCitizenInitiativeMessage $message): CommitteeFeedItem
    {
        $item = CommitteeFeedItem::createCitizenInitiative(
            $message->getCommittee(),
            $message->getAuthor(),
            $message->getContent(),
            $message->getCitizenInitiative(),
            $message->isPublished(),
            $message->getCreatedAt()->format(DATE_RFC2822)
        );

        $this->manager->persist($item);
        $this->manager->flush();

        $this->sendNotificationToOrganizer($item);
        $this->sendCitizenInitiativeToFollowers($item);

        return $item;
    }

    private function sendMessageToFollowers(CommitteeFeedItem $message, Committee $committee): void
    {
        foreach ($this->getOptinCommitteeFollowersChunks($committee) as $chunk) {
            $this->mailjet->sendMessage(CommitteeMessageNotificationMessage::create($chunk, $message));
        }
    }

    private function sendNotificationToOrganizer(CommitteeFeedItem $message): void
    {
        $contactLink = $this->generateUrl('app_adherent_contact', [
            'uuid' => (string) $message->getAuthor()->getUuid(),
            'from' => 'committee',
            'id' => (string) $message->getCommittee()->getUuid(),
        ]);
        $this->mailjet->sendMessage(CommitteeCitizenInitiativeOrganizerNotificationMessage::create(
            $message->getEvent()->getOrganizer(),
            $message,
            $contactLink
        ));
    }

    private function sendCitizenInitiativeToFollowers(CommitteeFeedItem $message): void
    {
        $initiative = $message->getEvent();
        $citizenInitiativeLink = $this->generateUrl('app_citizen_initiative_show', [
            'uuid' => (string) $initiative->getUuid(),
            'slug' => $initiative->getSlug(),
        ]);
        $attendLink = $this->generateUrl('app_citizen_initiative_attend', [
            'uuid' => (string) $initiative->getUuid(),
            'slug' => $initiative->getSlug(),
        ]);
        foreach ($this->getOptinCommitteeFollowersChunks($message->getCommittee()) as $chunk) {
            $this->mailjet->sendMessage(CommitteeCitizenInitiativeNotificationMessage::create(
                $chunk,
                $message,
                $citizenInitiativeLink,
                $attendLink
            ));
        }
    }

    private function getOptinCommitteeFollowersChunks(Committee $committee)
    {
        return array_chunk(
            $this->committeeManager->getOptinCommitteeFollowers($committee)->toArray(),
            MailjetService::PAYLOAD_MAXSIZE
        );
    }

    private function generateUrl(string $route, array $params = []): string
    {
        return $this->urlGenerator->generate($route, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
