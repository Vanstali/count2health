<?php

namespace Count2Health\AppBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;
use Count2Health\AppBundle\FatSecret\FatSecretException;

/**
 * @DI\Service
 * @DI\Tag("kernel.event_subscriber")
 */
class RegistrationListener implements EventSubscriberInterface
{

    private $router;
    private $fatsecret;
    private $tokenStorage;
    private $entityManager;

    /**
     * @DI\InjectParams({
     *     "router" = @DI\Inject("router"),
     *     "fatsecret" = @DI\Inject("fatsecret"),
     *     "tokenStorage" = @DI\Inject("security.token_storage"),
     *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(UrlGeneratorInterface $router,
            FatSecret $fatsecret,
            TokenStorage $tokenStorage,
            EntityManager $entityManager)
    {
        $this->router = $router;
        $this->fatsecret = $fatsecret;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return array(
                FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
                FOSUserEvents::REGISTRATION_COMPLETED => 'onRegistrationCompleted',
                );
    }

    public function onRegistrationSuccess(FormEvent $event)
    {
        $url = $this->router->generate('account');
        $event->setResponse(new RedirectResponse($url));
    }

    public function onRegistrationCompleted(FilterUserResponseEvent $event)
    {
        // Create the profile on FatSecret as well
        $user = $this->tokenStorage->getToken()->getUser();

        try
        {
        $response = $this->fatsecret->doApiCall('profile.create', array(
                    'user_id' => $user->getEmail(),
                    ));
        }
        catch (FatSecretException $e)
        {
        $response = $this->fatsecret->doApiCall('profile.get_auth', array(
                    'user_id' => $user->getEmail(),
                    ));
        }

        $user->setAuthToken($response->auth_token);
        $user->setAuthSecret($response->auth_secret);

        $this->entityManager->flush();

    }
}
