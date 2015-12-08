<?php
/**
 * This file contains a class that responds to events
 *
 * @license    https://github.com/allejo/bzion/blob/master/LICENSE.md GNU General Public License Version 3
 */

namespace BZIon\Event;

use BZIon\NotificationAdapter\WebSocketAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for bzion events
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * The FROM e-mail address
     * @var string
     */
    protected $from;

    /**
     * Constructor
     *
     * You will probably not need to instantiate an object of this class,
     * Symfony already does the hard work for us
     *
     * @param \Swift_Mailer $mailer    The mailer
     * @param \Twig_Environment $twig  The twig environment
     * @param string        $from      The FROM e-mail address
     * @param string        $siteTitle The title of the website
     */
    public function __construct(\Swift_Mailer $mailer, $twig, $from, $siteTitle)
    {
        $this->mailer = $mailer;
        $this->twig   = $twig;
        $this->from   = $from;
        $this->siteTitle = $siteTitle;
    }

    /**
     * The title of the website
     * @var string
     */
    protected $siteTitle;

    /**
     * Returns all the events that this subscriber handles, and which method
     * handles each one
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'conversation.abandon' => 'conversation',
            'conversation.join'    => 'conversation',
            'conversation.kick'    => 'conversation',
            'conversation.rename'  => 'conversation',
            'message.new'          => 'onNewMessage',
            'notification.new'     => 'onNewNotification',
            'team.delete'          => 'notify',
            'team.abandon'         => 'notify',
            'team.kick'            => 'notify',
            'team.join'            => 'notify',
            'team.invite'          => 'notify',
            'team.leader_change'   => 'notify',
            'welcome'              => 'notify',
        );
    }

    /**
     * Called every time a new message is sent
     * @param NewMessageEvent $event The event
     */
    public function onNewMessage(NewMessageEvent $event)
    {
        // Get a list of everyone who can see the message so we can notify them -
        // the sender of the message is excluded
        $conversation = $event->getMessage()->getConversation();
        $author = $event->getMessage()->getAuthor()->getId();
        $recipients = $conversation->getWaitingForEmailIDs($author);

        // The websocket will handle emails if it is enabled
        if (!WebSocketAdapter::isEnabled()) {
            $this->sendEmails(
                'New message received',
                $recipients,
                'message',
                array('message' => $event->getMessage())
            );
        }

        $event->getMessage()->getConversation()->markUnread($author);
        \Notification::pushEvent('message', array(
            'message'    => $event->getMessage(),
            'recipients' => $recipients
        ));
    }

    /**
     * Called every time a new notification is sent
     * @param NewNotificationEvent $event The event
     */
    public function onNewNotification(NewNotificationEvent $event)
    {
        if ($event->getNotification()->getReceiver()->canReceive('notification')) {
            if (!WebSocketAdapter::isEnabled()) {
                $this->emailNotification($event->getNotification());
            }
        }

        // Show the notification to the currently logged in players in real time
        $event->getNotification()->push();
    }

    /**
     * Called when an event needs to notify a user
     *
     * @param Event  $event The event
     * @param string $name  The name of the event
     */
    public function notify(Event $event, $name)
    {
        $event->notify($name);
    }

    /**
     * Called when a conversation event needs to be stored in the database
     *
     * @param Event  $event The event
     * @param string $name  The name of the event
     */
    public function conversation(Event $event, $name)
    {
        \ConversationEvent::storeEvent($event->getConversation()->getId(), $event, $name);
    }

    /**
     * Notify the user about a notification by e-
     * @param \Notification $notification The notification that will be mailed
     */
    public function emailNotification(\Notification $notification)
    {
        $text = $this->twig->render(
            'Notification/item.html.twig',
            array('notification' => $notification)
        );

        return $this->sendEmails(
            trim(strip_tags($text)),
            array($notification->getReceiver()->getId()),
            'notification',
            array('notification' => $notification, 'text' => $text)
        );
    }

    /**
     * Send emails to a list of recipients
     *
     * @param  string $subject    The subject of the messages
     * @param  int[]  $recipients The IDs of the players to which the messages will be sent
     * @param  string $template   The twig template name for the e-mail body
     * @param  array  $params     Any extra parameters to pass to twig
     * @return void
     */
    public function sendEmails($subject, $recipients, $template, $params = array())
    {
        if (!$this->from) {
            return;
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(array($this->from => $this->siteTitle))
            ->setBody($this->twig->render("Email/$template.txt.twig",  $params))
            ->addPart($this->twig->render("Email/$template.html.twig", $params), 'text/html');

        foreach ($recipients as $recipient) {
            $recipient = \Player::get($recipient);

            if (!$recipient->isVerified()) {
                continue;
            }

            $message->setTo($recipient->getEmailAddress());
            $this->mailer->send($message);
        }
    }
}
